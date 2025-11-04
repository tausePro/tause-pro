@php
    use App\Domains\Entity\EntityStats;
    $wordModels = EntityStats::word();
    $imageModels = EntityStats::image();

    $team = auth()->user()->getAttribute('team');
    $teamManager = auth()->user()->getAttribute('teamManager');

    $random = random_int(100000, 900000);
@endphp

<x-card
    class="w-full"
    class:body="md:py-9 lg:px-12"
    id="plan"
    data-name="{{ \App\Enums\Introduction::DASHBOARD_THREE }}"
    size="lg"
>
    <x-slot:head
        class="flex justify-between gap-1 px-8 py-6"
    >
        <h3 class="m-0">
            {{ __('Your Plan') }}
        </h3>

        <x-credit-list
            class:modal="m-0"
            showType="button"
            expanded-modal-trigger
            modal-trigger-variant="link"
        >
            <x-slot:trigger_label>
                {{ __('View Credits') }}
                {{-- blade-formatter-disable --}}
                <svg class="opacity-50" width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
				{{-- blade-formatter-enable --}}
            </x-slot:trigger_label>
        </x-credit-list>
    </x-slot:head>

    <div class="mb-4 flex flex-col items-center gap-2.5 text-center font-heading text-xl font-semibold">
        <x-tabler-diamond class="size-5" />
        @if (auth()->user()->activePlan() !== null)
            {{ __('Plan') }}: {{ getSubscriptionName() }}
        @else
            {{ __('No Active Plan') }}
        @endif
    </div>

    <p class="mb-2 text-balance text-center text-xs/5">
        @if ($team && $team?->allow_seats > 0)
            @lang("You have the Team plan which has a remaining balance of <strong class='font-bold '>:word</strong> words and <strong class='font-bold '>:image</strong> images. You can contact your team manager if you need more credits.", ['word' => $wordModels->totalCredits(), 'image' => $imageModels->totalCredits()])
        @else
            @if (auth()->user()->activePlan() !== null)
                {{ __('You have currently') }}
                <strong class="text-heading-foreground">{{ getSubscriptionName() }}</strong>
                {{ __('plan.') }}
                {{ __('Will refill automatically in') }} {{ getSubscriptionDaysLeft() }} {{ __('Days.') }}
                {{ checkIfTrial() === true ? __('You are in Trial time.') : '' }}
            @else
                {{ __('You have no subscription at the moment. Please select a subscription plan or a token pack.') }}
            @endif

            @if ($setting->feature_ai_image)
                {{ __('Total') }}
                <strong class="text-foreground">
                    @formatNumber($wordModels->checkIfThereUnlimited() ? __('Unlimited') : $wordModels->totalCredits())
                </strong>
                {{ __('word and') }}
                <strong class="text-foreground">
                    @formatNumber($imageModels->checkIfThereUnlimited() ? __('Unlimited') : $imageModels->totalCredits())

                </strong>
                {{ __('image tokens left.') }}
            @else
                {{ __('Total') }}
                <strong class="text-heading-foreground">
                    @formatNumber($wordModels->checkIfThereUnlimited() ? __('Unlimited') : $wordModels->totalCredits())
                </strong>
                {{ __('tokens left.') }}
            @endif
        @endif
    </p>

    <p class="m-0 flex flex-wrap items-center justify-between gap-1 border-b py-3">
        {{ __('Image') }}

        <span class="opacity-50">
            @formatNumber($imageModels->checkIfThereUnlimited() ? __('Unlimited') : $imageModels->totalCredits())
        </span>
    </p>

    <p class="mb-4 flex flex-wrap items-center justify-between gap-1 py-3">
        {{ __('Text') }}

        <span class="opacity-50">
            @formatNumber($wordModels->checkIfThereUnlimited() ? __('Unlimited') : $wordModels->totalCredits())
        </span>
    </p>

    <x-button
        class="w-full p-4"
        data-name="{{ \App\Enums\Introduction::SELECT_PLAN }}"
        variant="ghost-shadow"
        href="{{ route('dashboard.user.payment.subscription') }}"
    >
        {{ __('Manage Plan') }}
        <svg
            width="20"
            height="19"
            viewBox="0 0 20 19"
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z"
            />
        </svg>
    </x-button>
</x-card>
