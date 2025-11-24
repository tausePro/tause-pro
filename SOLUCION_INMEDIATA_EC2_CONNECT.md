# 🚀 Solución Inmediata: EC2 Instance Connect

## ⚠️ Problema

El User Data **NO se ejecuta** si la instancia ya existía. Solo se ejecuta en el **primer boot** de una instancia nueva.

Por eso modificar el User Data después no funciona.

---

## ✅ Solución: EC2 Instance Connect (5 minutos)

### Paso 1: Conectar desde AWS Console

1. **AWS Console**: https://console.aws.amazon.com/ec2/
2. **Instances** → Busca `i-0bbe91c13a1343538` (o la IP `44.211.83.213`)
3. **Selecciona la instancia**
4. **Click "Connect"** (botón arriba)
5. **Tab "EC2 Instance Connect"**
6. **Click "Connect"**

Esto te dará una terminal en el navegador sin necesidad de SSH.

---

### Paso 2: Ejecutar Comandos (Copia y Pega Todo)

Una vez conectado, copia y pega estos comandos **uno por uno**:

```bash
# 1. Deshabilitar firewall COMPLETAMENTE
sudo ufw --force disable
sudo iptables -F
sudo iptables -X
sudo iptables -P INPUT ACCEPT
sudo iptables -P FORWARD ACCEPT
sudo iptables -P OUTPUT ACCEPT

# 2. Verificar e iniciar servicios
sudo systemctl start ssh
sudo systemctl enable ssh
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# 3. Verificar que servicios están corriendo
sudo systemctl status ssh --no-pager | head -5
sudo systemctl status nginx --no-pager | head -5
sudo systemctl status php8.2-fpm --no-pager | head -5

# 4. Verificar permisos
sudo chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache
sudo chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache

# 5. Verificar que SSH está escuchando
sudo ss -tlnp | grep :22

# 6. Verificar que HTTP funciona localmente
curl -I http://localhost

# 7. AL FINAL: Configurar firewall correctamente (opcional, pero recomendado)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status
```

---

### Paso 3: Probar SSH desde tu Mac

Después de ejecutar los comandos, cierra EC2 Instance Connect y prueba desde tu Mac:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

**Debería funcionar ahora** ✅

---

## 🔍 Si EC2 Instance Connect No Funciona

### Alternativa: Systems Manager Session Manager

```bash
aws ssm start-session --target i-0bbe91c13a1343538
```

Esto requiere que tengas AWS CLI configurado.

---

## 📋 Checklist

- [ ] Conectar vía EC2 Instance Connect desde AWS Console
- [ ] Ejecutar comandos para deshabilitar firewall
- [ ] Verificar servicios están corriendo
- [ ] Probar SSH desde Mac
- [ ] Si funciona, configurar firewall correctamente

---

## 💡 Por Qué No Funcionó el User Data

- **User Data solo se ejecuta en el primer boot**
- Si la instancia ya existía, modificar User Data NO lo ejecuta
- Necesitas crear una **nueva instancia** para que el User Data se ejecute

**Por ahora, usa EC2 Instance Connect para arreglar manualmente.**


