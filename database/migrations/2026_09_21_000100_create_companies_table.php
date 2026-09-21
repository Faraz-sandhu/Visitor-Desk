<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('companies',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->string('email')->nullable();$t->string('phone',30)->nullable();$t->text('address')->nullable();$t->boolean('is_active')->default(true)->index();$t->timestamps();}); } public function down(): void {Schema::dropIfExists('companies');} };
