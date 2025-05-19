<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meract Framework</title>
    @includeMorph
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --text-color: #1a202c;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --gray: #e2e8f0;
            --dark-gray: #94a3b8;
			--elem-background: #2e2e2e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .hero {
            text-align: center;
            padding: 80px 0;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .hero p {
            font-size: 20px;
            max-width: 800px;
            margin: 0 auto 40px;
            color: var(--dark-gray);
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 60px 0;
        }
        
        .feature-card {
            background-color: var(--elem-background);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card h3 {
            color: var(--primary-color);
            margin-top: 20px;
        }
        
        .footer {
            text-align: center;
            padding: 40px 0;
            margin-top: 60px;
            border-top: 1px solid var(--gray);
            color: var(--dark-gray);
        }
        
        @media (max-width: 768px) {
            .features {
                grid-template-columns: 1fr;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <morph name="welcome">
        <span class="logo">Meract</span>
        <div class="hero">
            <div class="container">
                <h1>Welcome to Meract</h1>
                <p>A modern PHP framework with innovative features, including the built-in Morph frontend framework.</p>
                <a href="https://github.com/meract/meract" class="btn">Github</a>
            </div>
        </div>
        
        <div class="container">
            <div class="features">
                <div class="feature-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    <h3>MVC Architecture</h3>
                    <p>Clear separation of logic, presentation and data for easy development.</p>
                </div>
                
                <div class="feature-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <h3>Morph UI</h3>
                    <p>A built-in frontend framework with reactive components and routing.</p>
                </div>
                
                <div class="feature-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <h3>Authentication</h3>
                    <p>Ready-made authentication system with JWT and cookie-based sessions.</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="container">
                <p>© {{year}} Meract Framework. Все права защищены.</p>
            </div>
        </div>
    </morph>
</body>
</html>
