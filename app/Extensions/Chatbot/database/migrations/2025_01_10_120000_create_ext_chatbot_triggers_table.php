<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_chatbot_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatbot_id');
            $table->string('trigger_type', 100)->index();
            $table->string('name')->nullable();
            $table->string('trigger_name')->nullable();
            $table->text('message_template');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->string('action', 50)->default('show_message');
            $table->integer('priority')->default(5);
            $table->integer('cooldown_minutes')->default(5);
            $table->integer('frequency_limit')->default(3);
            $table->json('conditions')->nullable();
            $table->json('display_config')->nullable();
            $table->json('frequency_config')->nullable();
            $table->timestamps();

            $table->foreign('chatbot_id')
                ->references('id')
                ->on('ext_chatbots')
                ->onDelete('cascade');

            $table->unique(['chatbot_id', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_triggers');
    }
};



