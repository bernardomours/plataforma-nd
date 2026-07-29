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
        'due_date',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(QualityProcess::class, 'quality_process_id');
    }
}