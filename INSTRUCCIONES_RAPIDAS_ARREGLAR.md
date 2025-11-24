# 🚀 Instrucciones Rápidas: Arreglar Instancia Actual

## ⚡ Método Más Rápido: EC2 Instance Connect

Como SSH no funciona, usa **EC2 Instance Connect** desde AWS Console.

### Pasos:

1. **Abre AWS Console**: https://console.aws.amazon.com/ec2/
2. **Ve a Instances** → Busca `i-0bbe91c13a1343538`
3. **Selecciona la instancia**
4. **Click "Connect"** (botón arriba)
5. **Tab "EC2 Instance Connect"**
6. **Click "Connect"**

### Comandos para Ejecutar (Copia y Pega):

```bash
# Deshabilitar firewall
sudo ufw --force disable
sudo iptables -F
sudo iptables -X
sudo iptables -P INPUT ACCEPT
sudo iptables -P FORWARD ACCEPT
sudo iptables -P OUTPUT ACCEPT

# Iniciar servicios
sudo systemctl start ssh
sudo systemctl enable ssh
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Permisos
sudo chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache
sudo chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache

# Verificar HTTP
curl http://localhost

# Verificar Nginx
sudo nginx -t
sudo systemctl reload nginx

# Configurar firewall (AL FINAL)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# Verificar
sudo ufw status
```

### Después de Ejecutar:

**Desde tu Mac**, prueba:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
curl http://test.tause.pro
```

---

## 🔄 Alternativa: Modificar User Data y Reiniciar

Si EC2 Instance Connect no funciona:

1. **AWS Console** → Instances → `i-0bbe91c13a1343538`
2. **Actions** → **Instance settings** → **Edit user data**
3. **Reemplazar con** el contenido de `scripts/user-data-sin-firewall.sh`
4. **Save**
5. **Reboot instance**
6. Esperar 2 minutos
7. Probar SSH de nuevo

---

**Empieza con EC2 Instance Connect. Es lo más rápido.**



