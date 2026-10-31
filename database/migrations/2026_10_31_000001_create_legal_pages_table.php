<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Privacy Policy and Terms, edited in the CMS (ClickUp "Legal: Privacy Policy & terms").
| One row per App\Domain\Content\Enums\LegalDocument, created on first save.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('document', 20)->unique();
            $table->string('title', 160);
            // Markdown; empty = the page says it is being prepared.
            $table->mediumText('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
