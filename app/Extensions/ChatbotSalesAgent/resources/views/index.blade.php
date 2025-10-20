@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Sales Agent') . ' - ' . $chatbot->name)
@section('titlebar_subtitle', __('AI-powered sales assistant'))

@section('content')
    <div class="py-10">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('Sales Agent Dashboard') }}</h3>
                            <div class="card-actions">
                                <a
                                    class="btn btn-primary"
                                    href="{{ route('dashboard.chatbot.ecommerce.index', $chatbot) }}"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        fill="none"
                                    >
                                        <path
                                            stroke="none"
                                            d="M0 0h24v24H0z"
                                            fill="none"
                                        />
                                        <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                                        <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                    </svg>
                                    {{ __('E-commerce Settings') }}
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    fill="none"
                                    class="me-2"
                                >
                                    <path
                                        stroke="none"
                                        d="M0 0h24v24H0z"
                                        fill="none"
                                    />
                                    <path d="M12 9v4" />
                                    <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                    <path d="M12 16h.01" />
                                </svg>
                                <strong>{{ __('Sales Agent Enabled!') }}</strong>
                                {{ __('Your chatbot now has an AI-powered sales assistant that helps customers complete purchases directly in the chat.') }}
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h4 class="card-title">{{ count($products) }}</h4>
                                            <p class="text-muted mb-0">{{ __('Products Available') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h4 class="card-title">
                                                @if ($chatbot->woocommerce_enabled && $chatbot->wompi_enabled)
                                                    <span class="text-success">✓ {{ __('Active') }}</span>
                                                @else
                                                    <span class="text-warning">⚠ {{ __('Incomplete') }}</span>
                                                @endif
                                            </h4>
                                            <p class="text-muted mb-0">{{ __('Configuration Status') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h4 class="card-title">
                                                @if ($chatbot->sales_agent_enabled)
                                                    <span class="text-success">✓ {{ __('On') }}</span>
                                                @else
                                                    <span class="text-danger">✗ {{ __('Off') }}</span>
                                                @endif
                                            </h4>
                                            <p class="text-muted mb-0">{{ __('Sales Agent') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <h4>{{ __('How it works') }}</h4>
                                <ol class="mt-3">
                                    <li class="mb-2">{{ __('The AI recommends products based on customer queries') }}</li>
                                    <li class="mb-2">{{ __('Customer clicks on product link in the chat') }}</li>
                                    <li class="mb-2">{{ __('Sales Agent collects shipping and payment information') }}</li>
                                    <li class="mb-2">{{ __('Order is created in WooCommerce') }}</li>
                                    <li class="mb-2">{{ __('Payment link is generated via Wompi') }}</li>
                                    <li>{{ __('Customer completes payment and order is processed') }}</li>
                                </ol>
                            </div>

                            <div class="mt-4">
                                <a
                                    class="btn btn-success"
                                    href="{{ route('dashboard.chatbot.index', $chatbot) }}"
                                >
                                    {{ __('View Chatbot Dashboard') }}
                                </a>
                                <a
                                    class="btn btn-outline-primary"
                                    href="{{ route('dashboard.chatbot.embed.index', $chatbot) }}"
                                    target="_blank"
                                >
                                    {{ __('Test in Chatbot') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

