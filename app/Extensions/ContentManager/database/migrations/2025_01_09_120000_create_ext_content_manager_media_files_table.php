<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_content_manager_media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->enum('file_type', ['image', 'video', 'audio', 'document', 'archive', 'file']);
            $table->json('dimensions')->nullable(); // For images/videos: {width: 1920, height: 1080}
            $table->integer('duration')->nullable(); // For videos/audio in seconds
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->string('alt_text')->nullable(); // For accessibility
            $table->boolean('is_public')->default(true);
            $table->string('folder_path')->nullable(); // For organization
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'file_type']);
            $table->index(['user_id', 'is_public']);
            $table->index('folder_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_content_manager_media_files');
    }
};