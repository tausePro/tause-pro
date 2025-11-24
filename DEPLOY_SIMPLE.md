# 🚀 DEPLOY DIRECTO A AWS - PLAN SIMPLIFICADO

## ⏰ OBJETIVO: Sales Agent (WooCommerce + Wompi) listo para mañana 9am

---

## 📋 PASO 1: COMMIT LOCAL (Ya hecho ✅)

```bash
# Cambios commiteados:
- ProductOrchestratorService.php
- AgentOrchestratorService.php  
- ProductCardService.php
- ProductIntegrationService.php
- EnhancedKnowledgeBaseTrait.php
```

---

## 🔍 PASO 2: CONECTARSE A AWS Y VERIFICAR

### 2.1 Conectarse
```bash
ssh -i ~/.ssh/tu-key.pem usuario@tu-servidor-aws.com
cd /var/www/tausepro9.4  # O tu ruta
```

### 2.2 Verificar backups automáticos
```bash
# Ejecutar script de verificación
./scripts/check-backups-aws.sh

# O manualmente:
# Verificar backups RDS
aws rds describe-db-snapshots --region us-east-1 | grep -i "backup"

# Verificar backups locales
ls -lah /backups/*.sql* 2>/dev/null | tail -5
ls -lah /var/backups/*.sql* 2>/dev/null | tail -5

# Verificar último backup
find /backups /var/backups -name "*.sql*" -type f -mtime -1 | head -1
```

### 2.3 Verificar estado actual
```bash
# Verificar que app funciona
php artisan about

# Ver logs recientes
tail -n 50 storage/logs/laravel.log | grep -i "error\|exception"

# Ver productos actuales
php artisan tinker --execute="
    \$count = App\Extensions\Chatbot\System\Models\ChatbotProduct::count();
    echo 'Productos en BD: ' . \$count . PHP_EOL;
"
```

---

## 📤 PASO 3: SUBIR ARCHIVOS (Desde tu máquina local)

### Opción A: Script automático
```bash
# 1. Editar scripts/upload-to-aws.sh con tus credenciales:
#    - AWS_HOST
#    - AWS_USER  
#    - AWS_KEY_PATH
#    - AWS_REMOTE_PATH

# 2. Ejecutar:
chmod +x scripts/upload-to-aws.sh
./scripts/upload-to-aws.sh
```

### Opción B: SCP manual (más seguro)
```bash
# En tu máquina local:
cd /Users/tause/Documents/proyectos/tausepro9.4

# Subir los 5 archivos:
scp -i ~/.ssh/tu-key.pem \
    app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php \
    usuario@servidor:/var/www/tausepro9.4/app/Extensions/Chatbot/System/Services/

scp -i ~/.ssh/tu-key.pem \
    app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php \
    usuario@servidor:/var/www/tausepro9.4/app/Extensions/Chatbot/System/Services/

scp -i ~/.ssh/tu-key.pem \
    app/Extensions/Chatbot/System/Services/ProductCardService.php \
    usuario@servidor:/var/www/tausepro9.4/app/Extensions/Chatbot/System/Services/

scp -i ~/.ssh/tu-key.pem \
    app/Extensions/Chatbot/System/Services/ProductIntegrationService.php \
    usuario@servidor:/var/www/tausepro9.4/app/Extensions/Chatbot/System/Services/

scp -i ~/.ssh/tu-key.pem \
    app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php \
    usuario@servidor:/var/www/tausepro9.4/app/Extensions/Chatbot/System/Services/Traits/
```

### Opción C: SFTP/FileZilla
- Conectar con credenciales SSH
- Navegar a: `/var/www/tausepro9.4/app/Extensions/Chatbot/System/Services/`
- Subir los 5 archivos

---

## ✅ PASO 4: DEPLOY EN AWS (Después de subir)

```bash
# En AWS, ejecutar:

cd /var/www/tausepro9.4

# 1. Verificar archivos subidos
ls -lah app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php
ls -lah app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php

# 2. Verificar sintaxis
php -l app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php
php -l app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php
php -l app/Extensions/Chatbot/System/Services/ProductCardService.php
php -l app/Extensions/Chatbot/System/Services/ProductIntegrationService.php
php -l app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php

# 3. Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Regenerar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Verificar que funciona
php artisan about

# 6. Verificar que servicios se cargan
php artisan tinker --execute="
    \$service = new App\Extensions\Chatbot\System\Services\ProductOrchestratorService();
    echo '✅ ProductOrchestratorService OK' . PHP_EOL;
"
```

---

## 🧪 PASO 5: TESTING

### 5.1 Probar Sales Agent
1. Dashboard > Chatbot > [Tu Chatbot]
2. Activar "Sales Agent"
3. Verificar configuración WooCommerce
4. Enviar mensaje: "quiero comprar productos"
5. Verificar que encuentra productos activos y en stock

### 5.2 Verificar logs
```bash
tail -f storage/logs/laravel.log | grep -i "product\|sales\|agent"
```

### 5.3 Verificar productos
```bash
php artisan tinker --execute="
    \$products = App\Extensions\Chatbot\System\Models\ChatbotProduct::where('is_active', true)
        ->where('in_stock', true)
        ->count();
    echo 'Productos activos y en stock: ' . \$products . PHP_EOL;
"
```

---

## 🔄 ROLLBACK RÁPIDO (Si es necesario)

```bash
# En AWS:
cd /var/www/tausepro9.4

# Opción 1: Restaurar desde backup de archivos (si existe)
# Opción 2: Revertir cambios manualmente en los 5 archivos
# Opción 3: Restaurar desde snapshot de BD (si hay problema crítico)

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

---

## 📊 CHECKLIST FINAL

- [ ] Commit hecho localmente ✅
- [ ] Conectado a AWS
- [ ] Backups verificados
- [ ] Archivos subidos a AWS
- [ ] Sintaxis verificada
- [ ] Cache limpiado y regenerado
- [ ] Aplicación funciona
- [ ] Sales Agent probado
- [ ] Productos se encuentran correctamente
- [ ] Listo para presentación mañana 9am ✅

---

**¿Listo?** Primero conectémonos a AWS para verificar backups y estado actual.

