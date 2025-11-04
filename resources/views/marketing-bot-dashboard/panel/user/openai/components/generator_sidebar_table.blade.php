@if ($openai->type == 'audio')
    <div class="space-y-10">
        @forelse ($userOpenai as $entry)
            <x-card
                class="bg-background text-sm font-normal leading-6"
                class:body="max-md:p-5"
                size="lg"
            >
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-full bg-foreground/5 p-3 text-heading-foreground lg:flex-nowrap lg:px-6 lg:py-4">
                    <p class="relative m-0 hidden w-full max-w-[200px] truncate lg:block">
                        {{ basename($entry->input) }}
                    </p>
                    <div class="flex grow justify-end gap-2">
                        <div
                            class="data-audio flex grow items-center"
                            data-audio="/{{ $entry->input }}"
                        >
                            <button type="button">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="9"
                                    height="9"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        stroke="none"
                                        d="M0 0h24v24H0z"
                                        fill="none"
                                    ></path>
                                    <path
                                        d="M6 4v16a1 1 0 0 0 1.524 .852l13 -8a1 1 0 0 0 0 -1.704l-13 -8a1 1 0 0 0 -1.524 .852z"
                                        stroke-width="0"
                                        fill="currentColor"
                                    ></path>
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="10"
                                    height="10"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        stroke="none"
                                        d="M0 0h24v24H0z"
                                        fill="none"
                                    ></path>
                                    <path
                                        d="M9 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
                                        stroke-width="0"
                                        fill="currentColor"
                                    ></path>
                                    <path
                                        d="M17 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
                                        stroke-width="0"
                                        fill="currentColor"
                                    ></path>
                                </svg>
                            </button>
                            <div class="audio-preview grow"></div>
                            <span>0:00</span>
                        </div>
                        <x-button
                            class="relative z-10 size-9 shrink-0 border border-foreground/50 shadow-xs"
                            size="none"
                            variant="outline"
                            hover-variant="success"
                            href="/{{ $entry->input }}"
                            target="_blank"
                            title="{{ __('View and edit') }}"
                            download="{{ $entry->input }}"
                        >
                            <x-tabler-download class="size-4" />
                        </x-button>
                        <x-button
                            class="relative z-10 size-9 shrink-0 border border-foreground/50 shadow-xs"
                            size="none"
                            variant="outline"
                            hover-variant="danger"
                            href="{{ route('dashboard.user.openai.documents.image.delete', $entry->slug) }}"
                            onclick="return confirm('Are you sure?')"
                            title="{{ __('Delete') }}"
                        >
                            <x-tabler-x class="size-4" />
                        </x-button>
                    </div>
                </div>
                <p class="lqd-audio-output mb-5">
                    {!! $entry->output !!}
                </p>
                @if ((int) $setting->feature_ai_advanced_editor === 1)
                    <x-button
                        class="w-full"
                        size="lg"
                        variant="ghost-shadow"
                        href="{{ route('dashboard.user.generator.index', $entry->slug) }}"
                    >
                        @lang('Open in AI Editor')
                    </x-button>
                @endif
                <button
                    class="lqd-clipboard-copy absolute -bottom-4 -end-4 inline-flex size-9 items-center justify-center rounded-full border bg-heading-background p-0 text-heading-foreground shadow-lg transition-all hover:-translate-y-[2px] hover:scale-110"
                    data-copy-options='{ "content": ".lqd-audio-output", "contentIn": "<.lqd-card" }'
                    title="{{ __('Copy to clipboard') }}"
                >
                    <span class="sr-only">{{ __('Copy to clipboard') }}</span>
                    <x-tabler-copy
                        class="size-5"
                        stroke-width="1.75"
                    />
                </button>
            </x-card>
        @empty
            <h4>
                {{ __('No entries created yet.') }}
            </h4>
        @endforelse
    </div>
