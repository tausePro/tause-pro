@php
	use App\Extensions\MarketingBot\System\Enums\CampaignStatus;
	use App\Extensions\MarketingBot\System\Models\MarketingCampaign;
	use App\Extensions\MarketingBot\System\Models\MarketingMessageHistory;
	use App\Extensions\MarketingBot\System\Models\MarketingConversation;

	$scheduledCampaigns = MarketingCampaign::where('user_id' , auth()->id())->where('status', CampaignStatus::scheduled)->count();
	$totalCampaigns = MarketingCampaign::where('user_id' , auth()->id())->count();
	$newMessages = MarketingMessageHistory::where('user_id' , auth()->id())->where('created_at', '>=', now()->subDays(30))->count();
	$newContacts = MarketingConversation::where('user_id' , auth()->id())->where('created_at', '>=', now()->subDays(30))->count();
@endphp
<div
    class="col-span-full grid w-full grid-cols-1 gap-2.5 md:grid-cols-2 lg:grid-cols-4"
    id="campaign-stats"
>
    <x-card size="lg">
        <h3 class="mb-10 md:mb-20">
            {{ __('Scheduled Campaigns') }}
        </h3>

        <x-number-counter
            class="font-heading text-[34px] font-semibold leading-none"
            value="{{ $scheduledCampaigns }}"
        />
    </x-card>

    <x-card size="lg">
        <h3 class="mb-10 md:mb-20">
            {{ __('Total Campaigns') }}
        </h3>

        <x-number-counter
            class="font-heading text-[34px] font-semibold leading-none"
            value="{{ $totalCampaigns }}"
        />
    </x-card>

    <x-card size="lg">
        <h3 class="mb-10 md:mb-20">
            {{ __('New Messages') }}
        </h3>

        <x-number-counter
            class="font-heading text-[34px] font-semibold leading-none"
            value="{{ $newMessages }}"
        />
    </x-card>

    <x-card size="lg">
        <h3 class="mb-10 md:mb-20">
            {{ __('New Contacts') }}
        </h3>

        <x-number-counter
            class="font-heading text-[34px] font-semibold leading-none"
            value="{{ $newContacts }}"
        />
    </x-card>
</div>
