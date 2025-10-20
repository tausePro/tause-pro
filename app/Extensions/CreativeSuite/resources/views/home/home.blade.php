@php
    $generators = [];

    if (setting('dalle_hidden', 0) == '1') {
        $generators[] = ['value' => 'openai', 'label' => __('DALL-E')];
    }

    if (setting('stable_hidden', 0) !== '1') {
        $generators[] = ['value' => 'stable_diffusion', 'label' => __('Stable Diffusion')];
    }

    if (setting('enabled_gpt_image_1', 0) == '1') {
        $generators[] = ['value' => 'gpt-image-1', 'label' => __('GPT image 1')];
    }
@endphp

<div
    class="lqd-cs-home pt-[--header-h] transition-all"
    :class="{
        'opacity-0 invisible pointer-events-none overflow-hidden': currentView !== 'home',
    }"
>
    <div class="container">
        @include('creative-suite::home.includes.generator-form')
        @include('creative-suite::home.includes.recent-grid')
        @include('creative-suite::home.includes.templates-grid')
        @include('creative-suite::home.includes.predefined-artboard')
    </div>
</div>
