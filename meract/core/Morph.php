<?php
namespace Meract\Core;
class Morph
{
    private static string $encryptionKey; 
	public static function setMorphLiveEncription(string $key){
		self::$encryptionKey = $key;
	}

    public static function live(callable $controller, callable $middleware = null): string
    {
        $data = [
            self::callableToString($controller),
            $middleware ? self::callableToString($middleware) : null,
        ];

        $json = json_encode($data);
        $iv = random_bytes(16); // Случайный вектор инициализации
        $encrypted = openssl_encrypt($json, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
        $combined = $iv . $encrypted; // IV (16 байт) + зашифрованные данные
        return rtrim(strtr(base64_encode($combined), '+/', '-_'), '='); // URL-safe base64
    }

    public static function resolve(string $hash): ?array
    {
        try {
            $combined = base64_decode(strtr($hash, '-_', '+/'));
            $iv = substr($combined, 0, 16);
            $encrypted = substr($combined, 16);
            $json = openssl_decrypt($encrypted, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
            return $json ? json_decode($json, true) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    private static function callableToString(callable $callable): string
    {
        if (is_array($callable)) {
            $class = is_object($callable[0]) ? get_class($callable[0]) : $callable[0];
            return $class . '::' . $callable[1];
        } elseif (is_string($callable)) {
            return $callable;
        } elseif ($callable instanceof Closure) {
            $ref = new ReflectionFunction($callable);
            return 'closure@' . $ref->getFileName() . ':' . $ref->getStartLine();
        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
            return get_class($callable) . '::__invoke';
        }
        throw new InvalidArgumentException('Unsupported callable type');
    }
}
