<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('users',fn(Blueprint $table)=>$table->string('role',20)->default('desk'));
  $id=DB::table('users')->orderBy('id')->value('id');
  if($id) DB::table('users')->where('id',$id)->update(['role'=>'admin']);
 }
 public function down(): void { Schema::table('users',fn(Blueprint $table)=>$table->dropColumn('role')); }
};
