<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlosaReason extends Model
{
    public const TIPO_OCORRENCIA = 'ocorrencia';
    public const TIPO_PARECER    = 'parecer';

    protected $fillable = ['glosa_item_id', 'tipo', 'codigo', 'descricao'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(GlosaItem::class, 'glosa_item_id');
    }

    /** Grafia canônica do motivo; a descrição da própria linha pode vir corrompida. */
    public function code(): BelongsTo
    {
        return $this->belongsTo(GlosaReasonCode::class, 'codigo', 'codigo');
    }
}
