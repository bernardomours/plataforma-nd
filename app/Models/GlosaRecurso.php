<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlosaRecurso extends Model
{
    public const STATUS_EM_ANALISE = 'em_analise';
    public const STATUS_PAGAMENTO_EFETUADO = 'pagamento_efetuado';

    public const STATUS_OPTIONS = [
        self::STATUS_EM_ANALISE => 'Em Análise',
        self::STATUS_PAGAMENTO_EFETUADO => 'Pagamento Efetuado',
    ];

    protected $fillable = [
        'glosa_batch_id', 'lote', 'valor_recursado', 'valor_acatado', 'status', 'registered_by',
    ];

    protected $casts = [
        'valor_recursado' => 'decimal:2',
        'valor_acatado'   => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GlosaBatch::class, 'glosa_batch_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function statusLabel(): ?string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }
}
