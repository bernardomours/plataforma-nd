<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Pacientes\Index as PacientesIndex;
use App\Livewire\Pacientes\Create as PacientesCreate;
use App\Livewire\Pacientes\Edit as PacientesEdit;
use App\Livewire\Pacientes\Agenda as PacientesAgenda;
use App\Livewire\Pacientes\CargaHoraria as PacientesCargaHoraria;
use App\Livewire\Profissionais\Index as ProfissionaisIndex;
use App\Livewire\Profissionais\Create as ProfissionaisCreate;
use App\Livewire\Profissionais\Edit as ProfissionaisEdit;
use App\Livewire\Servicos\Index as ServicosIndex;
use App\Livewire\TerapiasRealizadas\Index as TerapiasRealizadasIndex;
use App\Livewire\TerapiasRealizadas\Create as TerapiasRealizadasCreate;
use App\Livewire\TerapiasRealizadas\Edit as TerapiasRealizadasEdit;
use App\Livewire\ChSolicitada\Index as ChSolicitadaIndex;
use App\Livewire\Relatorios\Geral as RelatorioGeral;
use App\Livewire\Coordenacao\Acompanhamentos\Index as AcompanhamentosIndex;
use App\Livewire\Coordenacao\Cronograma\Index as CronogramaIndex;
use App\Livewire\Coordenacao\Vinculos\Index as VinculosIndex;
use App\Livewire\AvaliacoesNeuro\Index as AvaliacoesNeuroIndex;
use App\Livewire\AvaliacoesNeuro\Create as AvaliacoesNeuroCreate;
use App\Livewire\AvaliacoesNeuro\Edit as AvaliacoesNeuroEdit;
use App\Livewire\AgendaProfissionais\Index as AgendaProfissionaisIndex;
use App\Livewire\Usuarios\Index as UsuariosIndex;
use App\Livewire\Usuarios\Create as UsuariosCreate;
use App\Livewire\Usuarios\Edit as UsuariosEdit;
use App\Livewire\Controles\Index as ControlesIndex;
use App\Livewire\Producao\Fechamento as ProducaoFechamento;
use App\Livewire\Producao\Index as ProducaoIndex;
use App\Livewire\Producao\RegrasPagamento\Index as RegrasPagamentoIndex;
use App\Livewire\Producao\AtendimentosRealizados\Index as AtendimentosIndex;
use App\Livewire\AuditoriaHumana\Index as AtendimentoHumanaIndex;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    // PACIENTES
    Route::get('/pacientes', PacientesIndex::class)->name('pacientes.index');
    Route::get('/pacientes/cadastrar', PacientesCreate::class)->name('pacientes.create');
    Route::get('/pacientes/{patient}/editar', PacientesEdit::class)->name('pacientes.edit');
    Route::get('/pacientes/{patient}/agenda', PacientesAgenda::class)->name('pacientes.agenda');
    Route::get('/pacientes/{patient}/carga-horaria', PacientesCargaHoraria::class)->name('pacientes.carga-horaria');
    // COORDENACAO  
    Route::get('/acompanhamentos', AcompanhamentosIndex::class)->name('acompanhamentos.index');
    Route::get('/cronograma', CronogramaIndex::class)->name('cronograma.index');
    Route::get('/vinculos', VinculosIndex::class)->name('vinculos.index');
    // SERVIÇOS
    Route::get('/servicos', ServicosIndex::class)->name('servicos.index');
    // PROFISSIONAIS
    Route::get('/agenda-profissionais', AgendaProfissionaisIndex::class)->name('agenda-profissionais.index');
    // TERAPIAS REALIZADAS
    Route::get('/terapias-realizadas', TerapiasRealizadasIndex::class)->name('terapias-realizadas.index');
    
    // FREQUENCIA
    Route::middleware('role:admin|manager|administrative')->group(function () {
        Route::get('/terapias-realizadas/cadastrar', TerapiasRealizadasCreate::class)->name('terapias-realizadas.create');
        Route::get('/terapias-realizadas/{id}/editar', TerapiasRealizadasEdit::class)->name('terapias-realizadas.edit');
        Route::get('/ch-solicitada', ChSolicitadaIndex::class)->name('ch-solicitada.index');
        Route::get('/avaliacoes-neuro', AvaliacoesNeuroIndex::class)->name('avaliacoes-neuro.index');
        Route::get('/avaliacoes-neuro/registrar', AvaliacoesNeuroCreate::class)->name('avaliacoes-neuro.create');
        Route::get('/avaliacoes-neuro/{assessment}/diario', AvaliacoesNeuroEdit::class)->name('avaliacoes-neuro.edit');
        Route::get('/profissionais', ProfissionaisIndex::class)->name('profissionais.index');
        Route::get('/profissionais/cadastrar', ProfissionaisCreate::class)->name('profissionais.create');
        Route::get('/profissionais/{professional}/editar', ProfissionaisEdit::class)->name('profissionais.edit');
    });

    Route::get('/relatorio-geral', RelatorioGeral::class)->middleware('role:admin|manager')->name('relatorios.geral');

    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios', UsuariosIndex::class)->name('usuarios.index');
        Route::get('/usuarios/criar', UsuariosCreate::class)->name('usuarios.create');
        Route::get('/usuarios/{user}/editar', UsuariosEdit::class)->name('usuarios.edit');
        Route::get('/controles', ControlesIndex::class)->name('controles.index');
        Route::get('/auditoria-humana', AtendimentoHumanaIndex::class)->middleware('role:admin|manager')->name('auditoria.humana');
    });
});
    
Route::middleware(['auth', 'producao.access'])->prefix('producao')->group(function () {
    Route::get('/', ProducaoIndex::class)->name('producao.index');
    Route::get('/fechamento', ProducaoFechamento::class)->name('producao.fechamento');
    Route::get('/regras-pagamento', RegrasPagamentoIndex::class)->name('producao.regras');
    Route::get('/auditoria-atendimentos', AtendimentosIndex::class)->name('producao.auditoria');
    
    Route::post('/sair', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('producao.sair');
});

require __DIR__.'/auth.php';