# 🚀 Configuración de GitHub Actions para Deploy a Staging

Este documento explica cómo configurar GitHub Actions para hacer deploy automático a staging.

## 📋 Secrets Requeridos en GitHub

Para que el workflow funcione, necesitas configurar estos secrets en GitHub:

### 1. Ir a Settings del Repositorio

1. Ve a: `https://github.com/tausePro/tause-pro/settings/secrets/actions`
2. O navega: **Settings** → **Secrets and variables** → **Actions**

### 2. Agregar los siguientes secrets:

#### `STAGING_SSH_KEY`
- **Descripción**: Clave SSH privada para conectarse al servidor staging
- **Cómo obtenerla**: 
  ```bash
  cat staging-tausepro-key.pem
  ```
- **Importante**: Copia TODO el contenido del archivo `.pem`, incluyendo:
  ```
  -----BEGIN RSA PRIVATE KEY-----
  ...
  -----END RSA PRIVATE KEY-----
  ```

#### (Opcional) `STAGING_HOST`
- Si quieres cambiar la IP del servidor sin modificar el workflow
- Por defecto usa: `3.220.198.180`

## 🔄 Cómo Funciona

### Trigger Automático
El workflow se ejecuta automáticamente cuando:
- Se hace `push` a la rama `feature/sales-agent-config-view`
- Se hace `push` a la rama `main`
- Se ignora cambios en archivos `.md` y `scripts/`

### Trigger Manual
También puedes ejecutarlo manualmente:
1. Ve a **Actions** → **Deploy to Staging**
2. Click en **Run workflow**
3. Selecciona la rama que quieres desplegar
4. Click en **Run workflow**

## 📝 Pasos del Deploy

1. **Pre-deployment Checks**
   - Valida sintaxis PHP
   - Verifica cambios críticos

2. **Deploy to Staging**
   - Conecta vía SSH al servidor
   - Hace `git pull` de la rama especificada
   - Ejecuta migraciones (`php artisan migrate --force`)
   - Limpia cachés (config y view)
   - Configura permisos

3. **Health Check**
   - Muestra información para verificar el deploy

## 🔍 Verificar el Deploy

Después del deploy, verifica:

```bash
# Conectarte al servidor
ssh -i staging-tausepro-key.pem ubuntu@3.220.198.180

# Ver versión actual
cd /var/www/magicai && cat version.txt

# Ver estado de migraciones
php artisan migrate:status | tail -5

# Ver logs recientes
tail -50 storage/logs/laravel.log
```

## 🐛 Troubleshooting

### Error: "Permission denied (publickey)"
- Verifica que el secret `STAGING_SSH_KEY` tiene el formato correcto
- Asegúrate de incluir las líneas `-----BEGIN` y `-----END`

### Error: "Host key verification failed"
- El workflow ya incluye `ssh-keyscan` para agregar el host automáticamente

### Error: "Branch not found"
- Verifica que la rama existe en GitHub
- El workflow intentará crear la rama si no existe localmente

### El deploy se cuelga en "Clearing caches"
- Esto es normal si `cache:clear` tarda mucho
- El workflow solo ejecuta `config:clear` y `view:clear` para evitar esto

## 🔐 Seguridad

- ✅ La clave SSH está almacenada como secret en GitHub (encriptada)
- ✅ Solo usuarios con acceso al repo pueden ver/ejecutar el workflow
- ✅ El servidor staging debe tener el Security Group configurado para permitir SSH desde GitHub Actions

## 📚 Referencias

- [GitHub Actions Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [SSH Action](https://github.com/appleboy/ssh-action)


