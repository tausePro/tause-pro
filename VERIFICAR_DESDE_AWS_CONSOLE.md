# 🔍 Verificar Staging desde AWS Console

## ⚠️ Problema Actual

- Instancia reiniciada pero sigue sin conectar
- SSH no funciona
- HTTP no responde
- Puede ser firewall interno o servicios no iniciados

---

## 🔍 Verificación desde AWS Console

### Paso 1: Ver Logs del Sistema

1. Ve a: https://console.aws.amazon.com/ec2/
2. Click en "Instances"
3. Selecciona `i-06113402909fd6b57`
4. Tab "Monitoring" → Click "Get system log"
5. Busca:
   - Errores de red
   - Mensajes sobre firewall (ufw/iptables)
   - Errores de SSH
   - Errores de Nginx

**Comparte los últimos 50-100 líneas del log del sistema.**

### Paso 2: Verificar Métricas

1. Tab "Monitoring"
2. Verifica:
   - **Network In**: ¿Hay tráfico entrante?
   - **Network Out**: ¿Hay tráfico saliente?
   - **Status Checks**: ¿Están pasando?

### Paso 3: Intentar Session Manager

1. Tab "Connect"
2. Click "Session Manager"
3. Si está disponible, click "Connect"
4. Esto te dará acceso a la terminal sin SSH

---

## 🔧 Si Puedes Conectar vía Session Manager

Una vez conectado, ejecuta estos comandos:

```bash
# 1. Verificar firewall
sudo ufw status verbose

# Si ufw está activo y bloqueando:
sudo ufw disable
# O permitir puertos específicos:
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 2. Verificar iptables
sudo iptables -L -n -v

# 3. Verificar servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status ssh

# 4. Verificar que están escuchando
sudo netstat -tlnp | grep :22
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443

# 5. Ver logs
sudo tail -20 /var/log/nginx/error.log
sudo journalctl -u nginx -n 20
sudo journalctl -u ssh -n 20
```

---

## 💡 Sobre SSL/HTTPS

El SSL/HTTPS **NO debería afectar**:
- ✅ SSH (puerto 22)
- ✅ HTTP (puerto 80)

El SSL solo afecta HTTPS (puerto 443). Si HTTP no funciona, el problema NO es SSL.

**Posibles causas reales:**
1. **Firewall interno** (ufw/iptables) bloqueando todo
2. **Servicios no iniciados** (Nginx, SSH)
3. **Configuración de red** incorrecta
4. **Problema de routing** en la VPC

---

## 🚀 Solución Más Probable

**El firewall interno (ufw) probablemente está bloqueando todo.**

Si puedes conectar vía Session Manager:

```bash
# Deshabilitar firewall temporalmente para probar
sudo ufw disable

# Luego probar SSH desde tu máquina
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
```

Si funciona después de deshabilitar ufw, entonces:

```bash
# Habilitar ufw pero permitir puertos necesarios
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

---

## 📋 Checklist

- [ ] Ver logs del sistema desde AWS Console
- [ ] Ver métricas de red
- [ ] Intentar Session Manager
- [ ] Si conectas, verificar firewall (ufw/iptables)
- [ ] Verificar servicios (Nginx, SSH)
- [ ] Verificar que puertos están escuchando

---

**¿Puedes ver los logs del sistema desde AWS Console y compartir qué ves? O intenta Session Manager desde la consola.**



