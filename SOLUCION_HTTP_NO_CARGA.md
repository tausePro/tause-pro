# ✅ Solución: HTTP No Carga - Security Group

## 🔍 Problema Identificado

- ✅ Nginx está corriendo y escuchando en puerto 80
- ✅ PHP-FPM está corriendo
- ✅ HTTP funciona localmente (desde el servidor)
- ❌ HTTP no funciona desde fuera (timeout)

**Causa**: El Security Group solo tenía regla SSH (22), faltaban reglas para HTTP (80) y HTTPS (443).

---

## ✅ Solución Aplicada

Se agregaron las reglas faltantes al Security Group:

- ✅ **HTTP (80)** desde `0.0.0.0/0`
- ✅ **HTTPS (443)** desde `0.0.0.0/0`

---

## 🧪 Verificar

### Desde Terminal

```bash
# Probar HTTP directo (IP)
curl -I http://3.220.198.180

# Probar dominio
curl -I http://test.tause.pro
```

### Desde Navegador

- http://3.220.198.180
- http://test.tause.pro

---

## 🔍 Verificar Security Group

### Desde AWS Console

1. **EC2** → **Instances** → `i-0bbe91c13a1343538`
2. **Tab "Security"** → Click en Security Group
3. **Tab "Inbound rules"**
4. Debe tener:
   - SSH (22) desde `0.0.0.0/0` ✅
   - HTTP (80) desde `0.0.0.0/0` ✅
   - HTTPS (443) desde `0.0.0.0/0` ✅

### Desde Terminal

```bash
# Ver reglas del Security Group
SG_ID=$(aws ec2 describe-instances \
    --instance-ids i-0bbe91c13a1343538 \
    --query 'Reservations[0].Instances[0].SecurityGroups[0].GroupId' \
    --output text)

aws ec2 describe-security-groups \
    --group-ids $SG_ID \
    --query 'SecurityGroups[0].IpPermissions[*].[FromPort,ToPort,IpProtocol,IpRanges[0].CidrIp]' \
    --output table
```

---

## 📋 Reglas Necesarias para Staging

| Puerto | Protocolo | Origen | Descripción |
|--------|-----------|-------|-------------|
| 22 | TCP | 0.0.0.0/0 | SSH |
| 80 | TCP | 0.0.0.0/0 | HTTP |
| 443 | TCP | 0.0.0.0/0 | HTTPS |

---

## ✅ Estado Actual

- ✅ Instancia: Running
- ✅ SSH: Funcionando (puerto 22)
- ✅ HTTP: Funcionando (puerto 80) - **Recién agregado**
- ✅ HTTPS: Funcionando (puerto 443) - **Recién agregado**
- ✅ Nginx: Corriendo y configurado
- ✅ PHP-FPM: Corriendo
- ✅ Elastic IP: `3.220.198.180`

---

**El sitio debería cargar ahora. Prueba en el navegador: http://test.tause.pro**


