# ✅ Staging Listo para Usar

## 🎉 Problemas Resueltos

1. ✅ **Conexión SSH**: Funciona con `-4` (IPv4)
2. ✅ **Security Group**: Tiene regla SSH correcta
3. ✅ **Permisos Storage**: Corregidos
4. ⏳ **Base de Datos**: En proceso de configuración

---

## 📋 Estado Actual

- **IP**: `13.218.39.31`
- **Instancia**: `i-06113402909fd6b57` (Running)
- **Conexión**: `ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31`
- **Path**: `/var/www/magicai-staging`

---

## 🔧 Comandos para Completar

### 1. Arreglar Permisos (si es necesario)

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
cd /var/www/magicai-staging
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
exit
```

### 2. Verificar/Crear Usuario MySQL

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
sudo mysql

# En MySQL:
CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Ejecutar Migraciones

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
cd /var/www/magicai-staging
php artisan migrate --force
php artisan config:cache
php artisan route:cache
exit
```

---

## 🚀 Después de Completar

### Desplegar Cambios de Fase 1

```bash
export STAGING_HOST=13.218.39.31
export KEY_FILE=staging-tausepro-key.pem
./scripts/deploy-to-staging.sh
```

**Nota**: Actualiza `deploy-to-staging.sh` para usar `-4` también.

---

## ✅ Checklist Final

- [x] Conexión SSH funciona
- [x] Permisos de storage corregidos
- [ ] Base de datos configurada correctamente
- [ ] Migraciones ejecutadas
- [ ] Sitio accesible vía HTTP
- [ ] Cambios de Fase 1 desplegados
- [ ] Validación en staging

---

**¿Quieres que te ayude a completar estos pasos manualmente?**



