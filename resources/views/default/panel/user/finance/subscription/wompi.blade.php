@extends('panel.layout.app')
@section('title', __('Subscription Payment'))
@section('titlebar_actions', '')

@section('content')
    <!-- Page body -->
    <div class="py-10">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-sm-8 col-lg-8">
                    @include('panel.user.finance.coupon.index')
                    
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title mb-4">{{ __('Complete your payment with Wompi') }}</h3>
                            
                            <div class="mb-3">
                                <p class="text-muted">
                                    {{ __('Complete your payment securely without leaving this page.') }}
                                </p>
                                <p class="text-muted">
                                    {{ __('Available payment methods:') }}
                                </p>
                                <ul class="text-muted">
                                    <li>{{ __('Credit/Debit Card') }}</li>
                                    <li>{{ __('Nequi') }}</li>
                                    <li>{{ __('PSE (Bank Transfer)') }}</li>
                                    <li>{{ __('Bancolombia Transfer') }}</li>
                                </ul>
                            </div>

                            <!-- Wompi Widget -->
                            <div class="text-center">
                                <form>
                                    <script 
                                        src="https://checkout.wompi.co/widget.js"
                                        data-render="button"
                                        data-public-key="{{ $widgetData['public_key'] }}"
                                        data-currency="{{ $widgetData['currency'] }}"
                                        data-amount-in-cents="{{ $widgetData['amount_in_cents'] }}"
                                        data-reference="{{ $widgetData['reference'] }}"
                                        data-signature:integrity="{{ $widgetData['integrity_signature'] }}"
                                        data-redirect-url="{{ $widgetData['redirect_url'] }}"
                                        data-customer-data:email="{{ Auth::user()->email }}"
                                        data-customer-data:full-name="{{ Auth::user()->name }}"
                                        @if(Auth::user()->phone)
                                        data-customer-data:phone-number="{{ Auth::user()->phone }}"
                                        data-customer-data:phone-number-prefix="+57"
                                        @endif
                                    >
                                    </script>
                                </form>
                            </div>

                            <p class="mt-3 text-center text-muted small">
                                {{ __('Amount to pay:') }} <strong>${{ number_format($finalPrice + $taxValue, 0, ',', '.') }} COP</strong>
                            </p>

                            <p class="mt-3 text-center">
                                {{ __('By purchasing you confirm our') }} 
                                <a href="{{ url('/') . '/terms' }}">{{ __('Terms and Conditions') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-4 col-lg-4">
                    @include('panel.user.finance.partials.plan_card')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    // Wompi Widget handles the payment flow automatically
    // When payment is completed, user will be redirected to the redirect_url
    console.log('Wompi Widget Configuration:', {
        publicKey: '{{ $widgetData['public_key'] }}',
        currency: '{{ $widgetData['currency'] }}',
        amountInCents: {{ $widgetData['amount_in_cents'] }},
        reference: '{{ $widgetData['reference'] }}',
        integritySignature: '{{ $widgetData['integrity_signature'] }}'
    });
</script>
@endpush
