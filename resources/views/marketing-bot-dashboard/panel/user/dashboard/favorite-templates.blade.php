<x-card
    class="col-span-full w-full"
    class:body="px-8"
    id="templates"
    size="lg"
>
    <x-slot:head
        class="flex justify-between gap-1 px-8 py-6"
    >
        <h3 class="m-0">
            {{ __('Favorite Templates') }}
        </h3>

        <x-button
            variant="link"
            href="{{ route('dashboard.user.openai.list') }}"
        >
            {{ __('More') }}
            {{-- blade-formatter-disable --}}
			<svg class="opacity-50" width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
			{{-- blade-formatter-enable --}}
        </x-button>
    </x-slot:head>

    <div
        class="lqd-docs-container group"
        data-view-mode="grid"
    >
        <div class="lqd-docs-list grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($favoriteOpenAis->take(4) as $entry)
                @php
                    $upgrade = false;
                    if ($entry->premium == 1 && $plan_type === 'regular') {
                        $upgrade = true;
                    }

                    if ($upgrade) {
                        $href = LaravelLocalization::localizeUrl(route('dashboard.user.payment.subscription'));
                    } elseif (isset($entry->slug) && in_array($entry->slug, ['ai_vision', 'ai_ai_chat_image', 'ai_code_generator', 'ai_youtube', 'ai_pdf'])) {
                        $href = LaravelLocalization::localizeUrl(route('dashboard.user.openai.generator.workbook', $entry->slug));
                    } else {
                        $href = LaravelLocalization::localizeUrl(route('dashboard.user.openai.generator', $entry->slug));
                    }
                @endphp
                @if ($upgrade || $entry->active == 1)
                    <a
                        class="lqd-posts-item relative flex w-full flex-col items-start rounded-card !border !p-6 transition-all"
                        href="{{ $href }}"
                    >
                    @else
                        <div class="lqd-posts-item relative flex w-full flex-col items-start rounded-card !border !p-6 transition-all">
                @endif
                <x-lqd-icon
                    class="mb-16 size-12"
                    size="lg"
                    style="background: {{ $entry->color }}"
                    active-badge
                    active-badge-condition="{{ $entry->active == 1 }}"
                >
                    <span class="flex size-5">
                        @if ($entry->image !== 'none')
                            {!! html_entity_decode($entry->image) !!}
                        @endif
                    </span>
                </x-lqd-icon>

                <h3 class="lqd-fav-temp-item-title mb-3">
                    {{ __($entry->title) }}
                </h3>

                <div class="h-full">
                    <span class="lqd-fav-temp-item-desc line-clamp-2 max-w-full text-ellipsis">
                        {{ str()->words(__($entry->description)) }}
                    </span>
                </div>

                @if ($upgrade)
                    <span class="absolute inset-0 flex items-center justify-center bg-background/50">
                        <x-badge
                            class="rounded-md py-1.5"
                            variant="info"
                        >
                            {{ __('Upgrade') }}
                        </x-badge>
                    </span>
                @endif
                @if ($upgrade || $entry->active == 1)
                    </a>
                @else
        </div>
        @endif
        @if ($loop->iteration == 4)
            @break
        @endif
        @endforeach
    </div>
    </div>
</x-card>
