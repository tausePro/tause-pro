@extends('panel.layout.app')

@section('title', __('Train Brain Brand: ') . $brainBrand->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('Train Brain Brand: ') . $brainBrand->name }}
                        <small class="text-muted">{{ $brainBrand->description }}</small>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('dashboard.user.brain-brand.statistics', $brainBrand) }}" class="btn btn-info btn-sm mr-2">
                            <i class="fas fa-chart-bar"></i> {{ __('Statistics') }}
                        </a>
                        <a href="{{ route('dashboard.user.brain-brand.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back to Brain Brands') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Brand Voice Configuration -->
                    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border">
                        <h4 class="text-lg font-semibold mb-3 text-blue-900">
                            <i class="fas fa-brain mr-2"></i>{{ __('Brand Voice Configuration') }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Brand Tone') }}</label>
                                <select class="form-control" id="brandTone">
                                    <option value="professional">{{ __('Professional') }}</option>
                                    <option value="friendly">{{ __('Friendly') }}</option>
                                    <option value="casual">{{ __('Casual') }}</option>
                                    <option value="formal">{{ __('Formal') }}</option>
                                    <option value="creative">{{ __('Creative') }}</option>
                                    <option value="witty">{{ __('Witty') }}</option>
                                    <option value="dramatic">{{ __('Dramatic') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Personality') }}</label>
                                <input type="text" class="form-control" id="brandPersonality" placeholder="{{ __('e.g., Helpful, Knowledgeable, Enthusiastic') }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Language Style') }}</label>
                                <select class="form-control" id="languageStyle">
                                    <option value="conversational">{{ __('Conversational') }}</option>
                                    <option value="technical">{{ __('Technical') }}</option>
                                    <option value="simple">{{ __('Simple & Clear') }}</option>
                                    <option value="detailed">{{ __('Detailed') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Training Tabs -->
                    <ul class="nav nav-tabs" id="trainingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="url-tab" data-bs-toggle="tab" data-bs-target="#url" type="button" role="tab">
                                <i class="fas fa-globe"></i> {{ __('Website Crawling') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="file-tab" data-bs-toggle="tab" data-bs-target="#file" type="button" role="tab">
                                <i class="fas fa-file-upload"></i> {{ __('Document Upload') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="text-tab" data-bs-toggle="tab" data-bs-target="#text" type="button" role="tab">
                                <i class="fas fa-keyboard"></i> {{ __('Manual Text') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="qa-tab" data-bs-toggle="tab" data-bs-target="#qa" type="button" role="tab">
                                <i class="fas fa-question-circle"></i> {{ __('Q&A Knowledge') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="distribution-tab" data-bs-toggle="tab" data-bs-target="#distribution" type="button" role="tab">
                                <i class="fas fa-share-alt"></i> {{ __('Distribution') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="embeddings-tab" data-bs-toggle="tab" data-bs-target="#embeddings" type="button" role="tab">
                                <i class="fas fa-cogs"></i> {{ __('Process & Train') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="trainingTabsContent">
                        <!-- Website Crawling -->
                        <div class="tab-pane fade show active" id="url" role="tabpanel">
                            <div class="mt-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-spider text-primary mr-2"></i>{{ __('Intelligent Website Crawling') }}
                                </h5>
                                <p class="text-muted mb-4">{{ __('Automatically crawl and extract content from websites. Perfect for training your Brain Brand with real website content.') }}</p>
                                
                                <form id="urlTrainingForm">
                                    <div class="row mb-4">
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-link"></i>
                                                </span>
                                                <input type="url" class="form-control" id="trainingUrl" 
                                                       placeholder="{{ __('https://example.com or https://example.com/products') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-spider mr-2"></i>{{ __('Start Crawling') }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Advanced Options -->
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h6 class="mb-0">{{ __('Advanced Crawling Options') }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="singlePage" checked>
                                                        <label class="form-check-label" for="singlePage">
                                                            <strong>{{ __('Single Page Only') }}</strong>
                                                            <small class="d-block text-muted">{{ __('Crawl only the specified URL') }}</small>
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="followLinks">
                                                        <label class="form-check-label" for="followLinks">
                                                            <strong>{{ __('Follow Internal Links') }}</strong>
                                                            <small class="d-block text-muted">{{ __('Crawl related pages automatically') }}</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ __('Max Pages') }}</label>
                                                        <input type="number" class="form-control" id="maxPages" value="10" min="1" max="50">
                                                        <small class="text-muted">{{ __('Maximum number of pages to crawl') }}</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ __('Content Filter') }}</label>
                                                        <select class="form-control" id="contentFilter">
                                                            <option value="all">{{ __('All Content') }}</option>
                                                            <option value="products">{{ __('Products Only') }}</option>
                                                            <option value="articles">{{ __('Articles/Blog Posts') }}</option>
                                                            <option value="faq">{{ __('FAQ/Help Pages') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div id="crawlingProgress" class="mt-4" style="display: none;">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('Crawling in progress...') }}</span>
                                            <span id="progressText">0%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div id="crawlingLog" class="mt-3 p-3 bg-dark text-light rounded" style="max-height: 200px; overflow-y: auto;">
                                            <small>{{ __('Starting crawl...') }}</small>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="tab-pane fade" id="file" role="tabpanel">
                            <div class="mt-4">
                                <h5>{{ __('Upload File') }}</h5>
                                <p class="text-muted">{{ __('Upload PDF, DOC, TXT, or Excel files for training') }}</p>
                                <form id="fileTrainingForm" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="trainingFile" accept=".pdf,.doc,.docx,.txt,.xlsx,.xls,.csv" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> {{ __('Upload & Train') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Text Input -->
                        <div class="tab-pane fade" id="text" role="tabpanel">
                            <div class="mt-4">
                                <h5>{{ __('Text Input') }}</h5>
                                <p class="text-muted">{{ __('Enter text content directly for training') }}</p>
                                <form id="textTrainingForm">
                                    <div class="mb-3">
                                        <label for="textTitle" class="form-label">{{ __('Title') }}</label>
                                        <input type="text" class="form-control" id="textTitle" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="textContent" class="form-label">{{ __('Content') }}</label>
                                        <textarea class="form-control" id="textContent" rows="10" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> {{ __('Save Text') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Q&A -->
                        <div class="tab-pane fade" id="qa" role="tabpanel">
                            <div class="mt-4">
                                <h5>{{ __('Question & Answer') }}</h5>
                                <p class="text-muted">{{ __('Add question and answer pairs for training') }}</p>
                                <form id="qaTrainingForm">
                                    <div class="mb-3">
                                        <label for="question" class="form-label">{{ __('Question') }}</label>
                                        <input type="text" class="form-control" id="question" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="answer" class="form-label">{{ __('Answer') }}</label>
                                        <textarea class="form-control" id="answer" rows="5" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> {{ __('Add Q&A') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Distribution -->
                        <div class="tab-pane fade" id="distribution" role="tabpanel">
                            <div class="mt-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-share-alt text-success mr-2"></i>{{ __('Distribute to All Chatbots') }}
                                </h5>
                                <p class="text-muted mb-4">{{ __('Automatically distribute your Brain Brand knowledge to all your chatbots and agents. Train once, use everywhere!') }}</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">{{ __('Your Chatbots') }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="chatbotsList">
                                                    <!-- Chatbots will be loaded here -->
                                                    <div class="text-center py-4">
                                                        <i class="fas fa-robot fa-2x text-muted mb-3"></i>
                                                        <p class="text-muted">{{ __('Loading your chatbots...') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">{{ __('Distribution Status') }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span>{{ __('Total Embeddings') }}</span>
                                                        <span class="badge bg-primary" id="totalEmbeddings">0</span>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span>{{ __('Distributed Chatbots') }}</span>
                                                        <span class="badge bg-success" id="distributedChatbots">0</span>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span>{{ __('Last Distribution') }}</span>
                                                        <span class="text-muted" id="lastDistribution">Never</span>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-success w-100" id="distributeBtn">
                                                    <i class="fas fa-rocket mr-2"></i>{{ __('Distribute Now') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>{{ __('Automatic Distribution') }}</strong><br>
                                        {{ __('When you add new content to Brain Brand, it will automatically be distributed to all your chatbots. No manual work needed!') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Process & Train -->
                        <div class="tab-pane fade" id="embeddings" role="tabpanel">
                            <div class="mt-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-cogs text-warning mr-2"></i>{{ __('Process & Generate Embeddings') }}
                                </h5>
                                <p class="text-muted mb-4">{{ __('Process your training data and generate AI embeddings for intelligent responses') }}</p>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-warning w-100 mb-3" id="generateEmbeddingsBtn">
                                            <i class="fas fa-magic mr-2"></i>{{ __('Generate All Embeddings') }}
                                        </button>
                                        <small class="text-muted">{{ __('Process all untrained content and generate AI embeddings') }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-outline-secondary w-100 mb-3" id="refreshDataBtn">
                                            <i class="fas fa-refresh mr-2"></i>{{ __('Refresh Data') }}
                                        </button>
                                        <small class="text-muted">{{ __('Reload and check training data status') }}</small>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">{{ __('Training Data Status') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="embeddingsList">
                                            <!-- Embeddings status will be loaded here -->
                                            <div class="text-center py-4">
                                                <i class="fas fa-database fa-2x text-muted mb-3"></i>
                                                <p class="text-muted">{{ __('Loading training data...') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const brainBrandId = {{ $brainBrand->id }};
    let isCrawling = false;
    
    // Initialize Brain Brand interface
    initializeBrainBrand();
    
    // URL Training with LinkParser
    document.getElementById('urlTrainingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (isCrawling) return;
        
        const url = document.getElementById('trainingUrl').value;
        const single = document.getElementById('singlePage').checked;
        const followLinks = document.getElementById('followLinks').checked;
        const maxPages = document.getElementById('maxPages').value;
        const contentFilter = document.getElementById('contentFilter').value;
        
        startCrawling(url, single, followLinks, maxPages, contentFilter);
    });
    
    // File Training
    document.getElementById('fileTrainingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const file = document.getElementById('trainingFile').files[0];
        
        if (file) {
            uploadAndTrainFile(file);
        }
    });
    
    // Text Training
    document.getElementById('textTrainingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const title = document.getElementById('textTitle').value;
        const content = document.getElementById('textContent').value;
        
        if (title && content) {
            addTextTraining(title, content);
        }
    });
    
    // Q&A Training
    document.getElementById('qaTrainingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const question = document.getElementById('question').value;
        const answer = document.getElementById('answer').value;
        
        if (question && answer) {
            addQATraining(question, answer);
        }
    });
    
    // Distribution
    document.getElementById('distributeBtn').addEventListener('click', function() {
        distributeToAllChatbots();
    });
    
    // Generate Embeddings
    document.getElementById('generateEmbeddingsBtn').addEventListener('click', function() {
        generateAllEmbeddings();
    });
    
    // Refresh Data
    document.getElementById('refreshDataBtn').addEventListener('click', function() {
        loadTrainingData();
        loadChatbotsList();
    });

    function initializeBrainBrand() {
        loadTrainingData();
        loadChatbotsList();
        updateDistributionStatus();
    }

    function startCrawling(url, single, followLinks, maxPages, contentFilter) {
        isCrawling = true;
        const progressDiv = document.getElementById('crawlingProgress');
        const progressBar = progressDiv.querySelector('.progress-bar');
        const progressText = document.getElementById('progressText');
        const logDiv = document.getElementById('crawlingLog');
        
        progressDiv.style.display = 'block';
        logDiv.innerHTML = '<small>Starting crawl of: ' + url + '</small>';
        
        // Simulate crawling progress (replace with real API call)
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 20;
            if (progress > 100) progress = 100;
            
            progressBar.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '%';
            
            if (progress >= 100) {
                clearInterval(interval);
                logDiv.innerHTML += '<br><small class="text-success">✓ Crawling completed successfully!</small>';
                setTimeout(() => {
                    progressDiv.style.display = 'none';
                    isCrawling = false;
                    loadTrainingData(); // Refresh the data
                }, 2000);
            } else {
                logDiv.innerHTML += '<br><small>✓ Crawled page ' + Math.round(progress/10) + '</small>';
                logDiv.scrollTop = logDiv.scrollHeight;
            }
        }, 1000);
        
        // Real API call
        fetch(`/dashboard/user/brain-brand/${brainBrandId}/url`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                id: brainBrandId,
                url: url,
                single: single ? '1' : '0'
            })
        }).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            clearInterval(interval);
            
            if (data.type === 'error') {
                // Show error message
                logDiv.innerHTML += '<br><small class="text-danger">× Error: ' + data.message + '</small>';
                if (data.details) {
                    logDiv.innerHTML += '<br><small class="text-danger">Details: ' + data.details + '</small>';
                }
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                isCrawling = false;
                return;
            }
            
            // Success
            progressBar.style.width = '100%';
            progressText.textContent = '100%';
            logDiv.innerHTML += '<br><small class="text-success">✓ Crawling completed successfully!</small>';
            
            if (data.data && data.data.length > 0) {
                logDiv.innerHTML += '<br><small class="text-info">✓ Found ' + data.data.length + ' pages with content</small>';
            }
            
            setTimeout(() => {
                progressDiv.style.display = 'none';
                isCrawling = false;
                loadTrainingData(); // Refresh the data
            }, 2000);
        })
        .catch(error => {
            clearInterval(interval);
            console.error('Crawling error:', error);
            logDiv.innerHTML += '<br><small class="text-danger">× Error: ' + error.message + '</small>';
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
            isCrawling = false;
        });
    }

    function loadTrainingData() {
        fetch(`/dashboard/user/brain-brand/${brainBrandId}/data`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            displayTrainingData(data);
            updateDistributionStatus();
        })
        .catch(error => console.error('Error loading training data:', error));
    }

    function displayTrainingData(embeddings) {
        const container = document.getElementById('embeddingsList');
        if (embeddings.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-database fa-2x text-muted mb-3"></i>
                    <p class="text-muted">{{ __('No training data yet. Start by crawling a website or uploading files.') }}</p>
                </div>
            `;
            return;
        }

        let html = '';
        embeddings.forEach(embedding => {
            const status = embedding.embedding ? 'trained' : 'pending';
            const statusClass = status === 'trained' ? 'success' : 'warning';
            const statusIcon = status === 'trained' ? 'check-circle' : 'clock';
            
            html += `
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div>
                        <h6 class="mb-1">${embedding.title || 'Untitled'}</h6>
                        <small class="text-muted">${embedding.type} - ${embedding.url || embedding.file || 'Manual content'}</small>
                    </div>
                    <div>
                        <span class="badge bg-${statusClass}">
                            <i class="fas fa-${statusIcon} mr-1"></i>${status}
                        </span>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function loadChatbotsList() {
        // This would load the user's chatbots
        const container = document.getElementById('chatbotsList');
        container.innerHTML = `
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                    <h6 class="mb-1">My Website Chatbot</h6>
                    <small class="text-muted">External chatbot</small>
                </div>
                <span class="badge bg-success">
                    <i class="fas fa-check mr-1"></i>Connected
                </span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                    <h6 class="mb-1">WhatsApp Business Bot</h6>
                    <small class="text-muted">WhatsApp integration</small>
                </div>
                <span class="badge bg-success">
                    <i class="fas fa-check mr-1"></i>Connected
                </span>
            </div>
        `;
    }

    function updateDistributionStatus() {
        // Update distribution statistics
        document.getElementById('totalEmbeddings').textContent = '5'; // This would be dynamic
        document.getElementById('distributedChatbots').textContent = '2'; // This would be dynamic
        document.getElementById('lastDistribution').textContent = '2 minutes ago'; // This would be dynamic
    }

    function distributeToAllChatbots() {
        const btn = document.getElementById('distributeBtn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Distributing...';
        btn.disabled = true;
        
        // Simulate distribution (replace with real API call)
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Distributed!';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                updateDistributionStatus();
            }, 2000);
        }, 3000);
    }

    function generateAllEmbeddings() {
        const btn = document.getElementById('generateEmbeddingsBtn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        btn.disabled = true;
        
        // Simulate embedding generation (replace with real API call)
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Completed!';
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-warning');
                loadTrainingData();
            }, 2000);
        }, 4000);
    }
});
</script>
@endsection