@elseif ($openai->type == 'voiceover')
    <x-table>
        <x-slot:head>
            <tr>
                <th>
                    {{ __('File') }}
                </th>
                <th>
                    {{ __('Language') }}
                </th>
                <th>
                    {{ __('Voice') }}
                </th>
                <th>
                    {{ __('Date') }}
                </th>
                <th>
                    {{ __('Play') }}
                </th>
                <th class="text-end">
                    {{ __('Action') }}
                </th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @forelse ($userOpenai as $entry)
                @if (empty(json_decode($entry->response)))
                    @continue
                @endif
                <tr class="text-2xs">
                    <td>
                        <p class="flex items-center gap-3 text-base font-semibold">
                            <span class="inline-grid size-10 place-items-center rounded-full bg-foreground/5">
                                <svg
                                    width="13"
                                    height="16"
                                    viewBox="0 0 13 16"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <use
                                        href="#music-icon"
                                        fill="currentColor"
                                    ></use>
                                </svg>
                            </span>
                            {{ $entry->title }}
                        </p>
                    </td>
                    <td class="text-3xs">
                        <span class="inline-block rounded-sm bg-heading-foreground/[0.06] px-1.5 py-0.5">
                            @foreach (array_unique(json_decode($entry->response)?->language ?? []) as $lang)
                                @php
                                    $parts = explode('-', $lang);
                                    $countryCode = $parts[1] ?? null;
                                @endphp
                                @if ($countryCode)
                                    {{ country2flag($countryCode) }}
                                @endif
                            @endforeach

                            {{ $lang }}
                        </span>
                    </td>
                    <td>
                        @foreach (array_unique(json_decode($entry->response)?->voices) ?? [] as $voice)
                            {{ getVoiceNames($voice) }}
                        @endforeach
                    </td>
                    <td>
                        <span>{{ $entry->created_at->format('M d, Y') }},
                            <span class="opacity-60">
                                {{ $entry->created_at->format('H:m') }}
                            </span>
                        </span>
                    </td>
                    <td
                        class="data-audio mt-3 flex items-center"
                        data-audio="/uploads/{{ $entry->output }}"
                    >
                        <button type="button">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="9"
                                height="9"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                fill="none"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    stroke="none"
                                    d="M0 0h24v24H0z"
                                    fill="none"
                                ></path>
                                <path
                                    d="M6 4v16a1 1 0 0 0 1.524 .852l13 -8a1 1 0 0 0 0 -1.704l-13 -8a1 1 0 0 0 -1.524 .852z"
                                    stroke-width="0"
                                    fill="currentColor"
                                ></path>
                            </svg>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="10"
                                height="10"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                fill="none"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    stroke="none"
                                    d="M0 0h24v24H0z"
                                    fill="none"
                                ></path>
                                <path
                                    d="M9 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
                                    stroke-width="0"
                                    fill="currentColor"
                                ></path>
                                <path
                                    d="M17 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
                                    stroke-width="0"
                                    fill="currentColor"
                                ></path>
                            </svg>
                        </button>
                        <div class="audio-preview grow"></div>
                        <span>0:00</span>
                    </td>
                    <td class="whitespace-nowrap text-end">
                        <x-button
                            class="relative z-10 size-9"
                            size="none"
                            variant="ghost-shadow"
                            hover-variant="primary"
                            href="/uploads/{{ $entry->output }}"
                            target="_blank"
                            title="{{ __('View and edit') }}"
                        >
                            <x-tabler-download class="size-4" />
                        </x-button>
                        <x-button
                            class="relative z-10 size-9"
                            size="none"
                            variant="danger"
                            href="{{ route('dashboard.user.openai.documents.image.delete', $entry->slug) }}"
                            onclick="return confirm('Are you sure?')"
                            title="{{ __('Delete') }}"
                        >
                            <x-tabler-x class="size-4" />
                        </x-button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('No entries created yet.') }}</td>
                </tr>
            @endforelse

        </x-slot:body>

    </x-table>

    <div class="float-right m-4">
        {{ $userOpenai->withPath(route('dashboard.user.openai.generator', 'ai_voiceover'))->links('pagination::bootstrap-5-alt') }}
    </div>
