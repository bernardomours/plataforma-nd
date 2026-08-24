<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de motivos de glosa. O código é a chave: ele é ASCII e chega íntegro, enquanto
 * a descrição varia de grafia conforme o encoding do arquivo de origem.
 */
class GlosaReasonCode extends Model
{
    protected $fillable = ['codigo', 'descricao', 'orientacao'];

    public function reasons(): HasMany
    {
        return $this->hasMany(GlosaReason::class, 'codigo', 'codigo');
    }
}
