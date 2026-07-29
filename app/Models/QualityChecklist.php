<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_process_id',
        'description',
        'is_completed',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(QualityProcess::class, 'quality_process_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    protected static function booted()
    {
        static::saved(function ($checklist) {
            $checklist->process->recalculateProgress();
        });

        static::deleted(function ($checklist) {
            $checklist->process->recalculateProgress();
        });
    }
}