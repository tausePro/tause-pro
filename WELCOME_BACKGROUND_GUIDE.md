# 🎨 Guía Completa del Welcome Background del Chatbot

## 📐 Medidas Recomendadas

### Dimensiones del Chatbot
- **Ancho:** 420px
- **Alto:** 745px
- **Relación de aspecto:** 9:16 (formato vertical/portrait)

### Especificaciones de la Imagen
- **Formato recomendado:** JPG, PNG, WebP
- **Tamaño máximo:** 2MB para mejor rendimiento
- **Resolución mínima:** 420x745px
- **Resolución recomendada:** 840x1490px (2x para pantallas retina)

### Consideraciones de Diseño
- **Área segura:** Deja 60px de margen en los bordes para el contenido
- **Gradiente automático:** El sistema aplica un gradiente oscuro en la parte inferior
- **Contraste:** Usa imágenes que permitan leer texto blanco encima
- **Orientación:** Vertical (portrait) funciona mejor que horizontal

## 🛠️ Cómo Usar

### Método: URL de Imagen (Recomendado)
1. Ve al dashboard de chatbots
2. Edita tu chatbot
3. Ve al paso "Customize"
4. En la sección "Welcome Screen"
5. Pega la URL de tu imagen en "Welcome Background Image URL"
6. Ejemplo: `https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=420&h=745&q=80`
7. Guarda los cambios

### Ventajas del método URL:
- ✅ No ocupa espacio en el servidor
- ✅ Carga más rápida
- ✅ Fácil de cambiar
- ✅ Puedes usar servicios gratuitos como Unsplash

## 🎯 Servicios Recomendados para Imágenes

### 🆓 Servicios Gratuitos
1. **Unsplash** (unsplash.com) - Imágenes profesionales gratuitas
2. **Imgur** (imgur.com) - Hosting gratuito de imágenes
3. **Cloudinary** - Plan gratuito disponible
4. **GitHub** - Para proyectos open source

### 📸 Cómo usar Unsplash
1. Ve a unsplash.com
2. Busca una imagen que te guste
3. Haz clic derecho → "Copiar dirección de imagen"
4. Agrega parámetros de optimización:
```
https://images.unsplash.com/photo-ID?ixlib=rb-4.0.3&auto=format&fit=crop&w=420&h=745&q=80
```

### 🔧 Parámetros de Unsplash
- `w=420` - Ancho exacto (420px)
- `h=745` - Alto exacto (745px)  
- `fit=crop` - Recorta para ajustar
- `q=80` - Calidad 80% (balance perfecto)
- `auto=format` - Formato automático optimizado

## 🎨 Tipos de Imágenes Recomendadas

### ✅ Funcionan Bien
- Paisajes urbanos
- Gradientes suaves
- Texturas abstractas
- Fondos con colores sólidos en la parte superior
- Imágenes con espacio negativo arriba

### ❌ Evitar
- Imágenes muy detalladas
- Texto en la imagen
- Colores muy claros (dificultan leer el texto blanco)
- Patrones muy repetitivos
- Imágenes horizontales

## 🔧 Solución de Problemas

### La imagen no se muestra
1. **Verifica la URL:** Asegúrate de que la URL sea accesible
2. **Formato correcto:** Solo JPG, PNG, WebP
3. **Tamaño del archivo:** Máximo 2MB
4. **Cache del navegador:** Presiona Ctrl+F5 para refrescar

### La imagen se ve pixelada
- Usa una resolución más alta (mínimo 420x745px)
- Considera usar 840x1490px para pantallas retina

### La imagen se ve cortada
- Verifica que la relación de aspecto sea 9:16
- Usa `fit=crop` en URLs de Unsplash

## 📱 Vista Previa

Para probar tu chatbot:
1. **URL del chatbot:** `https://tu-dominio.com/chatbot/{UUID}/frame`
2. **Páginas de prueba:**
   - `/test-chatbot-exact.html` - Prueba del HTML exacto
   - `/test-chatbot-complete.html` - Simulación completa

## 🎨 Campos Personalizables

Además del fondo, puedes personalizar:

- **Welcome Greeting:** Saludo principal (ej: "¡Hola! 👋")
- **Welcome Subtitle:** Subtítulo (ej: "¿Cómo podemos ayudarte?")
- **Button Text:** Texto del botón (ej: "Pregúntame lo que quieras")
- **Button Subtitle:** Subtítulo del botón (ej: "Responderemos pronto")

## 🚀 Implementación Técnica

### Archivos Modificados
- ✅ Modelo: `app/Extensions/Chatbot/System/Models/Chatbot.php`
- ✅ Migración: `database/migrations/2025_01_10_000001_add_background_fields_to_ext_chatbots_table.php`
- ✅ Request: `app/Extensions/Chatbot/System/Http/Requests/ChatbotCustomizeRequest.php`
- ✅ Controlador: `app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotFrameController.php`
- ✅ Vista: `app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-customize.blade.php`
- ✅ Frontend: `app/Extensions/Chatbot/resources/views/frontend-ui/views/welcome.blade.php`

### JavaScript Handler
El handler `welcomeBackgroundHandler` está implementado en:
`app/Extensions/Chatbot/resources/views/home/edit-window/edit-window.blade.php`

## 📞 Soporte

Si tienes problemas:
1. Verifica que todos los campos estén guardados
2. Limpia la caché: `php artisan optimize:clear`
3. Verifica la consola del navegador para errores JavaScript
4. Prueba con una URL de imagen diferente

---

**¡Disfruta personalizando tu chatbot! 🎉**