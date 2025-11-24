# ✅ Comandos Correctos para Staging

## ⚠️ IMPORTANTE

Los comandos deben ejecutarse **EN EL SERVIDOR STAGING**, no en tu Mac local.

---

## 🚀 Pasos Correctos

### Paso 1: Conectar al Servidor

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

**Espera a que veas el prompt del servidor** (debería decir algo como `ubuntu@ip-...`)

### Paso 2: Una vez conectado AL SERVIDOR, ejecuta:

```bash
cd /var/www/magicai
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
exit
```

### Paso 3: Probar que funciona

**Desde tu Mac local:**

```bash
curl http://test.tause.pro
```

O abre en navegador: `http://test.tause.pro`

---

## ❌ Lo que NO hacer

- ❌ NO ejecutar `php artisan` en tu Mac (eso es para tu proyecto local)
- ❌ NO ejecutar `chown www-data` en tu Mac (www-data no existe en macOS)
- ✅ SÍ ejecutar `ssh` para conectarte primero
- ✅ SÍ ejecutar los comandos DESPUÉS de conectarte

---

## 📋 Resumen

1. **Conectar**: `ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213`
2. **Esperar** a ver el prompt del servidor
3. **Ejecutar** los comandos de cache
4. **Salir**: `exit`
5. **Probar**: `curl http://test.tause.pro`

---

**Conecta primero con SSH, luego ejecuta los comandos en el servidor.**



