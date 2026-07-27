<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->foreignId('checklist_item_library_id')->constrained('checklist_item_libraries')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['checklist_template_id', 'checklist_item_library_id'], 'checklist_template_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_template_items');
    }
};
