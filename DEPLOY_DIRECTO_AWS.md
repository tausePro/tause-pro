# 🚀 PLAN DE DEPLOY DIRECTO A AWS (Sin GitHub)

## ⏰ OBJETIVO: Sales Agent (WooCommerce + Wompi) listo para mañana 9am

---

## 📋 FASE 1: PREPARACIÓN LOCAL

### Paso 1.1: Commit de cambios
```bash
cd /Users/tause/Documents/proyectos/tausepro9.4
git add app/Extensions/Chatbot/System/Services/
git add scripts/
git add DEPLOY_PLAN_AWS.md
git commit -m "fix: Correcciones críticas Sales Agent - ProductOrchestratorService, AgentOrchestratorService, ProductCardService"
git push origin main  # Solo para respaldo, NO usaremos para deploy
```

### Paso 1.2: Verificar archivos a subir
```bash
# Archivos modificados:
- app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php
- app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php
- app/Extensions/Chatbot/System/Services/ProductCardService.php
- app/Extensions/Chatbot/System/Services/ProductIntegrationService.php
- app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php
```

---

## 🔍 FASE 2: VERIFICACIÓN EN AWS (Conectarse primero)

### Paso 2.1: Conectarse a AWS
```bash
ssh -i ~/.ssh/tu-key.pem usuario@tu-servidor-aws.com
cd /var/www/tausepro9.4  # O tu ruta
```

### Paso 2.2: Verificar backups automáticos de BD
```bash
# Verificar backups automáticos (RDS o scripts)
# Opciones comunes:

# Si usas RDS:
aws rds describe-db-snapshots --region us-east-1 | grep -i "backup\|snapshot"

# Si tienes backups locales:
ls -lah /backups/  # O donde estén tus backups
ls -lah /var/backups/

# Verificar último backup de BD
find /backups -name "*.sql*" -type f -mtime -1 | head -5
find /var/backups -name "*.sql*" -type f -mtime -1 | head -5

# Verificar configuración de backups automáticos
crontab -l | grep -i backup
cat /etc/cron.daily/* | grep -i backup
```

### Paso 2.3: Verificar estado actual
```bash
# Verificar que la aplicación funciona
php artisan about

# Ver logs recientes
tail -n 50 storage/logs/laravel.log | grep -i "error\|exception"

# Verificar productos actuales
php artisan tinker --execute="
    \$products = App\Extensions\Chatbot\System\Models\ChatbotProduct::count();
    echo 'Productos en BD: ' . \$products . PHP_EOL;
"
```

---

## 📤 FASE 3: SUBIR ARCHIVOS DIRECTAMENTE

### Opción A: Usar script de subida (recomendado)
```bash
# En tu máquina local:
# 1. Editar scripts/upload-to-aws.sh con tus credenciales
# 2. Ejecutar:
chmod +x scripts/upload-to-aws.sh
./scripts/upload-to-aws.sh
```

### Opción B: Subir manualmente con SCP
```bash
# En tu máquina local:
cd /Users/tause/Documents/proyectos/tausepro9.4

# Subir archivos uno por uno:
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

### Opción C: Usar SFTP o cliente gráfico
- FileZilla, WinSCP, Cyberduck, etc.
- Conectar con credenciales SSH
- Subir los 5 archivos modificados

---

## ✅ FASE 4: DEPLOY EN AWS (Después de subir archivos)

### Paso 4.1: Verificar archivos subidos
```bash
# En AWS:
cd /var/www/tausepro9.4

# Verificar que los archivos están ahí
ls -lah app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php
ls -lah app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php
ls -lah app/Extensions/Chatbot/System/Services/ProductCardService.php
ls -lah app/Extensions/Chatbot/System/Services/ProductIntegrationService.php
ls -lah app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php
```

### Paso 4.2: Verificar sintaxis PHP
```bash
php -l app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php
php -l app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php
php -l app/Extensions/Chatbot/System/Services/ProductCardService.php
php -l app/Extensions/Chatbot/System/Services/ProductIntegrationService.php
php -l app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php

# O verificar todos:
find app/Extensions/Chatbot/System/Services -name "*.php" -exec php -l {} \;
```

### Paso 4.3: Limpiar y regenerar cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Paso 4.4: Verificación rápida
```bash
# Verificar que la aplicación funciona
php artisan about

# Verificar que no hay errores de sintaxis al cargar
php artisan tinker --execute="
    try {
        \$service = new App\Extensions\Chatbot\System\Services\ProductOrchestratorService();
        echo '✅ ProductOrchestratorService OK' . PHP_EOL;
    } catch (Exception \$e) {
        echo '❌ Error: ' . \$e->getMessage() . PHP_EOL;
    }
"
```

---

## 🧪 FASE 5: TESTING

### Paso 5.1: Probar Sales Agent
1. **Ir al Dashboard:**
   - Dashboard > Chatbot > [Tu Chatbot]
   - Activar "Sales Agent"
   - Verificar configuración de WooCommerce

2. **Probar búsqueda de productos:**
   - Enviar mensaje: "quiero comprar productos"
   - Enviar mensaje: "qué productos tienen disponibles"
   - Verificar que encuentra productos activos y en stock

3. **Verificar logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "product\|sales\|agent"
```

### Paso 5.2: Verificar integración WooCommerce
```bash
# Verificar productos sincronizados
php artisan tinker --execute="
    \$products = App\Extensions\Chatbot\System\Models\ChatbotProduct::where('is_active', true)
        ->where('in_stock', true)
        ->count();
    echo 'Productos activos y en stock: ' . \$products . PHP_EOL;
"
```

### Paso 5.3: Probar flujo completo
1. Activar Sales Agent en chatbot
2. Enviar mensaje con intención de compra
3. Verificar que muestra productos
4. Verificar que los productos tienen precio, imagen, etc.
5. Probar link de compra (si está configurado)

---

## 🔄 ROLLBACK (Si es necesario)

### Rollback rápido
```bash
# En AWS, restaurar archivos desde backup (si existe)
# O revertir manualmente los cambios en los 5 archivos

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

---

## 📊 CHECKLIST FINAL

### Pre-Deploy
- [ ] Commit hecho localmente
- [ ] Archivos verificados localmente
- [ ] Backup automático de BD verificado en AWS
- [ ] Conectado a AWS y verificado estado actual

### Deploy
- [ ] Archivos subidos a AWS
- [ ] Sintaxis PHP verificada
- [ ] Cache limpiado y regenerado
- [ ] Aplicación responde correctamente

### Testing
- [ ] Sales Agent se activa
- [ ] Encuentra productos correctamente
- [ ] Solo muestra productos activos y en stock
- [ ] No hay errores en logs
- [ ] Integración WooCommerce funciona
- [ ] Listo para presentación mañana 9am

---

## 🆘 SI HAY PROBLEMAS

1. **Revisar logs inmediatamente:**
```bash
tail -f storage/logs/laravel.log
```

2. **Verificar sintaxis:**
```bash
php -l [archivo-problematico]
```

3. **Rollback rápido:**
   - Restaurar archivos desde backup
   - O revertir cambios manualmente

4. **Contactar si es crítico antes de 9am**

---

**¿Listo para empezar?** Primero conectémonos a AWS para verificar backups y estado actual.

