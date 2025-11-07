<?php

namespace App\Extensions\OpenAIRealtimeChat\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity as EntityFacade;
use App\Helpers\Classes\Helper;
use App\Helpers\Classes\RateLimiter\RateLimiter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;

class RealtimeVoiceChatController extends Controller
{
    public function checkBalance(?bool $onStart = false): JsonResponse
    {
        if (Helper::appIsDemo()) {

            $clientIp = Helper::getRequestIp();
            $rateLimiter = new RateLimiter('voice-chat-attempt', 25);

            if ($rateLimiter->attempt($clientIp)) {
                return response()->json(['status' => 'success', 'message' => 'Demo mode'], 200);

            }

            return response()->json(['status' => 'error', 'message' => 'Exceeded messages limit on demo'], 200);
        }

        $driver = EntityFacade::driver(EntityEnum::GPT_4_O_REALTIME_PREVIEW);

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'error',
            ], 200);
        }

        return response()->json(['status' => 'success', 'message' => ''], 200);
    }
}
