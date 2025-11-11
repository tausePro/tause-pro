# ✅ PLAN DE DEPLOY SEGURO A PRODUCCIÓN (AWS)

## 🎯 Estrategia: Deploy Manual y Seguro

**NO usaremos GitHub Actions por ahora** - Deploy directo en AWS con backups completos.

---

## 📋 FASE 1: BACKUP COMPLETO (Ejecutar en AWS)

### Paso 1.1: Backup de Base de Datos y Archivos
```bash
# En el servidor AWS, ejecutar:
cd /var/www/tausepro9.4
./scripts/backup-pre-deploy-aws.sh
```

**Verificar:**
- [ ] Backup creado en `/backups/pre-deploy-YYYYMMDD-HHMMSS`
- [ ] Base de datos respaldada (database.sql.gz)
- [ ] Storage respaldado (storage.tar.gz)
- [ ] Configuración respaldada (.env.backup)
- [ ] Información del commit guardada

### Paso 1.2: Crear Snapshot/Imagen de EC2
```bash
# En el servidor AWS, ejecutar:
./scripts/create-ec2-backup.sh
```

**Verificar:**
- [ ] Snapshots de volúmenes creados
- [ ] AMI (imagen completa) creada (opcional pero recomendado)
- [ ] Información guardada en `/backups/ec2-backup-YYYYMMDD-HHMMSS.txt`

**Nota:** Los snapshots pueden tardar varios minutos. Puedes continuar mientras se completan.

---

## 🚀 FASE 2: DEPLOY (Ejecutar en AWS)

### Paso 2.1: Preparar cambios
```bash
# En tu máquina local (antes de subir):
cd /Users/tause/Documents/proyectos/tausepro9.4

# Verificar cambios
git status
git diff --stat

# Si estás en feature branch, hacer merge a main
git checkout main
git merge feature/sales-agent-config-view  # o tu branch

# Push a GitHub
git push origin main
```

### Paso 2.2: Deploy en servidor AWS
```bash
# En el servidor AWS:
cd /var/www/tausepro9.4

# 1. Verificar que no hay cambios locales sin commitear
git status

# 2. Obtener cambios desde GitHub
git fetch origin
git pull origin main

# 3. Verificar que los archivos correctos se actualizaron
git log -1 --name-only

# 4. Instalar dependencias (si hay cambios en composer.json)
composer install --no-dev --optimize-autoloader

# 5. Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 6. Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Verificar sintaxis
find app/Extensions/Chatbot/System/Services -name "*.php" -exec php -l {} \;
```

---

## ✅ FASE 3: VERIFICACIÓN POST-DEPLOY (Ejecutar en AWS)

### Paso 3.1: Verificación automática
```bash
# En el servidor AWS:
./scripts/verify-deployment-aws.sh
```

**Verificar que:**
- [ ] ✅ Aplicación responde
- [ ] ✅ Sintaxis PHP correcta
- [ ] ✅ Servicios se cargan
- [ ] ✅ No hay errores críticos en logs
- [ ] ✅ Conexión a BD OK
- [ ] ✅ Modelo ChatbotProduct OK

### Paso 3.2: Verificación manual funcional

**1. Probar Sales Agent:**
- [ ] Ir a Dashboard > Chatbot > [Tu Chatbot]
- [ ] Activar Sales Agent
- [ ] Enviar mensaje de prueba: "quiero comprar productos"
- [ ] Verificar que encuentra productos
- [ ] Verificar que solo muestra productos activos y en stock

**2. Verificar logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "product\|sales\|agent\|error"
```

**3. Verificar base de datos:**
```sql
-- Verificar productos
SELECT id, name, is_active, in_stock, stock_quantity 
FROM ext_chatbot_products 
WHERE chatbot_id = [ID_DEL_CHATBOT]
LIMIT 10;
```

---

## 🔄 ROLLBACK (Si es necesario)

### Opción 1: Restaurar desde backup de archivos
```bash
# Identificar backup
ls -la /backups/pre-deploy-*

# Restaurar
./scripts/restore-backup.sh /backups/pre-deploy-YYYYMMDD-HHMMSS
```

### Opción 2: Revertir código
```bash
# En servidor AWS:
cd /var/www/tausepro9.4
git log --oneline -5  # Ver commits recientes
git revert HEAD  # Revertir último commit
git push origin main
php artisan config:cache
php artisan route:cache
```

### Opción 3: Restaurar desde EC2 Snapshot/AMI
```bash
# Ver información del backup
cat /backups/ec2-backup-YYYYMMDD-HHMMSS.txt

# Seguir instrucciones en el archivo para restaurar desde snapshot o AMI
```

---

## 📊 MONITOREO POST-DEPLOY

### Primeras 24 horas:
- [ ] Monitorear logs: `tail -f storage/logs/laravel.log`
- [ ] Verificar que no hay errores 500
- [ ] Probar Sales Agent varias veces
- [ ] Verificar búsqueda de productos
- [ ] Revisar métricas de uso

### Comandos útiles:
```bash
# Ver errores recientes
tail -n 100 storage/logs/laravel.log | grep -i error

# Ver uso de memoria
free -h

# Ver procesos PHP
ps aux | grep php

# Ver espacio en disco
df -h
```

---

## 🆘 CONTACTO DE EMERGENCIA

Si encuentras problemas críticos:

1. **Ejecutar rollback inmediatamente**
2. **Revisar logs:** `tail -f storage/logs/laravel.log`
3. **Verificar backups disponibles:** `ls -la /backups/`
4. **Documentar el problema** para análisis posterior

---

## 📝 CHECKLIST COMPLETO

### Pre-Deploy
- [ ] Backup de BD y archivos creado
- [ ] Snapshot/AMI de EC2 creado
- [ ] Cambios revisados y aprobados
- [ ] Branch correcto (main)

### Deploy
- [ ] Código actualizado desde GitHub
- [ ] Dependencias instaladas
- [ ] Cache limpiado y regenerado
- [ ] Sintaxis verificada

### Post-Deploy
- [ ] Verificación automática pasada
- [ ] Sales Agent funciona
- [ ] Productos se encuentran correctamente
- [ ] No hay errores en logs
- [ ] Aplicación responde normalmente

### Post-Deploy (24h)
- [ ] Monitoreo continuo
- [ ] Sin errores críticos
- [ ] Funcionalidad confirmada

---

## 💡 NOTAS IMPORTANTES

1. **NO usar GitHub Actions** hasta que los directorios estén alineados
2. **Siempre crear backup completo** antes de cualquier cambio
3. **Crear snapshot/AMI** como medida de seguridad adicional
4. **Verificar manualmente** después del deploy automático
5. **Monitorear** las primeras 24 horas intensivamente

---

## 🔧 CONFIGURACIÓN INICIAL (Solo primera vez)

Si es la primera vez que ejecutas estos scripts:

```bash
# Hacer scripts ejecutables
chmod +x scripts/*.sh

# Verificar que el directorio de backups existe
mkdir -p /backups

# Verificar configuración de AWS CLI (para snapshots)
aws configure list
```

---

**¿Listo para empezar?** Ejecuta Fase 1 primero y verifica que los backups se crearon correctamente antes de continuar.

