{{-- GDPR Consent Modal --}}
<div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
    x-show="showGdprModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="closeGdprModal"
    style="display: none;"
>
    <div
        class="relative w-full max-w-md max-h-[85vh] overflow-y-auto rounded-2xl bg-white shadow-2xl my-auto"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
    >
        {{-- Content container with padding --}}
        <div class="p-6 pb-8">
            {{-- Header --}}
            <div class="mb-4 flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Protección de Datos') }}
                    </h3>
                </div>
                @if ($is_editor || (isset($chatbot) && !$chatbot->gdpr_required))
                    <button
                        type="button"
                        class="flex-shrink-0 text-gray-400 hover:text-gray-600"
                        @click="closeGdprModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Content --}}
            <div class="mb-6">
                <p class="text-sm leading-relaxed text-gray-600">
                    @if (!$is_editor && isset($chatbot) && $chatbot->gdpr_message)
                        {{ $chatbot->gdpr_message }}
                    @else
                        {{ __('Al usar este chat, autorizas el tratamiento de tus datos personales según la Ley 1581 de 2012.') }}
                    @endif
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col gap-3">
            <button
                type="button"
                class="w-full rounded-lg bg-[--lqd-ext-chat-primary] px-4 py-3 text-sm font-semibold text-white transition-all hover:opacity-90"
                @click="acceptGdprConsent"
                :disabled="gdprProcessing"
            >
                <span x-show="!gdprProcessing">{{ __('Acepto') }}</span>
                <span x-show="gdprProcessing">{{ __('Procesando...') }}</span>
            </button>

            @if ($is_editor || (isset($chatbot) && !$chatbot->gdpr_required))
                <button
                    type="button"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 transition-all hover:bg-gray-50"
                    @click="rejectGdprConsent"
                    :disabled="gdprProcessing"
                >
                    {{ __('No acepto') }}
                </button>
            @endif
        </div>

            {{-- Required notice --}}
            @if ($is_editor || (isset($chatbot) && $chatbot->gdpr_required))
                <p class="mt-4 text-center text-xs text-gray-500">
                    {{ __('Debes aceptar para continuar') }}
                </p>
            @endif
        </div>
    </div>
</div>


