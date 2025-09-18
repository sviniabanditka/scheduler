<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                text-align: center;
                background: white;
                padding: 3rem;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                max-width: 600px;
                margin: 2rem;
            }
            h1 {
                color: #333;
                font-size: 3rem;
                margin-bottom: 1rem;
                font-weight: 600;
            }
            .subtitle {
                color: #666;
                font-size: 1.2rem;
                margin-bottom: 2rem;
            }
            .status {
                background: #10b981;
                color: white;
                padding: 1rem 2rem;
                border-radius: 10px;
                display: inline-block;
                font-weight: 600;
                margin: 1rem 0;
            }
            .info {
                background: #f3f4f6;
                padding: 1.5rem;
                border-radius: 10px;
                margin: 2rem 0;
                text-align: left;
            }
            .info h3 {
                margin-top: 0;
                color: #374151;
            }
            .info ul {
                margin: 0;
                padding-left: 1.5rem;
            }
            .info li {
                margin: 0.5rem 0;
                color: #6b7280;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 Laravel Scheduler</h1>
            <p class="subtitle">Docker Environment Ready!</p>
            
            <div class="status">
                ✅ Application is running successfully
            </div>

            <div class="info">
                <h3>🐳 Docker Services Status:</h3>
                <ul>
                    <li>✅ Laravel App (PHP 8.2-fpm)</li>
                    <li>✅ Nginx Web Server</li>
                    <li>✅ MySQL Database</li>
                    <li>✅ Redis Cache</li>
                </ul>
            </div>

            <div class="info">
                <h3>🛠️ Available Commands:</h3>
                <ul>
                    <li><code>make build</code> - Build Docker containers</li>
                    <li><code>make up</code> - Start all services</li>
                    <li><code>make down</code> - Stop all services</li>
                    <li><code>make migrate</code> - Run database migrations</li>
                    <li><code>make seed</code> - Run database seeders</li>
                    <li><code>make shell</code> - Access app container</li>
                </ul>
            </div>

            <p style="color: #9ca3af; margin-top: 2rem;">
                Laravel v<?php echo e(Illuminate\Foundation\Application::VERSION); ?> (PHP v<?php echo e(PHP_VERSION); ?>)
            </p>
        </div>
    </body>
</html>
<?php /**PATH /var/www/resources/views/welcome.blade.php ENDPATH**/ ?>