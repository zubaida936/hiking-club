<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| CMS content (ClickUp "Manage content"): guide articles, gallery albums,
| homepage hero slides and team members. Images live on the public disk.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            // Shown on the article list; the body is Markdown.
            $table->string('excerpt', 300)->nullable();
            $table->mediumText('body');
            $table->string('author_name', 80);
            $table->string('cover_path')->nullable();
            // Null = draft; a future date = scheduled.
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            // The hike the photos are from; kept when the event is deleted.
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->date('taken_on')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('album_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            // Small line above the heading, e.g. "🍂 Autumn".
            $table->string('eyebrow', 40)->nullable();
            $table->string('heading', 120);
            $table->string('text', 300)->nullable();
            $table->string('cta_label', 40)->nullable();
            // A site path ("/events") or a full https:// link.
            $table->string('cta_url', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('role', 80);
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('album_photos');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('articles');
    }
};
