# Release 001: Fix Embedding Type Enum

## 📋 Información del Release

**Fecha:** 2025-11-11  
**Tipo:** Bugfix crítico  
**Ambiente:** Producción  
**Rollback:** Disponible  

---

## 🐛 Problema

El enum `EmbeddingTypeEnum` no tenía el case `product` definido, pero `WooCommerceService` lo estaba usando. Esto causaba:

- ❌ Error: `"product" is not a valid backing value for enum`
- ❌ Imposibilidad de sincronizar productos de WooCommerce
- ❌ Fallo al indexar sitios con WooCommerce conectado
- ❌ Error visible en producción al entrenar chatbots

---

## ✅ Solución

Agregar `case product = 'product';` al enum `EmbeddingTypeEnum`.

**Cambio realizado:**
```php
case website = 'website';
case file = 'file';
case text = 'text';
case qa = 'qa';
case product = 'product';  // ← NUEVO
```

---

## 📊 Impacto

**Sitios SIN WooCommerce:**
- ✅ NO afectados (usan tipo 'website')
- ✅ Siguen funcionando normalmente

**Sitios CON WooCommerce:**
- ✅ Dejan de dar error
- ✅ Pueden sincronizar productos
- ✅ Pueden indexar correctamente

---

## 📁 Archivos Modificados

1. `app/Extensions/Chatbot/System/Enums/EmbeddingTypeEnum.php`

---

## 🧪 Validación Pre-Deployment

- ✅ Sintaxis PHP verificada
- ✅ Enum carga correctamente en local
- ✅ WooCommerceService sin errores de sintaxis
- ✅ Todos los cases del enum retornan valores correctos

---

## 🚀 Deployment

```bash
# Desde local a producción (ya están sincronizados)
cd /Users/tause/Documents/proyectos/tausepro9.4
# El archivo ya está modificado y funcionando
```

---

## ⏪ Rollback

Si algo falla:

```bash
cd /Users/tause/Documents/proyectos/tausepro9.4/deploy/releases/release-001-fix-embedding-enum
cp backup/app/Extensions/Chatbot/System/Enums/EmbeddingTypeEnum.php \
   ../../../app/Extensions/Chatbot/System/Enums/EmbeddingTypeEnum.php
```

O restaurar desde backup ZIP:
```bash
unzip -o backup-pre-release-001-*.zip
```

---

## 📝 Notas

- Este fix es SEGURO porque solo AGREGA un case, no modifica casos existentes
- No requiere cambios en base de datos
- No requiere reiniciar servicios
- Es retrocompatible con datos existentes
