<?php

namespace App\Livewire\Pacientes;

use App\Models\Document;
use App\Models\Patient;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Laudos e Documentos do paciente. Dado de saúde sensível — restrito a
 * admin|manager|administrative + profissional multi-terapia (User::podeAcessarLaudosDocumentos()),
 * diferente do resto da ficha do paciente (que é aberta a qualquer papel autenticado de
 * propósito, para o profissional ver a própria agenda).
 *
 * Arquivo em si vive no disco 'documents' (config/filesystems.php) — local por padrão,
 * trocável pra S3/R2 via .env sem tocar em código. Visualizar/baixar são rotas dedicadas
 * (App\Http\Controllers\DocumentController), não ação do Livewire — precisam abrir numa
 * aba nova de verdade (target="_blank"), o que uma Response devolvida por ação do
 * Livewire não garante quando é redirect()->away() pra URL assinada do S3/R2.
 */
class Documentos extends Component
{
    use WithFileUploads;

    private const MIMES_PERMITIDOS = 'pdf,jpg,jpeg,png';
    private const TAMANHO_MAXIMO_KB = 10240; // 10MB

    public Patient $patient;

    // null = grade de pastas; com valor = dentro de uma pasta (ver Document::PASTAS).
    public ?string $pastaAtual = null;

    public bool $isModalOpen = false;
    public string $categoria = '';
    public $arquivo;

    public function mount(Patient $patient)
    {
        $this->autorizarAcesso();
        $this->patient = $patient;
    }

    public function abrirPasta(string $pasta)
    {
        if ($pasta === Document::CATEGORIA_DOCS_PESSOAIS && ! auth()->user()->podeAcessarDocumentosPessoais()) {
            return;
        }

        $this->pastaAtual = array_key_exists($pasta, Document::PASTAS) ? $pasta : null;
    }

    /**
     * SEGURANÇA: abrirPasta() barra a entrada, mas pastaAtual é propriedade pública
     * comum (não dá pra usar #[Locked] — precisa continuar gravável por abrirPasta())
     * — uma requisição forjada setando pastaAtual='docs_pessoais' direto contornaria
     * aquela checagem. Reimposto aqui, no ponto que decide de verdade o que é
     * consultado/exibido (render() e abrirModal() chamam isto antes de usar pastaAtual).
     */
    private function corrigirPastaAtual(): void
    {
        if ($this->pastaAtual === Document::CATEGORIA_DOCS_PESSOAIS && ! auth()->user()->podeAcessarDocumentosPessoais()) {
            $this->pastaAtual = null;
        }
    }

    public function voltarParaPastas()
    {
        $this->pastaAtual = null;
    }

    private function autorizarAcesso(): void
    {
        if (! auth()->user()->podeAcessarLaudosDocumentos()) {
            abort(403, 'Você não tem permissão para acessar laudos e documentos.');
        }
    }

    public function abrirModal()
    {
        $this->autorizarAcesso();
        $this->corrigirPastaAtual();
        $this->reset(['categoria', 'arquivo']);
        $this->resetValidation();

        // Se abriu o modal de dentro de uma pasta com categoria única (Docs Pessoais,
        // Laudos), já pré-seleciona. "Outros" agrupa 3 categorias, então fica em branco.
        $categoriasDaPasta = Document::PASTAS[$this->pastaAtual]['categorias'] ?? [];
        if (count($categoriasDaPasta) === 1) {
            $this->categoria = $categoriasDaPasta[0];
        }

        $this->isModalOpen = true;
    }

    public function fecharModal()
    {
        $this->isModalOpen = false;
        $this->reset(['categoria', 'arquivo']);
        $this->resetValidation();
    }

