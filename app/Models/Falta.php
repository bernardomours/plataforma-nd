<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Falta de paciente numa terapia agendada. Ver migration create_faltas_table pro porquê
 * de ficar fora de `appointments`.
 */
class Falta extends Model
{
    use HasFactory;

    public const MOTIVO_VIAGEM = 'viagem';
    public const MOTIVO_DOENCA = 'doenca';
    public const MOTIVO_FERIAS = 'ferias';
    public const MOTIVO_NAO_INFORMADO = 'nao_informado';
    public const MOTIVO_OUTRO = 'outro';

    public const MOTIVO_OPTIONS = [
        self::MOTIVO_VIAGEM => 'Viagem',
        self::MOTIVO_DOENCA => 'Doença',
        self::MOTIVO_FERIAS => 'Férias',
        self::MOTIVO_NAO_INFORMADO => 'Não informado',
        self::MOTIVO_OUTRO => 'Outro',
    ];

    protected $fillable = [
        'schedule_id',
        'patient_id',
        'professional_id',
        'therapy_id',
        'service_type_id',
        'date',
        'motivo',
        'observacao',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function motivoLabel(): string
    {
        return self::MOTIVO_OPTIONS[$this->motivo] ?? $this->motivo;
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class)->withTrashed();
    }

    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
