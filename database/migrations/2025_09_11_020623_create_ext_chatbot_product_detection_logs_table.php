<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ext_chatbot_product_detection_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatbot_id');
            $table->string('source_url', 500);
            $table->enum('status', ['pending', 'success', 'failed', 'partial'])->default('pending');
            $table->integer('products_found')->default(0);
            $table->text('error_message')->nullable();
            $table->decimal('detection_time', 8, 3)->nullable(); // Time in seconds
            $table->integer('retry_count')->default(0);
            $table->json('detection_metadata')->nullable();
            $table->json('extracted_data')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('chatbot_id')->references('id')->on('ext_chatbots')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['chatbot_id', 'status'], 'idx_chatbot_status');
            $table->index(['source_url', 'status'], 'idx_url_status');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['started_at'], 'idx_started_at');
            $table->index(['completed_at'], 'idx_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_product_detection_logs');
    }
};