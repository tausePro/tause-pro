<div>
    @if($isOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Media Manager') }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Upload Section -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="file" wire:model="uploadedFiles" multiple class="form-control" 
                                           accept="{{ implode(',', $this->getAllowedMimeTypes()) }}">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" wire:click="uploadFiles" class="btn btn-primary" 
                                            wire:loading.attr="disabled" wire:target="uploadFiles">
                                        <span wire:loading.remove wire:target="uploadFiles">{{ __('Upload Files') }}</span>
                                        <span wire:loading wire:target="uploadFiles">{{ __('Uploading...') }}</span>
                                    </button>
                                </div>
                            </div>
                            @error('uploadedFiles.*') 
                                <div class="text-danger mt-1">{{ $message }}</div> 
                            @enderror
                        </div>

                        <!-- Filters and Search -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" wire:model.live="search" class="form-control" 
                                       placeholder="{{ __('Search files...') }}">
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="currentFolder" class="form-select">
                                    <option value="">{{ __('All Folders') }}</option>
                                    @foreach($folders as $folder)
                                        <option value="{{ $folder }}">{{ $folder }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="sortBy" class="form-select">
                                    <option value="created_at">{{ __('Date Created') }}</option>
                                    <option value="title">{{ __('Title') }}</option>
                                    <option value="file_size">{{ __('File Size') }}</option>
                                    <option value="file_type">{{ __('File Type') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="btn-group" role="group">
                                    <button type="button" wire:click="$set('viewMode', 'grid')" 
                                            class="btn btn-outline-secondary {{ $viewMode === 'grid' ? 'active' : '' }}">
                                        <i class="fas fa-th"></i>
                                    </button>
                                    <button type="button" wire:click="$set('viewMode', 'list')" 
                                            class="btn btn-outline-secondary {{ $viewMode === 'list' ? 'active' : '' }}">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Files Display -->
                        <div class="media-files-container" style="max-height: 400px; overflow-y: auto;">
                            @if($viewMode === 'grid')
                                <div class="row g-3">
                                    @forelse($mediaFiles as $file)
                                        <div class="col-md-3 col-sm-4 col-6">
                                            <div class="card media-file-card {{ in_array($file->id, $selectedFiles) ? 'border-primary' : '' }}" 
                                                 wire:click="selectFile({{ $file->id }})" style="cursor: pointer;">
                                                <div class="card-body p-2 text-center">
                                                    @if($file->is_image)
                                                        <img src="{{ $file->url }}" alt="{{ $file->alt_text ?: $file->title }}" 
                                                             class="img-fluid mb-2" style="max-height: 80px; object-fit: cover;">
                                                    @elseif($file->is_video)
                                                        <i class="fas fa-video fa-3x text-primary mb-2"></i>
                                                    @elseif($file->is_audio)
                                                        <i class="fas fa-music fa-3x text-success mb-2"></i>
                                                    @elseif($file->is_document)
                                                        <i class="fas fa-file-alt fa-3x text-info mb-2"></i>
                                                    @else
                                                        <i class="fas fa-file fa-3x text-secondary mb-2"></i>
                                                    @endif
                                                    
                                                    <div class="small">
                                                        <div class="fw-bold text-truncate" title="{{ $file->title }}">
                                                            {{ Str::limit($file->title, 15) }}
                                                        </div>
                                                        <div class="text-muted">{{ $file->file_size_human }}</div>
                                                    </div>
                                                    
                                                    @if(in_array($file->id, $selectedFiles))
                                                        <div class="position-absolute top-0 end-0 p-1">
                                                            <i class="fas fa-check-circle text-primary"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="text-center py-4">
                                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">{{ __('No files found') }}</p>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="40"></th>
                                                <th wire:click="setSortBy('title')" style="cursor: pointer;">
                                                    {{ __('Title') }}
                                                    @if($sortBy === 'title')
                                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                                    @endif
                                                </th>
                                                <th>{{ __('Type') }}</th>
                                                <th wire:click="setSortBy('file_size')" style="cursor: pointer;">
                                                    {{ __('Size') }}
                                                    @if($sortBy === 'file_size')
                                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                                    @endif
                                                </th>
                                                <th wire:click="setSortBy('created_at')" style="cursor: pointer;">
                                                    {{ __('Date') }}
                                                    @if($sortBy === 'created_at')
                                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                                    @endif
                                                </th>
                                                <th width="80">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($mediaFiles as $file)
                                                <tr wire:click="selectFile({{ $file->id }})" 
                                                    class="{{ in_array($file->id, $selectedFiles) ? 'table-primary' : '' }}" 
                                                    style="cursor: pointer;">
                                                    <td>
                                                        @if($file->is_image)
                                                            <img src="{{ $file->url }}" alt="{{ $file->title }}" 
                                                                 class="img-thumbnail" style="width: 30px; height: 30px; object-fit: cover;">
                                                        @else
                                                            <i class="fas fa-file text-muted"></i>
                                                        @endif
                                                    </td>
                                                    <td>{{ $file->title }}</td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ $file->file_type_display }}</span>
                                                    </td>
                                                    <td>{{ $file->file_size_human }}</td>
                                                    <td>{{ $file->created_at->format('M j, Y') }}</td>
                                                    <td>
                                                        <button type="button" wire:click.stop="deleteFile({{ $file->id }})" 
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('{{ __('Are you sure?') }}')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                                                        <p class="text-muted mb-0">{{ __('No files found') }}</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <!-- Pagination -->
                        @if($mediaFiles->hasPages())
                            <div class="mt-3">
                                {{ $mediaFiles->links() }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="modal-footer">
                        <div class="me-auto">
                            @if(count($selectedFiles) > 0)
                                <span class="text-muted">
                                    {{ trans_choice('{1} :count file selected|[2,*] :count files selected', count($selectedFiles), ['count' => count($selectedFiles)]) }}
                                </span>
                            @endif
                        </div>
                        
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="confirmSelection" 
                                @if(empty($selectedFiles)) disabled @endif>
                            {{ __('Select Files') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('script')
<script>
    // Helper method to get allowed MIME types based on file types
    window.getAllowedMimeTypes = function(allowedTypes) {
        const mimeTypes = {
            'image': ['image/*'],
            'video': ['video/*'],
            'audio': ['audio/*'],
            'document': [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ],
            'all': ['*/*']
        };
        
        let types = [];
        allowedTypes.forEach(type => {
            if (mimeTypes[type]) {
                types = types.concat(mimeTypes[type]);
            }
        });
        
        return types.length > 0 ? types : ['*/*'];
    };
</script>
@endpush