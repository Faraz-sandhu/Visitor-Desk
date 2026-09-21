<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id','visitor_name','visitor_phone','visitor_email','id_card_number','visitor_company','photo_path','purpose','check_in_at','check_out_at','notes','created_by'];
    protected function casts(): array { return ['check_in_at' => 'datetime', 'check_out_at' => 'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function receptionist(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function getIsCheckedInAttribute(): bool { return is_null($this->check_out_at); }
    public function durationMinutes(?\Carbon\CarbonInterface $asOf = null): int {
        return max(0, (int) floor($this->check_in_at->diffInSeconds($this->check_out_at ?? $asOf ?? now(), false) / 60));
    }
    public function durationLabel(?\Carbon\CarbonInterface $asOf = null): string {
        $minutes=$this->durationMinutes($asOf);$hours=intdiv($minutes,60);$remaining=$minutes%60;
        $parts=[];
        if($hours) $parts[]=$hours.' '.($hours===1?'hour':'hours');
        if($remaining || !$hours) $parts[]=$remaining.' '.($remaining===1?'minute':'minutes');
        return implode(' ',$parts);
    }
}
