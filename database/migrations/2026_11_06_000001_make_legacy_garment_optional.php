<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void {
        // Preserve previously uploaded assets; new products no longer need an overlay.
        Schema::table('products', fn (Blueprint $table) => $table->string('garment')->nullable()->change());
    }

    public function down(): void {
        DB::table('products')->whereNull('garment')->update(['garment' => '']);
        Schema::table('products', fn (Blueprint $table) => $table->string('garment')->nullable(false)->change());
    }
};
