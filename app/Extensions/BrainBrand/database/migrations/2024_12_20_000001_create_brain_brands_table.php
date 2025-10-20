<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_brain_brands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('brand_voice')->nullable()->comment('Brand voice configuration');
            $table->string('tone')->nullable()->comment('Brand tone: professional, friendly, casual, etc.');
            $table->string('personality')->nullable()->comment('Brand personality traits');
            $table->boolean('active')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_brain_brands');
    }
};
