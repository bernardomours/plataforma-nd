<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalPaymentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'therapy_id',
        'agreement_id',
        'payment_type',
        'amount',
        'service_type_id',
        'valor_base',
        'valor_reajuste',
        'reajuste_9_meses_aplicado_em',
        'reajuste_18_meses_aplicado_em',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'valor_base' => 'decimal:2',
            'valor_reajuste' => 'decimal:2',
            'reajuste_9_meses_aplicado_em' => 'date',
            'reajuste_18_meses_aplicado_em' => 'date',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }
}