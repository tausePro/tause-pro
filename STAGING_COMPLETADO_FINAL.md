# ✅ Staging Completado - Estado Final

## 🎉 Lo que se Completó

1. ✅ **Producción arreglada**: Agregada regla SSH al Security Group
2. ✅ **BD clonada**: 155 tablas importadas desde producción
3. ✅ **Permisos**: Corregidos completamente
4. ✅ **Cache**: Actualizado

---

## 📊 Estado Actual

- **Staging IP**: `13.218.39.31`
- **BD**: `magicai_staging` con 155 tablas
- **Conexión SSH**: Funciona con `ssh -4`
- **Producción**: Funciona nuevamente

---

## 🚀 Próximo Paso: Desplegar Fase 1

Ahora que staging está completamente configurado, puedes desplegar los cambios de Fase 1:

### Opción 1: Desplegar desde Git

```bash
export STAGING_HOST=13.218.39.31
export KEY_FILE=staging-tausepro-key.pem
./scripts/deploy-to-staging.sh
```

**Nota**: Necesitas actualizar `deploy-to-staging.sh` para usar `-4` en comandos SSH.

### Opción 2: Desplegar Manualmente

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
cd /var/www/magicai-staging

# Hacer pull de cambios
git pull origin main

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Ejecutar migraciones (si hay nuevas)
php artisan migrate --force

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## ✅ Checklist Final

- [x] Producción conecta
- [x] BD clonada (155 tablas)
- [x] Permisos corregidos
- [x] Cache actualizado
- [ ] Cambios de Fase 1 desplegados
- [ ] Sitio accesible vía HTTP
- [ ] Validación de Fase 1 en staging

---

## 💡 Validación de Fase 1 en Staging

Una vez desplegados los cambios:

1. **Activar Sales Agent** en un chatbot
2. **Verificar** que se crea External Agent automáticamente
3. **Probar** chatbot embebido con mensajes reales
4. **Verificar logs**: `tail -f storage/logs/laravel.log | grep Agent`

---

**¿Quieres que actualice el script deploy-to-staging.sh para usar -4 y luego desplegar los cambios?**



