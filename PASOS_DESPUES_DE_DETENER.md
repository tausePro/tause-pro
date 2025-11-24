# 📋 Pasos Después de Detener Instancia

## ✅ Paso 1: Confirmar Detención

En el modal que tienes abierto:
- **Click "Force stop"** (botón naranja)
- ⏳ **Esperar** hasta que el estado sea **"Stopped"** (2-3 minutos)
- Puedes refrescar la página para ver el estado

---

## 🔧 Paso 2: Modificar User Data

**Una vez que la instancia esté "Stopped"**:

1. **Selecciona la instancia** (aunque esté detenida)
2. **Actions** → **Instance settings** → **Edit user data**
3. **Borra TODO** el contenido actual del campo de texto
4. **Copia y pega** el contenido completo de `USER_DATA_PARA_PEGAR.txt`
   - O abre el archivo `scripts/user-data-fix-ssh.sh` y copia todo
5. **Save**

---

## ▶️ Paso 3: Iniciar Instancia

1. **Selecciona la instancia**
2. **Instance state** → **Start instance**
3. ⏳ **Esperar** hasta que estado sea **"Running"** (1-2 minutos)
4. La IP puede cambiar, verifica la nueva IP si es necesario

---

## ✅ Paso 4: Probar SSH

**Después de que la instancia esté "Running"**:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

**Debería funcionar ahora** ✅ porque el nuevo User Data NO habilita el firewall.

---

## 🔒 Paso 5: Configurar Firewall (Después de Conectar)

Una vez que SSH funcione, conecta y ejecuta:

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
```

---

## 📝 Resumen

1. ✅ Force stop → Esperar "Stopped"
2. 🔧 Edit user data → Pegar contenido de `USER_DATA_PARA_PEGAR.txt` → Save
3. ▶️ Start instance → Esperar "Running"
4. ✅ Probar SSH
5. 🔒 Configurar firewall después de conectar

---

**El contenido del User Data está en `USER_DATA_PARA_PEGAR.txt` - cópialo completo y pégalo en AWS Console.**



