<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlosaItem extends Model
{
    public const STATUS_GLOSADO  = 'glosado';
    public const STATUS_PARCIAL  = 'parcial';
    public const STATUS_LIBERADO = 'liberado';

    protected $fillable = [
        'glosa_batch_id', 'competencia', 'unit_id',
        'dt_item', 'item_codigo', 'item_descricao',
        'conta', 'guia', 'carteira', 'lote',
        'qt_item', 'taxa', 'vl_apresentado', 'vl_liberado', 'vl_glosa', 'prorata',
        'beneficiario_nome', 'medico_nome',
        'appointment_id', 'patient_id', 'professional_id', 'status',
    ];

    protected $casts = [
        'competencia'    => 'date',
        'dt_item'        => 'date',
        'qt_item'        => 'decimal:4',
        'vl_apresentado' => 'decimal:2',
        'vl_liberado'    => 'decimal:2',
        'vl_glosa'       => 'decimal:2',
        'prorata'        => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GlosaBatch::class, 'glosa_batch_id');
    }

    public function reasons(): HasMany
    {
        return $this->hasMany(GlosaReason::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeGlosados($query)
    {
        return $query->whereIn('status', [self::STATUS_GLOSADO, self::STATUS_PARCIAL]);
    }

    /** Linha que o convênio cobrou mas que não existe como atendimento na plataforma. */
    public function scopeNaoConciliados($query)
    {
        return $query->whereNull('appointment_id');
    }
}
