<?php

namespace App\Livewire\AuditoriaHumana;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Appointment;
use App\Models\NeuroSession;
use App\Models\Unit;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;

    public $arquivo_csv;
    public $mes;
    public $ano;
    public $unidade_relatorio = '';
    
    public $resultados = [];
    public $processado = false;

    public function mount()
    {
        $this->mes = Carbon::now()->format('m');
        $this->ano = Carbon::now()->format('Y');
    }

    public function processar()
    {
        // 1. Validação para obrigar a escolha da unidade, mês, ano e arquivo
        $this->validate([
            'arquivo_csv' => 'required|file|mimes:csv,txt|max:5120', // Max 5MB
            'mes' => 'required|numeric',
            'ano' => 'required|numeric',
            'unidade_relatorio' => 'required',
        ]);

        // 2. LER O CSV DA HUMANA
        $path = $this->arquivo_csv->getRealPath();
        $file = fopen($path, 'r');
        
        $humanaData = [];

        while (($linha = fgetcsv($file, 1000, ";")) !== FALSE) {
            if (count($linha) < 10 || str_contains(strtolower($linha[0]), 'guia')) {
                continue;
            }

            // Garante que a leitura do CSV venha em UTF-8 para evitar alguns caracteres bizarros
            $linha = array_map(fn($value) => mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1'), $linha);

            $pacienteOriginal = trim(mb_strtoupper($linha[3]));
            $terapiaBruta = trim(mb_strtoupper($linha[8]));
            $qtd = (int) $linha[9];

            // --- NOVA LÓGICA DE TRADUÇÃO DE TERAPIAS (O Caça-Palavras) ---
            $terapiaSistema = $terapiaBruta; // Padrão caso não ache nada

            if (str_contains($terapiaBruta, 'ABA')) {
                $terapiaSistema = 'ABA';
            } elseif (str_contains($terapiaBruta, 'PSICOPEDAGOGIA')) {
                $terapiaSistema = 'PSICOPEDAGOGIA';
            } elseif (str_contains($terapiaBruta, 'PSICOMOTRICIDADE')) {
                $terapiaSistema = 'PSICOMOTRICIDADE';
            } elseif (str_contains($terapiaBruta, 'FONO')) {
                $terapiaSistema = 'FONOAUDIOLOGIA';
            } elseif (str_contains($terapiaBruta, 'TO -') || str_contains($terapiaBruta, 'TERAPIA OCUPACIONAL') || str_contains($terapiaBruta, 'AYRES')) {
                $terapiaSistema = 'TERAPIA OCUPACIONAL';
            } elseif (str_contains($terapiaBruta, 'PSICOTERAPIA') || str_contains($terapiaBruta, 'PSICOLOGIA')) {
                $terapiaSistema = 'PSICOTERAPIA';
            } elseif (str_contains($terapiaBruta, 'AVALIA') && str_contains($terapiaBruta, 'NEURO')) {
                $terapiaSistema = 'AVALIAÇÃO NEURO';
            }

            // Cria a chave única para agrupar (Paciente + Terapia Limpa)
            $chave = $pacienteOriginal . '|' . $terapiaSistema;

            if (!isset($humanaData[$chave])) {
                $humanaData[$chave] = [
                    'paciente' => $pacienteOriginal,
                    'terapia' => $terapiaSistema,
                    'qtd_humana' => 0
                ];
            }
            $humanaData[$chave]['qtd_humana'] += $qtd;
        }
        fclose($file);

        // 3. BUSCAR DADOS DO SISTEMA NO MESMO PERÍODO E UNIDADE
        $sistemaData = [];

        // --- 3.1 Busca Terapias Normais (Appointments) ---
        $queryAppointments = Appointment::with(['patient', 'therapy'])
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->whereHas('patient', function ($q) {
                // TRAVA DE SEGURANÇA: Buscar apenas pacientes do convênio Humana (ID 1)
                $q->where('agreement_id', 1);
                
                // Aplica o filtro da Unidade
                if ($this->unidade_relatorio) {
                    $q->where('unit_id', $this->unidade_relatorio);
                }
            });

        $systemAppointments = $queryAppointments->get();

        foreach ($systemAppointments as $app) {
            if (!$app->patient || !$app->therapy) continue;

            $paciente = trim(mb_strtoupper($app->patient->name));
            $terapia = trim(mb_strtoupper($app->therapy->name));
            
            $qtd = $app->session_number ?? 1; 

            $chave = $paciente . '|' . $terapia;

            if (!isset($sistemaData[$chave])) {
                $sistemaData[$chave] = 0;
            }
            $sistemaData[$chave] += $qtd;
        }

        // --- 3.2 Busca Avaliações Neuro (Diário de Sessões) ---
        $queryNeuro = NeuroSession::with(['assessment.patient'])
            ->whereYear('date', $this->ano)
            ->whereMonth('date', $this->mes)
            ->whereHas('assessment.patient', function ($q) {
                // TRAVA DE SEGURANÇA: Buscar apenas pacientes do convênio Humana (ID 1)
                $q->where('agreement_id', 1);
                
                // Filtra pela unidade cruzando a ponte
                if ($this->unidade_relatorio) {
                    $q->where('unit_id', $this->unidade_relatorio);
                }
            });

        $neuroSessions = $queryNeuro->get();

        foreach ($neuroSessions as $session) {
            // Verifica se as relações existem para o código não quebrar
            if (!$session->assessment || !$session->assessment->patient) continue;

            $paciente = trim(mb_strtoupper($session->assessment->patient->name));
            $terapia = 'AVALIAÇÃO NEURO'; // Nome fixo que dá "Match" com a conversão do CSV
            $qtd = 1; // Cada registro na tabela NeuroSession é 1 sessão realizada

            $chave = $paciente . '|' . $terapia;

            if (!isset($sistemaData[$chave])) {
                $sistemaData[$chave] = 0;
            }
            $sistemaData[$chave] += $qtd;
        }

        // 4. CRUZAR OS DADOS (O MATCH)
        $comparativo = [];
        $todasAsChaves = array_unique(array_merge(array_keys($humanaData), array_keys($sistemaData)));

        foreach ($todasAsChaves as $chave) {
            $partes = explode('|', $chave);
            $paciente = $partes[0];
            $terapia = $partes[1];

            $qtdHumana = $humanaData[$chave]['qtd_humana'] ?? 0;
            $qtdSistema = $sistemaData[$chave] ?? 0;

            if ($qtdSistema == $qtdHumana) {
                $status = 'Bateu';
                $cor = 'green';
            } elseif ($qtdSistema > $qtdHumana) {
                $status = 'Falta no Relatório Humana'; 
                $cor = 'red';
            } else {
                $status = 'Falta no Sistema'; 
                $cor = 'yellow';
            }

            $comparativo[] = [
                'paciente' => $paciente,
                'terapia' => $terapia,
                'qtd_sistema' => $qtdSistema,
                'qtd_humana' => $qtdHumana,
                'status' => $status,
                'cor' => $cor
            ];
        }

        usort($comparativo, function($a, $b) {
            // Se o nome do paciente for igual, ordena pela terapia
            if ($a['paciente'] === $b['paciente']) {
                return $a['terapia'] <=> $b['terapia'];
            }
            
            // Retorna a ordem alfabética dos pacientes
            return $a['paciente'] <=> $b['paciente'];
        });

        $this->resultados = $comparativo;
        $this->processado = true;
    }

    public function novaAuditoria()
    {
        $this->reset(['arquivo_csv', 'resultados', 'processado', 'unidade_relatorio']);
    }

    public function exportarPDF()
    {
        // 1. Pega o nome da unidade para o cabeçalho
        $unidadeNome = 'Todas as Unidades';
        if ($this->unidade_relatorio) {
            $unidade = Unit::find($this->unidade_relatorio);
            $unidadeNome = $unidade ? ($unidade->city ?? $unidade->name) : 'Todas as Unidades';
        }

        // 2. Prepara as estatísticas usando TODOS os resultados (para os cards do topo ficarem corretos)
        $totalSistema = array_sum(array_column($this->resultados, 'qtd_sistema'));
        $totalHumana = array_sum(array_column($this->resultados, 'qtd_humana'));
        $totalBateu = count(array_filter($this->resultados, fn($r) => $r['cor'] === 'green'));
        $totalDivergencias = count(array_filter($this->resultados, fn($r) => $r['cor'] !== 'green'));

        $resultadosDivergentes = array_filter($this->resultados, function($item) {
            return $item['cor'] !== 'green'; 
        });

        $data = [
            'resultados' => $resultadosDivergentes, // Tabela receberá apenas os erros
            'mes' => $this->mes,
            'ano' => $this->ano,
            'unidadeNome' => $unidadeNome,
            'totalSistema' => $totalSistema,
            'totalHumana' => $totalHumana,
            'totalBateu' => $totalBateu,
            'totalDivergencias' => $totalDivergencias,
        ];

        // 5. Gera o PDF usando a view dedicada
        $pdf = Pdf::loadView('pdf.auditoria-humana', $data);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "Auditoria-Divergencias-Humana-{$this->mes}-{$this->ano}.pdf");
    }

    public function render()
    {
        return view('livewire.auditoria-humana.index', [
            'unidades' => Unit::orderBy('name')->get()
        ]);
    }
}