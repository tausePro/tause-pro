@php
    $user_avatar = Auth::user()->avatar;

    if (!Auth::user()->github_token && !Auth::user()->google_token && !Auth::user()->facebook_token) {
        $user_avatar = '/' . $user_avatar;
    }
    $human_agent_conditions = [
        'When the issue is too complex or ambiguous.',
        'When the customer is frustrated or dissatisfied.',
        'When sensitive topics (legal, financial, medical, etc.) are involved.',
        'When the AI fails to understand after repeated attempts.',
        'When empathy or emotional intelligence is required.',
        'When the request is outside the AI’s scope or permissions.',
        'When the customer explicitly requests a human.',
    ];
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', $setting->site_name . __('Bots'))
@section('titlebar_subtitle')
    {{ __('View and manage external chatbots') }}
@endsection
@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.chatbot.analytics.index') }}"
        variant="ghost-shadow"
    >
        <x-tabler-chart-bar class="size-4" />
        @lang('Analytics')
    </x-button>

    <x-button
        href="#"
        variant="ghost-shadow"
        @click.prevent="$store.externalChatbotHistory.setOpen(true)"
        x-data="{}"
    >
        @lang('Chat History')
    </x-button>

    <x-button
        href="#"
        @click.prevent="$store.externalChatbotEditor.setActiveChatbot('new_chatbot', 1, true);"
        x-data="{}"
    >
        <x-tabler-plus class="size-4" />
        @lang('Add New Chatbot')
    </x-button>
@endsection

@section('content')
    <div class="py-10">
        <div
            class="lqd-external-chatbot-edit"
            x-data="externalChatbotEditor"
            @keydown.escape.window="setActiveChatbot(null)"
        >
            @include('chatbot::home.actions-grid')

            @include('chatbot::home.chatbots-list', ['chatbots' => $chatbots])

            @include('chatbot::home.edit-window.edit-window', ['avatars' => $avatars])
        </div>

        @include('chatbot::home.chats-history.chats-history')
    </div>
@endsection

