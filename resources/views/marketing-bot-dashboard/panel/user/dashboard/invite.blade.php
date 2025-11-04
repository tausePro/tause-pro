<figure class="mb-6 flex justify-center">
    <img
        src="{{ custom_theme_url('/assets/img/misc/img-1.png') }}"
        alt="Invite a Friend"
        width="119"
        height="115"
    >
</figure>

<h2 class="mb-3">
    {{ __('Invite a Friend') }}
</h2>

@if (showTeamFunctionality())
    @if ($team && $team?->allow_seats > 0)
        <p class="mx-auto mb-3 text-balance lg:w-8/12">
            {{ __('Add your team members’ email address to start collaborating.') }}
        </p>

        <form
            class="relative"
            @if ($app_is_demo) action="{{ route('dashboard.user.team.invitation.store', $team->id) }}" @else action="#" @endif
            method="post"
        >
            @csrf
            <input
                type="hidden"
                name="team_id"
                value="{{ $team?->id }}"
            >

            <x-forms.input
                class="h-[52px] px-5 placeholder:text-foreground"
                id="email"
                size="lg"
                type="email"
                name="email"
                placeholder="{{ __('Your friend’s email address') }}"
                required
            />
            @if ($app_is_demo)
                <x-button
                    class="absolute end-4 top-1/2 -translate-y-1/2 hover:-translate-y-1/2 hover:scale-110"
                    onclick="return toastr.info('This feature is disabled in Demo version.')"
                    title="{{ __('Send') }}"
                    variant="link"
                >
                    {{-- blade-formatter-disable --}}
					<svg width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
					{{-- blade-formatter-enable --}}
                </x-button>
            @else
                <x-button
                    class="absolute end-4 top-1/2 -translate-y-1/2 hover:-translate-y-1/2 hover:scale-110"
                    data-name="{{ \App\Enums\Introduction::AFFILIATE_SEND }}"
                    type="submit"
                    title="{{ __('Send') }}"
                    variant="link"
                >
                    {{-- blade-formatter-disable --}}
					<svg width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
					{{-- blade-formatter-enable --}}
                </x-button>
            @endif
        </form>
    @else
        <ol class="mb-3 flex flex-col gap-4 text-start text-heading-foreground">
            <li>
                <span class="me-2 inline-flex size-7 items-center justify-center rounded-full bg-primary/10 font-extrabold text-primary">
                    1
                </span>
                {!! __('You <strong>send your invitation link</strong> to your friends.') !!}
            </li>
            <li>
                <span class="me-2 inline-flex size-7 items-center justify-center rounded-full bg-primary/10 font-extrabold text-primary">
                    2
                </span>
                {!! __('<strong>They subscribe</strong> to a paid plan by using your refferral link.') !!}
            </li>
            <li>
                <span class="me-2 inline-flex size-7 items-center justify-center rounded-full bg-primary/10 font-extrabold text-primary">
                    3
                </span>
                @if ($is_onetime_commission)
                    {!! __('From their first purchase, you will begin <strong>earning one-time commissions</strong>.') !!}
                @else
                    {!! __('From their first purchase, you will begin <strong>earning recurring commissions</strong>.') !!}
                @endif
            </li>
        </ol>

        <form
            class="relative"
            id="send_invitation_form"
            onsubmit="return sendInvitationForm();"
        >
            <x-forms.input
                class="h-[52px] px-5 placeholder:text-foreground"
                id="to_mail"
                size="lg"
                type="email"
                name="to_mail"
                placeholder="{{ __('Your friend’s email address') }}"
                required
            />

            <x-button
                class="absolute end-4 top-1/2 -translate-y-1/2 hover:-translate-y-1/2 hover:scale-110"
                id="send_invitation_button"
                type="submit"
                form="send_invitation_form"
                title="{{ __('Send') }}"
                variant="link"
            >
                {{-- blade-formatter-disable --}}
				<svg width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
				{{-- blade-formatter-enable --}}
            </x-button>
        </form>
    @endif
@else
    <p class="mx-auto mb-3 text-balance lg:w-8/12">
        {{ __('Invite your friends and earn lifelong recurring commissions. 💸') }}
    </p>

    <form
        class="relative w-full"
        id="send_invitation_form"
        onsubmit="return sendInvitationForm();"
    >
        <x-forms.input
            class="h-[52px] px-5 placeholder:text-foreground"
            id="to_mail"
            size="lg"
            type="email"
            name="to_mail"
            placeholder="{{ __('Your friend’s email address') }}"
            required
        />

        <x-button
            class="absolute end-4 top-1/2 -translate-y-1/2 hover:-translate-y-1/2 hover:scale-110"
            id="send_invitation_button"
            type="submit"
            form="send_invitation_form"
            title="{{ __('Send') }}"
            variant="link"
        >
            {{-- blade-formatter-disable --}}
				<svg width="20" height="19" viewBox="0 0 20 19" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" xmlns="http://www.w3.org/2000/svg" > <path d="M0.833008 9.49998C0.833008 4.43737 4.93706 0.333313 9.99967 0.333313C15.0623 0.333313 19.1663 4.43737 19.1663 9.49998C19.1663 14.5626 15.0623 18.6666 9.99967 18.6666C4.93706 18.6666 0.833008 14.5626 0.833008 9.49998ZM9.33893 5.16072C9.01349 4.83529 8.48585 4.83529 8.16042 5.16072C7.83498 5.48616 7.83498 6.0138 8.16042 6.33923L11.3212 9.49998L8.16042 12.6607C7.83498 12.9862 7.83498 13.5138 8.16042 13.8392C8.48585 14.1647 9.01349 14.1647 9.33893 13.8392L13.0889 10.0892C13.4144 9.7638 13.4144 9.23616 13.0889 8.91072L9.33893 5.16072Z" /> </svg>
				{{-- blade-formatter-enable --}}
        </x-button>
    </form>
@endif
