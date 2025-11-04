@php
    $filter_check = [];
    foreach ($list as $item) {
        if ($item->active != 1) {
            continue;
        }
        if ($item->filters) {
            foreach (explode(',', $item->filters) as $filter) {
                $filter_check[] = $filter;
            }
        }
    }
    $filter_check = array_unique($filter_check);
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Smart Templates'))

@section('content')
    <div class="py-8">
        <div class="rounded-3xl bg-foreground/[3%] p-5">
            <x-header-search
                class:icon="size-5 text-foreground opacity-100 start-[26px] stroke-2"
                class:input="h-14 bg-card-background placeholder:text-foreground shadow-xl ps-14 shadow-black/5 max-lg:!rounded-full"
                class:input-wrap="max-lg:p-0 max-lg:!bg-transparent"
                class:kbd="hidden"
                class="max-lg:!visible max-lg:relative max-lg:bottom-auto max-lg:left-auto max-lg:opacity-100"
            />

            <ul
                class="lqd-filter-list mt-4 flex scroll-mt-6 flex-wrap items-center justify-center gap-x-4 gap-y-2 text-heading-foreground max-sm:gap-3"
                id="lqd-generators-filter-list"
            >
                <li>
                    <x-button
                        class="lqd-filter-btn inline-flex bg-heading-foreground/[3%] px-2.5 py-1 text-2xs leading-tight transition-all hover:translate-y-0 hover:bg-foreground/5 [&.active]:bg-foreground [&.active]:text-background"
                        data-filter="all"
                        tag="button"
                        type="button"
                        name="filter"
                        variant="ghost"
                        x-data="{}"
                        ::class="$store.generatorsFilter.filter === 'all' && 'active'"
                        @click="$store.generatorsFilter.changeFilter('all')"
                    >
                        {{ __('All') }}
                    </x-button>
                </li>
                <li>
                    <x-button
                        class="lqd-filter-btn inline-flex bg-heading-foreground/[3%] px-2.5 py-1 text-2xs leading-tight transition-all hover:translate-y-0 hover:bg-foreground/5 [&.active]:bg-foreground [&.active]:text-background"
                        data-filter="favorite"
                        tag="button"
                        type="button"
                        name="filter"
                        variant="ghost"
                        x-data="{}"
                        ::class="$store.generatorsFilter.filter === 'favorite' && 'active'"
                        @click="$store.generatorsFilter.changeFilter('favorite')"
                    >
                        <span class="sr-only">
                            {{ __('Favorite') }}
                        </span>
                        <x-tabler-star
                            class="size-4 fill-current"
                            stroke-width="0"
                        />
                    </x-button>
                </li>

                @foreach ($filters as $filter)
                    @if (in_array($filter->name, $filter_check))
                        <li>
                            <x-button
                                class="lqd-filter-btn inline-flex bg-heading-foreground/[3%] px-2.5 py-1 text-2xs leading-tight transition-all hover:translate-y-0 hover:bg-foreground/5 [&.active]:bg-foreground [&.active]:text-background"
                                data-filter="{{ $filter->name }}"
                                tag="button"
                                type="button"
                                name="filter"
                                variant="ghost"
                                x-data="{}"
                                ::class="$store.generatorsFilter.filter === '{{ $filter->name }}' && 'active'"
                                @click="$store.generatorsFilter.changeFilter('{{ $filter->name }}')"
                            >
                                {{ __(str()->ucfirst($filter->name)) }}
                            </x-button>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
        <div
            class="lqd-generators-container pb-8"
            id="lqd-generators-container"
        >
            <div
                class="lqd-generators-list grid grid-cols-2 gap-1.5 lg:grid-cols-3 xl:grid-cols-4"
                id="lqd-generators-list"
            >
                <h2
                    class="col-span-full m-0 mb-5 mt-8 hidden items-center gap-2"
                    data-filter="favorite"
                    x-data="generatorItem"
                    :class="{ hidden: $store.generatorsFilter.filter !== 'favorite', flex: $store.generatorsFilter.filter === 'favorite' }"
                >
                    {{ __('Favorite') }}
                </h2>

                @foreach ($filters as $filter)
                    <h2
                        class="col-span-full m-0 mb-5 mt-8 flex items-center gap-2"
                        id="filter-{{ $filter->name }}"
                        data-filter="{{ $filter->name }}"
                        x-data="generatorItem"
                        :class="{ hidden: isHidden, flex: !isHidden }"
                    >
                        {{ __(str()->ucfirst($filter->name)) }}
                        <span class="flex h-5 items-center justify-center rounded-full border px-2 py-0.5 text-4xs/5 font-medium">
                            {{ $list->where('active', 1)->filter(function ($item) use ($filter) {
                                    return !str()->startsWith($item->slug, 'ai_') && in_array($filter->name, explode(',', $item->filters));
                                })->count() }}
                        </span>
                    </h2>

                    @foreach ($list as $item)
                        @if ($item->active != 1 || str()->startsWith($item->slug, 'ai_') || !in_array($filter->name, explode(',', $item->filters)))
                            @continue
                        @endif
                        <x-generator-item :$item />
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ custom_theme_url('/assets/js/panel/openai_list.js') }}"></script>
@endpush
