@php
    use App\Extensions\MarketingBot\System\Models\MarketingCampaign;
    use App\Extensions\MarketingBot\System\Enums\CampaignStatus;
    use App\Extensions\MarketingBot\System\Enums\CampaignType;

    $recentCampaigns = MarketingCampaign::where('user_id', auth()->id())
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->limit(4)
        ->get();
@endphp
<x-card
    class="col-span-full"
    class:body="p-0"
>
    <x-slot:head
        class="flex justify-between gap-1 px-8 py-6"
    >
        <h3 class="m-0">
            {{ __('Recent Campaigns') }}
        </h3>

        {{-- Link to view all campaigns - Updated route --}}
        <x-button
            variant="link"
            href="{{ route('dashboard.user.marketing-bot.dashboard') }}"
        >
            {{ __('More') }}
            {{-- blade-formatter-disable --}}
			<svg class="opacity-50" width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
			{{-- blade-formatter-enable --}}
        </x-button>
    </x-slot:head>

    {{-- Display recent campaigns from database --}}
    @forelse ($recentCampaigns ?? [] as $campaign)
        <div class="group relative flex flex-wrap items-center justify-between gap-1 border-b px-8 py-6 last:border-b-0">
            <div>
                <h3 class="mb-0">
                    {{ $campaign->name }}
                    <x-tabler-arrow-right class="inline size-5 -translate-x-1 align-middle opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100" />
                </h3>
                <p class="mb-0">
                    {{ ucfirst($campaign->type->value) }}
                </p>
            </div>

            <div>
                <div class="flex gap-3.5">
                    {{ $campaign->created_at->format('M j, Y, H:i') }}

                    {{-- Campaign status badge --}}
                    <span @class([
                        'inline-flex items-center gap-1.5 border rounded-full px-2',
                        'text-green-500 border-green-500' =>
                            $campaign->status === CampaignStatus::published,
                        'text-blue-500 border-blue-500' =>
                            $campaign->status === CampaignStatus::scheduled,
                        'text-yellow-500 border-yellow-500' =>
                            $campaign->status === CampaignStatus::running,
                        'text-gray-500 border-gray-500' =>
                            $campaign->status === CampaignStatus::pending,
                    ])>
                        @if ($campaign->status === CampaignStatus::pending)
                            <x-tabler-check class="w-4" />
                        @elseif ($campaign->status === CampaignStatus::scheduled)
                            <x-tabler-clock class="w-4" />
                        @elseif ($campaign->status === CampaignStatus::running)
                            <x-tabler-player-play class="w-4" />
                        @else
                            <x-tabler-circle-dashed class="w-4" />
                        @endif
                        {{ str()->title($campaign->status->value) }}
                    </span>
                </div>
            </div>

            @if ($campaign->type === CampaignType::telegram)
                <a
                    class="absolute inset-0 z-1"
                    href="{{ route('dashboard.user.marketing-bot.telegram-campaign.edit', $campaign->id) }}"
                ></a>
            @elseif($campaign->type === CampaignType::whatsapp)
                <a
                    class="absolute inset-0 z-1"
                    href="{{ route('dashboard.user.marketing-bot.whatsapp-campaign.edit', $campaign->id) }}"
                ></a>
            @endif
        </div>
    @empty
        <div class="px-8 py-6 text-center text-base font-semibold">
            {{ __('No campaigns found') }}
        </div>
    @endforelse
</x-card>
