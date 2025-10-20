@extends('panel.layout.app')

@section('title', __('Brain Brand - Centralized Training'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Brain Brand - Centralized Training') }}</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBrainBrandModal">
                            <i class="fas fa-plus"></i> {{ __('Create Brain Brand') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($error))
                        <!-- Error Message -->
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>{{ __('Setup Required') }}</strong><br>
                            {{ $error }}
                        </div>
                    @elseif($brainBrands->count() > 0)
                        <!-- Quick Stats -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-brain fa-2x mb-2"></i>
                                        <h4>{{ $brainBrands->count() }}</h4>
                                        <small>{{ __('Brain Brands') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database fa-2x mb-2"></i>
                                        <h4>{{ $brainBrands->sum(fn($brand) => $brand->embeddings->count()) }}</h4>
                                        <small>{{ __('Total Embeddings') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-robot fa-2x mb-2"></i>
                                        <h4>2</h4>
                                        <small>{{ __('Connected Chatbots') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-sync fa-2x mb-2"></i>
                                        <h4>Auto</h4>
                                        <small>{{ __('Distribution') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Brain Brands Grid -->
                        <div class="row">
                            @foreach($brainBrands as $brainBrand)
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-brain mr-2"></i>{{ $brainBrand->name }}
                                                </h5>
                                                @if($brainBrand->tone)
                                                    <small class="opacity-75">{{ ucfirst($brainBrand->tone) }} tone</small>
                                                @endif
                                            </div>
                                            @if($brainBrand->is_favorite)
                                                <i class="fas fa-star text-warning"></i>
                                            @endif
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">{{ $brainBrand->description ?? __('No description provided') }}</p>
                                            
                                            <!-- Training Status -->
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-database text-primary mr-2"></i>
                                                        <div>
                                                            <small class="text-muted">{{ __('Embeddings') }}</small>
                                                            <div class="fw-bold">{{ $brainBrand->embeddings->count() }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                        <div>
                                                            <small class="text-muted">{{ __('Status') }}</small>
                                                            <div class="fw-bold text-success">{{ __('Active') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Training Types -->
                                            <div class="mb-3">
                                                <small class="text-muted d-block mb-2">{{ __('Training Sources') }}</small>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @if($brainBrand->embeddings->where('url', '!=', null)->count() > 0)
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-globe mr-1"></i>{{ __('Website') }}
                                                        </span>
                                                    @endif
                                                    @if($brainBrand->embeddings->where('file', '!=', null)->count() > 0)
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-file mr-1"></i>{{ __('Documents') }}
                                                        </span>
                                                    @endif
                                                    @if($brainBrand->embeddings->where('type', 'text')->count() > 0)
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-keyboard mr-1"></i>{{ __('Text') }}
                                                        </span>
                                                    @endif
                                                    @if($brainBrand->embeddings->where('type', 'qa')->count() > 0)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-question-circle mr-1"></i>{{ __('Q&A') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Last Updated -->
                                            <div class="text-muted">
                                                <small>
                                                    <i class="fas fa-clock mr-1"></i>
                                                    {{ __('Last updated') }}: {{ $brainBrand->updated_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="row">
                                                <div class="col-6">
                                                    <a href="{{ route('dashboard.user.brain-brand.train', $brainBrand) }}"
                                                       class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-cogs mr-1"></i>{{ __('Train') }}
                                                    </a>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100">
                                                        <i class="fas fa-share-alt mr-1"></i>{{ __('Distribute') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Welcome Section -->
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-brain fa-4x text-primary mb-3"></i>
                                <h2 class="mb-3">{{ __('Welcome to Brain Brand') }}</h2>
                                <p class="lead text-muted mb-4">{{ __('The centralized training system for all your chatbots and agents') }}</p>
                            </div>

                            <!-- Features Grid -->
                            <div class="row mb-5">
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-spider fa-2x text-primary mb-3"></i>
                                            <h6>{{ __('Smart Crawling') }}</h6>
                                            <small class="text-muted">{{ __('Automatically crawl websites with LinkParser') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-share-alt fa-2x text-success mb-3"></i>
                                            <h6>{{ __('Auto Distribution') }}</h6>
                                            <small class="text-muted">{{ __('Train once, use in all chatbots') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-voice fa-2x text-info mb-3"></i>
                                            <h6>{{ __('Brand Voice') }}</h6>
                                            <small class="text-muted">{{ __('Configure tone and personality') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-cogs fa-2x text-warning mb-3"></i>
                                            <h6>{{ __('AI Processing') }}</h6>
                                            <small class="text-muted">{{ __('Generate embeddings automatically') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- How it Works -->
                            <div class="row mb-5">
                                <div class="col-12">
                                    <h4 class="mb-4">{{ __('How Brain Brand Works') }}</h4>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <span class="fw-bold">1</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{ __('Train Your Brand') }}</h6>
                                                    <small class="text-muted">{{ __('Upload docs, crawl websites, add Q&A') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <span class="fw-bold">2</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{ __('AI Processing') }}</h6>
                                                    <small class="text-muted">{{ __('Generate embeddings and train models') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <span class="fw-bold">3</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{ __('Auto Distribution') }}</h6>
                                                    <small class="text-muted">{{ __('All chatbots get the knowledge automatically') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createBrainBrandModal">
                                <i class="fas fa-plus mr-2"></i>{{ __('Create Your First Brain Brand') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Brain Brand Modal -->
<div class="modal fade" id="createBrainBrandModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-brain mr-2"></i>{{ __('Create New Brain Brand') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dashboard.user.brain-brand.store') }}" method="POST" id="createBrainBrandForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag mr-1"></i>{{ __('Brand Name') }}
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="{{ __('e.g., My Company Brand') }}" required>
                                <small class="text-muted">{{ __('Choose a memorable name for your brand') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tone" class="form-label">
                                    <i class="fas fa-palette mr-1"></i>{{ __('Brand Tone') }}
                                </label>
                                <select class="form-control" id="tone" name="tone" required>
                                    <option value="">{{ __('Select tone') }}</option>
                                    <option value="professional">{{ __('Professional') }} - Formal and business-like</option>
                                    <option value="friendly">{{ __('Friendly') }} - Warm and approachable</option>
                                    <option value="casual">{{ __('Casual') }} - Relaxed and informal</option>
                                    <option value="formal">{{ __('Formal') }} - Strict and official</option>
                                    <option value="creative">{{ __('Creative') }} - Innovative and artistic</option>
                                    <option value="witty">{{ __('Witty') }} - Clever and humorous</option>
                                    <option value="dramatic">{{ __('Dramatic') }} - Bold and expressive</option>
                                </select>
                                <small class="text-muted">{{ __('This will influence how your chatbots respond') }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left mr-1"></i>{{ __('Description') }}
                        </label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="{{ __('Describe your brand, what it does, and its main characteristics...') }}"></textarea>
                        <small class="text-muted">{{ __('This helps AI understand your brand better') }}</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="personality" class="form-label">
                                    <i class="fas fa-user mr-1"></i>{{ __('Personality Traits') }}
                                </label>
                                <input type="text" class="form-control" id="personality" name="personality"
                                       placeholder="{{ __('e.g., Helpful, Knowledgeable, Enthusiastic') }}">
                                <small class="text-muted">{{ __('Describe key personality traits') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="language_style" class="form-label">
                                    <i class="fas fa-language mr-1"></i>{{ __('Language Style') }}
                                </label>
                                <select class="form-control" id="language_style" name="language_style">
                                    <option value="conversational">{{ __('Conversational') }} - Natural and chatty</option>
                                    <option value="technical">{{ __('Technical') }} - Precise and detailed</option>
                                    <option value="simple">{{ __('Simple & Clear') }} - Easy to understand</option>
                                    <option value="detailed">{{ __('Detailed') }} - Comprehensive explanations</option>
                                </select>
                                <small class="text-muted">{{ __('How detailed should responses be') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Setup Options -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-rocket mr-1"></i>{{ __('Quick Setup Options') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="auto_crawl" name="auto_crawl" value="1">
                                        <label class="form-check-label" for="auto_crawl">
                                            <strong>{{ __('Enable Auto Crawling') }}</strong>
                                            <small class="d-block text-muted">{{ __('Automatically crawl your website when training') }}</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="auto_distribute" name="auto_distribute" value="1" checked>
                                        <label class="form-check-label" for="auto_distribute">
                                            <strong>{{ __('Auto Distribution') }}</strong>
                                            <small class="d-block text-muted">{{ __('Automatically distribute to all chatbots') }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-brain mr-1"></i>{{ __('Create Brain Brand') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Brain Brand creation form
    const createForm = document.getElementById('createBrainBrandForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = createForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Creating...';
            submitBtn.disabled = true;
            
            // Submit form data
            const formData = new FormData(createForm);
            
            fetch(createForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    return response.json().then(errorData => {
                        console.error('Server error response:', errorData);
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }

                return response.json();
            })
            .then(data => {
                console.log('Success response data:', data);

                if (data.success) {
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message);
                    }

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createBrainBrandModal'));
                    modal.hide();

                    // Redirect to training page
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Reload page to show new Brain Brand
                        window.location.reload();
                    }
                } else {
                    throw new Error(data.message || 'Failed to create Brain Brand');
                }
            })
            .catch(error => {
                console.error('Error creating Brain Brand:', error);

                // Try to get more detailed error information
                let errorMessage = 'Failed to create Brain Brand';
                if (error.message) {
                    errorMessage = error.message;
                }

                // If the error is from a fetch response, try to parse it
                if (error.response) {
                    error.response.json().then(data => {
                        console.error('Server error details:', data);
                        if (data.errors) {
                            const validationErrors = Object.values(data.errors).flat().join('\n');
                            errorMessage = validationErrors;
                        } else if (data.message) {
                            errorMessage = data.message;
                        }

                        showError(errorMessage);
                    }).catch(() => {
                        showError(errorMessage);
                    });
                } else {
                    showError(errorMessage);
                }

                function showError(message) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }

                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }
    
    // Handle distribution buttons
    document.querySelectorAll('button[data-action="distribute"]').forEach(button => {
        button.addEventListener('click', function() {
            const brainBrandId = this.dataset.brainBrandId;
            if (brainBrandId) {
                distributeBrainBrand(brainBrandId);
            }
        });
    });
    
    function distributeBrainBrand(brainBrandId) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Distributing...';
        btn.disabled = true;
        
        // Simulate distribution (replace with real API call)
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-check mr-1"></i>Distributed!';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        }, 3000);
    }
});
</script>
@endsection
