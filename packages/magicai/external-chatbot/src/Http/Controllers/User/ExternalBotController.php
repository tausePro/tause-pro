<?php

namespace MagicAI\ExternalChatbot\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Chatbot\Chatbot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExternalBotController extends Controller
{
    public function index()
    {
        $bots = Chatbot::query()->where('user_id', Auth::id())->orderByDesc('id')->get();
        return view('externalchatbot::index', compact('bots'));
    }

    public function create()
    {
        return view('externalchatbot::create');
    }

    public function store(Request $request)
    {
        $bot = Chatbot::query()->create([
            'user_id' => Auth::id(),
            'title'   => $request->input('title', 'External Bot ' . Str::random(5)),
            'role'    => $request->input('role', 'assistant'),
            'status'  => 'active',
        ]);
        return redirect()->route('externalbots.embed', $bot->id);
    }

    public function embed(Chatbot $bot)
    {
        // Política custom aún no registrada: validamos propiedad manualmente
        if ($bot->getAttribute('user_id') !== Auth::id()) {
            abort(403);
        }
        $widgetKey = base64_encode('bot:' . $bot->id . '|u:' . Auth::id());
        return view('externalchatbot::embed', compact('bot', 'widgetKey'));
    }
}


