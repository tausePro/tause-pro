<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Expirado - {{ setting('app_name', 'MagicAI') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #ff6b6b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
            color: white;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            color: #856404;
        }
        
        .back-link {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            ⏰
        </div>
        
        <h1>Token Expirado</h1>
        <p class="subtitle">El enlace de conexión ha expirado</p>
        
        <div class="message">
            <strong>¿Qué pasó?</strong><br><br>
            El token de conexión que usaste ha expirado. Los tokens de conexión son válidos por 10 minutos por seguridad.<br><br>
            <strong>¿Qué hacer?</strong><br><br>
            Regresa al chatbot y genera un nuevo enlace de conexión. El proceso es rápido y seguro.
        </div>
        
        <a href="javascript:history.back()" class="back-link">← Volver al chatbot</a>
    </div>
</body>
</html>
