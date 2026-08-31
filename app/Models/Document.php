<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    public const CATEGORIA_LAUDO = 'laudo';
    public const CATEGORIA_RECEITA = 'receita';
    public const CATEGORIA_EXAME = 'exame';
    public const CATEGORIA_DOCS_PESSOAIS = 'docs_pessoais';
    public const CATEGORIA_OUTROS = 'outros';

    public const CATEGORIA_OPTIONS = [
        self::CATEGORIA_LAUDO         => 'Laudo',
        self::CATEGORIA_RECEITA       => 'Receita',
        self::CATEGORIA_EXAME         => 'Exame',
        self::CATEGORIA_DOCS_PESSOAIS => 'Documentos Pessoais',
        self::CATEGORIA_OUTROS        => 'Outros',
    ];

    /**
     * Agrupamento visual em "pastas" na tela (Documentos::render()) — não muda a
     * categoria gravada, só como a listagem organiza o que já existe. "Outros" reúne
     * tudo que não é laudo nem documento pessoal (inclusive receita e exame).
     */
    public const PASTAS = [
        'docs_pessoais' => ['label' => 'Documentos Pessoais', 'categorias' => [self::CATEGORIA_DOCS_PESSOAIS]],
        'laudos'        => ['label' => 'Laudos', 'categorias' => [self::CATEGORIA_LAUDO]],
        'outros'        => ['label' => 'Outros', 'categorias' => [self::CATEGORIA_RECEITA, self::CATEGORIA_EXAME, self::CATEGORIA_OUTROS]],
    ];

    protected $fillable = [
        'documentable_type', 'documentable_id', 'disk', 'path',
        'nome_original', 'mime_type', 'tamanho_bytes', 'categoria', 'uploaded_by',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function categoriaLabel(): string
    {
        return self::CATEGORIA_OPTIONS[$this->categoria] ?? $this->categoria;
    }

    public function tamanhoFormatado(): string
    {
        $bytes = $this->tamanho_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