@push('script')
    <link
        rel="stylesheet"
        href="{{ custom_theme_url('/assets/libs/prism/prism.css') }}"
    />
    <link
        rel="stylesheet"
        href="{{ custom_theme_url('assets/libs/jscolorpicker/dist/colorpicker.css') }}"
    >
    <script src="{{ custom_theme_url('assets/libs/jscolorpicker/dist/colorpicker.iife.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/prism/prism.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/beautify-html.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/markdown-it.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/libs/turndown.js') }}"></script>

    <script>
        (() => {
            document.addEventListener('alpine:init', () => {
                Alpine.data('externalChatbotEditor', () => ({
                    chatbots: @json($chatbots),
                    activeChatbot: {},
                    prevActiveChatbotId: null,
                    editingStep: 1,
                    submittingData: false,
                    // used for the chatbot ui
                    externalChatbot: null,
                    // used for the training tab
                    externalChatbotTraining: null,
                    testIframeWidth: 420,
                    testIframeHeight: 745,
                    defaultFormInputs: {
                        id: '',
                        interaction_type: 'automatic_response',
                        title: '{{ $setting->site_name . __('Bots') }}',
                        bubble_message: '{{ __('Hey there, How can I help you?') }}',
                        welcome_message: '{{ __('Hi, how can I help you?') }}',
                        connect_message: '{{ __('I’ve forwarded your request to a human agent. An agent will connect with you as soon as possible.') }}',
                        instructions: '',
                        do_not_go_beyond_instructions: 0,
                        language: '',
                        ai_model: 'gpt-3.5-turbo',
                        logo: '',
                        avatar: (@json($avatars?->isEmpty() ? [] : $avatars->random()))?.avatar || '{{ $user_avatar }}',
                        color: '#272733',
                        show_logo: true,
                        show_date_and_time: true,
                        show_average_response_time: true,
                        trigger_background: '',
                        trigger_avatar_size: '60px',
                        position: 'right',
                        active: true,
                        footer_link: '',
                        whatsapp_link: '',
                        telegram_link: '',
                        watch_product_tour_link: '',
                        is_email_collect: true,
                        is_contact: true,
                        is_attachment: true,
                        is_emoji: true,
                        is_articles: true,
                        is_links: true,
                        gdpr_enabled: false,
                        gdpr_message: 'We collect and process your data according to GDPR regulations. By continuing, you consent to our data processing practices.',
                        gdpr_required: true,
                        // WooCommerce
                        woocommerce_url: '',
                        woocommerce_key: '',
                        woocommerce_secret: '',
                        woocommerce_enabled: false,
                        // Wompi
                        wompi_public_key: '',
                        wompi_private_key: '',
                        wompi_enabled: false,
                        wompi_environment: 'test',
                        // Sales Agent
                        sales_agent_enabled: false,
                        sales_agent_keywords: ['comprar', 'precio', 'producto', 'catálogo', 'ver productos', 'busco'],
                        header_bg_type: 'color',
                        header_bg_color: '',
                        header_bg_gradient: '',
                        header_bg_image: '',
                        header_bg_image_blob: null,
                        human_agent_conditions: []
                    },
                    formErrors: {},
                    contactInfo: {
                        activeTab: 'details',
                        editMode: false,
                    },
                    mobile: {
                        filtersVisible: false,
                        contactInfoVisible: false,
                    },

                    init() {
                        this.createNewChatObj();
                        this.initFormErrors();

                        Alpine.store('externalChatbotEditor', this);
                    },
                    createNewChatObj() {
                        this.chatbots.data.unshift({
                            ...this.defaultFormInputs,
                            id: 'new_chatbot',
                        })
                    },
                    initFormErrors() {
                        Object.keys(this.defaultFormInputs).forEach(key => {
                            this.formErrors[key] = [];
                        });
                    },
                    setActiveChatbot(chatbotId, step, skipCRUD = false) {
                        const topNoticeBar = document.querySelector('.top-notice-bar');
                        const navbar = document.querySelector('.lqd-navbar');
                        const pageContentWrap = document.querySelector('.lqd-page-content-wrap');
                        const navbarExpander = document.querySelector('.lqd-navbar-expander');

                        const activeChatbotId = this.activeChatbot.id;

                        this.activeChatbot = this.chatbots.data.find(c => c.id === chatbotId) || {
                            id: chatbotId
                        };

                        if (activeChatbotId) {
                            this.prevActiveChatbotId = activeChatbotId;
                        }

                        if (step) {
                            this.setEditingStep(step, skipCRUD);
                        }

                        this.formErrors = {};

                        document.documentElement.style.overflow = this.activeChatbot.id ? 'hidden' : '';

                        if (window.innerWidth >= 992) {

                            if (navbar) {
                                navbar.style.position = this.activeChatbot.id ? 'fixed' : '';
                            }

                            if (pageContentWrap && navbar?.offsetWidth > 0) {
                                pageContentWrap.style.paddingInlineStart = this.activeChatbot.id ? 'var(--navbar-width)' : '';
                            }

                            if (topNoticeBar) {
                                topNoticeBar.style.visibility = this.activeChatbot.id ? 'hidden' :
                                    '';
                            }

                            if (navbarExpander) {
                                navbarExpander.style.visibility = this.activeChatbot.id ? 'hidden' :
                                    '';
                                navbarExpander.style.opacity = this.activeChatbot.id ? 0 : 1;
                            }
                        }
                    },
                    async setEditingStep(step, skipCRUD = false) {
                        const prevStep = this.editingStep;
                        let editingStep = step;

                        if (step === '>') {
                            editingStep = Math.min(4, this.editingStep + 1);
                        } else if (step === '<') {
                            editingStep = Math.max(1, this.editingStep - 1);
                        }

                        if (
                            !skipCRUD &&
                            prevStep !== editingStep &&
                            prevStep === 1 &&
                            this.activeChatbot.id === 'new_chatbot'
                        ) {
                            await this.createNewChatbot();
                            return;
                        }

                        if (
                            !skipCRUD &&
                            prevStep !== editingStep &&
                            (prevStep === 2 || (prevStep === 1 && editingStep === 2)) &&
                            this.activeChatbot.id !== 'new_chatbot'
                        ) {
                            await this.updateChatbot();
                        }

                        if (
                            !skipCRUD &&
                            this.externalChatbotTraining != null &&
                            editingStep === 3 &&
                            this.activeChatbot.id !== 'new_chatbot'
                        ) {
                            this.externalChatbotTraining.fetchEmbeddings();
                        }

                        this.prevEditingStep = this.editingStep;
                        this.editingStep = editingStep;
                    },
                    async toggleChatbotActivation(chatbotId) {
                        const chatbot = this.chatbots.data.find(c => c.id === chatbotId);

                        if (!chatbot) return;

                        await this.updateChatbot(chatbot);
                    },
                    async deleteChatbot(event) {
                        if (!confirm(
                                '{{ __('Are you sure you want to delete this chatbot?') }}')) {
                            return;
                        }

                        const form = event.target;
                        const id = form.elements['id'].value;
                        const chatbotIndex = this.chatbots.data.findIndex(c => c.id == id);

                        this.submittinData = true;

                        const res = await fetch(form.action, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: this.getFormData(this.chatbots.data.at(chatbotIndex))
                        });

                        if (!res.ok) {

                            const data = await res.json();

                            toastr.error(data.message);

                            return;
                        }

                        const data = await res.json();

                        if (data.type !== 'success') {
                            toastr.error(data.message);
                            return;
                        }

                        if (chatbotIndex !== -1) {
                            this.chatbots.data.splice(chatbotIndex, 1);
                        }

                        this.submittingData = false;

                        toastr.clear();
                        toastr.success(data.message ||
                            '{{ __('Chatbot deleted successfully') }}');
                    },
                    training: {
                        activeTab: 'website',
                        setActiveTab(tab) {
                            if (this.activeTab === tab) return;
                            this.activeTab = tab;
                        }
                    },
                    async createNewChatbot() {
                        this.submittingData = true;
                        this.formErrors = {};

                        const res = await fetch('{{ route('dashboard.chatbot.store') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                // 'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: this.getFormData()
                        });
                        const data = await res.json();
                        const {
                            data: chatbotData
                        } = data;

                        this.submittingData = false;

                        if (!res.ok || !chatbotData) {
                            if (data.errors) {
                                this.formErrors = data.errors;
                            } else if (data.message) {
                                toastr.error(data.message);
                            }

                            this.setEditingStep(1, true);
                            return;
                        }

                        this.chatbots.data.shift();

                        this.chatbots.data.unshift({
                            ...this.defaultFormInputs,
                            ...chatbotData
                        });

                        this.setActiveChatbot(chatbotData.id);
                        this.setEditingStep(2, true);
                        this.createNewChatObj();

                        toastr.clear();
                        toastr.success('{{ __('Chatbot created successfully') }}');
                    },
                    async updateChatbot(chatbot) {
                        this.submittingData = true;
                        this.formErrors = {};

                        const res = await fetch('{{ route('dashboard.chatbot.update') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                // Content-Type header'ını kaldırıyoruz, FormData kendi ayarlayacak
                            },
                            body: this.getFormData(chatbot)
                        });

                        const data = await res.json();
                        const {
                            data: chatbotData
                        } = data;

                        this.submittingData = false;

                        if (!res.ok || !chatbotData) {
                            if (data.errors) {
                                this.formErrors = data.errors;
                            } else if (data.message) {
                                toastr.error(data.message);
                            }
                            return;
                        }

                        const chatbotIndex = this.chatbots.data.findIndex(c => c.id === chatbotData.id);

                        if (chatbotIndex !== -1) {
                            this.chatbots.data[chatbotIndex] = {
                                ...this.chatbots.data[chatbotIndex],
                                ...chatbotData
                            };
                        }

                        toastr.clear();
                        toastr.success('{{ __('Chatbot updated successfully') }}');
                    },

                    onHumanAgentConditionsChange(event) {
                        const checkboxEl = event.currentTarget;
                        const conditionValue = checkboxEl.getAttribute('data-condition')?.trim();

                        if (!conditionValue) return;

                        if (!this.activeChatbot.human_agent_conditions) {
                            this.activeChatbot.human_agent_conditions = [];
                        }

                        const existingConditionIndex = this.activeChatbot.human_agent_conditions.findIndex(condition => condition === conditionValue);

                        if (checkboxEl.checked && existingConditionIndex === -1) {
                            this.activeChatbot.human_agent_conditions.push(conditionValue);
                        } else if (!checkboxEl.checked) {
                            this.activeChatbot.human_agent_conditions.splice(existingConditionIndex, 1);
                        }
                    },

                    getFormData(chatbot) {
                        const chatbotData = chatbot || this.activeChatbot;
                        const formData = new FormData();

                        Object.keys(chatbotData).forEach(key => {
                            const value = chatbotData[key];

                            // Dosya kontrolü
                            if (value instanceof File) {
                                formData.append(key, value);
                            }
                            // Array kontrolü (çoklu dosyalar için)
                            else if (Array.isArray(value)) {
                                value.forEach((item, index) => {
                                    if (item instanceof File) {
                                        formData.append(`${key}[${index}]`, item);
                                    } else {
                                        formData.append(`${key}[${index}]`, item);
                                    }
                                });
                            } else if (typeof value === 'boolean') {
                                formData.append(key, value ? 1 : 0);
                            }

                            // Null değerleri atla
                            else if (value !== null && value !== undefined) {
                                formData.append(key, value);
                            }
                        });

                        return formData;
                    }
                }));
            });
        })();
    </script>
@endpush
