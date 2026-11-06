<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false));
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('brand'); $t->string('size'); $t->string('condition');
            $t->text('description'); $t->unsignedInteger('price'); $t->string('photo'); $t->string('garment');
            $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->uuid('token')->unique(); $t->foreignId('product_id')->constrained();
            $t->string('name'); $t->string('email'); $t->string('phone'); $t->text('address');
            $t->unsignedInteger('total'); $t->string('status')->default('pending'); $t->string('proof')->nullable(); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); Schema::dropIfExists('products'); Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin')); }
};
