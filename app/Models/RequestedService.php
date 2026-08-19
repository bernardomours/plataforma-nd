<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestedService extends Model
{
    use HasFactory;

    protected $fillable = [
        'month_year',
        'requisition_number',
        'requested_hours',
        'approved_hours',
        // Semanal (legado, mantido como contexto "x/semana" na tela).
        'planned_hours',
        // Total do MÊS, congelado no momento em que a CH é salva. É o valor que o
        // painel de faltas usa; não muda sozinho se a agenda for alterada depois.
        'planned_sessions',
        'planned_from_schedule',
        'patient_id',
        'therapy_id',
        'service_type_id',
    ];

    protected $casts = [
        'month_year' => 'string',
        'requested_hours' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'planned_hours' => 'decimal:2',
        'planned_sessions' => 'integer',
        'planned_from_schedule' => 'boolean',
        'service_type_id' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
