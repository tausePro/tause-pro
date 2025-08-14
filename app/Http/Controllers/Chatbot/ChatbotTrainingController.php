<?php

namespace App\Http\Controllers\Chatbot;

use App\Helpers\Classes\Helper;
use App\Helpers\Classes\OpenAiHelper;
use App\Helpers\Classes\ApiHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotTrainingRequest;
use App\Models\Chatbot\Chatbot;
use App\Models\Chatbot\ChatbotData;
use App\Models\Chatbot\ChatbotDataVector;
use App\Services\Chatbot\LinkCrawler;
use App\Services\Chatbot\ParserExcelService;
use App\Services\Chatbot\ParserService;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatbotTrainingController extends Controller
{
    public function qa(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.qa.list', [
                    'items' => $chatbot->data()->where('type', 'qa')->get(),
                ])->render(),
            ]);
        }

        $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
        ]);

        $id = $request->get('qa_id');

        $chatBotData = ChatBotData::query()->where('id', $id)->first();

        if ($chatBotData) {
            $chatBotData->update([
                'type_value' => $request->get('question'),
                'content'    => $request->get('answer'),
                'status'     => 'waiting',
            ]);

            ChatbotDataVector::query()->where('chatbot_data_id', $id)->delete();
        } else {
            ChatBotData::query()->firstOrCreate([
                'chatbot_id' => $chatbot->getAttribute('id'),
                'type'       => 'qa',
                'type_value' => $request->get('question'),
            ], [
                'content' => $request->get('answer'),
                'status'  => 'waiting',
            ]);
        }

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.qa.list', [
                'items' => $chatbot->data()->where('type', 'qa')->get(),
            ])->render(),
            'message' => trans('Qa uploaded successfully.'),
            'count'   => $chatbot->data()->where('type', 'qa')->count(),
        ]);
    }

    public function text(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.text.list', [
                    'items' => $chatbot->data()->where('type', 'text')->get(),
                ])->render(),
            ]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'text'  => 'required|string',
        ]);

        $id = $request->get('text_id');

        $chatBotData = ChatBotData::query()->where('id', $id)->first();

        if ($chatBotData) {
            $chatBotData->update([
                'type_value' => $request->get('title'),
                'content'    => $request->get('text'),
                'status'     => 'waiting',
            ]);

            ChatbotDataVector::query()->where('chatbot_data_id', $id)->delete();
        } else {
            ChatBotData::query()->firstOrCreate([
                'chatbot_id' => $chatbot->getAttribute('id'),
                'type'       => 'text',
                'type_value' => $request->get('title'),
            ], [
                'content' => $request->get('text'),
                'status'  => 'waiting',
            ]);
        }

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.text.list', [
                'items' => $chatbot->data()->where('type', 'text')->get(),
            ])->render(),
            'message' => trans('Text uploaded successfully.'),
            'count'   => $chatbot->data()->where('type', 'text')->count(),
        ]);
    }

    public function uploadPdf(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.pdf.list', [
                    'items' => $chatbot->data()->where('type', 'pdf')->get(),
                ])->render(),
            ]);
        }

        $request->validate([
            'file' => 'required|mimes:pdf,xls,xlsx,csv',
        ]);

        $file = $request->file('file');

        $extension = $file->getClientOriginalExtension();

        $defaultDisk = 'public';

        $path = $file->store('chatbot', ['disk' => $defaultDisk]);

        $name = $file->getClientOriginalName();

        $storagePath = config('filesystems.disks.' . $defaultDisk . '.root') . '/' . $path;

        if ($extension === 'xls' || $extension === 'xlsx' || $extension === 'csv') {
            $parser = app(ParserExcelService::class);

            $parser->setPath($storagePath)->parse();

        } else {
            $parser = app(ParserService::class);

            $parser->setPdfPath($storagePath)->parse();
        }

        ChatBotData::query()->firstOrCreate([
            'chatbot_id' => $chatbot->getAttribute('id'),
            'type'       => 'pdf',
            'type_value' => $name,
        ], [
            'content' => $parser->getText(),
            'status'  => 'waiting',
            'path'    => $path,
        ]);

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.pdf.list', [
                'items' => $chatbot->data()->where('type', 'pdf')->get(),
            ])->render(),
            'message' => trans('Pdf file uploaded successfully.'),
        ]);
    }

    public function getWebSites(Request $request, Chatbot $chatbot)
    {
        return response()->json([
            'content' => view('panel.admin.chatbot.particles.web-site.crawler', [
                'items' => $chatbot->data()->where('type', 'url')->get(),
            ])->render(),
        ]);
    }

    public function postWebSites(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.web-site.crawler', [
                    'items' => $chatbot->data()->where('type', 'url')->get(),
                ])->render(),
            ]);
        }
        // Normalizar URL: si viene sin esquema, asumir https
        $rawUrl = trim((string) $request->input('url'));
        if ($rawUrl && ! preg_match('#^https?://#i', $rawUrl)) {
            $request->merge(['url' => 'https://' . $rawUrl]);
        }

        $request->validate([
            'url' => 'required|url',
        ]);

        $single = $request->input('type') == 'single';

        $targetUrl = (string) $request->input('url');

        $crawler = new LinkCrawler($targetUrl);

        $crawler->crawl($single);

        $content = $crawler->getContents();

        if (! mb_check_encoding($content, 'UTF-8')) {
            // Convert the content to UTF-8 encoding if needed
            $content = mb_convert_encoding($content, 'UTF-8');
        }

        $insightContextParts = [];

        foreach ($content as $url => $data) {
            ChatBotData::query()->firstOrCreate([
                'chatbot_id' => $chatbot->getAttribute('id'),
                'type'       => 'url',
                'type_value' => $url,
            ], [
                'content' => $data,
                'status'  => 'waiting',
            ]);

            // Acumular contexto para insights (hasta 400 chars por página)
            if (is_string($data) && strlen($data) > 0) {
                $insightContextParts[] = mb_strimwidth($data, 0, 400, '…');
            }
        }

        // Optional: Enriquecer descubrimiento con Serper si la API Key está configurada
        try {
            $settingsTwo = \App\Models\SettingTwo::getCache();
            $serperKey = $settingsTwo?->serper_api_key;
            if (!empty($serperKey)) {
                $domain = parse_url($targetUrl, PHP_URL_HOST) ?: $targetUrl;
                $queries = [
                    "site:$domain",
                    "site:$domain about",
                    "site:$domain contact",
                    "site:$domain blog",
                    "site:$domain pricing",
                    "site:$domain faq",
                ];
                $client = new \GuzzleHttp\Client([
                    'base_uri' => 'https://google.serper.dev',
                    'timeout'  => 10,
                ]);
                foreach ($queries as $q) {
                    $resp = $client->post('/search', [
                        'headers' => [
                            'X-API-KEY'   => $serperKey,
                            'Content-Type'=> 'application/json',
                        ],
                        'json' => [
                            'q'      => $q,
                            'num'    => 10,
                            // geolocalización/idioma opcional
                            // 'gl'  => app()->getLocale() === 'es' ? 'co' : 'us',
                            // 'hl'  => app()->getLocale() ?? 'es',
                        ],
                    ]);
                    $body = json_decode((string) $resp->getBody(), true);

                    $organic = $body['organic'] ?? [];
                    $summaryLines = [];
                    foreach ($organic as $item) {
                        $itemUrl = $item['link'] ?? null;
                        $title   = $item['title'] ?? '';
                        $snippet = $item['snippet'] ?? '';
                        if (!$itemUrl) { continue; }

                        // Guardar como URL si no existe
                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'url',
                            'type_value' => $itemUrl,
                        ], [
                            'content' => $title . "\n" . $snippet,
                            'status'  => 'waiting',
                        ]);

                        if ($title || $snippet) {
                            $summaryLines[] = ($title ? ("- " . $title) : '') . ($snippet ? (" — " . $snippet) : '');
                        }
                    }

                    // People Also Ask (PAA) -> QA
                    $paa = $body['peopleAlsoAsk'] ?? [];
                    foreach ($paa as $qa) {
                        $question = $qa['question'] ?? null;
                        $answer   = $qa['answer'] ?? null;
                        if (!$question || !$answer) { continue; }
                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'qa',
                            'type_value' => $question,
                        ], [
                            'content' => $answer,
                            'status'  => 'waiting',
                        ]);
                    }

                    // Resumen breve como texto
                    if (!empty($summaryLines)) {
                        $title = 'SERP Summary: ' . $q;
                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'text',
                            'type_value' => $title,
                        ], [
                            'content' => implode("\n", array_slice($summaryLines, 0, 10)),
                            'status'  => 'waiting',
                        ]);

                        $insightContextParts[] = implode("\n", array_slice($summaryLines, 0, 10));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso: no romper flujo si Serper falla
        }

        // Optional: Investigación con Tavily si está configurado (Settings -> tavily_api_key)
        try {
            $tavilyKey = (string) setting('tavily_api_key');
            if (! empty($tavilyKey)) {
                $domain = parse_url($targetUrl, PHP_URL_HOST) ?: $targetUrl;
                $queries = [
                    $domain,
                    $domain . ' competitors',
                    $domain . ' reviews',
                    $domain . ' testimonials',
                    $domain . ' social media',
                    'site:trustpilot.com ' . $domain,
                ];
                $client = new \GuzzleHttp\Client([
                    'base_uri' => 'https://api.tavily.com',
                    'timeout'  => 15,
                ]);
                foreach ($queries as $q) {
                    $resp = $client->post('/search', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'api_key'               => $tavilyKey,
                            'query'                 => $q,
                            'search_depth'          => 'basic',
                            'include_answer'        => true,
                            'include_images'        => false,
                            'include_raw_content'   => false,
                            'max_results'           => 10,
                        ],
                    ]);
                    $body = json_decode((string) $resp->getBody(), true);
                    $results = $body['results'] ?? [];
                    $answer = $body['answer'] ?? null;
                    $summaryLines = [];

                    foreach ($results as $item) {
                        $itemUrl = $item['url'] ?? null;
                        $title   = $item['title'] ?? '';
                        $content = $item['content'] ?? '';
                        if (! $itemUrl) { continue; }

                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'url',
                            'type_value' => $itemUrl,
                        ], [
                            'content' => $title . "\n" . mb_strimwidth($content, 0, 400, '...'),
                            'status'  => 'waiting',
                        ]);

                        if ($title || $content) {
                            $summaryLines[] = ($title ? ('- ' . $title) : '') . ($content ? (' — ' . mb_strimwidth($content, 0, 140, '…')) : '');
                        }
                    }

                    if (! empty($answer)) {
                        $title = 'Tavily Answer: ' . $q;
                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'text',
                            'type_value' => $title,
                        ], [
                            'content' => $answer,
                            'status'  => 'waiting',
                        ]);

                        $insightContextParts[] = $answer;
                    }

                    if (! empty($summaryLines)) {
                        $title = 'Tavily Summary: ' . $q;
                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'text',
                            'type_value' => $title,
                        ], [
                            'content' => implode("\n", array_slice($summaryLines, 0, 10)),
                            'status'  => 'waiting',
                        ]);

                        $insightContextParts[] = implode("\n", array_slice($summaryLines, 0, 10));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso: no romper flujo si Tavily falla
        }

        // 4) Generar "Agency Insights" con OpenAI si hay contexto suficiente y clave
        try {
            $contextBlob = trim(implode("\n\n", array_filter($insightContextParts)));
            if (strlen($contextBlob) > 200) {
                $domain = parse_url($targetUrl, PHP_URL_HOST) ?: $targetUrl;
                $apiKey = ApiHelper::setOpenAiKey();
                if (! empty($apiKey)) {
                    $messages = [
                        ['role' => 'system', 'content' => 'Eres un estratega senior de marketing digital para pymes en Colombia. Escribe en español, claro y accionable.'],
                        ['role' => 'user', 'content' => "Con base en este contexto (recortes de web/serp/reseñas), dame: 1) hallazgos clave; 2) 8–12 recomendaciones accionables divididas en SEO, Contenido, Paid, Social, Email/CRM; 3) 5 ideas de ofertas/CTAs; 4) próximos pasos para 2 semanas. Sé concreto y específico. Contexto:\n\n" . mb_strimwidth($contextBlob, 0, 6000, '…')],
                    ];
                    $resp = OpenAI::chat()->create([
                        'model'    => 'gpt-4o-mini',
                        'messages' => $messages,
                        'temperature' => 0.4,
                        'max_tokens'  => 1200,
                    ]);
                    $insights = $resp->choices[0]->message->content ?? null;
                    if (! empty($insights)) {
                        ChatBotData::query()->firstOrCreate([
                            'chatbot_id' => $chatbot->getAttribute('id'),
                            'type'       => 'text',
                            'type_value' => 'Agency Insights: ' . $domain,
                        ], [
                            'content' => $insights,
                            'status'  => 'waiting',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.web-site.crawler', [
                'items' => $chatbot->data()->where('type', 'url')->get(),
            ])->render(),
            'message' => trans('Web sites added successfully.'),
        ]);
    }

    public function training(
        ChatbotTrainingRequest $request,
        Chatbot $chatbot
    ) {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $selected = $request->input('chatbot_data') ?: [];

        $data = $this->chatbotData($chatbot, $selected)->toArray();

        OpenAiHelper::embeddingData(
            $chatbot->getAttribute('id'),
            $data,
            $chatbot->trainingData()->pluck('id')->toArray()
        );

        // Marcar como entrenados los items consumidos en esta sesión
        try {
            if (! empty($selected)) {
                ChatBotData::query()
                    ->where('chatbot_id', $chatbot->getAttribute('id'))
                    ->where('type', $request->get('type'))
                    ->whereIn('id', $selected)
                    ->update(['status' => 'trained']);
            } else {
                // Si no hubo selección (entrenamiento masivo por tipo)
                ChatBotData::query()
                    ->where('chatbot_id', $chatbot->getAttribute('id'))
                    ->where('type', $request->get('type'))
                    ->update(['status' => 'trained']);
            }
        } catch (\Throwable $e) {
            // no bloquear si falla el update visual
        }

        $type = $request->get('type');

        $matchView = match ($type) {
            'url'  => 'panel.admin.chatbot.particles.web-site.crawler',
            'pdf'  => 'panel.admin.chatbot.particles.pdf.list',
            'text' => 'panel.admin.chatbot.particles.text.list',
            'qa'   => 'panel.admin.chatbot.particles.qa.list',
        };

        $chatbot->update([
            'status' => 'trained',
        ]);

        return response()->json([
            'content' => view($matchView, [
                'items' => $chatbot->data()->where('type', $request->get('type'))->get(),
            ])->render(),
            'message' => trans('Training Completed Successfully.'),
        ]);
    }

    public function deleteItem(Chatbot $chatbot, $id)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $chatbot->data()->where('id', $id)->delete();

        ChatbotDataVector::query()->where('chatbot_data_id', $id)->delete();

        return response()->json([
            'message' => trans('Item deleted successfully.'),
        ]);
    }

    public function chatbotData(Chatbot $chatbot, ?array $data = null)
    {
        return ChatBotData::query()
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->where('type', request('type'))
            ->when($data, function ($query) use ($data) {
                return $query->whereIn('id', $data);
            })
            ->get()
            ->map(function ($item) {

                $content = $item->getAttribute('type') == 'qa'
                    ? "When you receive the following question or a similar one, answer it like this: '" . $item->getAttribute('content') . "' \n Question: '" . $item->getAttribute('type_value') . "'"
                    : $item->getAttribute('content');

                return [
                    'id'      => $item->getAttribute('id'),
                    'content' => $content,
                ];
            })
            ->pluck('content', 'id');
    }
}
