# 🔧 SOLUCIÓN RÁPIDA PARA ERROR 504

## ✅ CAMBIOS YA APLICADOS:

- **APP_ENV** cambiado a `local` (mejor logging y sin cache)
- **php-local-dev.ini** creado con timeouts aumentados
- **Caches limpiados**

---

## 🚀 OPCIONES PARA SOLUCIONAR EL 504:

### **OPCIÓN 1: Servidor PHP con timeouts aumentados (RECOMENDADA)** ⭐

1. **Detén el servidor actual** si está corriendo en puerto 8001:
   ```bash
   # Busca el proceso
   ps aux | grep "artisan serve" | grep 8001
   
   # Mátalo (reemplaza XXXX con el PID)
   kill XXXX
   ```

2. **Inicia con configuración optimizada:**
   ```bash
   php -c php-local-dev.ini artisan serve --host=0.0.0.0 --port=8001
   ```

3. **Accede a:**
   ```
   http://localhost:8001
   ```

---

### **OPCIÓN 2: Solo recargar (si ya cambió APP_ENV)**

Ya se cambió `APP_ENV` a `local`, simplemente:

1. Recarga la página: `https://tausepro.test`
2. Si persiste el 504 → usa Opción 1

---

### **OPCIÓN 3: Reiniciar Valet (requiere contraseña)**

```bash
valet restart
```

Luego accede a: `https://tausepro.test`

---

## 🔍 DIAGNÓSTICO RÁPIDO:

Si el 504 persiste, verifica qué está tardando:

```bash
# Ver últimos errores
tail -50 storage/logs/laravel.log

# Ver procesos PHP activos
ps aux | grep php

# Verificar qué puerto usar
lsof -ti:8001
```

---

## 🎯 PARA PROBAR EL SALES AGENT:

Una vez solucionado el 504:

1. Abre el chatbot (ID: 5)
2. Abre consola (F12)
3. Escribe: `"¿Tienen iPhone 15?"`
4. Deberías ver el grid de productos automáticamente

---

## 🚨 SI NADA FUNCIONA:

```bash
# Mata todos los procesos PHP
killall php
killall php-fpm

# Reinicia desde cero
php -c php-local-dev.ini artisan serve --host=0.0.0.0 --port=8001
```

---

## 📊 CONFIGURACIÓN ACTUAL:

```
APP_ENV: local
APP_URL: https://tausepro.test
PHP max_execution_time: 300s (en php-local-dev.ini)
PHP memory_limit: 1024M
```

---

**Fecha**: 18 de Octubre, 2025
**Estado**: Configuración aplicada, listo para probar
