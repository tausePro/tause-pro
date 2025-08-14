@php
    $cart_color = in_array($item['id'], $cartExists) ? 'text-green-500' : 'text-foreground';
@endphp

@if (!$item['licensed'] && $item['price'] && $item['is_buy'] && !$item['only_show'])
    @if ($app_is_not_demo)
        @if (isset($item['parent']))
            @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered($item['parent']['slug']))
                @if ($item['only_premium'])
                    <div
                        class="inset-0 z-1"
                        @if ($item['check_subscription']) data-toogle="cart"
							data-url="{{ route('dashboard.admin.marketplace.cart.add-delete', $item['id']) }}"
						@else
							onclick="return toastr.info('This extension is for premium customers only.')" @endif
                    >
                        <a href="#">
                            <x-tabler-shopping-cart
                                id="{{ $item['id'] . '-icon' }}"
                                @class(['size-9 rounded border p-1', $cart_color])
                            />
                        </a>
                    </div>
                @else
                    <div
                        class="inset-0 z-1"
                        data-toogle="cart"
                        data-url="{{ route('dashboard.admin.marketplace.cart.add-delete', $item['id']) }}"
                    >
                        <a href="#">
                            <x-tabler-shopping-cart
                                id="{{ $item['id'] . '-icon' }}"
                                @class(['size-9 rounded border p-1', $cart_color])
                            />
                        </a>
                    </div>
                @endif
            @else
                <div
                    class="inset-0 z-1"
                    onclick="return toastr.info('{{ $item['parent']['message'] }}')"
                >
                    <a href="#">
                        <x-tabler-shopping-cart
                            id="{{ $item['id'] . '-icon' }}"
                            @class(['size-9 rounded border p-1', $cart_color])
                        />
                    </a>
                </div>
            @endif
        @else
            @if ($item['only_premium'])
                <div
                    class="inset-0 z-1"
                    @if ($item['check_subscription']) data-toogle="cart"
						data-url="{{ route('dashboard.admin.marketplace.cart.add-delete', $item['id']) }}"
					@else
						onclick="return toastr.info('This extension is for premium customers only.')" @endif
                >
                    <a href="#">
                        <x-tabler-shopping-cart
                            id="{{ $item['id'] . '-icon' }}"
                            @class(['size-9 rounded border p-1', $cart_color])
                        />
                    </a>
                </div>
            @else
                <div
                    class="inset-0 z-1"
                    data-toogle="cart"
                    data-url="{{ route('dashboard.admin.marketplace.cart.add-delete', $item['id']) }}"
                >
                    <a href="#">
                        <x-tabler-shopping-cart
                            id="{{ $item['id'] . '-icon' }}"
                            @class(['size-9 rounded border p-1', $cart_color])
                        />
                    </a>
                </div>
            @endif
        @endif
    @endif
@endif
