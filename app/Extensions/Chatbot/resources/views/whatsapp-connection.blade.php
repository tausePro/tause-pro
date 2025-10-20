<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar WhatsApp - {{ setting('app_name', 'MagicAI') }}</title>
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
        
        .whatsapp-icon {
            width: 80px;
            height: 80px;
            background: #25D366;
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
        
        .instructions {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
            color: #555;
        }
        
        .step:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            background: #25D366;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .connect-button {
            background: #25D366;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .connect-button:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3);
        }
        
        .token-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
            color: #1976d2;
            word-break: break-all;
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
        
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="whatsapp-icon">
            📱
        </div>
        
        <h1>Conectar WhatsApp</h1>
        <p class="subtitle">Vincula tu WhatsApp para recibir notificaciones del chatbot</p>
        
        <div class="instructions">
            <div class="step">
                <div class="step-number">1</div>
                <div>Haz clic en el botón "Abrir WhatsApp"</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div>Se abrirá WhatsApp con nuestro número de soporte</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div>Envía el comando que aparece automáticamente</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div>¡Listo! Tu WhatsApp estará conectado</div>
            </div>
        </div>
        
        <div class="token-info">
            <strong>Token de conexión:</strong><br>
            {{ $token }}
        </div>
        
        <a href="{{ $whatsappUrl }}" class="connect-button" target="_blank">
            📱 Abrir WhatsApp
        </a>
        
        <div class="status" id="status" style="display: none;">
            <!-- El estado se mostrará aquí -->
        </div>
        
        <a href="javascript:history.back()" class="back-link">← Volver al chatbot</a>
    </div>

    <script>
        // Verificar el estado de la conexión cada 5 segundos
        function checkConnectionStatus() {
            fetch(`/api/v2/chatbot/whatsapp/connection-status/{{ $token }}`)
                .then(response => response.json())
                .then(data => {
                    if (data.connected) {
                        document.getElementById('status').style.display = 'block';
                        document.getElementById('status').className = 'status success';
                        document.getElementById('status').innerHTML = '✅ ¡WhatsApp conectado exitosamente! Puedes cerrar esta ventana.';
                        clearInterval(checkInterval);
                    }
                })
                .catch(error => {
                    console.log('Checking connection status...');
                });
        }
        
        const checkInterval = setInterval(checkConnectionStatus, 5000);
        
        // Limpiar el intervalo después de 5 minutos
        setTimeout(() => {
            clearInterval(checkInterval);
        }, 300000);
    </script>
</body>
</html>
