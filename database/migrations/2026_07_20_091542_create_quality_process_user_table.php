<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector',
        'process_name',
        'procedure_code',
        'due_date',
        'status',
        'progress',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'quality_process_user');
    }

    public function checklists()
    {
        return $this->hasMany(QualityChecklist::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateProgress()
    {
        $total = $this->checklists()->count();

        if ($total === 0) {
            $this->update(['progress' => 0]);
            return;
        }

        $completed = $this->checklists()->where('is_completed', true)->count();
        $percentage = (int) round(($completed / $total) * 100);

        $status = $this->status;
        if ($percentage === 100) {
            $status = 'concluido';
        } elseif ($percentage > 0 && $status === 'pendente') {
            $status = 'em_andamento';
        }

        $this->update([
            'progress' => $percentage,
            'status' => $status
        ]);
    }
}