# 🔧 Arreglar 404 usando EC2 Instance Connect

## ⚠️ Situación

- ✅ Instancia: Running
- ✅ HTTP responde (no timeout)
- ❌ SSH no conecta (timeout)
- ❌ HTTP muestra 404 Not Found

**Solución**: Usar EC2 Instance Connect para acceder sin SSH.

---

## 🚀 Pasos para Arreglar

### Paso 1: Conectar vía EC2 Instance Connect

1. **AWS Console**: https://console.aws.amazon.com/ec2/
2. **Instances** → Selecciona `i-0bbe91c13a1343538`
3. **Connect** → Tab **"EC2 Instance Connect"**
4. **Connect**

---

### Paso 2: Verificar Directorio de Aplicación

Una vez conectado, ejecuta:

```bash
# Verificar si existe el directorio
ls -la /var/www/magicai/

# Verificar directorio public
ls -la /var/www/magicai/public/

# Verificar si index.php existe
test -f /var/www/magicai/public/index.php && echo "✅ Existe" || echo "❌ NO existe"
```

---

### Paso 3: Verificar Configuración Nginx

```bash
# Ver configuración activa
sudo cat /etc/nginx/sites-enabled/test.tause.pro

# Verificar qué directorio está usando
sudo cat /etc/nginx/sites-enabled/test.tause.pro | grep root
```

**Debe mostrar:**
```nginx
root /var/www/magicai/public;
```

---

### Paso 4: Soluciones Según lo Encontrado

#### Si el directorio NO existe:

```bash
# Crear directorio
sudo mkdir -p /var/www/magicai/public

# Crear index.php básico temporal
sudo tee /var/www/magicai/public/index.php > /dev/null << 'EOF'
<?php
phpinfo();
EOF

# Dar permisos
sudo chown -R www-data:www-data /var/www/magicai
sudo chmod -R 755 /var/www/magicai

# Recargar Nginx
sudo systemctl reload nginx
```

#### Si el directorio existe pero está vacío:

Necesitas desplegar la aplicación. Desde tu Mac:

```bash
# Copiar archivos (ajusta la ruta)
scp -r -i staging-tausepro-key.pem \
    /Users/tause/Documents/proyectos/tausepro9.4/public/* \
    ubuntu@3.220.198.180:/var/www/magicai/public/
```

#### Si la configuración Nginx está mal:

```bash
# Editar configuración
sudo nano /etc/nginx/sites-enabled/test.tause.pro

# Cambiar la línea root a:
root /var/www/magicai/public;

# Verificar sintaxis
sudo nginx -t

# Recargar
sudo systemctl reload nginx
```

#### Si los permisos están mal:

```bash
sudo chown -R www-data:www-data /var/www/magicai
sudo chmod -R 755 /var/www/magicai
sudo chmod -R 775 /var/www/magicai/storage 2>/dev/null || true
sudo chmod -R 775 /var/www/magicai/bootstrap/cache 2>/dev/null || true
```

---

### Paso 5: Verificar Después de Corregir

```bash
# Probar HTTP localmente
curl -I http://localhost

# Ver logs de Nginx
sudo tail -20 /var/log/nginx/error.log
```

---

## 🔍 Diagnóstico Rápido

Ejecuta esto en EC2 Instance Connect para ver qué falta:

```bash
echo "=== Verificando Aplicación ==="
echo ""
echo "1. Directorio existe?"
ls -d /var/www/magicai 2>/dev/null && echo "✅ Sí" || echo "❌ No"
echo ""
echo "2. Directorio public existe?"
ls -d /var/www/magicai/public 2>/dev/null && echo "✅ Sí" || echo "❌ No"
echo ""
echo "3. index.php existe?"
test -f /var/www/magicai/public/index.php && echo "✅ Sí" || echo "❌ No"
echo ""
echo "4. Configuración Nginx root:"
sudo grep "root" /etc/nginx/sites-enabled/test.tause.pro | head -1
echo ""
echo "5. Permisos:"
ls -la /var/www/magicai/public/index.php 2>/dev/null | awk '{print $1, $3, $4}'
```

---

## 📋 Checklist

- [ ] Conectar vía EC2 Instance Connect
- [ ] Verificar directorio `/var/www/magicai/public/` existe
- [ ] Verificar `index.php` existe
- [ ] Verificar configuración Nginx apunta al directorio correcto
- [ ] Corregir según diagnóstico
- [ ] Recargar Nginx
- [ ] Probar HTTP desde navegador

---

**Usa EC2 Instance Connect para acceder y ejecuta el diagnóstico rápido arriba.**


