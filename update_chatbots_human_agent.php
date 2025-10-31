<?php

// Script temporal para actualizar chatbots con comando de agente en español
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Extensions\Chatbot\System\Models\Chatbot;

echo "🔧 Actualizando chatbots con comando de agente en español...\n\n";

$updated = Chatbot::where(function($query) {
    $query->whereNull('human_agent_command')
          ->orWhere('human_agent_command', 'humanagent');
})->update([
    'human_agent_command' => 'agente',
    'human_agent_tip_message' => '💡 **Tip:** En cualquier momento puedes escribir #agente para ser atendido por un asesor humano.'
]);

echo "✅ {$updated} chatbots actualizados\n\n";

// Mostrar algunos ejemplos
$chatbots = Chatbot::take(3)->get(['id', 'title', 'human_agent_command', 'human_agent_tip_message']);

echo "📋 Ejemplos de chatbots actualizados:\n";
foreach ($chatbots as $chatbot) {
    echo "  - ID: {$chatbot->id} | Título: {$chatbot->title}\n";
    echo "    Comando: #{$chatbot->human_agent_command}\n";
    echo "    Mensaje: " . substr($chatbot->human_agent_tip_message, 0, 60) . "...\n\n";
}

echo "✅ Proceso completado\n";
