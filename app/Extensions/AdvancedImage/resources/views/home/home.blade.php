@php
	$generators = [];
	$flux = null;

	$isFalAIEnabled = \App\Helpers\Classes\ApiHelper::setFalAIKey();
	$isIdeogramAvailable = class_exists('App\Extensions\Ideogram\System\IdeogramServiceProvider');

	if (setting('dalle_hidden', 0) == '1') {
		$generators[] = ['value' => 'openai', 'label' => __('DALL-E')];
	}

	if (setting('stable_hidden', 0) !== '1') {
		$generators[] = ['value' => 'stable_diffusion', 'label' => __('Stable Diffusion')];
	}

	if ($isFalAIEnabled) {
		$fluxLabel = setting('fal_ai_default_model') === 'flux-realism'
					? __('Flux Realism Lora')
					: __('Flux Pro');

		$generators[] = ['value' => 'flux-pro', 'label' => $fluxLabel];
		$flux = 'flux-pro';
	}

	if ($isFalAIEnabled && $isIdeogramAvailable) {
		$generators[] = ['value' => 'ideogram', 'label' => 'Ideogram'];
	}

	if (setting('enabled_gpt_image_1', 0) == '1') {
		$generators[] = ['value' => 'gpt-image-1', 'label' => __('GPT image 1')];
	}

	if (setting('enabled_flux_pro_kontext', 1) == '1') {
		$generators[] = ['value' => 'flux-pro-kontext', 'label' => __('Flux Pro Kontext')];
	}

	if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('nano-banana')) {
		$generators[] = ['value' => 'nano-banana', 'label' => __('Nano banana')];
	}
@endphp

<div
    class="lqd-adv-img-editor-home transition-all"
    :class="{
        'opacity-0': currentView !== 'home',
        'invisible': currentView !== 'home',
        'pointer-events-none': currentView !== 'home'
    }"
>
    <div class="container">
        @include('advanced-image::home.generator-form')
        @include('advanced-image::home.advanced-options')
        @if ($app_is_demo)
            @if ($tools)
                @include('advanced-image::home.tools-grid', ['tools' => $tools])
            @endif
        @else
            @include('advanced-image::home.recent-images-grid', ['images' => $images])
        @endif
        @include('advanced-image::home.templates-grid')
        @if ($app_is_demo)
            @include('advanced-image::home.recent-images-grid', ['images' => $images])
        @else
            @if ($tools)
                @include('advanced-image::home.tools-grid', ['tools' => $tools])
            @endif
        @endif
        @include('advanced-image::home.predefined-prompts-grid')
    </div>
</div>
