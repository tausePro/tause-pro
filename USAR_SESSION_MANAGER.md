# 🔧 Usar AWS Session Manager para Conectar

## ⚠️ Problema

SSH no funciona, pero puedes usar **AWS Systems Manager Session Manager** para conectar sin SSH.

---

## 🚀 Solución: Session Manager

### Paso 1: Verificar que Session Manager Está Habilitado

```bash
aws ssm describe-instance-information \
  --filters "Key=InstanceIds,Values=i-06113402909fd6b57" \
  --query 'InstanceInformationList[0].[PingStatus,LastPingDateTime]' \
  --output table
```

Si `PingStatus` es "Online", puedes usar Session Manager.

### Paso 2: Conectar vía Session Manager

```bash
aws ssm start-session --target i-06113402909fd6b57
```

Esto te conecta directamente a la terminal de la instancia **sin necesidad de SSH**.

### Paso 3: Una vez Conectado

```bash
# Verificar servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# Verificar firewall
sudo ufw status
sudo iptables -L -n

# Si ufw está bloqueando:
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Verificar que Nginx está escuchando
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :22

# Ver logs de Nginx
sudo tail -20 /var/log/nginx/error.log
```

---

## 🔍 Si Session Manager No Está Habilitado

### Habilitar Session Manager

**Desde AWS Console:**

1. Ve a EC2 → Instances
2. Selecciona `i-06113402909fd6b57`
3. Tab "Connect" → "Session Manager"
4. Si no está disponible, necesitas instalar el agente

**Instalar SSM Agent (si puedes conectar de otra forma):**

```bash
# En la instancia
sudo snap install amazon-ssm-agent --classic
sudo systemctl enable amazon-ssm-agent
sudo systemctl start amazon-ssm-agent
```

---

## 💡 Alternativa: Verificar desde AWS Console

### Ver Logs del Sistema

1. Ve a EC2 → Instances
2. Selecciona `i-06113402909fd6b57`
3. Tab "Monitoring" → "Get system log"
4. Busca errores relacionados con:
   - Network
   - Firewall
   - SSH
   - Nginx

### Ver Métricas

1. Tab "Monitoring"
2. Verifica:
   - Network In/Out
   - CPU Utilization
   - Status Checks

---

## 🔧 Verificar Firewall Interno (si puedes conectar)

Si logras conectar vía Session Manager:

```bash
# Verificar ufw
sudo ufw status verbose

# Si está activo y bloqueando SSH:
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

# Verificar iptables
sudo iptables -L -n -v

# Si hay reglas bloqueando, permitir:
sudo iptables -I INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables-save | sudo tee /etc/iptables/rules.v4
```

---

## 📋 Checklist

- [ ] Intentar Session Manager
- [ ] Ver logs del sistema desde AWS Console
- [ ] Verificar métricas de red
- [ ] Si puedes conectar, verificar firewall interno
- [ ] Verificar que servicios están corriendo

---

**Intenta usar Session Manager primero. Es la forma más fácil de conectar cuando SSH no funciona.**



