# ⚡ Pasos Rápidos: Arreglar SSH Bloqueado

## 🚨 Problema

SSH completamente bloqueado - ni desde Mac ni desde AWS Console funciona.

---

## ✅ Solución en 3 Pasos

### 1️⃣ Detener Instancia

**AWS Console** → EC2 → Instances → `i-0bbe91c13a1343538`
- **Instance state** → **Stop instance**
- ⏳ Esperar hasta "Stopped" (2-3 min)

### 2️⃣ Modificar User Data

**Mientras está detenida**:
- **Actions** → **Instance settings** → **Edit user data**
- **Borra todo** el contenido actual
- **Copia y pega** el contenido completo de `scripts/user-data-fix-ssh.sh`
- **Save**

### 3️⃣ Iniciar Instancia

- **Instance state** → **Start instance**
- ⏳ Esperar hasta "Running" (1-2 min)
- **Probar SSH**:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

**Debería funcionar ahora** ✅

---

## 📋 Después de Conectar

Una vez que SSH funcione, ejecuta:

```bash
# Configurar firewall (AL FINAL)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

**Total tiempo: 5-10 minutos**



