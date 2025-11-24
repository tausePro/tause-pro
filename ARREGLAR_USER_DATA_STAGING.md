# 🔧 Arreglar User Data en Staging

## ⚠️ Problema

Agregaste User Data pero la instancia ya estaba corriendo. **User Data solo se ejecuta al iniciar la instancia**.

---

## 🚀 Solución: Reiniciar Instancia

### Opción 1: Reiniciar desde AWS Console (Recomendado)

1. Ve a: https://console.aws.amazon.com/ec2/
2. Instances → Selecciona `i-0bbe91c13a1343538`
3. **Instance state** → **Reboot instance**
4. Confirma
5. ⏳ Espera 2-3 minutos
6. Prueba conectar de nuevo

### Opción 2: Reiniciar desde AWS CLI

```bash
aws ec2 reboot-instances --instance-ids i-0bbe91c13a1343538
```

Espera 2-3 minutos y prueba conectar.

---

## 🔍 Verificar que User Data se Ejecutó

Después de reiniciar, verifica los logs:

**Desde AWS Console:**
1. EC2 → Instances → `i-0bbe91c13a1343538`
2. Tab "Monitoring" → "Get system log"
3. Busca líneas que empiezan con "user-data" o el contenido de tu script

**O si puedes conectar:**
```bash
cat /var/log/user-data.log
```

---

## 💡 Si User Data No Se Ejecutó

Si después de reiniciar el User Data no se ejecutó, ejecuta los comandos manualmente:

```bash
# Conectar vía EC2 Instance Connect o SSH
cd /var/www/magicai

# Deshabilitar firewall
sudo ufw disable
sudo iptables -F
sudo iptables -P INPUT ACCEPT

# Iniciar servicios
sudo systemctl start ssh
sudo systemctl enable ssh
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Configurar firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## ✅ Checklist

- [ ] Reiniciar instancia desde AWS Console
- [ ] Esperar 2-3 minutos
- [ ] Verificar logs del sistema (User Data)
- [ ] Probar conectar vía SSH o EC2 Instance Connect
- [ ] Si no funciona, ejecutar comandos manualmente

---

**Reinicia la instancia desde AWS Console y espera 2-3 minutos. El User Data se ejecutará automáticamente.**



