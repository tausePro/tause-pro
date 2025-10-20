@include('external-chatbot.frontend-ui', [
    'is_editor' => false,
    'is_iframe' => true,
    'chatbot' => $chatbot,
    'routes' => $routes,
])

