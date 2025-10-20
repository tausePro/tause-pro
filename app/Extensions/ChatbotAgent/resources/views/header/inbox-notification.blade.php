<x-button
    {{ $attributes->twMerge('lqd-light-dark-switch size-6 max-lg:hidden max-lg:size-10 flex items-center justify-center hover:bg-transparent max-lg:rounded-full max-lg:border max-lg:dark:bg-white/[3%] relative') }}
    size="none"
    href="{{ route('dashboard.chatbot-agent.index') }}"
    title="{{ __('Human Agent') }}"
    variant="link"
    x-data="{}"
>
    <span
        class="absolute -right-0.5 -top-0.5 flex hidden h-3 w-3 items-center justify-center rounded-full bg-red-500 text-[8px] text-white"
        id="inbox-notification-chatbot-agent"
    >0</span>
    <x-tabler-inbox
        class="size-5 dark:block"
        stroke-width="1.5"
    />
</x-button>

@push('script')
    @if (config('chatbot.notification_enabled', true))
        <script>
            $(document).ready(function() {
                // Function to fetch the notification count
                function fetchNotificationCount() {
                    $.ajax({
                        url: '{{ route('dashboard.chatbot-agent.notification.count') }}',
                        type: 'GET',
                        success: function(data) {
                            let inboxNotification = $('#inbox-notification-chatbot-agent');

                            if (data.count > 0) {
                                inboxNotification.text(data.count);
                                inboxNotification.removeClass('hidden')
                            } else {
                                inboxNotification.addClass('hidden')
                            }
                        },
                        error: function() {
                            console.error('Error fetching notification count');
                        }
                    });
                }

                // Fetch the notification count on page load
                fetchNotificationCount();

                // Optionally, you can set an interval to refresh the count periodically
                setInterval(fetchNotificationCount, 3000); // Refresh every minute
            });
        </script>
    @endif
@endpush
