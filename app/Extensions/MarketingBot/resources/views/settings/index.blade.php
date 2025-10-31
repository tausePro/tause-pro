@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Marketing Bot Settings'))

@section('titlebar_actions')

@endsection

@section('content')
    <div class="grid grid-cols-1 gap-8 py-10 lg:grid-cols-2">
        <x-card>
            <x-slot name="head">
                {{ __('Marketing Bot Telegram') }}
            </x-slot>
            <form
                action="{{ route('dashboard.user.marketing-bot.settings.telegram') }}"
                method="post"
            >
                @method('post')
                <x-forms.input
                    id="access_token"
                    size="lg"
                    label="{{ __('Access Token') }}"
                    name="access_token"
                    required
                    value="{{ $app_is_demo ? '**********' : $telegram?->access_token }}"
                />
                @if ($app_is_demo)
                    <x-button
                        class="mt-3"
                        type="button"
                        onclick="return toastr.info('This feature is disabled in Demo version.');"
                    >
                        {{ __('Save') }}
                    </x-button>
                @else
                    <x-button
                        class="mt-3"
                        type="submit"
                    >
                        {{ __('Save') }}
                    </x-button>
                @endif
            </form>
        </x-card>
        <x-card>
            <x-slot name="head">
                {{ __('Marketing Bot Whatsapp Settings') }}
            </x-slot>
            <form
                action="{{ route('dashboard.user.marketing-bot.settings.whatsapp') }}"
                method="post"
                id="whatsapp-settings-form"
            >
                <input
                    hidden
                    name="channel"
                    value="whatsapp"
                >
                <input
                    hidden
                    name="user_id"
                    value="{{ \Illuminate\Support\Facades\Auth::id() }}"
                >
                @csrf
                
                {{-- Provider Selection --}}
                <div class="mb-4">
                    <x-forms.input
                        id="provider"
                        type="select"
                        size="lg"
                        name="provider"
                        label="{{ __('WhatsApp Provider') }}"
                        onchange="toggleProviderFields()"
                    >
                        <option
                            {{ ($whatsapp?->provider ?? 'twilio') === 'twilio' ? 'selected' : '' }}
                            value="twilio"
                        >Twilio</option>
                        <option
                            {{ $whatsapp?->provider === 'evolution' ? 'selected' : '' }}
                            value="evolution"
                        >Evolution API</option>
                    </x-forms.input>
                    <small class="text-muted">
                        {{ __('Choose between Twilio (paid) or Evolution API (free, self-hosted)') }}
                    </small>
                </div>

                {{-- Twilio Fields --}}
                <div id="twilio-fields" style="display: {{ ($whatsapp?->provider ?? 'twilio') === 'twilio' ? 'block' : 'none' }};">
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Whatsapp sid')"
                            name="whatsapp_sid"
                            size="lg"
                            value="{{ $app_is_demo ? '**********' : $whatsapp?->whatsapp_sid }}"
                        >
                        </x-forms.input>
                    </div>
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Whatsapp token')"
                            name="whatsapp_token"
                            size="lg"
                            value="{{ $app_is_demo ? '**********' : $whatsapp?->whatsapp_token }}"
                        >
                        </x-forms.input>
                    </div>
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Whatsapp phone')"
                            name="whatsapp_phone"
                            size="lg"
                            value="{{ $app_is_demo ? '**********' : $whatsapp?->whatsapp_phone }}"
                        >
                        </x-forms.input>
                    </div>
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Whatsapp sandbox phone')"
                            name="whatsapp_sandbox_phone"
                            size="lg"
                            value="{{ $app_is_demo ? '**********' : $whatsapp?->whatsapp_sandbox_phone }}"
                        >
                        </x-forms.input>
                    </div>
                    <div class="mb-3">
                        <x-forms.input
                            id="whatsapp_environment"
                            type="select"
                            size="lg"
                            name="whatsapp_environment"
                            label="{{ __('Environment') }}"
                        >
                            <option
                                {{ $whatsapp?->whatsapp_environment === 'sandbox' ? 'selected' : '' }}
                                value="sandbox"
                            >@lang('SANDBOX')</option>
                            <option
                                {{ $whatsapp?->whatsapp_environment === 'production' ? 'selected' : '' }}
                                value="production"
                            >@lang('PRODUCTION')</option>
                        </x-forms.input>
                    </div>
                </div>

                {{-- Evolution API Fields --}}
                <div id="evolution-fields" style="display: {{ $whatsapp?->provider === 'evolution' ? 'block' : 'none' }};">
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Evolution API URL')"
                            name="evolution_api_url"
                            size="lg"
                            placeholder="https://wa.tause.pro"
                            value="{{ $app_is_demo ? '**********' : ($whatsapp?->evolution_credentials['api_url'] ?? '') }}"
                        >
                        </x-forms.input>
                        <small class="text-muted">{{ __('Example: https://wa.tause.pro') }}</small>
                    </div>
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Evolution API Key')"
                            name="evolution_api_key"
                            size="lg"
                            placeholder="tause2024SecretKey"
                            value="{{ $app_is_demo ? '**********' : ($whatsapp?->evolution_credentials['api_key'] ?? '') }}"
                        >
                        </x-forms.input>
                        <small class="text-muted">{{ __('Your Evolution API authentication key') }}</small>
                    </div>
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Evolution Instance Name')"
                            name="evolution_instance"
                            size="lg"
                            placeholder="tausepro"
                            value="{{ $app_is_demo ? '**********' : ($whatsapp?->evolution_credentials['instance'] ?? '') }}"
                        >
                        </x-forms.input>
                        <small class="text-muted">{{ __('Your WhatsApp instance name in Evolution API') }}</small>
                    </div>
                    <div class="alert alert-info">
                        <strong>💡 {{ __('Tip') }}:</strong> {{ __('Evolution API is a free, self-hosted alternative to Twilio. Perfect for unlimited WhatsApp campaigns at no cost.') }}
                    </div>
                </div>

                @if ($whatsapp)
                    <div class="mb-3">
                        <x-forms.input
                            :label="__('Webhook Url')"
                            name="webhook"
                            size="lg"
                            readonly
                            value="{{ route('api.marketing-bot.whatsapp.webhook', $whatsapp?->id) }}"
                        >
                        </x-forms.input>
                        <small class="text-muted">{{ __('Configure this URL in your provider settings') }}</small>
                    </div>
                @endif

                @if ($app_is_demo)
                    <x-button
                        type="button"
                        onclick="return toastr.info('This feature is disabled in Demo version.');"
                    >
                        {{ __('Save') }}
                    </x-button>
                @else
                    <x-button
                        class="mt-3"
                        type="submit"
                    >
                        {{ __('Save') }}
                    </x-button>
                @endif
            </form>
        </x-card>
    </div>
@endsection

@push('script')
<script>
    function toggleProviderFields() {
        const provider = document.getElementById('provider').value;
        const twilioFields = document.getElementById('twilio-fields');
        const evolutionFields = document.getElementById('evolution-fields');
        
        if (provider === 'twilio') {
            twilioFields.style.display = 'block';
            evolutionFields.style.display = 'none';
            
            // Hacer campos de Twilio requeridos
            document.querySelectorAll('#twilio-fields input').forEach(input => {
                if (input.name !== 'whatsapp_sandbox_phone') {
                    input.required = true;
                }
            });
            
            // Remover required de Evolution
            document.querySelectorAll('#evolution-fields input').forEach(input => {
                input.required = false;
            });
        } else if (provider === 'evolution') {
            twilioFields.style.display = 'none';
            evolutionFields.style.display = 'block';
            
            // Hacer campos de Evolution requeridos
            document.querySelectorAll('#evolution-fields input').forEach(input => {
                input.required = true;
            });
            
            // Remover required de Twilio
            document.querySelectorAll('#twilio-fields input').forEach(input => {
                input.required = false;
            });
        }
    }
    
    // Ejecutar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        toggleProviderFields();
    });
</script>
@endpush
