<?php

namespace App\Extensions\ContentManager\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class MediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ext_content_manager_media_files';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_type',
        'dimensions',
        'duration',
        'metadata',
        'alt_text',
        'is_public',
        'folder_path',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'duration' => 'integer',
    ];

    protected $appends = [
        'url',
        'file_type_display',
        'file_size_human',
        'is_image',
        'is_video',
        'is_audio',
        'is_document',
    ];

    /**
     * Get the user that owns the media file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL of the media file.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_public) {
            return Storage::disk('public')->url($this->file_path);
        }
        
        return route('content-manager::media.serve', ['file' => $this->id]);
    }

    /**
     * Get human-readable file type.
     */
    public function getFileTypeDisplayAttribute(): string
    {
        return match ($this->file_type) {
            'image' => 'Image',
            'video' => 'Video',
            'audio' => 'Audio',
            'document' => 'Document',
            'archive' => 'Archive',
            default => 'File',
        };
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * Check if file is a video.
     */
    public function getIsVideoAttribute(): bool
    {
        return $this->file_type === 'video';
    }

    /**
     * Check if file is audio.
     */
    public function getIsAudioAttribute(): bool
    {
        return $this->file_type === 'audio';
    }

    /**
     * Check if file is a document.
     */
    public function getIsDocumentAttribute(): bool
    {
        return $this->file_type === 'document';
    }

    /**
     * Scope to filter by file type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter public files.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to search by title or filename.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('original_filename', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get file type from mime type.
     */
    public static function getFileTypeFromMime(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];
        
        if (in_array($mimeType, $documentTypes)) {
            return 'document';
        }
        
        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
        ];
        
        if (in_array($mimeType, $archiveTypes)) {
            return 'archive';
        }
        
        return 'file';
    }

    /**
     * Delete the file from storage when model is deleted.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($mediaFile) {
            if ($mediaFile->file_path && Storage::disk('public')->exists($mediaFile->file_path)) {
                Storage::disk('public')->delete($mediaFile->file_path);
            }
        });
    }
}