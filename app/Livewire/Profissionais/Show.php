<?php

namespace App\Livewire\Profissionais;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use App\Models\Professional;
use Illuminate\Support\Carbon;

#[Layout('layouts.app')]
class Show extends Component
{
    // SEGURANÇA (IDOR): sem #[Locked], nada impede uma requisição forjada trocando
    // este model por $wire.set('professional', <outro id>) — o synthesizer de Eloquent
    // do Livewire re-resolveria a propriedade pelo ID recebido usando a query PADRÃO
    // (sem a checagem de unidade feita em mount()), já que mount() não roda de novo em
    // requisições subsequentes. A tela é só leitura (sem nenhuma ação que dependa de um
    // "professional" diferente do carregado), então travar por completo é seguro aqui.
    #[Locked]
    public Professional $professional;

    public function mount(Professional $professional)
    {
        // $professional já vem resolvido pelo route model binding do Livewire — a rota
        // tem ->withTrashed() (ver web.php) porque, diferente de Edit (que não permite
        // editar profissional inativado), esta tela é só leitura e serve pra auditar
        // histórico de pagamento de quem já saiu, sem precisar restaurar o registro antes.
        //
        // CUIDADO: "public Professional $professional" com o mesmo nome do parâmetro de
        // rota {professional} faz o Livewire resolver o model ANTES de mount() rodar
        // (Livewire\Drawer\ImplicitRouteBinding::resolveComponentProps()). Um findOrFail()
        // manual aqui dentro receberia a INSTANCIA já resolvida em vez do ID e quebraria
        // silenciosamente (a query monta um WHERE com o objeto no lugar do id e nunca
        // casa, virando 404) — foi exatamente esse bug que a versão anterior tinha.
        $professional->load([
            'units',
            'therapies',
            'paymentRules' => fn ($q) => $q->orderBy('id'),
            'paymentRules.therapy',
            'paymentRules.agreement',
            'paymentRules.serviceType',
        ]);

        // SEGURANÇA (IDOR): mesmo padrão de Edit::authorizeUnitAccess() — Professional
        // não tem unit_id, o vínculo é a pivô professional_unit, então sem esta checagem
        // trocar o ID na URL abria a ficha de qualquer profissional de qualquer clínica.
        if (! auth()->user()->canAccessAnyUnit($professional->units->pluck('id')->toArray())) {
            abort(403, 'Você não tem permissão para acessar profissionais desta unidade.');
        }

        $this->professional = $professional;
    }

    /**
     * Estado de um marco de reajuste (9 ou 18 meses) pra uma regra específica — usada
     * pela trilha de tempo de casa na view. Não recalcula nada: só interpreta as colunas
     * já gravadas (aplicado_em) contra mesesDeEmpresa() do profissional. Calculado aqui
     * (e não com $this-> direto no blade) pra manter a view simples de testar isolada.
     */
    private function statusDoMarco(int $mesesAlvo, ?Carbon $aplicadoEm): array
    {
        if ($aplicadoEm) {
            return ['estado' => 'aplicado', 'data' => $aplicadoEm];
        }

        $meses = $this->professional->mesesDeEmpresa();

        if ($meses === null) {
            return ['estado' => 'indefinido', 'data' => null];
        }

        if ($meses >= $mesesAlvo) {
            return ['estado' => 'pendente', 'data' => null];
        }

        $previsao = $this->professional->contract_date?->copy()->addMonths($mesesAlvo);

        return ['estado' => 'futuro', 'data' => $previsao];
    }

    public function render()
    {
        $regras = $this->professional->paymentRules->map(fn ($regra) => [
            'regra' => $regra,
            'marco9' => $this->statusDoMarco(9, $regra->reajuste_9_meses_aplicado_em),
            'marco18' => $this->statusDoMarco(18, $regra->reajuste_18_meses_aplicado_em),
        ]);

        return view('livewire.profissionais.show', ['regras' => $regras]);
    }
}
