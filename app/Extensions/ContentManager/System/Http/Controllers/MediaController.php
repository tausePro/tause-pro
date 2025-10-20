<?php

namespace App\Extensions\ContentManager\System\Http\Controllers;

use App\Extensions\ContentManager\System\Models\MediaFile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Serve a media file.
     */
    public function serve(Request $request, MediaFile $file): Response|StreamedResponse
    {
        // Check if user has permission to access this file
        if (!$file->is_public && $file->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to media file');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'Media file not found');
        }

        $filePath = Storage::disk('public')->path($file->file_path);
        
        // For images, we can serve them directly
        if ($file->is_image) {
            return response()->file($filePath, [
                'Content-Type' => $file->mime_type,
                'Cache-Control' => 'public, max-age=31536000', // 1 year cache
            ]);
        }

        // For other files, force download or stream
        return response()->download($filePath, $file->original_filename, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    /**
     * Get media file information.
     */
    public function info(MediaFile $file)
    {
        // Check permissions
        if (!$file->is_public && $file->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to media file');
        }

        return response()->json([
            'id' => $file->id,
            'title' => $file->title,
            'description' => $file->description,
            'filename' => $file->filename,
            'original_filename' => $file->original_filename,
            'file_size' => $file->file_size,
            'file_size_human' => $file->file_size_human,
            'mime_type' => $file->mime_type,
            'file_type' => $file->file_type,
            'dimensions' => $file->dimensions,
            'duration' => $file->duration,
            'url' => $file->url,
            'created_at' => $file->created_at,
            'updated_at' => $file->updated_at,
        ]);
    }

    /**
     * Update media file metadata.
     */
    public function update(Request $request, MediaFile $file)
    {
        // Check permissions
        if ($file->user_id !== auth()->id()) {
            abort(403, 'Unauthorized to update this media file');
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'alt_text' => 'sometimes|nullable|string|max:255',
        ]);

        $file->update($request->only(['title', 'description', 'alt_text']));

        return response()->json([
            'message' => 'Media file updated successfully',
            'file' => $file->fresh(),
        ]);
    }

    /**
     * Delete a media file.
     */
    public function destroy(MediaFile $file)
    {
        // Check permissions
        if ($file->user_id !== auth()->id()) {
            abort(403, 'Unauthorized to delete this media file');
        }

        $file->delete();

        return response()->json([
            'message' => 'Media file deleted successfully',
        ]);
    }

    /**
     * Get media files for API.
     */
    public function index(Request $request)
    {
        $query = MediaFile::where('user_id', auth()->id());

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('folder')) {
            $query->where('folder_path', 'like', $request->folder . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $mediaFiles = $query->paginate($request->get('per_page', 20));

        return response()->json($mediaFiles);
    }
}