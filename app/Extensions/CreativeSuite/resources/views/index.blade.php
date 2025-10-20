@php
    $google_fonts = \App\Services\Common\FontsService::getGoogleFonts();
    $google_fonts_list = [];

    $prompt_filters = [
        'all' => __('All'),
        'favorite' => __('Favorite'),
    ];

    $templates_list_url = url('/vendor/creative-suite/templates/templates.json?v=' . time());

    $sizes = [
        // Instagram
        'ig_story' => [
            'image' => url('vendor/creative-suite/img/instagram-story.png'),
            'label' => __('Instagram Story/Reels'),
            'width' => 1080,
            'height' => 1920,
            'aspect' => __('Vertical'),
            'featured' => true,
        ],
        'ig_post' => [
            'label' => __('Instagram Post (Square)'),
            'image' => url('vendor/creative-suite/img/instagram-post.png'),
            'width' => 1080,
            'height' => 1080,
            'aspect' => __('Square'),
            'featured' => true,
        ],
        'ig_post_portrait' => [
            'label' => __('Instagram Post (Portrait)'),
            'width' => 1080,
            'height' => 1350,
            'aspect' => __('Vertical'),
        ],
        'ig_feed_ad' => [
            'label' => __('Instagram Feed Ad'),
            'width' => 1080,
            'height' => 1080,
            'aspect' => __('Square'),
        ],

        // Facebook
        'fb_post' => [
            'label' => __('Facebook Feed Post/Ad'),
            'width' => 1200,
            'height' => 628,
            'aspect' => __('Horizontal'),
        ],
        'fb_story' => [
            'label' => __('Facebook Story'),
            'width' => 1080,
            'height' => 1920,
            'aspect' => __('Vertical'),
        ],

        // Twitter (X)
        'tw_header' => [
            'label' => __('Twitter/X Header'),
            'width' => 1500,
            'height' => 500,
            'aspect' => __('Horizontal'),
        ],
        'tw_post' => [
            'label' => __('Twitter/X Post Image'),
            'width' => 1200,
            'height' => 675,
            'aspect' => __('Horizontal'),
        ],

        // YouTube
        'yt_thumb' => [
            'label' => __('YouTube Thumbnail'),
            'image' => url('vendor/creative-suite/img/youtube-thumbnail.png'),
            'width' => 1280,
            'height' => 720,
            'aspect' => __('Horizontal'),
            'featured' => true,
        ],
        'yt_channel' => [
            'label' => __('YouTube Channel Banner'),
            'width' => 2560,
            'height' => 1440,
            'aspect' => __('Horizontal'),
        ],

        // LinkedIn
        'li_feed' => [
            'label' => __('LinkedIn Feed Image'),
            'width' => 1200,
            'height' => 627,
            'aspect' => __('Horizontal'),
        ],
        'li_banner' => [
            'label' => __('LinkedIn Profile Banner'),
            'image' => url('vendor/creative-suite/img/linkedin-profile-banner.png'),
            'width' => 1584,
            'height' => 396,
            'aspect' => __('Horizontal'),
            'featured' => true,
        ],
        'li_story' => [
            'label' => __('LinkedIn Story Ad'),
            'width' => 1080,
            'height' => 1920,
            'aspect' => __('Vertical'),
        ],

        // Google Display Ads
        'g_square' => [
            'label' => __('Google Square Ad'),
            'width' => 250,
            'height' => 250,
            'aspect' => __('Square'),
        ],
        'g_rectangle' => [
            'label' => __('Google Rectangle Ad'),
            'image' => url('vendor/creative-suite/img/google-rectangle-ad.png'),
            'width' => 336,
            'height' => 280,
            'aspect' => __('Horizontal'),
            'featured' => true,
        ],
        'g_leaderboard' => [
            'label' => __('Google Leaderboard Ad'),
            'width' => 728,
            'height' => 90,
            'aspect' => __('Horizontal'),
        ],
        'g_skyscraper' => [
            'label' => __('Google Skyscraper Ad'),
            'width' => 160,
            'height' => 600,
            'aspect' => __('Vertical'),
        ],
        'g_mobile' => [
            'label' => __('Google Mobile Banner'),
            'width' => 320,
            'height' => 100,
            'aspect' => __('Horizontal'),
        ],
    ];

    foreach ($google_fonts['items'] ?? [] as $font) {
        $family = $font['family'];
        $lowercase_family = strtolower($family);

        if (!str_contains($lowercase_family, 'icon') && !str_contains($lowercase_family, 'material symbols')) {
            $google_fonts_list[] = $family;
        }
    }
@endphp

@extends('panel.layout.app', [
    'disable_tblr' => true,
    'disable_header' => true,
    'disable_footer' => true,
    'disable_navbar' => true,
    'disable_default_sidebar' => true,
    'disable_titlebar' => true,
    'layout_wide' => true,
    'disable_mobile_bottom_menu' => true,
])
@section('title', __('Creative Suite'))
@section('titlebar_actions', '')

@push('before-head-close')
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"
    />
@endpush

@section('content')
    <div
        class="pointer-events-none absolute inset-x-0 top-0 z-0 overflow-hidden opacity-30 dark:hidden"
        aria-hidden="true"
    >
        <img
            class="w-full"
            src="{{ custom_theme_url('assets/img/advanced-image/image-editor-bg.jpg') }}"
            alt="Background image"
        >
    </div>
    <div
        class="lqd-creative-suite-wrap relative z-1 [--header-h:60px] [--sidebar-w:80px] [--toolspanel-w:365px] max-lg:[--sidebar-h:94px] max-lg:[--sidebar-w:0px] max-lg:[--toolspanel-w:100vw]"
        x-data="creativeSuite({ assetsUrl: '{{ url(custom_theme_url('/assets')) }}' })"
        :style="{
            '--stage-w': `${stage?.width() ?? 0}px`,
            '--stage-h': `${stage?.height() ?? 0}px`,
            '--zoom-level': (zoomLevel / 100),
            '--zoom-offset-x': `${zoomOffsetX}px`,
            '--zoom-offset-y': `${zoomOffsetY}px`
        }"
        @keyup.escape.window="switchView('<')"
        :class="{ 'overflow-hidden': currentView === 'editor' }"
    >
        @include('creative-suite::includes.top-navbar')
        @include('creative-suite::home.home', ['sizes' => $sizes])
        @include('creative-suite::editor.editor', ['sizes' => $sizes])
        @include('creative-suite::gallery.gallery')

        @include('panel.user.openai_chat.components.prompt_library_modal')
    </div>
@endsection

@push('script')
    <script src="{{ custom_theme_url('assets/libs/konva/konva.min.js') }}"></script>
    <link
        rel="stylesheet"
        href="{{ custom_theme_url('assets/libs/jscolorpicker/dist/colorpicker.css') }}"
    >
    <script src="{{ custom_theme_url('assets/libs/jscolorpicker/dist/colorpicker.iife.min.js') }}"></script>

    <script>
        window.lqdGoogleFontsList = @json($google_fonts_list);
    </script>
@endpush
