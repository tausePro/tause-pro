# 🎯 Comenzar con Staging - Guía Paso a Paso

Ya tienes una instancia EC2 de staging creada. Sigue estos pasos para configurarla.

## 📋 Tu Instancia Staging

- **IP**: `13.218.39.31`
- **Instance ID**: `i-06113402909fd6b57`
- **Estado**: Running ✅

---

## 🚀 Inicio Rápido (5 minutos)

### 1. Guardar la Clave Privada

```bash
cd /Users/tause/Documents/proyectos/tausepro9.4

# Crear archivo
cat > staging-tausepro-key.pem << 'EOF'
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAvwR24oDzA5CSFatYr4itoxQdQmkIuSZn4lyJg/aNTjr0mwE1
mRVNHaxo+AIA4mG9SznzqO3Gp15Ot3z+2xdYyGDNttcVu5WjNRwO/C6sj3U/FLtP
TqvqjVwRsHGFv6tHmy0rheKmF/EfYRRz1jM/sc2Viefq1+nWYKYullY8ASz6/t2m
cEyExiWEaum/axSe7hzxcxcEEbNk84xHSKN2aDrgYUbiTdCygvmu9rBJBgvCXZry
vp1YL4qr52XLpGgO89Sl5bGIYeT26YiD94+InYjv24AOlvflCnIDyubNtuJdaecE
GHUgnKOl1C0AiAcOdYG2TYcq/Nck2LtgOdVi0QIDAQABAoIBAF0kuyflwxoo4+Mn
I16s4iaUR1Q7zWIjRSLPBstPbUYJX386Dr2v8mOWz7SnnXDGQjytbJAiKe0xAmdc
zxVchBFpisYuiU1oQSZDoVb9F234uSLN13VARWZaz9Fe+d4lkgwr8X4er+kazbdT
9swrP1LfMZ0GdrCEOOH2Bt+N+0KCqWbWj/8isdguc1sADZfeMq966SfQArk2oBUe
yR20V2XktyVXWQiBsoldJWQf2lKVImqD3osvhU6XhbBbbJeDmSxi/QBAEgNHIrpw
DGZNe8uS03bYqXLCup2eSyV1JhkX3ch2jz4Y+z/hl/T1HeQCqNP2uCz7zBMTO+4c
FPYf6kUCgYEA64EOpGAq4Ck5ajTiAY1u8SpGbDu9ErPvc0XOoVMtMrdAG9MjAuTb
PSH+3IIm8Dr2kxW3XN0qSp1jU6q3e1abLpSFklFvQuoKpNSsqxFy8lbeSK/aqGSZ
NsW2135F4lkRUGFfNltipyE4obQdibY2/BylKuaV8/0GV7u1I+58J4MCgYEAz6RC
tXdsuFYjBRdQTnHZFmFRGqCQOj0H10b4xhojvJiNx/F+YiF9KySARHI8R5tRpFZA
AVQffSC/JXtXF8fLce2SHY8C4Ux9RoqYQwGzoRL32jnIuWza4na+Oh6xR+vAad4Q
C4tbBnbzBCMIJL2CVBgvq87brGXm/78lZ6OQaBsCgYEAhm1vjzJ5puTBKjevflVe
K3kHI8bhwShGmVUSgpG47gceKAPYK1G5N2cNVI9SbLQrhX/S38Y62saGKP78pwGj
qO4MZJ3pVZfEpZvkR+244E3nqjP/KznpHxOyr8UbXP2cXXaHY768TEwxSFRIvA/v
yO8M9LUUiWY31aKG3lDGO8MCgYABB5qBFd+HJn4z90KhsPTXpJHnZOZyM31HdwWi
zdxhggwp8quixvG89ghgzoQ4ArAr9XWJzCX/09q+z9bFumrq3Le9x8jG3z87y1In
+ukuIk7yWRkCumR3fQlCdoaAic3BeKVxuTYxWQOpgJb4v6vWyOvrmTIDr9muaP9B
fVEpAwKBgERU3SS1giAuPkpMJbTh065EHbg7R0fH8fBlvUCzNt0p9ytJeSFkakBo
AqEWfi1rvvrNsqXRSy7sK8ea8Bdj1aS/mDmjfpgCA33K99NiTYHYuhRniOUODxX1
6cbV/T5CX5Srq4rt5mwQxL8b+b9H22vKqqRX84FmH/rHYa2VhJEy
-----END RSA PRIVATE KEY-----
EOF

# Configurar permisos seguros
chmod 400 staging-tausepro-key.pem
```

### 2. Cargar Configuración

```bash
source scripts/staging-config.sh
```

### 3. Verificar Conexión

```bash
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31 "echo '✅ Conexión exitosa'"
```

### 4. Ejecutar Configuración Completa

```bash
./scripts/setup-staging-complete.sh
```

El script te guiará paso a paso. Selecciona opción **7** para configuración completa automática.

---

## ⏱️ Tiempo Estimado Total

- Configuración del servidor: 5-10 min
- Configuración de BD: 2 min
- Clonar código: 5-10 min
- Clonar BD: 5-15 min
- Configurar Nginx: 2 min
- Configurar Laravel: 5 min

**Total**: ~30-45 minutos

---

## 📝 Después de la Configuración

Una vez completado, necesitarás:

1. **Configurar `.env`** en staging con tus credenciales
2. **Instalar dependencias**: `composer install && npm install`
3. **Ejecutar migraciones**: `php artisan migrate`
4. **Configurar permisos**: Los scripts lo hacen automáticamente

---

## 🚀 Deployment Futuro

Para desplegar cambios a staging:

```bash
source scripts/staging-config.sh
./scripts/deploy-to-staging.sh
```

---

## 🆘 Problemas Comunes

### No puedo conectar por SSH

```bash
# Verificar permisos
chmod 400 staging-tausepro-key.pem

# Verificar que la instancia está corriendo en AWS Console
# Verificar Security Group permite puerto 22
```

### El script falla

Revisa los logs del script. La mayoría de errores son por:
- Contraseñas incorrectas
- Conexión a internet lenta
- Permisos incorrectos

---

## ✅ Checklist Final

- [ ] Clave privada guardada y con permisos 400
- [ ] Variables de configuración cargadas
- [ ] Conexión SSH funciona
- [ ] Servidor configurado (PHP, MySQL, Nginx)
- [ ] Base de datos creada
- [ ] Código clonado
- [ ] Base de datos clonada
- [ ] Nginx configurado
- [ ] Laravel configurado (.env, dependencias, migraciones)
- [ ] Sitio accesible

---

**¿Listo?** Empieza con el Paso 1 arriba.



