<?php

namespace App\Extensions\Chatbot\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ChatbotFrameController extends Controller
{
    public function frame(Request $request, Chatbot $chatbot): View
    {
        $session = $this->getVisitor();

        $customer = $this->createCustomer($chatbot, $session);

        $customerId = $customer->getKey();

        $conversations = ChatbotConversation::query()
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->where('session_id', $session)
            ->get();

        $chatbot->setAttribute('enabled_sound', $customer->getAttribute('enabled_sound'));

        $this->updateChatbotConversation($conversations, $customerId);

        return view('chatbot::frame', compact('chatbot', 'session', 'conversations'));
    }

    public function updateChatbotConversation(Collection $conversations, $customerId): void
    {
        if ($conversations->whereNull('chatbot_customer_id')?->count()) {
            ChatbotConversation::query()
                ->whereIn(
                    'id',
                    $conversations->whereNull('chatbot_customer_id')->pluck('id')->toArray()
                )
                ->update([
                    'chatbot_customer_id' => $customerId,
                ]);
        }
    }

    public function createCustomer(Chatbot $chatbot, string $session)
    {
        $customer = ChatbotCustomer::query()->firstOrCreate([
            'user_id'         => $chatbot->getAttribute('user_id'),
            'chatbot_id'      => $chatbot->getAttribute('id'),
            'session_id'      => $session,
            'chatbot_channel' => 'frame',
        ], [
            'name'            => 'Anonymous User',
            'ip_address'      => Helper::getRequestIp(),
            'country_code'    => Helper::getRequestCountryCode(),
            'enabled_sound'   => true,
        ]);

        $customer->update([
            'ip_address'      => Helper::getRequestIp(),
            'country_code'    => Helper::getRequestCountryCode(),
        ]);

        return $customer;
    }

    protected function getVisitor(): string
    {
        $cookie = Cookie::has('CHATBOT_VISITOR');

        if ($cookie) {
            return Cookie::get('CHATBOT_VISITOR');
        }

        $sessionId = md5(uniqid(mt_rand(), true));

        Cookie::queue('CHATBOT_VISITOR', $sessionId, 60 * 24 * 365);

        return $sessionId;
    }
}