@elseif ($openai->type == \App\Domains\Entity\Enums\EntityEnum::ISOLATOR->value)
    <x-table>
        <x-slot:head>
            <tr>
                <th>
                    {{ __('File') }}
                </th>
                <th>
                    {{ __('Info') }}
                </th>
                <th>
                    {{ __('Play') }}
                </th>
                <th class="text-end">
                    {{ __('Action') }}
                </th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @forelse ($userOpenai as $entry)
                @if (empty(json_decode($entry->response)))
                    @continue
                @endif
                <tr class="text-2xs">
                    <td class="flex items-center gap-3">
                        <x-button
                            class="relative z-10 size-9"
                            size="none"
                            variant="secondary"
                        >
                            <x-tabler-speakerphone class="size-4" />
                        </x-button>
                        {{ $entry->title }}
                    </td>
                    <td>
                        <span>{{ $entry->created_at->format('M d, Y') }},
                            <span class="opacity-60">
                                @php
                                    $size = filesize(public_path('uploads/' . $entry->output));
                                    $size = $size / 1024;
                                    if ($size < 1024) {
                                        echo round($size, 2) . ' KB';
                                    } else {
                                        echo round($size / 1024, 2) . ' MB';
                                    }
                                @endphp
                            </span>
                        </span>
                    </td>
                    <td
                        class="data-audio mt-3 flex items-center"
                        data-audio="/uploads/{{ $entry->output }}"
                    >
                        <button type="button">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="9"
                                height="9"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                fill="none"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    stroke="none"
                                    d="M0 0h24v24H0z"
                                    fill="none"
                                ></path>
                                <path
                                    d="M6 4v16a1 1 0 0 0 1.524 .852l13 -8a1 1 0 0 0 0 -1.704l-13 -8a1 1 0 0 0 -1.524 .852z"
                                    stroke-width="0"
                                    fill="currentColor"
                                ></path>
                            </svg>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="10"
                                height="10"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                fill="none"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    stroke="none"
                                    d="M0 0h24v24H0z"
                                    fill="none"
                                ></path>
                                <path
                                    d="M9 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
                                    stroke-width="0"
                                    fill="currentColor"
                                ></path>
                                <path
                                    d="M17 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
                                    stroke-width="0"
                                    fill="currentColor"
                                ></path>
                            </svg>
                        </button>
                        <div class="audio-preview grow"></div>
                        <span>0:00</span>
                    </td>
                    <td class="whitespace-nowrap text-end">
                        <x-button
                            class="relative z-10 size-9"
                            size="none"
                            variant="ghost-shadow"
                            hover-variant="primary"
                            href="/uploads/{{ $entry->output }}"
                            target="_blank"
                            title="{{ __('View and edit') }}"
                        >
                            <x-tabler-download class="size-4" />
                        </x-button>
                        <x-button
                            class="relative z-10 size-9"
                            size="none"
                            variant="danger"
                            href="{{ route('dashboard.user.openai.documents.image.delete', $entry->slug) }}"
                            onclick="return confirm('Are you sure?')"
                            title="{{ __('Delete') }}"
                        >
                            <x-tabler-x class="size-4" />
                        </x-button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('No entries created yet.') }}</td>
                </tr>
            @endforelse

        </x-slot:body>

    </x-table>

    <div class="float-right m-4">
        {{ $userOpenai->withPath(route('dashboard.user.openai.generator', 'ai_voiceover'))->links('pagination::bootstrap-5-alt') }}
    </div>
