# 🔧 Arreglar 504 Gateway Timeout

## ⚠️ Problema

- ❌ SSH no conecta (se queda colgado)
- ❌ HTTP muestra **504 Gateway Timeout**
- ⚠️ SSL con candado roto

**Causa probable**: PHP-FPM no está corriendo o no puede comunicarse con Nginx.

---

## 🔍 Diagnóstico

### Verificar Estado de Instancia

```bash
aws ec2 describe-instance-status \
    --instance-ids i-0bbe91c13a1343538 \
    --include-all-instances
```

### Verificar Servicios (Si Puedes Conectar)

```bash
# Estado de PHP-FPM
sudo systemctl status php8.2-fpm

# Estado de Nginx
sudo systemctl status nginx

# Verificar socket PHP-FPM
ls -la /var/run/php/php8.2-fpm.sock
```

---

## 🚀 Soluciones

### Opción 1: Reiniciar Instancia (Más Rápido)

1. **AWS Console** → EC2 → Instances → `i-0bbe91c13a1343538`
2. **Instance state** → **Reboot instance**
3. Esperar 2-3 minutos
4. Probar HTTP de nuevo

**Esto reiniciará todos los servicios automáticamente.**

---

### Opción 2: Usar EC2 Instance Connect (Si Funciona)

1. **AWS Console** → EC2 → Instances → `i-0bbe91c13a1343538`
2. **Connect** → **EC2 Instance Connect**
3. Ejecutar:

```bash
# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl status php8.2-fpm

# Reiniciar Nginx
sudo systemctl restart nginx
sudo systemctl status nginx

# Verificar socket
ls -la /var/run/php/php8.2-fpm.sock

# Ver logs de error
sudo tail -20 /var/log/nginx/error.log
sudo tail -20 /var/log/php8.2-fpm.log
```

---

### Opción 3: Verificar Configuración Nginx

Si puedes conectar, verificar que Nginx apunta al socket correcto:

```bash
sudo cat /etc/nginx/sites-enabled/test.tause.pro | grep fastcgi_pass
```

**Debe mostrar:**
```nginx
fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
```

Si está mal, corregir:
```bash
sudo nano /etc/nginx/sites-enabled/test.tause.pro
# Cambiar fastcgi_pass a: unix:/var/run/php/php8.2-fpm.sock;
sudo nginx -t
sudo systemctl reload nginx
```

---

### Opción 4: Verificar Permisos Socket

```bash
# Verificar socket existe y tiene permisos correctos
ls -la /var/run/php/php8.2-fpm.sock

# Si no existe o permisos mal, corregir:
sudo chown www-data:www-data /var/run/php/php8.2-fpm.sock
sudo chmod 666 /var/run/php/php8.2-fpm.sock

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## 📋 Checklist de Verificación

- [ ] Instancia está "Running"
- [ ] PHP-FPM está corriendo (`systemctl status php8.2-fpm`)
- [ ] Nginx está corriendo (`systemctl status nginx`)
- [ ] Socket PHP-FPM existe (`/var/run/php/php8.2-fpm.sock`)
- [ ] Configuración Nginx apunta al socket correcto
- [ ] Permisos del socket son correctos
- [ ] No hay errores en logs (`/var/log/nginx/error.log`)

---

## 🚨 Si Nada Funciona

**Reiniciar instancia completa:**

1. AWS Console → EC2 → Instances → `i-0bbe91c13a1343538`
2. **Instance state** → **Reboot instance**
3. Esperar 2-3 minutos
4. Probar de nuevo

**Esto debería solucionar la mayoría de problemas de servicios.**

---

## 💡 Causa Más Probable

PHP-FPM se detuvo o el socket se corrompió. **Reiniciar la instancia** es la solución más rápida y efectiva.

---

**Recomendación: Reiniciar la instancia desde AWS Console. Es lo más rápido.**