    public function salvar()
    {
        $this->autorizarAcesso();

        // SEGURANÇA: valida contra o conjunto PERMITIDO pro papel do usuário, não
        // contra todas as categorias — sem isso, alguém sem acesso a Documentos
        // Pessoais ainda conseguiria escolher essa categoria adulterando o <select>
        // (ou mandando o valor direto), já que o campo em si é um texto comum.
        $this->validate([
            'categoria' => ['required', Rule::in(array_keys($this->categoriasPermitidas()))],
            'arquivo'   => ['required', 'file', 'mimes:' . self::MIMES_PERMITIDOS, 'max:' . self::TAMANHO_MAXIMO_KB],
        ]);

        // Nome no disco é sempre um UUID — nunca o nome que o usuário enviou. Evita
        // path traversal e não expõe nome de paciente/arquivo na estrutura do storage.
        $extensao = $this->arquivo->getClientOriginalExtension();
        $caminho = $this->arquivo->storeAs(
            'pacientes/' . $this->patient->id,
            Str::uuid() . '.' . $extensao,
            'documents'
        );

        Document::create([
            'documentable_type' => Patient::class,
            'documentable_id'   => $this->patient->id,
            'disk'              => 'documents',
            'path'              => $caminho,
            'nome_original'     => $this->arquivo->getClientOriginalName(),
            'mime_type'         => $this->arquivo->getMimeType(),
            'tamanho_bytes'     => $this->arquivo->getSize(),
            'categoria'         => $this->categoria,
            'uploaded_by'       => auth()->id(),
        ]);

        $this->fecharModal();
    }

    private function documentoDoPaciente(int $documentId): Document
    {
        return Document::where('documentable_type', Patient::class)
            ->where('documentable_id', $this->patient->id)
            ->findOrFail($documentId);
    }

    public function excluir(int $documentId)
    {
        $this->autorizarAcesso();

        // Pedido do usuário (02/09/2026): excluir documento salvo é só pra
        // admin|manager|administrative — mais restrito que o resto da aba (que
        // também libera coordenador/supervisor/profissional multi-terapia).
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para excluir documentos.');
        }

        $this->documentoDoPaciente($documentId)->delete();
    }

    private function documentosDoPaciente()
    {
        return Document::where('documentable_type', Patient::class)
            ->where('documentable_id', $this->patient->id);
    }

    /**
     * Categorias que o usuário logado pode ver/escolher — todas, ou todas menos
     * Documentos Pessoais. Único lugar que decide isso, reaproveitado por render()
     * (pra filtrar pasta e dropdown) e salvar() (pra validar contra tampering).
     */
    private function categoriasPermitidas(): array
    {
        if (auth()->user()->podeAcessarDocumentosPessoais()) {
            return Document::CATEGORIA_OPTIONS;
        }

        return collect(Document::CATEGORIA_OPTIONS)
            ->except(Document::CATEGORIA_DOCS_PESSOAIS)
            ->all();
    }

    public function render()
    {
        $this->corrigirPastaAtual();

        // Contagem por pasta pro badge da grade — uma consulta agregada, não uma por
        // pasta, pra não repetir SELECT sobre a mesma tabela pequena.
        $porCategoria = $this->documentosDoPaciente()
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        // Documentos Pessoais (02/09/2026): pasta some da grade pra quem não é
        // admin|manager|administrative — não só escondida no blade, filtrada aqui,
        // que é o que também decide o que pastaAtual pode assumir (corrigirPastaAtual).
        $pastas = collect(Document::PASTAS)
            ->when(
                ! auth()->user()->podeAcessarDocumentosPessoais(),
                fn ($p) => $p->except(Document::CATEGORIA_DOCS_PESSOAIS)
            )
            ->map(fn ($pasta) => [
                ...$pasta,
                'total' => collect($pasta['categorias'])->sum(fn ($c) => $porCategoria[$c] ?? 0),
            ]);

        $documentos = $this->pastaAtual
            ? $this->documentosDoPaciente()
                ->whereIn('categoria', Document::PASTAS[$this->pastaAtual]['categorias'])
                ->with('uploadedBy')
                ->latest()
                ->get()
            : collect();

        return view('livewire.pacientes.documentos', [
            'pastas'            => $pastas,
            'documentos'        => $documentos,
            'categoriaOptions'  => $this->categoriasPermitidas(),
        ]);
    }
}
