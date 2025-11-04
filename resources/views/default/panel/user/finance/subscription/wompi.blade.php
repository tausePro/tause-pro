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
                                    {{ __('You will be redirected to Wompi to complete your payment securely.') }}
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

                            <form method="POST" action="{{ route('dashboard.user.payment.subscription.wompi.process') }}" id="wompi-form">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="coupon" value="{{ request()->get('coupon') }}">
                                
                                <button type="submit" class="btn btn-primary btn-lg w-100" id="wompi-button">
                                    <i class="ti ti-credit-card me-2"></i>
                                    {{ __('Pay with Wompi') }} - ${{ number_format($finalPrice + $taxValue, 0, ',', '.') }} COP
                                </button>
                            </form>

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
    document.getElementById('wompi-form').addEventListener('submit', function(e) {
        const button = document.getElementById('wompi-button');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __("Processing...") }}';
    });
</script>
@endpush
