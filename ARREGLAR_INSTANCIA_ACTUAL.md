# 🔧 Arreglar Instancia Staging Actual

## 📋 Situación Actual

- **Instance ID**: `i-0bbe91c13a1343538`
- **IP Pública**: `44.211.83.213`
- **Problema**: SSH no conecta, HTTP no responde
- **Causa probable**: Firewall bloqueando conexiones

---

## 🚀 Solución: Usar EC2 Instance Connect

Como SSH no funciona, usaremos **EC2 Instance Connect** desde AWS Console.

### Paso 1: Conectar vía EC2 Instance Connect

1. **AWS Console**: https://console.aws.amazon.com/ec2/
2. **Instances** → Selecciona `i-0bbe91c13a1343538`
3. **Connect** → Tab **"EC2 Instance Connect"**
4. **Connect**

Esto te dará acceso directo sin SSH.

### Paso 2: Ejecutar Comandos de Arreglo

Una vez conectado, ejecuta estos comandos **uno por uno**:

```bash
# 1. Deshabilitar firewall completamente
sudo ufw --force disable
sudo iptables -F
sudo iptables -X
sudo iptables -P INPUT ACCEPT
sudo iptables -P FORWARD ACCEPT
sudo iptables -P OUTPUT ACCEPT

# 2. Verificar servicios
sudo systemctl status ssh
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# 3. Iniciar servicios si no están corriendo
sudo systemctl start ssh
sudo systemctl enable ssh
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# 4. Verificar permisos
sudo chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache
sudo chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache

# 5. Verificar que HTTP funciona localmente
curl http://localhost

# 6. Verificar Nginx está configurado
sudo nginx -t
sudo systemctl reload nginx

# 7. Verificar puertos están escuchando
sudo ss -tlnp | grep :22
sudo ss -tlnp | grep :80

# 8. AL FINAL: Configurar firewall correctamente
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
```

### Paso 3: Probar Conexión SSH

Después de deshabilitar firewall, prueba desde tu Mac:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

Debería funcionar ahora.

---

## 🔄 Alternativa: Systems Manager Session Manager

Si EC2 Instance Connect no funciona, usa **Systems Manager**:

```bash
aws ssm start-session --target i-0bbe91c13a1343538
```

Esto requiere que la instancia tenga el agente SSM instalado (probablemente ya lo tiene si es Ubuntu).

---

## 📋 Checklist de Arreglo

- [ ] Conectar vía EC2 Instance Connect o Systems Manager
- [ ] Deshabilitar firewall completamente
- [ ] Verificar servicios (SSH, Nginx, PHP-FPM)
- [ ] Iniciar servicios si no están corriendo
- [ ] Verificar permisos de storage
- [ ] Verificar HTTP funciona localmente
- [ ] Verificar Nginx está configurado correctamente
- [ ] Probar conexión SSH desde Mac
- [ ] Configurar firewall correctamente al final
- [ ] Probar HTTP desde navegador: http://test.tause.pro

---

## ⚠️ Si Nada Funciona

Si ni EC2 Instance Connect ni Systems Manager funcionan:

1. **Detener instancia** desde AWS Console
2. **Modificar User Data** para que NO habilite firewall
3. **Iniciar instancia** de nuevo
4. Conectar inmediatamente vía SSH

---

**Empieza con EC2 Instance Connect desde AWS Console. Es la forma más fácil de arreglar la instancia actual.**



