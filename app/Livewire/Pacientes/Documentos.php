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
 * admin|manager|administrative, diferente do resto da ficha do paciente (que é aberta
 * a qualquer papel autenticado de propósito, para o profissional ver a própria agenda).
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
        $this->pastaAtual = array_key_exists($pasta, Document::PASTAS) ? $pasta : null;
    }

    public function voltarParaPastas()
    {
        $this->pastaAtual = null;
    }

    private function autorizarAcesso(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para acessar laudos e documentos.');
        }
    }

    public function abrirModal()
    {
        $this->autorizarAcesso();
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

        $this->validate([
            'categoria' => ['required', Rule::in(array_keys(Document::CATEGORIA_OPTIONS))],
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

        $this->documentoDoPaciente($documentId)->delete();
    }

    private function documentosDoPaciente()
    {
        return Document::where('documentable_type', Patient::class)
            ->where('documentable_id', $this->patient->id);
    }

    public function render()
    {
        // Contagem por pasta pro badge da grade — uma consulta agregada, não uma por
        // pasta, pra não repetir SELECT sobre a mesma tabela pequena.
        $porCategoria = $this->documentosDoPaciente()
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        $pastas = collect(Document::PASTAS)->map(fn ($pasta) => [
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
            'categoriaOptions'  => Document::CATEGORIA_OPTIONS,
        ]);
    }
}
