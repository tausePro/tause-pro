<?php

namespace App\Extensions\Chatbot\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;

class ChatbotFrameController extends Controller
{
    public function frame(Request $request, Chatbot $chatbot): Response
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

        $view = view('chatbot::frame', compact('chatbot', 'session', 'conversations'));

        $response = response($view);
        $response->headers->remove('X-Frame-Options');
        $response->headers->set('Content-Security-Policy', 'frame-ancestors *', false);

        return $response;
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

    public function serveAvatar(Request $request, string $path): Response
    {
        // Decode URL-encoded path
        $path = urldecode($path);

        // Remove 'uploads/avatars/' prefix if present
        $path = preg_replace('#^uploads/avatars/#', '', $path);
        $path = preg_replace('#^uploads/#', '', $path);
        
        // Remove any leading slashes
        $path = ltrim($path, '/');

        // Security: prevent directory traversal and null bytes
        if (strpos($path, '..') !== false || strpos($path, "\0") !== false) {
            abort(404);
        }

        // Build full path - try avatars directory first
        $fullPath = public_path('uploads/avatars/' . $path);

        // Also try uploads directory if not found
        if (! File::exists($fullPath)) {
            $fullPath = public_path('uploads/' . $path);
        }

        // Security: prevent directory traversal
        $realPath = realpath($fullPath);
        $publicPath = realpath(public_path('uploads'));

        if (! $realPath || ! $publicPath || strpos($realPath, $publicPath) !== 0) {
            \Log::warning('Chatbot avatar not found or security check failed', [
                'requested_path' => $path,
                'full_path' => $fullPath,
                'real_path' => $realPath,
                'public_path' => $publicPath,
            ]);
            abort(404);
        }

        if (! File::exists($fullPath)) {
            \Log::warning('Chatbot avatar file does not exist', [
                'requested_path' => $path,
                'full_path' => $fullPath,
            ]);
            abort(404);
        }

        $content = File::get($fullPath);
        $mimeType = File::mimeType($fullPath) ?: 'image/png';

        return response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Accept, Origin, Content-Type')
            ->header('Cache-Control', 'public, max-age=31536000');
    }
}
