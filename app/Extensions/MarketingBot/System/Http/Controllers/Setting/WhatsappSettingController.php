<?php

namespace App\Extensions\MarketingBot\System\Http\Controllers\Setting;

use App\Extensions\MarketingBot\System\Models\Whatsapp\WhatsappChannel;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappSettingController extends Controller
{
    public function __invoke(Request $request)
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $provider = $request->input('provider', 'twilio');

        // Validación base
        $rules = [
            'provider' => 'required|in:twilio,evolution',
        ];

        // Validación según el provider
        if ($provider === 'twilio') {
            $rules = array_merge($rules, [
                'whatsapp_sid'           => 'required|string',
                'whatsapp_token'         => 'required|string',
                'whatsapp_phone'         => 'required|string',
                'whatsapp_sandbox_phone' => 'nullable|string',
                'whatsapp_environment'   => 'required',
            ]);
        } elseif ($provider === 'evolution') {
            $rules = array_merge($rules, [
                'evolution_api_url'  => 'required|url',
                'evolution_api_key'  => 'required|string',
                'evolution_instance' => 'required|string',
            ]);
        }

        $data = $request->validate($rules);

        // Preparar datos según el provider
        $channelData = [
            'provider' => $provider,
        ];

        if ($provider === 'twilio') {
            $channelData = array_merge($channelData, [
                'whatsapp_sid'           => $data['whatsapp_sid'],
                'whatsapp_token'         => $data['whatsapp_token'],
                'whatsapp_phone'         => $data['whatsapp_phone'],
                'whatsapp_sandbox_phone' => $data['whatsapp_sandbox_phone'] ?? null,
                'whatsapp_environment'   => $data['whatsapp_environment'],
                'evolution_credentials'  => null,
            ]);
        } elseif ($provider === 'evolution') {
            $channelData = array_merge($channelData, [
                'whatsapp_sid'           => null,
                'whatsapp_token'         => null,
                'whatsapp_phone'         => null,
                'whatsapp_sandbox_phone' => null,
                'whatsapp_environment'   => 'production',
                'evolution_credentials'  => [
                    'api_url'  => $data['evolution_api_url'],
                    'api_key'  => $data['evolution_api_key'],
                    'instance' => $data['evolution_instance'],
                ],
            ]);
        }

        WhatsappChannel::query()->updateOrCreate([
            'user_id' => auth()->id(),
        ], $channelData);

        return back()->with([
            'message' => __('WhatsApp settings updated successfully.'),
            'type'    => 'success',
        ]);
    }
}
