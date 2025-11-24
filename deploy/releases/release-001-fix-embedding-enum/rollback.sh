#!/bin/bash

echo "⏪ ROLLBACK: Release 001 - Fix Embedding Enum"
echo ""

PROJECT_ROOT="/Users/tause/Documents/proyectos/tausepro9.4"
BACKUP_FILE="$PROJECT_ROOT/deploy/releases/release-001-fix-embedding-enum/backup/EmbeddingTypeEnum.php.original"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Error: Archivo de backup no encontrado"
    echo "   Buscando: $BACKUP_FILE"
    exit 1
fi

echo "📦 Restaurando archivo desde backup..."
cp "$BACKUP_FILE" "$PROJECT_ROOT/app/Extensions/Chatbot/System/Enums/EmbeddingTypeEnum.php"

if [ $? -eq 0 ]; then
    echo "✅ Rollback completado exitosamente"
    echo ""
    echo "Archivo restaurado:"
    echo "  app/Extensions/Chatbot/System/Enums/EmbeddingTypeEnum.php"
    echo ""
    echo "El enum ahora tiene estos valores:"
    php -r "require '$PROJECT_ROOT/vendor/autoload.php'; \$cases = App\Extensions\Chatbot\System\Enums\EmbeddingTypeEnum::cases(); foreach (\$cases as \$c) { echo '  - ' . \$c->value . PHP_EOL; }"
else
    echo "❌ Error al restaurar archivo"
    exit 1
fi