@else
    <x-table>
        <x-slot:head>
            <tr>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Result') }}</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @forelse ($userOpenai as $entry)
                <tr>
                    <td>
                        <span
                            class="inline-flex size-11 items-center justify-center rounded-full bg-cover bg-center [&_svg]:h-[20px] [&_svg]:w-[20px]"
                            style="background: {{ $entry->generator->color }}"
                        >
                            @if ($entry->generator->image !== 'none')
                                {!! html_entity_decode($entry->generator->image) !!}
                            @endif
                        </span>
                    </td>
                    @if ($openai->type == 'text')
                        <td>
                            {!! $entry->output !!}
                        </td>
                    @elseif($openai->type == 'code')
                        <td>
                            <div class="mt-4 min-h-full border-t pt-8">
                                <div
                                    class="line-numbers min-h-full resize [direction:ltr] [&_kbd]:inline-flex [&_kbd]:rounded [&_kbd]:bg-primary/10 [&_kbd]:px-1 [&_kbd]:py-0.5 [&_kbd]:font-semibold [&_kbd]:text-primary [&_pre[class*=language]]:my-4 [&_pre[class*=language]]:rounded"
                                    id="code-pre"
                                >
                                    <div
                                        class="prose dark:prose-invert"
                                        id="code-output"
                                    >{{ $entry->output }}</div>
                                </div>
                            </div>
                        </td>
                    @else
                        <td>
                            {{ $entry->output }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="2">{{ __('No entries created yet.') }}</td>
                </tr>
            @endforelse
        </x-slot:body>
    </x-table>
@endif

<svg
    class="hidden"
    width="13"
    height="16"
    viewBox="0 0 13 16"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
>
    <path
        id="music-icon"
        d="M12.0065 1.08453C11.9013 1.00353 11.7789 0.948008 11.6487 0.922314C11.5185 0.89662 11.3841 0.901455 11.2561 0.93644L4.63803 2.71546C4.4084 2.77714 4.20577 2.91338 4.06201 3.10276C3.91824 3.29215 3.84149 3.52394 3.84381 3.76169V11.2656C3.84381 11.4894 3.69905 11.6826 3.47492 11.758L3.47094 11.7597L1.74438 12.3573C1.08397 12.5781 0.656312 13.1914 0.656312 13.9189C0.653893 14.2148 0.722713 14.5069 0.856959 14.7706C0.991205 15.0343 1.18694 15.2618 1.42762 15.4339C1.73756 15.6591 2.11068 15.7806 2.49377 15.7812C2.69419 15.7809 2.89323 15.748 3.08313 15.6839L3.09575 15.6796L3.8209 15.416C4.13546 15.311 4.40927 15.1101 4.60392 14.8417C4.79858 14.5732 4.90431 14.2505 4.90631 13.9189V6.8798C4.90631 6.63775 5.06469 6.44119 5.31006 6.37943L5.31703 6.37744L11.1153 4.82884C11.1349 4.82378 11.1554 4.82327 11.1752 4.82734C11.195 4.8314 11.2136 4.83994 11.2297 4.85231C11.2457 4.86467 11.2587 4.88053 11.2676 4.89868C11.2766 4.91682 11.2812 4.93677 11.2813 4.95701V9.66953C11.2813 9.89365 11.1402 10.0806 10.9124 10.1569L10.9041 10.1599L9.21442 10.7612C8.88582 10.8693 8.60012 11.0791 8.39867 11.3603C8.19722 11.6415 8.09045 11.9795 8.09381 12.3254C8.09085 12.6222 8.15939 12.9154 8.29364 13.18C8.42789 13.4447 8.62391 13.6732 8.86512 13.8461C9.10128 14.0169 9.37508 14.1282 9.66337 14.1707C9.95165 14.2131 10.2459 14.1855 10.5213 14.0902L10.5332 14.0862L11.2584 13.8222C11.5729 13.7173 11.8467 13.5165 12.0414 13.2481C12.236 12.9797 12.3418 12.657 12.3438 12.3254V1.76951C12.3445 1.63699 12.3144 1.50613 12.2559 1.38725C12.1973 1.26837 12.112 1.16473 12.0065 1.08453Z"
    />
</svg>
