<?php

namespace App\Extensions\ContentManager\System\Http\Livewire;

use App\Extensions\ContentManager\System\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MediaManagerModal extends Component
{
    use WithFileUploads, WithPagination;

    public $isOpen = false;
    public $allowedTypes = ['all'];
    public $isMultiple = false;
    public $selectedFiles = [];
    public $uploadedFiles = [];
    public $search = '';
    public $currentFolder = '';
    public $viewMode = 'grid'; // grid or list
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $listeners = [
        'openMediaManager' => 'openModal',
        'closeMediaManager' => 'closeModal',
    ];

    protected $rules = [
        'uploadedFiles.*' => 'file|max:51200', // 50MB max
    ];

    public function mount()
    {
        $this->selectedFiles = [];
    }

    public function openModal($data = [])
    {
        $this->isOpen = true;
        $this->allowedTypes = $data['allowedTypes'] ?? ['all'];
        $this->isMultiple = $data['isMultiple'] ?? false;
        $this->selectedFiles = [];
        $this->search = '';
        $this->currentFolder = '';
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->selectedFiles = [];
        $this->uploadedFiles = [];
        $this->search = '';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCurrentFolder()
    {
        $this->resetPage();
    }

    public function selectFile($fileId)
    {
        if ($this->isMultiple) {
            if (in_array($fileId, $this->selectedFiles)) {
                $this->selectedFiles = array_filter($this->selectedFiles, fn($id) => $id != $fileId);
            } else {
                $this->selectedFiles[] = $fileId;
            }
        } else {
            $this->selectedFiles = [$fileId];
        }
    }

    public function confirmSelection()
    {
        if (empty($this->selectedFiles)) {
            $this->addError('selection', 'Please select at least one file.');
            return;
        }

        $files = MediaFile::whereIn('id', $this->selectedFiles)->get();
        
        $selectedItems = $files->map(function ($file) {
            return [
                'id' => $file->id,
                'title' => $file->title,
                'url' => $file->url,
                'type' => $file->file_type,
                'mime_type' => $file->mime_type,
                'size' => $file->file_size_human,
                'dimensions' => $file->dimensions,
            ];
        })->toArray();

        $this->dispatch('mediaSelected', [
            'items' => $selectedItems,
            'type' => $this->isMultiple ? 'multiple' : 'single'
        ]);

        $this->closeModal();
    }

    public function uploadFiles()
    {
        $this->validate();

        foreach ($this->uploadedFiles as $file) {
            $this->processUploadedFile($file);
        }

        $this->uploadedFiles = [];
        $this->dispatch('$refresh');
    }

    protected function processUploadedFile(UploadedFile $file)
    {
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Determine folder path
        $folderPath = $this->currentFolder ?: 'uploads/' . date('Y/m');
        
        // Store file
        $filePath = $file->storeAs($folderPath, $filename, 'public');
        
        // Get file dimensions for images/videos
        $dimensions = null;
        $duration = null;
        
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imagePath = Storage::disk('public')->path($filePath);
            if (file_exists($imagePath)) {
                $imageSize = getimagesize($imagePath);
                if ($imageSize) {
                    $dimensions = [
                        'width' => $imageSize[0],
                        'height' => $imageSize[1]
                    ];
                }
            }
        }
        
        // Create media file record
        MediaFile::create([
            'user_id' => auth()->id(),
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_type' => MediaFile::getFileTypeFromMime($file->getMimeType()),
            'dimensions' => $dimensions,
            'duration' => $duration,
            'folder_path' => $folderPath,
            'is_public' => true,
        ]);
    }

    public function deleteFile($fileId)
    {
        $file = MediaFile::where('id', $fileId)
            ->where('user_id', auth()->id())
            ->first();
            
        if ($file) {
            $file->delete();
            $this->dispatch('$refresh');
        }
    }

    public function setSortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function getMediaFilesProperty()
    {
        $query = MediaFile::where('user_id', auth()->id());

        // Apply search filter
        if ($this->search) {
            $query->search($this->search);
        }

        // Apply folder filter
        if ($this->currentFolder) {
            $query->where('folder_path', 'like', $this->currentFolder . '%');
        }

        // Apply type filter
        if (!in_array('all', $this->allowedTypes)) {
            $query->whereIn('file_type', $this->allowedTypes);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    public function getFoldersProperty()
    {
        return MediaFile::where('user_id', auth()->id())
            ->whereNotNull('folder_path')
            ->distinct()
            ->pluck('folder_path')
            ->sort()
            ->values();
    }

    public function getAllowedMimeTypes()
    {
        $mimeTypes = [
            'image' => ['image/*'],
            'video' => ['video/*'],
            'audio' => ['audio/*'],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ],
            'all' => ['*/*']
        ];
        
        $types = [];
        foreach ($this->allowedTypes as $type) {
            if (isset($mimeTypes[$type])) {
                $types = array_merge($types, $mimeTypes[$type]);
            }
        }
        
        return !empty($types) ? $types : ['*/*'];
    }

    public function render()
    {
        return view('content-manager::livewire.media-manager-modal', [
            'mediaFiles' => $this->mediaFiles,
            'folders' => $this->folders,
        ]);
    }
}