<?php
namespace Meract\Core;

use Meract\Core\Qryli;
use Meract\Core\Request;
use Meract\Core\Response;
use Exception;

class Auth
{
    private static $config = [
        'table' => 'users',
        'login_fields' => ['email', 'password'],
        'registration_fields' => ['email', 'password', 'name'],
        'jwt_secret' => 'your-secret-key',
        'jwt_algorithm' => 'HS256',
        'jwt_expire' => 3600, // 1 час
        'refresh_expire' => 2592000, // 30 дней
        'cookie_name' => 'AUTHTOKEN',
        'cookie_domain' => '',
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'tokens_table' => 'meract_tokens',
        'token_cleanup_probability' => 0.1, // 10% chance
        'max_invalid_tokens' => 1000 // Максимальное количество хранимых невалидных токенов
    ];

    private $user = null;
    private $tokens = null;
    private $request;

    /**
     * Конструктор с инъекцией запроса
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->maybeCleanupTokens();
    }

    /**
     * Установка конфигурации
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Старт аутентификации
     */
    public static function start(Request $request): self
    {
        $auth = new self($request);
        $auth->tryAuthenticate();
        return $auth;
    }

    /**
     * Попытка аутентификации
     */
    private function tryAuthenticate(): void
    {
        $token = $this->getTokenFromRequest();

        if ($token) {
            $payload = $this->validateToken($token);

            if ($payload) {
                $this->user = $this->getUserById($payload->user_id);
                if ($this->user) {
                    $this->tokens = $this->generateTokens($this->user['id']);
                }
            }
        }
    }

    /**
     * Регистрация нового пользователя
     */
    public static function register(array $data, Request $request): self
    {
        self::validateData($data, self::$config['registration_fields']);

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($data['password']) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        // Проверка существования пользователя
        $exists = Qryli::select('id')
            ->from(self::$config['table'])
            ->where('email = ?', [$data['email']])
            ->run();

        if (!empty($exists)) {
            throw new Exception("User already exists");
        }

        $result = Qryli::insert(self::$config['table'], $data)
            ->run();

        if ($result['affected_rows'] === 1) {
            $userId = Qryli::select('LAST_INSERT_ID() as id')
                ->run()[0]['id'];

            $auth = new self($request);
            $auth->user = $auth->getUserById($userId);
            $auth->tokens = $auth->generateTokens($userId);

            return $auth;
        }

        throw new Exception("Registration failed");
    }

    /**
     * Авторизация пользователя
     */
    public static function login(array $credentials, Request $request): self
    {
        self::validateData($credentials, self::$config['login_fields']);

        $user = Qryli::select('*')
            ->from(self::$config['table'])
            ->where(self::$config['login_fields'][0] . ' = ?', [$credentials[self::$config['login_fields'][0]]])
            ->run();

        if (empty($user)) {
            throw new Exception("User not found");
        }

        if (!password_verify($credentials['password'], $user[0]['password'])) {
            throw new Exception("Invalid password");
        }

        $auth = new self($request);
        $auth->user = $user[0];
        $auth->tokens = $auth->generateTokens($user[0]['id']);

        return $auth;
    }

    /**
     * Выход из системы
     */
    public function logout(Response $response): Response
    {
        $token = $this->request->cookie(self::$config['cookie_name']) ?? 
                $this->request->header('Authorization');
        
        if ($token) {
            self::invalidateToken($token);
        }

        return $this->clearAuthCookie($response);
    }

    /**
     * Удаление пользователя
     */
    public static function delete(int $userId): bool
    {
        $result = Qryli::delete(self::$config['table'])
            ->where('id = ?', [$userId])
            ->run();

        return $result['affected_rows'] > 0;
    }

    /**
     * Установка токенов в ответ
     */
    public function set(Response $response): Response
    {
        if ($this->tokens) {
            $response->cookie(
                self::$config['cookie_name'],
                $this->tokens['access'],
                time() + self::$config['jwt_expire'],
                '/',
                self::$config['cookie_domain'],
                self::$config['cookie_secure'],
                self::$config['cookie_httponly']
            );
        }

        return $response;
    }

    /**
     * Получение токенов
     */
    public function getTokens(): array
    {
        return $this->tokens ?? [];
    }

    /**
     * Инвалидация токена
     */
    public static function invalidateToken(string $token): void
    {
        if (empty($token)) return;

        // Проверяем, не превысили ли лимит невалидных токенов
        $count = Qryli::select('COUNT(*) as count')
            ->from(self::$config['tokens_table'])
            ->run()[0]['count'];

        if ($count >= self::$config['max_invalid_tokens']) {
            self::cleanupTokens(true);
        }

        Qryli::insert(self::$config['tokens_table'], [
            "token" => $token,
            "created_at" => date('Y-m-d H:i:s')
        ])->run();
    }

