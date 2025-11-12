# Release 002 - Fix URL Injection System

## 🔥 HOTFIX 3 - 2025-11-12 (Matching basado en contexto)

**Problema final:** El matching solo funcionaba si el texto del link contenía las keywords del producto, pero la IA generaba links con textos genéricos como "Compra aquí".

### Ejemplo del problema:
```
IA genera: "Te recomiendo el Botiquín Esencial... [Compra aquí](url-incorrecta)"
Sistema buscaba keywords en: "Compra aquí"
Keywords: ["BOTIQUÍN", "ESENCIAL", "CASA"]
Resultado: ❌ No match
```

### Solución Final:
Cambié el algoritmo para buscar keywords en **toda la respuesta**, no solo en el texto del link:

```php
// Normalizar la respuesta completa
$responseNormalized = $normalizeText($response);

// Buscar keywords en la respuesta completa, no en el texto del link
foreach ($mapping['keywords'] as $keyword) {
    $found = strpos($responseNormalized, $keywordNormalized) !== false;
}
```

### Resultado:
- ✅ Funciona con cualquier texto de link ("Compra aquí", "Ver producto", etc.)
- ✅ Detecta el producto mencionado en la conversación
- ✅ Corrige automáticamente CUALQUIER link incorrecto a ese producto

---

## 🔥 HOTFIX 2 - 2025-11-12 (Normalización de acentos)

**Bug encontrado:** `strpos()` fallaba con acentos porque "BOTIQUíN" ≠ "BOTIQUÍN"

### Solución:
```php
$normalizeText = function($text) {
    $text = strtoupper($text);
    $unwanted_array = [
        'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U', 'Ñ'=>'N',
        'á'=>'A', 'é'=>'E', 'í'=>'I', 'ó'=>'O', 'ú'=>'U', 'ñ'=>'N'
    ];
    return strtr($text, $unwanted_array);
};
```

---

## 🔥 HOTFIX 1 - 2025-11-12 (Regex a strpos)

**Bug encontrado:** `preg_match(): Unknown modifier 'M'`

### Problema
La versión inicial usaba un patrón regex dinámico que podía generar patrones inválidos:
```php
$keyPattern = implode('.*', array_map('preg_quote', $keywords));
if (preg_match('/' . $mapping['pattern'] . '/i', $linkTextUpper)) {
```

### Solución
Reemplazado `preg_match()` por `strpos()` - método más simple y seguro:
```php
$allKeywordsMatch = true;
foreach ($mapping['keywords'] as $keyword) {
    if (strpos($linkTextUpper, $keyword) === false) {
        $allKeywordsMatch = false;
        break;
    }
}
```

---

## 📊 Resumen de Hotfixes

| # | Problema | Solución | Estado |
|---|----------|----------|--------|
| 1 | Error preg_match | strpos() | ✅ Resuelto |
| 2 | Acentos no matcheaban | Normalización | ✅ Resuelto |
| 3 | Links con texto genérico | Context-based matching | ✅ Resuelto |

**Tiempo total desde deploy hasta fix final:** ~2 horas
**Resultado:** Sistema robusto que corrige cualquier link incorrecto

---

**Fecha:** 2025-11-12
**Tipo:** MEJORA (Enhancement)
**Prioridad:** ALTA
**Riesgo:** BAJO

## 🎯 Objetivo

Mejorar el sistema de corrección automática de URLs en las respuestas del chatbot para que detecte y corrija links incorrectos de forma más inteligente y flexible.

## 🐛 Problema Identificado

El método `injectProductUrlsFromEmbeddings()` en `ChatbotApplicationController` tenía las siguientes limitaciones:

1. **Solo buscaba embeddings `website`**, ignorando los embeddings `product`
2. **Requería coincidencia exacta del título completo** en el texto del link
3. **No detectaba links parciales** como `[BOTIQUÍN ESENCIAL](url)` cuando el título era `BOTIQUÍN ESENCIAL PARA TU CASA - Aliviate`

### Ejemplo del problema:
```
Embedding título: "BOTIQUÍN ESENCIAL PARA TU CASA - Aliviate"
URL correcta: https://aliviate.com.co/producto/producto-botiquin-esencial-hogar-medellin/

IA genera: [BOTIQUÍN ESENCIAL](https://aliviate.com.co/)
Resultado: ❌ No se corregía la URL (título no coincidía exactamente)
```

## ✅ Solución Implementada

### Cambios en `ChatbotApplicationController.php`:

1. **Incluye embeddings `product` y `website`**
   ```php
   ->whereIn('type', ['website', 'product'])
   ```

2. **Limpieza inteligente de títulos**
   - Remueve prefijo "Producto:"
   - Remueve sufijos como "- Aliviate"
   
3. **Extracción de palabras clave**
   - Extrae palabras significativas (> 3 caracteres)
   - Excluye stop words (PARA, CON, SIN, LOS, LAS, UNA, UNO, DEL, DE)
   
4. **Matching flexible usando `strpos()`** (corregido en hotfix)
   - Busca coincidencias por palabras clave usando `strpos()` en lugar de regex
   - Ejemplo: Si el producto tiene keywords ["BOTIQUÍN", "ESENCIAL", "CASA"], matchea con "[BOTIQUÍN ESENCIAL](url)"
   - **Más robusto que regex** - no puede fallar por caracteres especiales

5. **Corrección automática**
   - Detecta URLs genéricas (solo dominio)
   - Detecta URLs incorrectas
   - Reemplaza con la URL correcta del embedding

### Ejemplo después de la mejora:
```
Embedding: "BOTIQUÍN ESENCIAL PARA TU CASA - Aliviate"
Keywords: ["BOTIQUÍN", "ESENCIAL", "CASA"]

IA genera: [BOTIQUÍN ESENCIAL](https://aliviate.com.co/)
Resultado: ✅ Detecta keywords → Corrige URL correctamente
           [BOTIQUÍN ESENCIAL](https://aliviate.com.co/producto/producto-botiquin-esencial-hogar-medellin/)
```

## 📦 Archivos Modificados

- `app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php`
  - Método: `injectProductUrlsFromEmbeddings()` (líneas ~760-822)

## ✅ Verificación Post-Despliegue

1. Abrir chatbot Ali en: https://aliviate.com.co
2. Preguntar: "Dame información sobre el botiquín esencial"
3. Verificar que el link generado sea correcto y apunte a la URL específica del producto
4. Revisar logs: `ssh ubuntu@34.207.248.220 "tail -f /var/www/magicai/storage/logs/laravel.log"`

## 📊 Impacto Esperado

- ✅ **Mejora UX**: Los usuarios recibirán links correctos a productos específicos
- ✅ **Reducción de errores**: Menos links genéricos o incorrectos
- ✅ **Mejor conversión**: Links directos a productos facilitan la compra
- ✅ **Sin breaking changes**: Método mejorado mantiene compatibilidad

## 🔒 Nivel de Riesgo: BAJO

- Cambio aislado a un solo método
- No afecta base de datos
- No modifica lógica de negocio crítica
- Mejora gradual (si falla, mantiene comportamiento original)
- Logs agregados para debugging
- **HOTFIX aplicado:** Reemplazado regex por strpos() para mayor robustez

## 📝 Notas Adicionales

- El método sigue siendo llamado desde `storeMessage()` después de generar la respuesta de la IA
- Compatible con todos los tipos de embeddings existentes
- No requiere re-indexar contenido existente
- **Lección aprendida:** `strpos()` es más simple y seguro que regex dinámicos para matching de strings
