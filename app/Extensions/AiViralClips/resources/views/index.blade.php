@extends('panel.layout.app', [
    'disable_tblr' => true,
    'disable_titlebar' => true,
    'disable_header' => true,
    'disable_footer' => true,
])

@section('title', __('AI Viral Clips'))

@section('content')
    <div class="py-10">
        <div
            class="lqd-external-chatbot-edit"
            x-data="aiInflucencerData"
        >
            @include('ai-viral-clips::create-clips.create-clips-window')
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiInflucencerData', () => ({
                aiClipsWindowKey: 0,
                init() {
                    Alpine.store('aiInflucencerData', this);
                    this.toggleWindow();
                },
                // when open or close the window, change some neccessary css.
                toggleWindow(open = true) {
                    Alpine.nextTick(() => {
                        this.aiClipsWindowKey = 1;
                    })
                    const topNoticeBar = document.querySelector('.top-notice-bar');
                    const navbar = document.querySelector('.lqd-navbar');
                    const pageContentWrap = document.querySelector('.lqd-page-content-wrap');
                    const navbarExpander = document.querySelector('.lqd-navbar-expander');

                    document.documentElement.style.overflow = open ? 'hidden' : '';

                    if (window.innerWidth >= 992) {

                        if (navbar) {
                            navbar.style.position = open ? 'fixed' : '';
                        }

                        if (pageContentWrap && navbar?.offsetWidth > 0) {
                            pageContentWrap.style.paddingInlineStart = open ? 'var(--navbar-width)' : '';
                        }

                        if (topNoticeBar) {
                            topNoticeBar.style.visibility = open ? 'hidden' :
                                '';
                        }

                        if (navbarExpander) {
                            navbarExpander.style.visibility = open ? 'hidden' :
                                '';
                            navbarExpander.style.opacity = open ? 0 : 1;
                        }
                    }
                }
            }))
        });
    </script>
@endpush
