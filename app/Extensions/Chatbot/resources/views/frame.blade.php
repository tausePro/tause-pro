@if (!$chatbot['active'])
    <p>
        @lang('This chatbot is not active.')
    </p>
@else
    @include('chatbot::frontend-ui.frontend-ui', [
        'is_editor' => false,
        'is_iframe' => true,
        'session' => $session,
        'chatbot' => $chatbot,
        'conversations' => $conversations,
        'routes' => [
            'index' => route('api.v2.chatbot.index', [$chatbot->getAttribute('uuid'), $session]),
            'getSession' => route('api.v2.chatbot.index.session', [$chatbot->getAttribute('uuid'), $session]),
            'conversations' => route('api.v2.chatbot.conversion.store', [$chatbot->getAttribute('uuid'), $session]),
            'send-email' => route('api.v2.chatbot.send-email.store', [$chatbot->getAttribute('uuid'), $session]),
            'collect-email' => route('api.v2.chatbot.collect.email', [$chatbot->getAttribute('uuid'), $session]),
            'articles' => route('api.v2.chatbot.articles', [$chatbot->getAttribute('uuid')]),
            'enable-sound' => route('api.v2.chatbot.enable-sound', [$chatbot->getAttribute('uuid'), $session]), // Enabled and disabled route
        ],
    ])
@endif
