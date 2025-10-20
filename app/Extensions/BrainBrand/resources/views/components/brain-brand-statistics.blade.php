@props(['brainBrand'])

<div class="card border-0 shadow-sm">
    <div class="card-header bg-gradient-primary text-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-chart-bar mr-2"></i>{{ __('Brain Brand Statistics') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-database fa-2x text-primary mb-2"></i>
                    <h4 class="mb-1">{{ $brainBrand->embeddings->count() }}</h4>
                    <small class="text-muted">{{ __('Total Embeddings') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h4 class="mb-1">{{ $brainBrand->embeddings->whereNotNull('embedding')->count() }}</h4>
                    <small class="text-muted">{{ __('Trained') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4 class="mb-1">{{ $brainBrand->embeddings->whereNull('embedding')->count() }}</h4>
                    <small class="text-muted">{{ __('Pending') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                    <h4 class="mb-1">{{ $brainBrand->updated_at->diffForHumans() }}</h4>
                    <small class="text-muted">{{ __('Last Updated') }}</small>
                </div>
            </div>
        </div>

        <hr class="my-3">

        <div class="row">
            <div class="col-md-6">
                <h6 class="mb-2">{{ __('Content Types') }}</h6>
                <div class="d-flex flex-wrap gap-2">
                    @if($brainBrand->embeddings->where('type', 'url')->count() > 0)
                        <span class="badge bg-primary">
                            <i class="fas fa-globe mr-1"></i>{{ __('Website') }} ({{ $brainBrand->embeddings->where('type', 'url')->count() }})
                        </span>
                    @endif
                    @if($brainBrand->embeddings->where('type', 'text')->count() > 0)
                        <span class="badge bg-info">
                            <i class="fas fa-keyboard mr-1"></i>{{ __('Text') }} ({{ $brainBrand->embeddings->where('type', 'text')->count() }})
                        </span>
                    @endif
                    @if($brainBrand->embeddings->where('type', 'qa')->count() > 0)
                        <span class="badge bg-success">
                            <i class="fas fa-question-circle mr-1"></i>{{ __('Q&A') }} ({{ $brainBrand->embeddings->where('type', 'qa')->count() }})
                        </span>
                    @endif
                    @if($brainBrand->embeddings->where('type', 'file')->count() > 0)
                        <span class="badge bg-secondary">
                            <i class="fas fa-file mr-1"></i>{{ __('Files') }} ({{ $brainBrand->embeddings->where('type', 'file')->count() }})
                        </span>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="mb-2">{{ __('Brand Configuration') }}</h6>
                <div class="small">
                    @if($brainBrand->tone)
                        <div><strong>{{ __('Tone') }}:</strong> {{ ucfirst($brainBrand->tone) }}</div>
                    @endif
                    @if($brainBrand->personality)
                        <div><strong>{{ __('Personality') }}:</strong> {{ $brainBrand->personality }}</div>
                    @endif
                    @if($brainBrand->brand_voice && $brainBrand->brand_voice['language_style'])
                        <div><strong>{{ __('Language Style') }}:</strong> {{ ucfirst($brainBrand->brand_voice['language_style']) }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>