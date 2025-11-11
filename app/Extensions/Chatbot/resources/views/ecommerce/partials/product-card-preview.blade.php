{{-- Product Card Preview Component --}}
<div 
    class="enhanced-product-card"
    style="
        border: 2px solid {{ $cardConfig['card_border_color'] ?? '#e5e7eb' }};
        border-radius: {{ $cardConfig['card_border_radius'] ?? '0.75rem' }};
        box-shadow: {{ match($cardConfig['card_shadow'] ?? 'md') {
            'none' => 'none',
            'sm' => '0 1px 2px rgba(0,0,0,0.05)',
            'md' => '0 4px 6px rgba(0,0,0,0.1)',
            'lg' => '0 10px 15px rgba(0,0,0,0.1)',
            default => '0 4px 6px rgba(0,0,0,0.1)'
        } }};
        overflow: hidden;
        background: white;
        max-width: 200px;
    "
>
    {{-- Imagen --}}
    <img 
        src="https://via.placeholder.com/200x130?text=Muletas+Aluminio"
        alt="Preview"
        style="width: 100%; height: 130px; object-fit: cover;"
    />
    
    {{-- Contenido --}}
    <div style="padding: 0.75rem;">
        {{-- Nombre --}}
        <div style="font-weight: 600; font-size: 0.85rem; color: #1f2937; margin-bottom: 0.5rem;">
            {{ __('Aluminum Crutches') }}
        </div>
        
        {{-- Precio --}}
        <div 
            style="
                font-size: 1.1rem; 
                font-weight: 700; 
                margin-bottom: 0.5rem;
                color: {{ $cardConfig['price_color'] ?? '#10b981' }};
            "
        >
            $89.900 COP
        </div>
        
        {{-- Badge de Descuento --}}
        @if($cardConfig['show_discount_badge'] ?? true)
            <div 
                style="
                    display: inline-block; 
                    color: white; 
                    padding: 0.15rem 0.4rem; 
                    border-radius: 0.25rem; 
                    font-size: 0.7rem; 
                    font-weight: 600; 
                    margin-bottom: 0.5rem;
                    background-color: {{ $cardConfig['discount_badge_color'] ?? '#ef4444' }};
                "
            >
                -15%
            </div>
        @endif
        
        {{-- Stock Indicator --}}
        @if($cardConfig['show_stock_indicator'] ?? true)
            <div style="font-size: 0.7rem; color: #10b981; margin-bottom: 0.5rem; font-weight: 600;">
                ✓ {{ __('In Stock') }}
            </div>
        @endif
        
        {{-- Botón Comprar --}}
        <button 
            style="
                width: 100%; 
                border: none; 
                padding: 0.6rem; 
                border-radius: 0.5rem; 
                font-weight: 600; 
                font-size: 0.85rem; 
                cursor: pointer;
                transition: all 0.2s;
                @if(($cardConfig['button_style'] ?? 'solid') === 'solid')
                    background-color: {{ $cardConfig['button_color'] ?? '#10b981' }};
                    color: {{ $cardConfig['button_text_color'] ?? '#ffffff' }};
                    border: none;
                @elseif(($cardConfig['button_style'] ?? 'solid') === 'outline')
                    background-color: transparent;
                    color: {{ $cardConfig['button_color'] ?? '#10b981' }};
                    border: 2px solid {{ $cardConfig['button_color'] ?? '#10b981' }};
                @elseif(($cardConfig['button_style'] ?? 'solid') === 'gradient')
                    background: linear-gradient(135deg, {{ $cardConfig['button_color'] ?? '#10b981' }} 0%, {{ $cardConfig['button_color'] ?? '#10b981' }}dd 100%);
                    color: {{ $cardConfig['button_text_color'] ?? '#ffffff' }};
                    border: none;
                @endif
            "
            onmouseover="this.style.opacity='0.9'"
            onmouseout="this.style.opacity='1'"
        >
            🛒 {{ __('Buy') }}
        </button>
    </div>
</div>
