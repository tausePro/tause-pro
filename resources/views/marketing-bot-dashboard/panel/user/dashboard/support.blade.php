<div class="text-center">
    <div class="relative mb-6 inline-grid place-items-center rounded-full text-[32px] leading-none">
        <div class="col-start-1 col-end-1 row-start-1 row-end-1 size-[58px] rounded-full bg-[linear-gradient(to_right,var(--gradient-stops))]"></div>
        <div class="col-start-1 col-end-1 row-start-1 row-end-1 inline-grid size-12 place-items-center rounded-full bg-card-background">
            🛟
        </div>
    </div>

    <h2 class="mb-3">
        {{ __('Have a question?') }}
    </h2>

    <p class="mb-5 text-balance">
        {{ __('We\'re here to help! Submit a support request, and our team will get back to you shortly.') }}
    </p>

    <x-button
        class="w-full p-4"
        variant="ghost-shadow"
        href="{{ route('dashboard.support.list') }}"
    >
        {{ __('Submit a ticket') }}
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
</div>
