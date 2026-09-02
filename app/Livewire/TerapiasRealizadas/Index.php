<?php

namespace App\Livewire\TerapiasRealizadas;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientService;
use App\Models\Professional;
use App\Models\Agreement;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletingScope;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination, WithFileUploads;

    public $patient_id = '';
    public $professional_id = '';
    public $agreement_id = '';
    public $therapy_id = '';
    public $service_type_id = '';
    public $unit_id = '';
    public $guide = '';
    public $start_date = '';
    public $end_date = '';
    public $search = '';

    // Trava por vínculo de coordenação/supervisão (PatientService) — null quando o
    // usuário não é coordenador/supervisor (sem trava nenhuma dessa natureza); array
    // (mesmo vazio) quando é, restringindo a query aos pacientes vinculados a ele.
    public ?array $patientIdsVinculados = null;

    // para o import da unimed
    public $showImportModal = false;
    public $unidade_relatorio = '';
    public $arquivo_csv;
    public $importMessages = []; 
    public $importSuccess = false;

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'patient_id', 'professional_id', 'agreement_id', 
            'therapy_id', 'service_type_id', 'unit_id', 
            'guide', 'start_date', 'end_date', 'search'
        ]);
        $this->resetPage();
    }

    private function buildQuery()
    {
        // SEGURANÇA: withoutGlobalScopes() derrubava IsolatesByUnit E o SoftDeletingScope
        // de uma vez. Aqui a intenção real é apenas continuar exibindo o paciente que já
        // teve saída registrada (soft delete), então removemos SOMENTE o SoftDeletingScope
        // e mantemos o isolamento por unidade ativo. Mesmo raciocínio nas ocorrências abaixo.
        $query = Appointment::with([
            'patient' => fn($q) => $q->withoutGlobalScope(SoftDeletingScope::class)->with('agreement'),
            'therapy',
            'agreement',
        ]);

        $allowedUnitIds = auth()->user()->getAllowedUnitIds();
        
        if ($allowedUnitIds !== null) {
            $query->whereHas('patient', function($q) use ($allowedUnitIds) {
                $q->whereIn('unit_id', $allowedUnitIds);
            });
        }

        // Trava de coordenador/supervisor (ver mount()): restringe às crianças
        // vinculadas. Array vazio (coordenador sem nenhum vínculo cadastrado) tem que
        // devolver zero linhas, não todas — por isso o whereIn entra mesmo vazio, nunca
        // um "when" condicionado a !empty().
        if ($this->patientIdsVinculados !== null) {
            $query->whereIn('patient_id', $this->patientIdsVinculados);
        }

        if (!empty($this->patient_id)) {
            $query->where('patient_id', $this->patient_id);
        }
        
        if (!empty($this->professional_id)) {
            $query->where('professional_id', $this->professional_id);
        }

        // CORREÇÃO: filtra pelo convênio DO ATENDIMENTO. O fallback para o paciente cobre
        // os registros anteriores à migration que congelou o campo no atendimento.
        if (!empty($this->agreement_id)) {
            $query->where(function ($q) {
                $q->where('appointments.agreement_id', $this->agreement_id)
                  ->orWhere(function ($legado) {
                      $legado->whereNull('appointments.agreement_id')
                             ->whereHas('patient', function ($p) {
                                 $p->withoutGlobalScope(SoftDeletingScope::class)
                                   ->where('agreement_id', $this->agreement_id);
                             });
                  });
            });
        }

        if (!empty($this->therapy_id)) {
            $query->where('therapy_id', $this->therapy_id);
        }

        if (!empty($this->service_type_id)) {
            $query->where('service_type_id', $this->service_type_id);
        }

        // CORREÇÃO: filtra pela unidade DO ATENDIMENTO, com o mesmo fallback.
        if (!empty($this->unit_id)) {
            $query->where(function ($q) {
                $q->where('appointments.unit_id', $this->unit_id)
                  ->orWhere(function ($legado) {
                      $legado->whereNull('appointments.unit_id')
                             ->whereHas('patient', function ($p) {
                                 $p->withoutGlobalScope(SoftDeletingScope::class)
                                   ->where('unit_id', $this->unit_id);
                             });
                  });
            });
        }

        if (!empty($this->guide)) {
            $query->where('guide', 'like', '%' . $this->guide . '%');
        }

        if (!empty($this->start_date)) {
            $query->whereDate('appointment_date', '>=', $this->start_date);
        }

        if (!empty($this->end_date)) {
            $query->whereDate('appointment_date', '<=', $this->end_date);
        }

        if (!empty($this->search)) {
            $query->whereHas('patient', function($q) {
                $q->withoutGlobalScope(SoftDeletingScope::class)->where('name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('appointment_date', 'desc')->orderBy('check_in', 'desc');
    }   

    public function render()
    {
        $query = $this->buildQuery();
        
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $unitsQuery = Unit::orderBy('name');
        // SEGURANÇA: mantém o isolamento por unidade (IsolatesByUnit) e remove apenas o
        // SoftDeletingScope, para que o filtro continue listando pacientes com saída.
        $patientsQuery = Patient::withoutGlobalScope(SoftDeletingScope::class)->orderBy('name');
        $professionalsQuery = Professional::orderBy('name');

        if ($allowedUnitIds !== null) {
            $unitsQuery = Unit::orderBy('name');
            if ($allowedUnitIds !== null) {
                $unitsQuery->whereIn('id', $allowedUnitIds);
            }
            
            $patientsQuery->whereIn('unit_id', $allowedUnitIds);
            
            $professionalsQuery->whereHas('units', function($q) use ($allowedUnitIds) {
                $q->whereIn('units.id', $allowedUnitIds);
            });
        }

        // Mesma trava do buildQuery(): sem isso o combobox de paciente listaria toda a
        // unidade, e o coordenador selecionaria uma criança que não é sua e sempre veria
        // "nenhum resultado" sem entender o porquê.
        if ($this->patientIdsVinculados !== null) {
            $patientsQuery->whereIn('id', $this->patientIdsVinculados);
        }

        $totalConsultas = $query->count();
        $totalSessoes = $query->sum('session_number');

        return view('livewire.terapias-realizadas.index', [
            'totalConsultas' => $totalConsultas,
            'totalSessoes' => $totalSessoes,
            'appointments' => $this->buildQuery()->paginate(10),
            'patients' => $patientsQuery->get(),
            'professionals' => $professionalsQuery->get(),
            'agreements' => Agreement::orderBy('name')->get(),
            'therapies' => Therapy::orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'units' => $unitsQuery->get(),
        ]);
    }

    public $selectedColumns = [
        'nome' => true,
        'data' => true,
        'guia' => false,
        'terapia' => true,
        'tipo_atendimento' => false,
        'check_in' => true,
        'check_out' => true,
        'qtd_sessoes' => true,
        'profissional' => false,
        'registrado_em' => false,
        'atualizado_em' => false,
    ];

    public function resetColumns()
    {
        $this->selectedColumns = [
            'nome' => true,
            'data' => true,
            'guia' => false,
            'terapia' => true,
            'tipo_atendimento' => false,
            'check_in' => true,
            'check_out' => true,
            'qtd_sessoes' => true,
            'profissional' => false,
            'registrado_em' => false,
            'atualizado_em' => false,
        ];
    }

    public function exportPdf()
    {
        // SEGURANÇA: a tela só mostra este botão para admin|manager, mas a ação do
        // Livewire não passa pelo middleware da rota — sem esta checagem, qualquer
        // usuário autenticado podia disparar o método direto e baixar o relatório.
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para exportar relatórios.');
        }

        $appointments = $this->buildQuery()->get();

        $totalConsultas = $appointments->count();
        $totalSessoes = $appointments->sum('session_number');

        $pdf = Pdf::loadView('pdf.terapias', [
            'appointments' => $appointments,
            'totalConsultas' => $totalConsultas,
            'totalSessoes' => $totalSessoes,
            'selectedColumns' => $this->selectedColumns, 
        ]);

        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'relatorio-terapias-' . now()->timezone('America/Fortaleza')->format('d-m-Y_H-i') . '.pdf');
    }

    public function deleteAppointment($id)
    {
        // SEGURANÇA: a coluna "Ações" só aparece para admin|manager|administrative.
        // Sem esta checagem, um profissional podia chamar o método direto e excluir
        // qualquer atendimento da própria unidade — o teste de unidade abaixo não
        // bloqueia isso, porque ele também é o dono da sua unidade.
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para excluir atendimentos.');
        }

        // SEGURANÇA (IDOR): Appointment não tem unit_id nem global scope. Sem esta
        // checagem, um ID no payload do Livewire permitia excluir o atendimento de um
        // paciente de outra clínica. A unidade vem do paciente vinculado.
        $appointment = Appointment::find($id);

        if ($appointment) {
            $patientUnitId = Patient::withoutGlobalScopes()
                ->whereKey($appointment->patient_id)
                ->value('unit_id');

            if (! auth()->user()->canAccessUnit($patientUnitId)) {
                abort(403, 'Você não tem permissão para excluir atendimentos desta unidade.');
            }

            $appointment->delete();

            $this->dispatch('notify', 'Atendimento excluído com sucesso!');
        }
    }

    public function exportExcel()
    {
        // SEGURANÇA: mesmo caso do exportPdf — a ação do Livewire não passa pelo
        // middleware da rota, então o botão escondido na tela não basta sozinho.
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para exportar relatórios.');
        }

        // 1. Executa a query aplicando as mesmas regras de filtragem e unidade permitida
        $atendimentos = $this->buildQuery()->get();

        $fileName = 'atendimentos-' . now()->timezone('America/Fortaleza')->format('d-m-Y_H-i') . '.csv';

        return response()->streamDownload(function () use ($atendimentos) {
            $file = fopen('php://output', 'w');
            
            // Força o Excel a reconhecer caracteres especiais (acentos, cedilhas, etc)
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $separador = ';';

            // 2. Monta o cabeçalho dinamicamente com base nas colunas visíveis
            $headers = [];
            if ($this->selectedColumns['nome']) $headers[] = 'Nome do Paciente';
            if ($this->selectedColumns['data']) $headers[] = 'Data';
            if ($this->selectedColumns['guia']) $headers[] = 'Guia';
            if ($this->selectedColumns['terapia']) $headers[] = 'Terapia';
            if ($this->selectedColumns['tipo_atendimento']) $headers[] = 'Tipo de Atendimento';
            if ($this->selectedColumns['check_in']) $headers[] = 'Check-in';
            if ($this->selectedColumns['check_out']) $headers[] = 'Check-out';
            if ($this->selectedColumns['qtd_sessoes']) $headers[] = 'Qtd de Sessões';
            if ($this->selectedColumns['profissional']) $headers[] = 'Profissional';
            if ($this->selectedColumns['registrado_em']) $headers[] = 'Registrado em';
            if ($this->selectedColumns['atualizado_em']) $headers[] = 'Atualizado em';

            fputcsv($file, $headers, $separador);

            // 3. Alimenta as linhas da planilha dinamicamente
            foreach ($atendimentos as $atendimento) {
                $row = [];
                
                if ($this->selectedColumns['nome']) {
                    $row[] = $atendimento->patient->name ?? '-';
                }
                if ($this->selectedColumns['data']) {
                    $row[] = $atendimento->appointment_date ? \Carbon\Carbon::parse($atendimento->appointment_date)->format('d/m/Y') : '-';
                }
                if ($this->selectedColumns['guia']) {
                    $row[] = $atendimento->guide ?? '-';
                }
                if ($this->selectedColumns['terapia']) {
                    $row[] = $atendimento->therapy->name ?? '-';
                }
                if ($this->selectedColumns['tipo_atendimento']) {
                    $row[] = $atendimento->serviceType->name ?? '-';
                }
                if ($this->selectedColumns['check_in']) {
                    $row[] = $atendimento->check_in ? \Carbon\Carbon::parse($atendimento->check_in)->format('H:i') : '-';
                }
                if ($this->selectedColumns['check_out']) {
                    $row[] = $atendimento->check_out ? \Carbon\Carbon::parse($atendimento->check_out)->format('H:i') : '-';
                }
                if ($this->selectedColumns['qtd_sessoes']) {
                    $row[] = $atendimento->session_number ?? '0';
                }
                if ($this->selectedColumns['profissional']) {
                    $row[] = $atendimento->professional->name ?? '-';
                }
                if ($this->selectedColumns['registrado_em']) {
                    $row[] = $atendimento->created_at ? $atendimento->created_at->format('d/m/Y H:i') : '-';
                }
                if ($this->selectedColumns['atualizado_em']) {
                    $row[] = $atendimento->updated_at ? $atendimento->updated_at->format('d/m/Y H:i') : '-';
                }

                fputcsv($file, $row, $separador);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function rules()
    {
        return [
            'unidade_relatorio' => 'required|string',
            'arquivo_csv' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ];
    }

    public function mount()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'manager', 'administrative'])) {
            return;
        }

        // Qualquer papel não-organizacional que chegue aqui (coordenador, supervisor
        // ou profissional comum) abre a tela já no mês vigente — pedido do usuário,
        // estendido de coordenador/supervisor pra profissional também. Valor inicial
        // dos campos de data, não uma trava: continuam editáveis.
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

        // Coordenador/supervisor vê as crianças que tem vinculadas (mesma fonte de
        // Coordenacao\Vinculos\Index: PatientService.coordinator_id/supervisor_id),
        // não as próprias sessões atendidas. Checado ANTES do bloco "profissional" logo
        // abaixo porque ~17 usuários acumulam papel coordinator + profissional (ver
        // CLAUDE.md "Autorização") — sem essa ordem, a trava por professional_id abaixo
        // pegaria esses coordenadores também, escondendo os pacientes que coordenam.
        if ($user->hasAnyRole(['coordinator', 'supervisor']) && $user->professional) {
            $professionalId = $user->professional->id;

            $this->patientIdsVinculados = PatientService::query()
                ->where('coordinator_id', $professionalId)
                ->orWhere('supervisor_id', $professionalId)
                ->pluck('patient_id')
                ->unique()
                ->values()
                ->all();

            // Coordenador ABA (papel Spatie coordinator + atende ABA na própria
            // especialidade) abre a tela já filtrada pra ABA — é a terapia que importa
            // pra ele entre as várias que os pacientes coordenados podem ter. Continua
            // um filtro normal, não uma trava: dá pra trocar pra outra terapia na tela.
            if ($user->hasRole('coordinator') && $user->professional->atendeAba()) {
                $this->therapy_id = Therapy::where('name', 'ABA')->value('id') ?? '';
            }

            return;
        }

        if ($user->hasRole('profissional') && $user->professional) {
            $this->professional_id = $user->professional->id;
        }
    }

    public function processImport()
    {
        // SEGURANÇA: o modal só é aberto pelo botão "Importar CSV", visível apenas para
        // admin|manager, mas o HTML do modal fica sempre no DOM (só escondido por
        // x-show) e o wire:submit não passa pelo middleware da rota. Sem esta checagem,
        // qualquer usuário autenticado podia forjar a chamada e gravar atendimentos em
        // massa cruzando pacientes/profissionais.
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Você não tem permissão para importar atendimentos.');
        }

        // 1. Validação do formulário
        $this->validate();
        
        $this->importMessages = [];
        $this->importSuccess = false;

        // 2. Leitura do Arquivo
        $filePath = $this->arquivo_csv->getRealPath();
        $unidadeSelecionada = $this->unidade_relatorio;
        
        $file = fopen($filePath, 'r');
        fgetcsv($file, 0, ';'); // Pula o cabeçalho

        $importados = 0;
        $numeroLinha = 1;
        $errosDetalhados = [];

        // Pré-carrega dados para performance e aplica scopes se existirem
        // SEGURANÇA (crítico): estas duas coleções alimentam o "match" por nome do CSV.
        // Com withoutGlobalScopes()/Professional::all() elas varriam TODAS as clínicas,
        // então uma planilha importada por um usuário de uma unidade podia casar com um
        // paciente/profissional de outra e gravar atendimento cruzado entre clínicas.
        // Agora ambas respeitam as unidades permitidas ao usuário logado.
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        // Remove só o SoftDeletingScope: pacientes com saída registrada ainda precisam
        // ser reconhecidos no CSV, mas continuam limitados às unidades permitidas.
        $pacientesQuery = Patient::withoutGlobalScope(SoftDeletingScope::class)
            ->with(['agreement', 'unit']);

        $profissionaisQuery = Professional::query();

        if ($allowedUnitIds !== null) {
            $pacientesQuery->whereIn('unit_id', $allowedUnitIds);
            $profissionaisQuery->whereHas('units', fn($q) => $q->whereIn('units.id', $allowedUnitIds));
        }

        $todosPacientes = $pacientesQuery->get();
        $todosProfissionais = $profissionaisQuery->get();

        // 3. Processamento Linha a Linha
        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $numeroLinha++;
            $row = array_map(fn($value) => mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1'), $row);

            if (!isset($row[1]) || trim($row[1]) === '') continue;

            $motivosErroLinha = [];
            $numeroGuia = trim($row[2] ?? '');

            // Data
            $appointmentDate = null;
            try {
                $appointmentDate = Carbon::createFromFormat('d/m/Y', trim($row[1]))->format('Y-m-d');
            } catch (\Exception $e) {
                $motivosErroLinha[] = "Data inválida ou em branco";
            }

            // Horários
            $checkinBruto = trim($row[12] ?? '');
            $checkoutBruto = trim($row[13] ?? '');
            $checkIn = explode(' ', $checkinBruto)[1] ?? null;
            $checkOut = explode(' ', $checkoutBruto)[1] ?? null;
            if (!$checkIn) $motivosErroLinha[] = "Check-in ausente";

            // Terapia e Local
            $procedimentoBruto = strtoupper(trim($row[16] ?? ''));
            $terapiaNome = 'INDEFINIDA';
            $tipoAtendimentoNome = 'Clínica';

            if (str_contains($procedimentoBruto, 'ABA')) {
                $terapiaNome = 'ABA';
                if (str_contains($procedimentoBruto, 'DOMICILIAR')) {
                    $tipoAtendimentoNome = 'Domiciliar';
                } elseif (str_contains($procedimentoBruto, 'ESCOLAR')) {
                    $tipoAtendimentoNome = 'Escolar';
                }
            } elseif (str_contains($procedimentoBruto, 'DENVER')) { $terapiaNome = 'DENVER'; } 
            elseif (str_contains($procedimentoBruto, 'PSICOPEDAGOGIA')) { $terapiaNome = 'PSICOPEDAGOGIA'; } 
            elseif (str_contains($procedimentoBruto, 'FONO')) { $terapiaNome = 'FONOAUDIOLOGIA'; } 
            elseif (str_contains($procedimentoBruto, 'PSICOMOTRICIDADE')) { $terapiaNome = 'PSICOMOTRICIDADE'; } 
            elseif (str_contains($procedimentoBruto, 'TO -') || str_contains($procedimentoBruto, 'TERAPIA OCUPACIONAL')) { $terapiaNome = 'TERAPIA OCUPACIONAL'; } 
            elseif (str_contains($procedimentoBruto, 'TERAPIA ALIMENTAR')) { $terapiaNome = 'TERAPIA ALIMENTAR'; } 
            elseif (str_contains($procedimentoBruto, 'FISIO')) { $terapiaNome = 'FISIOTERAPIA'; } 
            elseif (str_contains($procedimentoBruto, 'ANAMNESE')) { $terapiaNome = 'ANAMNESE'; } 
            elseif (str_contains($procedimentoBruto, 'AVALIA')) { $terapiaNome = 'AVALIAÇÃO'; } 
            elseif (str_contains($procedimentoBruto, 'PSICO')) { $terapiaNome = 'PSICOTERAPIA'; } 
            else { $terapiaNome = $procedimentoBruto; }

            $therapy = Therapy::firstOrCreate(['name' => $terapiaNome]);
            $serviceType = ServiceType::firstOrCreate(['name' => $tipoAtendimentoNome]);

            // Nomes e limpeza
            $patientNameCsv = trim($row[6] ?? '');
            $carteiraCsvRaw = trim($row[7] ?? '');
            $carteiraCsv = ltrim(preg_replace('/[^0-9]/', '', $carteiraCsvRaw), '0');
            $professionalNameCsv = trim($row[10] ?? '');

            $limparNome = function($nome) {
                $slug = \Illuminate\Support\Str::slug($nome);
                $slug = preg_replace('/-(de|do|da|dos|das)-/', '-', $slug);
                $slug = preg_replace('/-(de|do|da|dos|das)$/', '', $slug);
                return $slug;
            };

            $patientSlugCsv = $limparNome($patientNameCsv);
            $professionalSlugCsv = $limparNome($professionalNameCsv);

            // Busca de Paciente
            $pacientesFiltrados = $todosPacientes->filter(function($p) use ($unidadeSelecionada) {
                $nomeConvenio = $p->agreement->name ?? '';
                $nomeUnidade = $p->unit->city ?? $p->unit->name ?? '';
                $ehUnimed = str_contains(strtolower($nomeConvenio), 'unimed');
                $ehUnidadeCorreta = str_contains(strtolower($nomeUnidade), strtolower($unidadeSelecionada));
                return $ehUnimed && $ehUnidadeCorreta;
            });

            $patient = null;
            if (strlen($carteiraCsv) > 5) {
                $patient = $pacientesFiltrados->first(function($p) use ($carteiraCsv) {
                    $dbCarteira = ltrim(preg_replace('/[^0-9]/', '', $p->agreement_number ?? ''), '0');
                    return $dbCarteira === $carteiraCsv;
                });
            }

            if (!$patient) {
                $melhorPaciente = null;
                $maiorSimilaridadePaciente = 0;
                foreach ($pacientesFiltrados as $p) {
                    $dbSlug = $limparNome($p->name);
                    if ($dbSlug === $patientSlugCsv) {
                        $melhorPaciente = $p; $maiorSimilaridadePaciente = 100; break;
                    }
                    if (strlen($patientSlugCsv) > 8 && (str_starts_with($dbSlug, $patientSlugCsv) || str_starts_with($patientSlugCsv, $dbSlug))) {
                        $melhorPaciente = $p; $maiorSimilaridadePaciente = 95; continue;
                    }
                    similar_text($dbSlug, $patientSlugCsv, $porcentagem);
                    if ($porcentagem > $maiorSimilaridadePaciente) {
                        $maiorSimilaridadePaciente = $porcentagem; $melhorPaciente = $p;
                    }
                }
                if ($melhorPaciente && $maiorSimilaridadePaciente >= 85) {
                    $patient = $melhorPaciente;
                }
            }

            // Busca de Profissional
            $melhorProfissional = null;
            $maiorSimilaridadeProfissional = 0;
            foreach ($todosProfissionais as $pro) {
                $dbSlug = $limparNome($pro->name);
                if ($dbSlug === $professionalSlugCsv) {
                    $melhorProfissional = $pro; $maiorSimilaridadeProfissional = 100; break;
                }
                if (strlen($professionalSlugCsv) > 5 && (str_starts_with($dbSlug, $professionalSlugCsv) || str_starts_with($professionalSlugCsv, $dbSlug))) {
                    $melhorProfissional = $pro; $maiorSimilaridadeProfissional = 95; continue;
                }
                $tamanho = min(strlen($dbSlug), strlen($professionalSlugCsv));
                if ($tamanho > 0) {
                    similar_text(substr($dbSlug, 0, $tamanho), substr($professionalSlugCsv, 0, $tamanho), $porcentagem);
                    if ($porcentagem > $maiorSimilaridadeProfissional) {
                        $maiorSimilaridadeProfissional = $porcentagem; $melhorProfissional = $pro;
                    }
                }
            }
            $professional = ($melhorProfissional && $maiorSimilaridadeProfissional >= 80) ? $melhorProfissional : null;

            // Verificações
            if (!$patient) $motivosErroLinha[] = "Paciente '{$patientNameCsv}' não encontrado";
            if (!$professional) $motivosErroLinha[] = "Profissional '{$professionalNameCsv}' não encontrado";

            if (count($motivosErroLinha) > 0) {
                $errosDetalhados[] = "Linha {$numeroLinha}: " . implode(', ', $motivosErroLinha);
                continue;
            }

            // Salvar no Banco
            $sessionNumber = isset($row[9]) ? (int) trim($row[9]) : 1;

            try {
                if (!empty($numeroGuia)) {
                    Appointment::updateOrCreate(
                        ['guide' => $numeroGuia],
                        [
                            'appointment_date' => $appointmentDate, 'check_in' => $checkIn, 'check_out' => $checkOut, 'session_number' => $sessionNumber,
                            'patient_id' => $patient->id, 'professional_id' => $professional->id, 'therapy_id' => $therapy->id, 'service_type_id' => $serviceType->id,
                            // Congela convênio/unidade do paciente casado no momento da importação.
                            'agreement_id' => $patient->agreement_id, 'unit_id' => $patient->unit_id,
                        ]
                    );
                } else {
                    Appointment::create([
                        'guide' => null, 'appointment_date' => $appointmentDate, 'check_in' => $checkIn, 'check_out' => $checkOut, 'session_number' => $sessionNumber,
                        'patient_id' => $patient->id, 'professional_id' => $professional->id, 'therapy_id' => $therapy->id, 'service_type_id' => $serviceType->id,
                        'agreement_id' => $patient->agreement_id, 'unit_id' => $patient->unit_id,
                    ]);
                }
                $importados++;
            } catch (\Exception $e) {
                $errosDetalhados[] = "Linha {$numeroLinha}: Erro de banco de dados.";
            }
        }
        fclose($file);

        // 4. Feedback
        $this->reset(['arquivo_csv']);
        
        if (count($errosDetalhados) > 0) {
            $this->importMessages = $errosDetalhados;
            session()->flash('warning', "Importámos {$importados} registos, mas ocorreram erros. Verifique a lista abaixo.");
        } else {
            $this->importSuccess = true;
            session()->flash('success', "Todos os {$importados} atendimentos foram importados com sucesso!");
        }
    }
    
}