<?php

namespace MagicAI\BrainBrand\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Chatbot\Chatbot;
use App\Models\Chatbot\ChatbotData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Chatbot\ChatbotTrainingController;

class BrainBrandController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()->where('user_id', Auth::id())->orderBy('name')->get();
        $chatbots = Chatbot::query()->where('user_id', Auth::id())->orderBy('title')->get();
        return view('brainbrand::index', [
            'companies' => $companies,
            'chatbots'  => $chatbots,
        ]);
    }

    public function metrics(): JsonResponse
    {
        $companyId = (int) request('company_id');
        $company = Company::query()->where('user_id', Auth::id())->findOrFail($companyId);
        $chatbotId = (int) request('chatbot_id');
        $bot = $chatbotId
            ? Chatbot::query()->where('user_id', Auth::id())->findOrFail($chatbotId)
            : Chatbot::query()->where('user_id', Auth::id())->latest('id')->first();

        $coverage = 0; $freshnessDays = null; $gaps = 0; $competitors = 0;
        if ($bot) {
            $data = ChatbotData::query()->where('chatbot_id', $bot->getKey());
            $total = (clone $data)->count();
            $keyPages = (clone $data)->whereIn('type_value', ['about', 'contact', 'pricing'])->count();
            $coverage = $total > 0 ? (int) round(($keyPages / $total) * 100) : 0;
            $lastTrained = (clone $data)->where('status', 'trained')->latest('updated_at')->first();
            if ($lastTrained) {
                $freshnessDays = now()->diffInDays($lastTrained->updated_at);
            }
            $paa = (clone $data)->where('type', 'qa')->count();
            $textPaa = (clone $data)->where('type', 'text')->where('type_value', 'like', 'SERP Summary:%')->count();
            $gaps = max(0, $textPaa - $paa);
            $competitors = (clone $data)->where('type', 'text')->where('type_value', 'like', 'Tavily%')->count();
        }

        return response()->json([
            'coverage' => $coverage,
            'freshness_days' => $freshnessDays,
            'gaps' => $gaps,
            'competitors' => $competitors,
        ]);
    }

    public function knowledge(): JsonResponse
    {
        $companyId = (int) request('company_id');
        $company = Company::query()->where('user_id', Auth::id())->findOrFail($companyId);
        $chatbotId = (int) request('chatbot_id');
        $bot = $chatbotId
            ? Chatbot::query()->where('user_id', Auth::id())->findOrFail($chatbotId)
            : Chatbot::query()->where('user_id', Auth::id())->latest('id')->first();
        if (! $bot) {
            return response()->json(['items' => []]);
        }
        $type = request('type', 'text');
        $items = ChatbotData::query()
            ->where('chatbot_id', $bot->getKey())
            ->where('type', $type)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'type', 'type_value', 'status']);
        return response()->json(['items' => $items]);
    }

    public function research(Request $request, ChatbotTrainingController $trainer)
    {
        $companyId = (int) $request->input('company_id');
        Company::query()->where('user_id', Auth::id())->findOrFail($companyId);
        $chatbotId = (int) $request->input('chatbot_id');
        $bot = $chatbotId
            ? Chatbot::query()->where('user_id', Auth::id())->findOrFail($chatbotId)
            : Chatbot::query()->where('user_id', Auth::id())->latest('id')->firstOrFail();
        // Usar el dominio/website de la company si existe, sino el title del bot
        $url = $request->input('url');
        if (empty($url)) {
            $url = $request->input('website');
        }
        if (empty($url)) {
            return response()->json(['status' => 'error', 'message' => 'Missing URL'], 422);
        }
        // Finge request de websites para reutilizar lógica
        $sub = Request::create("/dashboard/chatbot/{$bot->getKey()}/web-sites", 'POST', [
            'url'  => $url,
            'type' => 'single',
        ]);
        $sub->setUserResolver(fn() => Auth::user());
        app()->instance('request', $sub);
        return $trainer->postWebSites($sub, $bot);
    }

    public function train(Request $request, ChatbotTrainingController $trainer)
    {
        $companyId = (int) $request->input('company_id');
        Company::query()->where('user_id', Auth::id())->findOrFail($companyId);
        $chatbotId = (int) $request->input('chatbot_id');
        $bot = $chatbotId
            ? Chatbot::query()->where('user_id', Auth::id())->findOrFail($chatbotId)
            : Chatbot::query()->where('user_id', Auth::id())->latest('id')->firstOrFail();
        $type = $request->input('type', 'text');
        $sub = Request::create("/dashboard/chatbot/{$bot->getKey()}/training", 'POST', [
            'type'          => $type,
            'chatbot_data'  => $request->input('ids', []),
        ]);
        $sub->setUserResolver(fn() => Auth::user());
        app()->instance('request', $sub);
        return $trainer->training($sub, $bot);
    }
}


