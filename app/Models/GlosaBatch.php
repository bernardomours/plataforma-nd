<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlosaBatch extends Model
{
    protected $fillable = [
        'competencia', 'prestador_codigo', 'prestador_nome', 'unit_id',
        'arquivo_nome', 'arquivo_hash',
        'total_itens', 'vl_apresentado', 'vl_liberado', 'vl_glosa', 'imported_by',
    ];

    protected $casts = [
        'competencia'    => 'date',
        'vl_apresentado' => 'decimal:2',
        'vl_liberado'    => 'decimal:2',
        'vl_glosa'       => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(GlosaItem::class);
    }

    // Raro, mas um lote pode ter mais de um recurso (reenvio, nova tentativa) —
    // por isso hasMany, não hasOne. Ver Producao\Glosas\Recursos para a agregação.
    public function recursos(): HasMany
    {
        return $this->hasMany(GlosaRecurso::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
