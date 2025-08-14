<?php

namespace MagicAI\ExternalChatbot\Http\Controllers\Embed;

use App\Http\Controllers\Controller;
use App\Helpers\Classes\ApiHelper;
use App\Helpers\Classes\Helper;
use App\Models\Chatbot\Chatbot;
use App\Models\Chatbot\Domain;
use App\Models\Setting;
use App\Models\SettingTwo;
use App\Models\User;
use App\Services\VectorService;
use Illuminate\Http\Request;
use App\Domains\Entity\Facades\Entity as EntityFacade;
use App\Domains\Entity\Enums\EntityEnum;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\RateLimiter;

class EmbedController extends Controller
{
    public function script(string $key)
    {
        // Decode widget key: expected format base64('bot:{id}|u:{user}')
        $decoded = base64_decode($key, true) ?: '';
        preg_match('/bot:(\d+)/', $decoded, $mb);
        preg_match('/u:(\d+)/', $decoded, $mu);
        $chatbotId = $mb[1] ?? null;
        $userId = isset($mu[1]) ? (int) $mu[1] : null;

        // Basic checks to avoid leaking widget when plan/ownership invalid
        $allow = false;
        if ($chatbotId && $userId) {
            $bot = Chatbot::query()->find($chatbotId);
            if ($bot && (int) $bot->getAttribute('user_id') === $userId) {
                $subscription = Helper::getCurrentActiveSubscription($userId);
                $plan = $subscription?->plan;
                if ($plan && $plan->checkOpenAiItem('ext_chat_bot')) {
                    $allow = true;
                }
            }
        }

        // Domain allowlist: if domains exist for this bot, only allow matching Origin/Referer
        $originHeader = request()->headers->get('Origin') ?: request()->headers->get('Referer');
        $originHost = $originHeader ? parse_url($originHeader, PHP_URL_HOST) : null;
        if ($allow && $originHost) {
            $allowedDomains = Domain::query()->where('chatbot_id', $chatbotId)->pluck('domain')->filter()->map(fn($d) => strtolower($d))->values();
            if ($allowedDomains->isNotEmpty()) {
                $hostLc = strtolower($originHost);
                $allow = $allowedDomains->contains(function ($d) use ($hostLc) {
                    return $hostLc === strtolower($d) || str_ends_with($hostLc, '.' . ltrim(strtolower($d), '.'));
                });
            }
        }

        if (! $allow) {
            $disabled = "(function(){try{console.warn('External widget disabled for this plan or ownership mismatch.');}catch(e){}})();";
            return response($disabled, 403)
                ->header('Content-Type', 'application/javascript')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Vary', 'Origin');
        }

        $origin = url('/');
        $chatUrl = $origin . '/embed/' . $key . '/chat';
        $botTitle = addslashes((string) ($bot->title ?? 'Assistant'));
        $botColor = addslashes((string) ($bot->color ?: '#6c4bf4'));
        $firstMsg = addslashes((string) ($bot->first_message ?: '¿En qué puedo ayudarte?'));
        $script = "(function(){var w=window,d=document;function css(el,s){Object.assign(el.style,s);}function load(){if(d.getElementById('mx-bot'))return;var wrap=d.createElement('div');wrap.id='mx-bot';css(wrap,{position:'fixed',right:'24px',bottom:'24px',zIndex:'99999'});var btn=d.createElement('button');btn.innerHTML='Chat';css(btn,{background:'{$botColor}',color:'#fff',borderRadius:'24px',padding:'10px 16px',border:'none',cursor:'pointer',boxShadow:'0 6px 16px rgba(0,0,0,.15)'});var panel=d.createElement('div');css(panel,{display:'none',position:'fixed',right:'24px',bottom:'72px',width:'360px',height:'520px',background:'#fff',border:'1px solid #e5e7eb',borderRadius:'12px',overflow:'hidden',boxShadow:'0 10px 30px rgba(0,0,0,.1)'});var header=d.createElement('div');header.textContent='{$botTitle}';css(header,{padding:'10px 12px',background:'#f8f9fb',borderBottom:'1px solid #e5e7eb',fontWeight:'600',display:'flex',justifyContent:'space-between',alignItems:'center'});var brand=d.createElement('a');brand.href='https://tause.pro';brand.target='_blank';brand.rel='noopener noreferrer';brand.textContent='Powered by tause.pro';css(brand,{fontSize:'12px',fontWeight:'500',color:'#6b7280',textDecoration:'none',marginLeft:'12px'});header.appendChild(brand);var body=d.createElement('div');css(body,{padding:'10px',height:'calc(100% - 98px)',overflow:'auto',fontFamily:'system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial'});var inputBox=d.createElement('div');css(inputBox,{padding:'10px',borderTop:'1px solid #e5e7eb',display:'flex',gap:'8px'});var ta=d.createElement('input');ta.type='text';ta.placeholder='Escribe un mensaje';css(ta,{flex:'1',border:'1px solid #e5e7eb',borderRadius:'8px',padding:'8px'});var send=d.createElement('button');send.textContent='Enviar';css(send,{background:'{$botColor}',color:'#fff',border:'none',borderRadius:'8px',padding:'8px 12px',cursor:'pointer'});inputBox.appendChild(ta);inputBox.appendChild(send);panel.appendChild(header);panel.appendChild(body);panel.appendChild(inputBox);btn.onclick=function(){panel.style.display=panel.style.display==='none'?'block':'none';if(body.dataset.init!=='1'){addMsg('{$firstMsg}','bot');body.dataset.init='1';}};wrap.appendChild(btn);d.body.appendChild(wrap);d.body.appendChild(panel);function addMsg(text,role){var b=d.createElement('div');b.textContent=text;css(b,{margin:'6px 0',padding:'8px 10px',borderRadius:'10px',maxWidth:'85%'});if(role==='user'){css(b,{background:'#eef2ff',marginLeft:'auto'});}else{css(b,{background:'#f2f4f7'});}body.appendChild(b);body.scrollTop=body.scrollHeight;}send.addEventListener('click',async function(){var m=ta.value.trim();if(!m)return;ta.value='';addMsg(m,'user');try{var r=await fetch('{$chatUrl}',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:m})});var j=await r.json();addMsg(j.reply||'...', 'bot');}catch(e){addMsg('Error al conectar con el asistente','bot');}});}if(d.readyState==='complete')load();else w.addEventListener('load',load);}());";
        return response($script, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Vary', 'Origin');
    }

    public function chat(Request $request, string $key)
    {
        // CORS preflight
        $originHeader = $request->headers->get('Origin');
        $allowHeaders = 'Content-Type';
        $allowMethods = 'POST, OPTIONS';
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $originHeader ?: '*')
                ->header('Access-Control-Allow-Headers', $allowHeaders)
                ->header('Access-Control-Allow-Methods', $allowMethods)
                ->header('Access-Control-Max-Age', '600')
                ->header('Vary', 'Origin');
        }

