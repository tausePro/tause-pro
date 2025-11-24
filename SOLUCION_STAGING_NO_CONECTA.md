# 🚨 Solución: Staging No Conecta

## 📊 Diagnóstico

- ✅ **Instancia**: Running (`i-06113402909fd6b57`)
- ✅ **IP**: `13.218.39.31` (correcta)
- ❌ **Ping**: No responde (timeout)
- ❌ **SSH**: No conecta

**Conclusión**: El Security Group está bloqueando el tráfico o hay un problema de red.

---

## 🔧 Solución: Verificar Security Group

### Opción 1: Desde AWS Console (Más Fácil)

1. Ve a: https://console.aws.amazon.com/ec2/
2. Click en "Instances"
3. Busca instancia `i-06113402909fd6b57`
4. Click en la instancia → Tab "Security"
5. Click en el Security Group (`sg-0933986b1aa1f35eb`)
6. Verifica reglas **Inbound**:
   - ¿Hay regla SSH (puerto 22) desde `0.0.0.0/0`?
   - ¿Hay regla HTTP (puerto 80) desde `0.0.0.0/0`?
   - ¿Hay regla HTTPS (puerto 443) desde `0.0.0.0/0`?

**Si faltan reglas:**
- Click "Edit inbound rules"
- Click "Add rule"
- Tipo: SSH, Puerto: 22, Origen: `0.0.0.0/0`
- Guardar

### Opción 2: Desde AWS CLI

```bash
# Ver reglas actuales
aws ec2 describe-security-groups \
  --group-ids sg-0933986b1aa1f35eb \
  --query 'SecurityGroups[0].IpPermissions' \
  --output table

# Agregar regla SSH si falta
aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --ip-permissions IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges="[{CidrIp=0.0.0.0/0,Description='SSH from anywhere'}]"

# Agregar regla HTTP si falta
aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --ip-permissions IpProtocol=tcp,FromPort=80,ToPort=80,IpRanges="[{CidrIp=0.0.0.0/0,Description='HTTP'}]"

# Agregar regla HTTPS si falta
aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --ip-permissions IpProtocol=tcp,FromPort=443,ToPort=443,IpRanges="[{CidrIp=0.0.0.0/0,Description='HTTPS'}]"
```

---

## 🔍 Verificar Network ACLs

Si el Security Group está bien pero aún no conecta, puede ser un Network ACL bloqueando.

**Desde AWS Console:**
1. Ve a VPC → Network ACLs
2. Busca el Network ACL asociado a la subnet de staging
3. Verifica reglas Inbound permiten tráfico

---

## 🚀 Después de Arreglar Security Group

Espera 30 segundos y prueba:

```bash
# Probar ping
ping -c 3 13.218.39.31

# Probar SSH
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31 "echo 'OK'"
```

---

## 💡 Si Aún No Funciona

### Reiniciar Instancia

```bash
# Reiniciar
aws ec2 reboot-instances --instance-ids i-06113402909fd6b57

# Esperar 2 minutos
sleep 120

# Probar de nuevo
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31 "echo 'OK'"
```

### Verificar Logs del Sistema

Usa AWS Systems Manager Session Manager (si está habilitado):

```bash
aws ssm start-session --target i-06113402909fd6b57
```

---

## ✅ Checklist

- [ ] Security Group tiene regla SSH (puerto 22)
- [ ] Security Group tiene regla HTTP (puerto 80)
- [ ] Security Group tiene regla HTTPS (puerto 443)
- [ ] Network ACLs permiten tráfico
- [ ] Instancia está en estado "running"
- [ ] Probado conexión después de cambios

---

**Verifica el Security Group en AWS Console primero. Es lo más probable que esté bloqueando el tráfico.**



