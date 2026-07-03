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
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->unsignedInteger('points');
            $table->boolean('is_secret')->default(false);
            $table->foreignId('target_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamp('release_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