        $payload = $request->json()->all();
        $message = trim((string)($payload['message'] ?? ''));
        if (strlen($message) > 4000) {
            $message = substr($message, 0, 4000);
        }
        if ($message === '') {
            return response()->json(['reply' => ''])
                ->header('Access-Control-Allow-Origin', $originHeader ?: '*')
                ->header('Access-Control-Allow-Headers', $allowHeaders)
                ->header('Access-Control-Allow-Methods', $allowMethods)
                ->header('Vary', 'Origin');
        }
        // Decode widget key: expected format base64('bot:{id}|u:{user}')
        $decoded = base64_decode($key, true) ?: '';
        preg_match('/bot:(\d+)/', $decoded, $mb);
        preg_match('/u:(\d+)/', $decoded, $mu);
        $chatbotId = $mb[1] ?? null;
        $userId = isset($mu[1]) ? (int) $mu[1] : null;
        if (! $chatbotId) {
            return response()->json(['reply' => 'Bot not found']);
        }
        $bot = Chatbot::query()->find($chatbotId);
        if (! $bot) {
            return response()->json(['reply' => 'Bot not found']);
        }
        if (! $userId || (int) $bot->getAttribute('user_id') !== $userId) {
            return response()->json(['reply' => 'Unauthorized bot access'], 403);
        }

        // Domain allowlist: if domains exist for this bot, only allow matching Origin
        $originHeader = $originHeader ?: $request->headers->get('Referer');
        $originHost = $originHeader ? parse_url($originHeader, PHP_URL_HOST) : null;
        if ($originHost) {
            $allowedDomains = Domain::query()->where('chatbot_id', $chatbotId)->pluck('domain')->filter()->map(fn($d) => strtolower($d))->values();
            if ($allowedDomains->isNotEmpty()) {
                $hostLc = strtolower($originHost);
                $isAllowed = $allowedDomains->contains(function ($d) use ($hostLc) {
                    return $hostLc === strtolower($d) || str_ends_with($hostLc, '.' . ltrim(strtolower($d), '.'));
                });
                if (! $isAllowed) {
                    return response()->json(['reply' => 'Unauthorized domain'], 403);
                }
            }
        }

