<?php

namespace App\Livewire\TerapiasRealizadas;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Agreement;
use App\Models\Therapy;
use App\Models\ServiceType;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithFileUploads;
use Carbon\Carbon;

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
        $query = Appointment::with(['patient' => fn($q) => $q->withoutGlobalScopes(), 'therapy']);

        $allowedUnitIds = auth()->user()->getAllowedUnitIds();
        
        if ($allowedUnitIds !== null) {
            $query->whereHas('patient', function($q) use ($allowedUnitIds) {
                $q->whereIn('unit_id', $allowedUnitIds);
            });
        }

        if (!empty($this->patient_id)) {
            $query->where('patient_id', $this->patient_id);
        }
        
        if (!empty($this->professional_id)) {
            $query->where('professional_id', $this->professional_id);
        }

        if (!empty($this->agreement_id)) {
            $query->whereHas('patient', function ($q) {
                $q->withoutGlobalScopes()->where('agreement_id', $this->agreement_id);
            });
        }

        if (!empty($this->therapy_id)) {
            $query->where('therapy_id', $this->therapy_id);
        }

        if (!empty($this->service_type_id)) {
            $query->where('service_type_id', $this->service_type_id);
        }

        if (!empty($this->unit_id)) {
                    $query->whereHas('patient', function($q) {
                        $q->withoutGlobalScopes()->where('unit_id', $this->unit_id);
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
                $q->withoutGlobalScopes()->where('name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('appointment_date', 'desc')->orderBy('check_in', 'desc');
    }   

    public function render()
    {
        $query = $this->buildQuery();
        
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $unitsQuery = Unit::orderBy('name');
        $patientsQuery = Patient::withoutGlobalScopes()->orderBy('name');
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
        $appointment = Appointment::find($id); 
        
        if ($appointment) {
            $appointment->delete();
            
            $this->dispatch('notify', 'Atendimento excluído com sucesso!');
        }
    }

    public function exportExcel()
    {
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
        
        if (!$user->hasAnyRole(['admin', 'manager', 'administrative']) && $user->hasRole('profissional')) {
            if ($user->professional) {
                $this->professional_id = $user->professional->id;
            }
        }
    }

    public function processImport()
    {
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
        $todosPacientes = Patient::withoutGlobalScopes()->with(['agreement', 'unit'])->get();
        $todosProfissionais = Professional::all();

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
                        ]
                    );
                } else {
                    Appointment::create([
                        'guide' => null, 'appointment_date' => $appointmentDate, 'check_in' => $checkIn, 'check_out' => $checkOut, 'session_number' => $sessionNumber,
                        'patient_id' => $patient->id, 'professional_id' => $professional->id, 'therapy_id' => $therapy->id, 'service_type_id' => $serviceType->id,
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