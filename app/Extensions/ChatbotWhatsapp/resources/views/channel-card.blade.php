@php
	$image = 'vendor/chatbot-multi-channel/icons/whatsapp.svg';
	$image_dark_version = 'vendor/chatbot-multi-channel/icons/whatsapp-light.svg';
	$darkImageExists = file_exists(public_path($image_dark_version));
@endphp

<x-modal
	class:modal-head="border-b-0"
	class:modal-body="pt-3"
	class:modal-container="max-w-[600px]"
>
	<x-slot:trigger
		class="rounded-sm lqd-social-media-card flex flex-col  text-heading-foreground transition-all hover:scale-105 hover:border-heading-foreground/10 hover:shadow-lg hover:shadow-black/5"
		variant="outline"
		size="lg"
		type="button"
	>
		<figure class="mb-8 w-9 transition-all group-hover/card:scale-125">
			<img
				@class([
					'w-full h-auto',
					'dark:hidden' => $darkImageExists,
				])
				src="{{ asset($image) }}"
				alt="whatsapp"
			/>
			@if ($darkImageExists)
				<img
					class="hidden h-auto w-full dark:block"
					src="{{ asset($image_dark_version) }}"
					alt="whatsapp"
				/>
			@endif
		</figure>
		<h4 class="mb-2 text-lg text-inherit">
			Whatsapp
		</h4>

	</x-slot:trigger>

	<x-slot:modal>
		<div x-data="{ activeTab: 'evolution' }">
			<h3 class="mb-3.5">
				WhatsApp Integration
			</h3>
			<p class="mb-5 text-heading-foreground/60">
				@lang('Choose between Evolution API (recommended, free) or Twilio.')
			</p>

			{{-- Tabs --}}
			<div class="mb-6 flex gap-2 border-b">
				<button
					type="button"
					class="px-4 py-2 font-semibold transition-all"
					:class="activeTab === 'evolution' ? 'border-b-2 border-primary text-primary' : 'text-heading-foreground/60'"
					@click="activeTab = 'evolution'"
				>
					🚀 Evolution API (Recommended)
				</button>
				<button
					type="button"
					class="px-4 py-2 font-semibold transition-all"
					:class="activeTab === 'twilio' ? 'border-b-2 border-primary text-primary' : 'text-heading-foreground/60'"
					@click="activeTab = 'twilio'"
				>
					Twilio (Legacy)
				</button>
			</div>

			{{-- Evolution API Form --}}
			<div x-show="activeTab === 'evolution'">
				<div class="mb-4 rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
					<p class="text-sm font-semibold text-green-800 dark:text-green-200">
						✅ @lang('Benefits: Free, No limits, Your own number')
					</p>
				</div>

				<form id="storeForm-whatsapp-evolution" action="{{ route('dashboard.chatbot-multi-channel.whatsapp.store') }}">
					<input hidden name="channel" value="whatsapp">
					<input hidden name="credentials[provider]" value="evolution">
					<input hidden name="user_id" value="{{ \Illuminate\Support\Facades\Auth::id() }}">
					
					<div class="mb-3">
						<x-forms.input
							:label="__('Evolution API URL')"
							name="credentials[evolution_api_url]"
							placeholder="https://wa.tause.pro"
							size="lg"
							required
						/>
					</div>

					<div class="mb-3">
						<x-forms.input
							:label="__('API Key')"
							name="credentials[evolution_api_key]"
							placeholder="B6D03xxxx-xxxx-xxxx-xxxx-xxxxxxxxxx"
							type="password"
							size="lg"
							required
						/>
					</div>

					<div class="mb-3">
						<x-forms.input
							:label="__('Instance Name')"
							name="credentials[evolution_instance]"
							placeholder="my_chatbot_instance"
							size="lg"
							required
						/>
					</div>

					<div class="mb-4 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
						<p class="text-xs text-blue-800 dark:text-blue-200">
							<strong>📱 Webhook:</strong> Configúralo después en Evolution API
						</p>
					</div>

					@if ($app_is_demo)
						<x-button
							type="button"
							onclick="return toastr.info('This feature is disabled in Demo version.');"
						>
							{{ __('Add Channel') }}
						</x-button>
					@else
						<x-button
							type="button"
							x-on:click="storeChannel('storeForm-whatsapp-evolution')"
							size="lg"
						>
							<span x-show="storeChannelFetch">Loading...</span>
							<span x-show="!storeChannelFetch">{{ __('Add Channel') }}</span>
						</x-button>
					@endif
				</form>
			</div>

			{{-- Twilio Form --}}
			<div x-show="activeTab === 'twilio'">
				<div class="mb-4 rounded-lg bg-orange-50 p-3 dark:bg-orange-900/20">
					<p class="text-sm text-orange-800 dark:text-orange-200">
						⚠️ @lang('Twilio charges per message. Consider Evolution API for free messaging.')
					</p>
				</div>

				<form id="storeForm-whatsapp-twilio" action="{{ route('dashboard.chatbot-multi-channel.whatsapp.store') }}">
					<input hidden name="channel" value="whatsapp">
					<input hidden name="credentials[provider]" value="twilio">
					<input hidden name="user_id" value="{{ \Illuminate\Support\Facades\Auth::id() }}">
					
					<div class="mb-3">
						<x-forms.input
							:label="__('Whatsapp sid')"
							name="credentials[whatsapp_sid]"
							size="lg"
							required
						/>
					</div>

					<div class="mb-3">
						<x-forms.input
							:label="__('Whatsapp token')"
							name="credentials[whatsapp_token]"
							size="lg"
							type="password"
							required
						/>
					</div>

					<div class="mb-3">
						<x-forms.input
							:label="__('Whatsapp phone')"
							name="credentials[whatsapp_phone]"
							size="lg"
							required
						/>
					</div>

					<div class="mb-3">
						<x-forms.input
							:label="__('Whatsapp sandbox phone')"
							name="credentials[whatsapp_sandbox_phone]"
							size="lg"
							required
						/>
					</div>

					<div class="mb-3">
						<x-forms.input
							id="whatsapp_environment"
							type="select"
							size="lg"
							name="credentials[whatsapp_environment]"
							label="{{ __('Environment') }}"
						>
							<option value="sandbox">@lang('SANDBOX')</option>
							<option value="production">@lang('PRODUCTION')</option>
						</x-forms.input>
					</div>

					@if ($app_is_demo)
						<x-button
							type="button"
							onclick="return toastr.info('This feature is disabled in Demo version.');"
						>
							{{ __('Add Channel') }}
						</x-button>
					@else
						<x-button
							type="button"
							x-on:click="storeChannel('storeForm-whatsapp-twilio')"
							size="lg"
						>
							<span x-show="storeChannelFetch">Loading...</span>
							<span x-show="!storeChannelFetch">{{ __('Add Channel') }}</span>
						</x-button>
					@endif
				</form>
			</div>
		</div>
	</x-slot:modal>
</x-modal>
