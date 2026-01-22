<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_request_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_id')->default(0)->index();
            $table->string('method', 10)->index();
            $table->string('path', 191)->index();
            $table->unsignedSmallInteger('status_code')->default(0)->index();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('ip', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('body')->nullable();
            $table->unsignedInteger('created_at')->index();

            $table->index(['path', 'created_at']);
            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_request_logs');
    }
};

