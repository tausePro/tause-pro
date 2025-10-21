# 🚀 Instrucciones de Despliegue - Sistema de Orquestación de Agentes

**Fecha:** 2025-10-20  
**Sistema:** Agent Orchestration System  
**Ambiente:** Producción AWS

---

## ✅ Pre-requisitos Verificados

- ✅ Sistema funciona en local (probado con tinker)
- ✅ Migración creada y probada
- ✅ Seeder creado y probado
- ✅ Código con fallbacks seguros
- ✅ Backup automático incluido
- ✅ Rollback script listo

---

## 🎯 Archivos Listos para Desplegar

### **Nuevos:**
- `database/migrations/2025_10_20_202126_create_ext_chatbot_agents_table.php`
- `database/seeders/MigrateSalesAgentConfigSeeder.php`
- `app/Extensions/Chatbot/System/Models/ChatbotAgent.php`
- `app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php`
- `app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php`

### **Modificados:**
- `app/Extensions/Chatbot/System/Generators/OpenAIGenerator.php`
- `app/Extensions/Chatbot/System/Services/GeneratorService.php`
- `app/Extensions/Chatbot/System/Models/Chatbot.php`
- `app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php`

---

## 🚀 OPCIÓN 1: Despliegue Automático (RECOMENDADO)

### **Paso 1: Ejecutar script de despliegue**

```bash
./deploy-orchestration-to-production.sh
```

**El script hace:**
1. ✅ Backup automático de BD
2. ✅ Backup de archivos críticos
3. ✅ Upload de archivos nuevos
4. ✅ Ejecución de migración
5. ✅ Ejecución de seeder
6. ✅ Limpieza de cachés
7. ✅ Verificación del sistema

**Duración estimada:** 2-3 minutos

---

### **Paso 2: Verificar en producción**

1. **Abrir chatbot Ali:**
   ```
   https://app.tause.pro/chatbot/786ce971-da76-4fd6-adbb-262cbee1200d
   ```

2. **Enviar mensaje de prueba:**
   ```
   "¿Tienen iPhone 15?"
   ```

3. **Verificar en consola (F12):**
   - Buscar en Network → Response del mensaje
   - Debe tener campo `orchestration` con datos de agentes

4. **Verificar grid de productos:**
   - Debe aparecer automáticamente si hay productos

---

### **Paso 3: Si algo falla - Rollback**

```bash
./rollback-orchestration-production.sh
```

**El script hace:**
1. ✅ Rollback de migración
2. ✅ Restaura archivos desde backup
3. ✅ Elimina archivos nuevos
4. ✅ Limpia cachés
5. ✅ Verifica sistema

---

## 📋 OPCIÓN 2: Despliegue Manual

### **Paso 1: Conectar a AWS**

```bash
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220
cd /var/www/magicai
```

---

### **Paso 2: Backup Manual**

```bash
# Backup BD
mysqldump -u root magicai > /tmp/backup-orchestration-$(date +%Y%m%d_%H%M%S).sql

# Backup archivos
tar -czf /tmp/backup-orchestration-files-$(date +%Y%m%d_%H%M%S).tar.gz \
    app/Extensions/Chatbot/System/Generators/OpenAIGenerator.php \
    app/Extensions/Chatbot/System/Services/GeneratorService.php \
    app/Extensions/Chatbot/System/Models/Chatbot.php \
    app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php
```

---

### **Paso 3: Upload Archivos**

Desde tu máquina local:

```bash
# Upload migration
scp -i magicai-tause-key.pem \
    database/migrations/2025_10_20_202126_create_ext_chatbot_agents_table.php \
    ubuntu@34.207.248.220:/tmp/

# Upload seeder
scp -i magicai-tause-key.pem \
    database/seeders/MigrateSalesAgentConfigSeeder.php \
    ubuntu@34.207.248.220:/tmp/

# Upload models
scp -i magicai-tause-key.pem \
    app/Extensions/Chatbot/System/Models/ChatbotAgent.php \
    ubuntu@34.207.248.220:/tmp/

# Upload services
scp -i magicai-tause-key.pem \
    app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php \
    app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php \
    ubuntu@34.207.248.220:/tmp/

# Upload modified files
scp -i magicai-tause-key.pem \
    app/Extensions/Chatbot/System/Generators/OpenAIGenerator.php \
    app/Extensions/Chatbot/System/Services/GeneratorService.php \
    app/Extensions/Chatbot/System/Models/Chatbot.php \
    app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php \
    ubuntu@34.207.248.220:/tmp/
```

---

### **Paso 4: Mover Archivos en Producción**

En AWS:

```bash
cd /var/www/magicai

# Migration
sudo cp /tmp/2025_10_20_202126_create_ext_chatbot_agents_table.php database/migrations/

# Seeder
sudo cp /tmp/MigrateSalesAgentConfigSeeder.php database/seeders/

# Models
sudo cp /tmp/ChatbotAgent.php app/Extensions/Chatbot/System/Models/

# Services
sudo cp /tmp/AgentOrchestratorService.php app/Extensions/Chatbot/System/Services/
sudo cp /tmp/ProductOrchestratorService.php app/Extensions/Chatbot/System/Services/

# Modified files
sudo cp /tmp/OpenAIGenerator.php app/Extensions/Chatbot/System/Generators/
sudo cp /tmp/GeneratorService.php app/Extensions/Chatbot/System/Services/
sudo cp /tmp/Chatbot.php app/Extensions/Chatbot/System/Models/
sudo cp /tmp/ChatbotApplicationController.php app/Extensions/Chatbot/System/Http/Controllers/Api/

# Permisos
sudo chown -R www-data:www-data app/Extensions/Chatbot/
sudo chown -R www-data:www-data database/
```

---

### **Paso 5: Ejecutar Migración**

```bash
php artisan migrate --path=database/migrations/2025_10_20_202126_create_ext_chatbot_agents_table.php --force
```

**Verificar:**
```bash
mysql -u root -p -e "SELECT COUNT(*) FROM ext_chatbot_agents;"
```

---

### **Paso 6: Ejecutar Seeder**

```bash
php artisan db:seed --class=MigrateSalesAgentConfigSeeder --force
```

**Verificar:**
```bash
mysql -u root -p -e "SELECT id, chatbot_id, agent_type, name FROM ext_chatbot_agents;"
```

---

### **Paso 7: Limpiar Cachés**

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload --optimize
```

---

### **Paso 8: Verificar Sistema**

```bash
php artisan tinker --execute="
\$chatbot = \App\Extensions\Chatbot\System\Models\Chatbot::where('title', 'Ali')->first();
echo 'Chatbot: ' . \$chatbot->title . PHP_EOL;
echo 'Agents: ' . \$chatbot->agents->count() . PHP_EOL;

\$orchestrator = app(\App\Extensions\Chatbot\System\Services\AgentOrchestratorService::class);
\$result = \$orchestrator->orchestrate(\$chatbot, 'test', 'test');
echo 'Orchestration works: ' . (isset(\$result['message']) ? 'YES' : 'NO') . PHP_EOL;
"
```

---

## 🔄 Rollback Manual

Si algo falla:

```bash
# 1. Rollback migración
php artisan migrate:rollback --step=1 --force

# 2. Restaurar archivos
cd /var/www/magicai
sudo tar -xzf /tmp/backup-orchestration-files-YYYYMMDD_HHMMSS.tar.gz

# 3. Eliminar archivos nuevos
sudo rm -f app/Extensions/Chatbot/System/Models/ChatbotAgent.php
sudo rm -f app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php

# 4. Limpiar cachés
php artisan config:clear
php artisan route:clear
composer dump-autoload
```

---

## ✅ Checklist Post-Despliegue

- [ ] Migración ejecutada sin errores
- [ ] Seeder ejecutado sin errores
- [ ] Tabla `ext_chatbot_agents` existe
- [ ] Agentes creados en BD
- [ ] Cachés limpiados
- [ ] Chatbot Ali responde normalmente
- [ ] Campo `orchestration` aparece en API response
- [ ] Grid de productos se muestra automáticamente
- [ ] No hay errores en logs

---

## 📊 Monitoreo Post-Despliegue

### **Logs a revisar:**

```bash
# Laravel logs
tail -f /var/www/magicai/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log
```

### **Queries a ejecutar:**

```sql
-- Ver agentes creados
SELECT * FROM ext_chatbot_agents;

-- Ver chatbots con agentes
SELECT c.id, c.title, COUNT(a.id) as agents_count
FROM ext_chatbots c
LEFT JOIN ext_chatbot_agents a ON c.id = a.chatbot_id
GROUP BY c.id;
```

---

## 🎯 Criterios de Éxito

✅ **Despliegue exitoso si:**
1. Migración se ejecuta sin errores
2. Tabla `ext_chatbot_agents` existe con datos
3. Chatbot responde normalmente
4. Campo `orchestration` aparece en respuestas
5. No hay errores 500 en logs
6. Grid de productos funciona

❌ **Hacer rollback si:**
1. Migración falla
2. Chatbot deja de responder
3. Errores 500 en producción
4. Usuarios reportan problemas

---

## 📞 Contacto

**Desarrollador:** Cascade AI  
**Fecha implementación:** 2025-10-20  
**Documentación completa:** `AGENT_ORCHESTRATION_SYSTEM_IMPLEMENTED.md`

---

**¡Listo para desplegar!** 🚀