    /**
     * Обновление токенов
     */
    public static function refreshTokens(string $refreshToken): array
    {
        $auth = new self(new Request());
        $payload = $auth->validateToken($refreshToken, true);

        if (!$payload) {
            throw new Exception("Invalid refresh token");
        }

        self::invalidateToken($refreshToken);

        return $auth->generateTokens($payload->user_id);
    }

    /**
     * API аутентификация
     */
    public static function apiLogin(string $token): ?self
    {
        $request = new Request();
        $auth = new self($request);
        $payload = $auth->validateToken($token);

        if ($payload) {
            $auth->user = $auth->getUserById($payload->user_id);
            return $auth;
        }

        return null;
    }

    /**
     * Магические методы для доступа к данным пользователя
     */
    public function __get($name)
    {
        return $this->user[$name] ?? null;
    }

    public function __set($name, $value)
    {
        if ($this->user) {
            $this->user[$name] = $value;

            Qryli::update(self::$config['table'], [$name => $value])
                ->where('id = ?', [$this->user['id']])
                ->run();
        }
    }

    /**
     * Валидация токена
     */
    private function validateToken(string $token, bool $isRefresh = false): ?object
    {
        try {
            $payload = $this->decodeAndVerifyJWT($token);
            
            if (!$payload) return null;
            if ($this->isTokenExpired($payload)) return null;
            if ($isRefresh && (!isset($payload->refresh) || !$payload->refresh)) return null;
            if (!$isRefresh && (isset($payload->refresh) && $payload->refresh)) return null;
            
            return $payload;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Декодирование и верификация JWT
     */
    private function decodeAndVerifyJWT(string $token): ?object
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($header64, $payload64, $signature64) = $parts;

        // Проверка алгоритма
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $header64)));
        if (!$header || $header->alg !== self::$config['jwt_algorithm']) {
            return null;
        }

        // Проверка подписи
        $signature = hash_hmac('sha256', "$header64.$payload64", self::$config['jwt_secret'], true);
        $computedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($signature64, $computedSignature)) {
            return null;
        }

        // Декодирование payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload64)));
        if (!$payload || !isset($payload->user_id, $payload->exp)) {
            return null;
        }

        // Проверка в черном списке
        if ($this->isTokenInvalidated($token)) {
            return null;
        }

        return $payload;
    }

    /**
     * Генерация пары токенов
     */
    private function generateTokens(int $userId): array
    {
        return [
            'access' => $this->generateJWT($userId, self::$config['jwt_expire']),
            'refresh' => $this->generateJWT($userId, self::$config['refresh_expire'], true)
        ];
    }

    /**
     * Генерация JWT токена
     */
    private function generateJWT(int $userId, int $expire, bool $isRefresh = false): string
    {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => self::$config['jwt_algorithm']
        ]);

        $payload = json_encode([
            'user_id' => $userId,
            'exp' => time() + $expire,
            'refresh' => $isRefresh,
            'iat' => time(),
            'jti' => bin2hex(random_bytes(16)) // Уникальный идентификатор токена
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$config['jwt_secret'], true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * Проверка срока действия токена
     */
    private function isTokenExpired(object $payload): bool
    {
        return $payload->exp < time();
    }

    /**
     * Проверка токена в черном списке
     */
    private function isTokenInvalidated(string $token): bool
    {
        $result = Qryli::select('COUNT(*) as count')
            ->from(self::$config['tokens_table'])
            ->where('token = ?', [$token])
            ->run();

        return $result[0]['count'] > 0;
    }

    /**
     * Очистка старых токенов
     */
    private function maybeCleanupTokens(bool $force = false): void
    {
        if ($force || mt_rand(1, 100) <= (self::$config['token_cleanup_probability'] * 100)) {
            $expireTime = time() - self::$config['refresh_expire'];
            Qryli::delete(self::$config['tokens_table'])
                ->where('created_at < ?', [date('Y-m-d H:i:s', $expireTime)])
                ->run();
        }
    }

    /**
     * Очистка auth cookie
     */
    private function clearAuthCookie(Response $response): Response
    {
        $response->cookie(
            self::$config['cookie_name'],
            '',
            time() - 3600,
            '/',
            self::$config['cookie_domain'],
            self::$config['cookie_secure'],
            self::$config['cookie_httponly']
        );
		return $response;
    }

    /**
     * Получение пользователя по ID
     */
    private function getUserById(int $userId): ?array
    {
        $user = Qryli::select('*')
            ->from(self::$config['table'])
            ->where('id = ?', [$userId])
            ->run();

        return $user[0] ?? null;
    }

    /**
     * Получение токена из запроса
     */
    private function getTokenFromRequest(): ?string
    {
        $header = $this->request->header('Authorization');
        if ($header && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return $matches[1];
        }

        return $this->request->cookie(self::$config['cookie_name']) ?? null;
    }

    /**
     * Валидация данных
     */
    private static function validateData(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field $field is required");
            }
        }
    }
}
