# ✅ Checklist de Deploy a Producción

## 📋 Pre-Deploy

### Backup
- [ ] Ejecutar `./scripts/backup-pre-deploy.sh`
- [ ] Verificar que el backup se creó correctamente
- [ ] Anotar ubicación del backup: `/backups/pre-deploy-YYYYMMDD-HHMMSS`

### Verificación de Código
- [ ] Todos los cambios están commiteados
- [ ] Branch actual: `main` o `feature/sales-agent-config-view`
- [ ] No hay cambios sin commitear
- [ ] Verificar sintaxis PHP: `find app/Extensions/Chatbot -name "*.php" -exec php -l {} \;`

### Verificación de Cambios
- [ ] Revisar archivos modificados:
  - `ProductOrchestratorService.php` ✅
  - `AgentOrchestratorService.php` ✅
  - `ProductCardService.php` ✅
  - `ProductIntegrationService.php` ✅
  - `EnhancedKnowledgeBaseTrait.php` ✅

## 🚀 Deploy

### Opción 1: Deploy Manual (Recomendado para primera vez)
```bash
# 1. Crear backup
./scripts/backup-pre-deploy.sh

# 2. Hacer merge a main (si estás en otra rama)
git checkout main
git merge feature/sales-agent-config-view

# 3. Push a GitHub
git push origin main

# 4. En el servidor de producción:
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Verificar
./scripts/verify-deployment.sh
```

### Opción 2: Deploy Automático (GitHub Actions)
- [ ] Configurar secrets en GitHub:
  - `AWS_ACCESS_KEY_ID`
  - `AWS_SECRET_ACCESS_KEY`
  - `AWS_EC2_HOST`
  - `AWS_EC2_USER`
  - `AWS_EC2_SSH_KEY`
- [ ] Hacer push a `main`
- [ ] Verificar que el workflow se ejecuta correctamente
- [ ] Revisar logs del workflow

## ✅ Post-Deploy

### Verificación Funcional
- [ ] La aplicación carga correctamente
- [ ] No hay errores 500 en logs
- [ ] Sales Agent funciona:
  - [ ] Activar Sales Agent en un chatbot
  - [ ] Enviar mensaje con keywords: "producto", "comprar"
  - [ ] Verificar que encuentra productos
- [ ] Productos se muestran correctamente:
  - [ ] Verificar que solo muestra productos activos y en stock
  - [ ] Verificar que campos se muestran correctamente (precio, imagen, etc.)

### Verificación de Logs
```bash
# Revisar logs recientes
tail -f storage/logs/laravel.log | grep -i "error\|exception\|product\|sales"

# Buscar errores específicos
grep -i "availability\|undefined\|null" storage/logs/laravel.log | tail -20
```

### Verificación de Base de Datos
```sql
-- Verificar productos
SELECT id, name, is_active, in_stock, stock_quantity 
FROM ext_chatbot_products 
WHERE chatbot_id = [ID_DEL_CHATBOT]
LIMIT 10;

-- Verificar que no hay errores relacionados
```

## 🔄 Rollback (Si es necesario)

Si encuentras problemas críticos:

```bash
# 1. Identificar el backup a restaurar
ls -la /backups/pre-deploy-*

# 2. Restaurar desde backup
./scripts/restore-backup.sh /backups/pre-deploy-YYYYMMDD-HHMMSS

# 3. O revertir el commit
git revert HEAD
git push origin main
```

## 📊 Métricas a Monitorear

Después del deploy, monitorear por 24-48 horas:

- [ ] Tasa de errores en logs
- [ ] Tiempo de respuesta del chatbot
- [ ] Funcionalidad de Sales Agent
- [ ] Búsqueda de productos
- [ ] Conversiones/ventas (si aplica)

## 🆘 Contacto de Emergencia

Si hay problemas críticos:
1. Ejecutar rollback inmediatamente
2. Revisar logs: `tail -f storage/logs/laravel.log`
3. Verificar backups disponibles
4. Documentar el problema para análisis posterior

