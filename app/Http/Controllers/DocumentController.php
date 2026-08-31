<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;

/**
 * Rota dedicada (não ação do Livewire) porque o link precisa abrir de verdade numa
 * aba nova (target="_blank") — devolver Response de uma ação do Livewire funciona
 * pra baixar, mas navegaria a aba atual pra fora do app no caso do redirect()->away()
 * (URL assinada do S3/R2).
 */
class DocumentController extends Controller
{
    public function visualizar(Document $document)
    {
        $this->autorizar($document);

        try {
            return redirect()->away(
                Storage::disk($document->disk)->temporaryUrl($document->path, now()->addMinutes(10))
            );
        } catch (\RuntimeException) {
            // Disco local não implementa temporaryUrl(); serve inline diretamente.
            return Storage::disk($document->disk)->response($document->path, $document->nome_original);
        }
    }

    public function baixar(Document $document)
    {
        $this->autorizar($document);

        try {
            return redirect()->away(
                Storage::disk($document->disk)->temporaryUrl($document->path, now()->addMinutes(10), [
                    'ResponseContentDisposition' => 'attachment; filename="' . addslashes($document->nome_original) . '"',
                ])
            );
        } catch (\RuntimeException) {
            return Storage::disk($document->disk)->download($document->path, $document->nome_original);
        }
    }

    /**
     * SEGURANÇA: rota dedicada não passa pela cadeia de proteção do componente
     * Livewire (que só é alcançável através do paciente já autorizado via route
     * model binding com global scope). Aqui o ID do documento chega direto na URL,
     * então repete papel + isolamento por unidade — mesmo padrão de
     * AvaliacoesNeuro\Edit::authorizeAssessmentAccess(), lendo com
     * withoutGlobalScopes() de propósito para poder NEGAR mesmo quando o scope
     * esconderia o paciente.
     */
    private function autorizar(Document $document): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para acessar este documento.');
        }

        if ($document->documentable_type === Patient::class) {
            $unitId = Patient::withoutGlobalScopes()
                ->whereKey($document->documentable_id)
                ->value('unit_id');

            if (! auth()->user()->canAccessUnit($unitId)) {
                abort(403, 'Você não tem permissão para acessar documentos desta unidade.');
            }
        }
    }
}