        // Simple rate limit per bot+domain+ip
        try {
            $ip = $request->ip();
            $hostKey = $originHost ?: 'any';
            $rateKey = 'extbot:'.$chatbotId.':'.$hostKey.':'.$ip;
            if (RateLimiter::tooManyAttempts($rateKey, 60)) {
                return response()->json(['reply' => 'Rate limit exceeded'], 429)
                    ->header('Access-Control-Allow-Origin', $originHeader ?: '*')
                    ->header('Vary', 'Origin');
            }
            RateLimiter::hit($rateKey, 60);
        } catch (\Throwable $e) {}

        // Plan/feature gating: require ext_chat_bot enabled in user's plan
        $subscription = Helper::getCurrentActiveSubscription($userId);
        $plan = $subscription?->plan;
        if (! $plan || ! $plan->checkOpenAiItem('ext_chat_bot')) {
            return response()->json(['reply' => 'Widget disabled for this plan'], 403)
                ->header('Access-Control-Allow-Origin', $originHeader ?: '*')
                ->header('Vary', 'Origin');
        }

        // Prepare OpenAI call with settings and embeddings context
        $settings = Setting::getCache();
        $settings_two = SettingTwo::getCache();
        // Choose API key: user_api -> use user's own keys, else system key
        try {
            if ((bool) ($plan->getAttribute('user_api') ?? false)) {
                $user = User::query()->find($userId);
                $apiKeys = array_filter(array_map('trim', explode(',', (string) $user?->getAttribute('api_keys'))));
                if ($apiKeys !== []) {
                    config(['openai.api_key' => $apiKeys[array_rand($apiKeys)]]);
                } else {
                    // fallback to system key
                    $sysKeys = array_filter(array_map('trim', explode(',', (string) $settings?->getAttribute('openai_api_secret'))));
                    if ($sysKeys !== []) {
                        config(['openai.api_key' => $sysKeys[array_rand($sysKeys)]]);
                    }
                }
            } else {
                $sysKeys = array_filter(array_map('trim', explode(',', (string) $settings?->getAttribute('openai_api_secret'))));
                if ($sysKeys !== []) {
                    config(['openai.api_key' => $sysKeys[array_rand($sysKeys)]]);
                }
            }
        } catch (\Throwable $e) {
            // keep default config if something goes wrong
        }
        $system = $bot->instructions ?: 'You are a helpful assistant.';

        // Credit check before calling model
        $modelSlug = $bot->model ?: ($settings?->openai_default_model ?: config('openai.model', 'gpt-4o-mini'));
        try {
            $driver = EntityFacade::driver(EntityEnum::fromSlug($modelSlug) ?? EntityEnum::GPT_4_O)
                ->forUser($userId)
                ->forPlan($plan);
            $driver->redirectIfNoCreditBalance();
        } catch (\Throwable $e) {
            return response()->json(['reply' => 'No credits left'], 402)
                ->header('Access-Control-Allow-Origin', $originHeader ?: '*')
                ->header('Vary', 'Origin');
        }

        // retrieve similar content from embeddings of this chatbot
        $context = '';
        try {
            $context = (string) (new VectorService())->getMostSimilarText($message, 0, 2, $chatbotId);
        } catch (\Throwable $e) {}

        $messages = [
            ['role' => 'system', 'content' => $system . "\nSi hay 'Document Content' úsalo como fuente. Si no hay evidencia suficiente, pide aclaración y no inventes."],
        ];
        if ($context !== '') {
            $messages[] = ['role' => 'user', 'content' => "User question: {$message}\n\nDocument Content:\n{$context}"];
        } else {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        try {
            $model = $modelSlug;
            $res = OpenAI::chat()->create([
                'model'      => $model,
                'messages'   => $messages,
                'max_tokens' => 700,
            ]);
            $reply = $res->choices[0]->message->content ?? '';
        } catch (\Throwable $e) {
            $reply = 'Error: ' . $e->getMessage();
        }
        // Decrease credit based on reply size as done elsewhere in app
        try {
            $used = function_exists('countWords') ? (int) countWords($reply) : strlen((string) $reply) / 4;
            $driver->input($reply)->calculateCredit()->decreaseCredit();
        } catch (\Throwable $e) {
            // ignore credit errors to not break reply
        }

        return response()->json(['reply' => $reply])
            ->header('Access-Control-Allow-Origin', $originHeader ?: '*')
            ->header('Access-Control-Allow-Headers', $allowHeaders)
            ->header('Access-Control-Allow-Methods', $allowMethods)
            ->header('Vary', 'Origin');
    }
}


