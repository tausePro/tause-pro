# 🚀 Solución Final Rápida: Staging

## ⚠️ Problema

SSH no conecta (timeout) aunque Security Group está bien. Probablemente firewall interno bloqueando.

---

## ✅ Solución: EC2 Instance Connect (2 minutos)

### Paso 1: Conectar desde AWS Console

1. Ve a: https://console.aws.amazon.com/ec2/
2. Instances → Busca `i-0bbe91c13a1343538` (o IP `44.211.83.213`)
3. Click **"Connect"** (botón azul arriba)
4. Tab **"EC2 Instance Connect"**
5. Click **"Connect"**

Esto te dará acceso directo sin SSH.

### Paso 2: Ejecutar Comandos

Una vez conectado, ejecuta estos comandos:

```bash
# Deshabilitar firewall temporalmente
sudo ufw disable

# Ir al proyecto
cd /var/www/magicai

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Actualizar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar firewall correctamente
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Verificar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Probar localmente
curl http://localhost
```

### Paso 3: Probar desde tu Mac

```bash
curl http://test.tause.pro
```

O abre en navegador: `http://test.tause.pro`

---

## ✅ Checklist Final

- [ ] Conectar vía EC2 Instance Connect
- [ ] Deshabilitar firewall temporalmente
- [ ] Actualizar cache Laravel
- [ ] Configurar firewall correctamente
- [ ] Verificar permisos
- [ ] Probar sitio

---

## 💡 Resumen

**Todo está listo** - solo falta:
1. Conectar vía EC2 Instance Connect (no SSH)
2. Ejecutar comandos de cache (2 minutos)
3. Listo ✅

**Usa EC2 Instance Connect desde AWS Console - es más rápido y no requiere SSH.**



