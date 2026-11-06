<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $t) {
            $t->string('fashn_image')->nullable();
            $t->string('fashn_photo_type')->default('flat-lay');
        });
        Schema::create('tryon_runs', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->foreignId('product_id')->constrained();
            $t->string('owner_hash', 64)->index(); $t->string('status')->default('queued');
            $t->string('person_path'); $t->string('result_path')->nullable(); $t->string('error')->nullable();
            $t->timestamp('expires_at')->index(); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tryon_runs');
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['fashn_image','fashn_photo_type']));
    }
};
