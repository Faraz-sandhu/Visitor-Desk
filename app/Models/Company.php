<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'address', 'phone', 'email', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function employees(): HasMany { return $this->hasMany(Employee::class); }
}
