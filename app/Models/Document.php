<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    public const CATEGORIA_DOCS_PESSOAIS = 'docs_pessoais';
    public const CATEGORIA_LAUDO = 'laudo';
    public const CATEGORIA_ANAMNESE = 'anamnese';
    public const CATEGORIA_RELATORIO = 'relatorio';
    public const CATEGORIA_OUTROS = 'outros';

    public const CATEGORIA_OPTIONS = [
        self::CATEGORIA_DOCS_PESSOAIS => 'Documentos Pessoais',
        self::CATEGORIA_LAUDO         => 'Laudo',
        self::CATEGORIA_ANAMNESE      => 'Anamnese',
        self::CATEGORIA_RELATORIO     => 'Relatório',
        self::CATEGORIA_OUTROS        => 'Outros',
    ];

    /**
     * Agrupamento visual em "pastas" na tela (Documentos::render()) — hoje é 1:1 com
     * as categorias (cada pasta tem exatamente uma), mas a estrutura continua sendo
     * "pasta agrupa categorias" de propósito: já foi N:1 antes (Outros reunia
     * Receita+Exame+Outros) e pode voltar a ser sem mexer no componente/blade.
     */
    public const PASTAS = [
        'docs_pessoais' => ['label' => 'Documentos Pessoais', 'categorias' => [self::CATEGORIA_DOCS_PESSOAIS]],
        'laudos'        => ['label' => 'Laudos', 'categorias' => [self::CATEGORIA_LAUDO]],
        'anamnese'      => ['label' => 'Anamnese', 'categorias' => [self::CATEGORIA_ANAMNESE]],
        'relatorios'    => ['label' => 'Relatórios', 'categorias' => [self::CATEGORIA_RELATORIO]],
        'outros'        => ['label' => 'Outros', 'categorias' => [self::CATEGORIA_OUTROS]],
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
