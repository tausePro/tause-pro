@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('AI Chat'))
@section('titlebar_actions')
    @php
        $route = 'dashboard.user.chat-setting.chat-template.create';
        $customChat = \Illuminate\Support\Facades\Route::has($route) && setting('chat_setting_for_customer', 1) == 1;
    @endphp
    @if ($customChat)
        <x-button href="{{ route($route) }}">
            <x-tabler-plus class="size-4" />
            {{ __('New') }}
        </x-button>
    @endif
@endsection

@section('content')
    <div class="py-8">
        <div
            class="mb-8 rounded-3xl bg-foreground/5 p-4"
            style="
				background-image: url({{ custom_theme_url('/assets/img/misc/generators-bg.png') }});
				background-size: 800px;
				background-position: 50% 20px;
				background-repeat: no-repeat;
			"
        >

            <div class="relative mb-4">
                <x-tabler-search class="pointer-events-none absolute start-5 top-1/2 z-1 size-5 -translate-y-1/2" />
                <x-forms.input
                    class="h-14 rounded-full border-none bg-card-background ps-14 shadow-xl shadow-black/5 placeholder:text-foreground"
                    type="search"
                    placeholder="{{ __('Search for smart chatbots') }}"
                    aria-label="{{ __('Search for smart chatbots') }}"
                    size="lg"
                    x-data="{}"
                    @keyup="$store.chatsFilter.setSearchStr($el.value)"
                />
            </div>

            <ul
                class="lqd-filter-list flex scroll-mt-6 flex-wrap items-center justify-center gap-x-4 gap-y-2 text-heading-foreground max-sm:gap-3"
                id="lqd-chats-filter-list"
            >
                <li>
                    <x-button
                        class="lqd-filter-btn inline-flex bg-foreground/5 px-2.5 py-1 text-2xs leading-tight transition-colors hover:translate-y-0 [&.active]:bg-secondary [&.active]:text-secondary-foreground"
                        tag="button"
                        type="button"
                        name="filter"
                        variant="ghost"
                        x-data="{}"
                        ::class="$store.chatsFilter.filter === 'all' && 'active'"
                        @click="$store.chatsFilter.changeFilter('all')"
                    >
                        {{ __('All') }}
                    </x-button>
                </li>
                <li>
                    <x-button
                        class="lqd-filter-btn inline-flex bg-foreground/5 px-2.5 py-1 text-2xs leading-tight transition-colors hover:translate-y-0 [&.active]:bg-secondary [&.active]:text-secondary-foreground"
                        tag="button"
                        type="button"
                        name="filter"
                        variant="ghost"
                        x-data="{}"
                        ::class="$store.chatsFilter.filter === 'favorite' && 'active'"
                        @click="$store.chatsFilter.changeFilter('favorite')"
                    >
                        <span class="sr-only">
                            {{ __('Favorite') }}
                        </span>
                        <x-tabler-star-filled class="size-3.5" />
                    </x-button>
                </li>

                @foreach ($categoryList as $category)
                    <li>
                        <x-button
                            class="lqd-filter-btn inline-flex bg-foreground/5 px-2.5 py-1 text-2xs leading-tight transition-colors hover:translate-y-0 [&.active]:bg-secondary [&.active]:text-secondary-foreground"
                            tag="button"
                            type="button"
                            name="filter"
                            variant="ghost"
                            x-data="{}"
                            ::class="$store.generatorsFilter.filter === '{{ $category->name }}' && 'active'"
                            @click="$store.chatsFilter.changeFilter('{{ $category->name }}')"
                        >
                            {{ __(str()->ucfirst($category->name)) }}
                        </x-button>
                    </li>
                @endforeach
            </ul>
        </div>

        @include('panel.user.openai_chat.components.list')
    </div>
@endsection

@push('script')
    <script>
        let message = @json($message);
        if (message === true) {
            toastr.warning("{{ __('Cannot access premium plan') }}");
        }
        let stream_type = '{!! $settings_two->openai_default_stream_server !!}';
        @if (setting('default_ai_engine', 'openai') == 'anthropic')
            const stream_type = 'backend';
        @endif
    </script>
    <script src="{{ custom_theme_url('/assets/js/panel/openai_chat.js') }}"></script>
@endpush
