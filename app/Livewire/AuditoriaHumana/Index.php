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

    /**
     * FUNÇÃO PURIFICADORA 1: Remove acentos e espaços duplos
     */
    private function limparTexto($texto) 
    {
        if (empty($texto)) return '';
        
        $texto = trim(mb_strtoupper($texto, 'UTF-8'));
        
        $mapa = [
            'Á'=>'A', 'À'=>'A', 'Ã'=>'A', 'Â'=>'A', 'Ä'=>'A',
            'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E',
            'Í'=>'I', 'Ì'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ó'=>'O', 'Ò'=>'O', 'Õ'=>'O', 'Ô'=>'O', 'Ö'=>'O',
            'Ú'=>'U', 'Ù'=>'U', 'Û'=>'U', 'Ü'=>'U',
            'Ç'=>'C', 'Ñ'=>'N'
        ];
        
        $texto = strtr($texto, $mapa);
        
        // Substitui 2 ou mais espaços por apenas 1 espaço (Resolve o bug do "Luis Guilherme" duplicado)
        $texto = preg_replace('/\s+/', ' ', $texto);
        
        return trim($texto);
    }

    /**
     * FUNÇÃO PURIFICADORA 2: Unifica o nome das terapias
     */
    private function padronizarTerapia($nomeBruto) 
    {
        $terapia = $this->limparTexto($nomeBruto); // Tira acentos antes de analisar
        
        if (str_contains($terapia, 'ABA')) return 'ABA';
        if (str_contains($terapia, 'PSICOPEDAGOGIA')) return 'PSICOPEDAGOGIA';
        if (str_contains($terapia, 'PSICOMOTRICIDADE')) return 'PSICOMOTRICIDADE';
        if (str_contains($terapia, 'FONO')) return 'FONOAUDIOLOGIA';
        if (str_contains($terapia, 'OCUPACIONAL') || str_contains($terapia, 'AYRES') || str_contains($terapia, 'TO -')) return 'TERAPIA OCUPACIONAL';
        if (str_contains($terapia, 'PSICOTERAPIA') || str_contains($terapia, 'PSICOLOGIA')) return 'PSICOTERAPIA';
        
        // Se tiver "AVALIA" no nome (Avaliação, Avaliacao, Avaliação Neuro), junta tudo!
        if (str_contains($terapia, 'AVALIA')) return 'AVALIAÇÃO NEURO'; 

        return $terapia; // Fallback
    }

    public function processar()
    {
        $this->validate([
            'arquivo_csv' => 'required|file|mimes:csv,txt|max:5120',
            'mes' => 'required|numeric',
            'ano' => 'required|numeric',
            'unidade_relatorio' => 'required',
        ]);

        // ==========================================
        // 1. LER O CSV DA HUMANA
        // ==========================================
        $path = $this->arquivo_csv->getRealPath();
        $file = fopen($path, 'r');
        
        $humanaData = [];

        while (($linha = fgetcsv($file, 1000, ";")) !== FALSE) {
            if (count($linha) < 10 || str_contains(strtolower($linha[0]), 'guia')) {
                continue;
            }

            $linha = array_map(fn($value) => mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1'), $linha);

            // Usa os Purificadores!
            $pacienteOriginal = $this->limparTexto($linha[3]);
            $terapiaSistema = $this->padronizarTerapia($linha[8]);
            $qtd = (int) $linha[9];

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

        // ==========================================
        // 2. BUSCAR DADOS DO SISTEMA NO MESMO PERÍODO
        // ==========================================
        $sistemaData = [];

        // --- 2.1 Busca Terapias Normais (Appointments) ---
        $queryAppointments = Appointment::with(['patient', 'therapy'])
            ->whereYear('appointment_date', $this->ano)
            ->whereMonth('appointment_date', $this->mes)
            ->whereHas('patient', function ($q) {
                $q->where('agreement_id', 1);
                if ($this->unidade_relatorio) {
                    $q->where('unit_id', $this->unidade_relatorio);
                }
            });

        $systemAppointments = $queryAppointments->get();

        foreach ($systemAppointments as $app) {
            if (!$app->patient || !$app->therapy) continue;

            // Usa os Purificadores para o Banco de Dados também!
            $paciente = $this->limparTexto($app->patient->name);
            $terapia = $this->padronizarTerapia($app->therapy->name); 
            
            $qtd = $app->session_number ?? 1; 
            $chave = $paciente . '|' . $terapia;

            if (!isset($sistemaData[$chave])) {
                $sistemaData[$chave] = 0;
            }
            $sistemaData[$chave] += $qtd;
        }

        // --- 2.2 Busca Avaliações Neuro (Diário de Sessões) ---
        $queryNeuro = NeuroSession::with(['assessment.patient'])
            ->whereYear('date', $this->ano)
            ->whereMonth('date', $this->mes)
            ->whereHas('assessment.patient', function ($q) {
                $q->where('agreement_id', 1);
                if ($this->unidade_relatorio) {
                    $q->where('unit_id', $this->unidade_relatorio);
                }
            });

        $neuroSessions = $queryNeuro->get();

        foreach ($neuroSessions as $session) {
            if (!$session->assessment || !$session->assessment->patient) continue;

            $paciente = $this->limparTexto($session->assessment->patient->name);
            $terapia = 'AVALIAÇÃO NEURO'; 
            $qtd = 1; 

            $chave = $paciente . '|' . $terapia;

            if (!isset($sistemaData[$chave])) {
                $sistemaData[$chave] = 0;
            }
            $sistemaData[$chave] += $qtd;
        }

        // ==========================================
        // 3. CRUZAR OS DADOS (O MATCH)
        // ==========================================
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
            if ($a['paciente'] === $b['paciente']) {
                return $a['terapia'] <=> $b['terapia'];
            }
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
        $unidadeNome = 'Todas as Unidades';
        if ($this->unidade_relatorio) {
            $unidade = Unit::find($this->unidade_relatorio);
            $unidadeNome = $unidade ? ($unidade->city ?? $unidade->name) : 'Todas as Unidades';
        }

        $totalSistema = array_sum(array_column($this->resultados, 'qtd_sistema'));
        $totalHumana = array_sum(array_column($this->resultados, 'qtd_humana'));
        $totalBateu = count(array_filter($this->resultados, fn($r) => $r['cor'] === 'green'));
        $totalDivergencias = 0;
        foreach ($this->resultados as $item) {
            $totalDivergencias += abs($item['qtd_sistema'] - $item['qtd_humana']);
        }

        $resultadosDivergentes = array_filter($this->resultados, function($item) {
            return $item['cor'] !== 'green'; 
        });

        $data = [
            'resultados' => $resultadosDivergentes,
            'mes' => $this->mes,
            'ano' => $this->ano,
            'unidadeNome' => $unidadeNome,
            'totalSistema' => $totalSistema,
            'totalHumana' => $totalHumana,
            'totalBateu' => $totalBateu,
            'totalDivergencias' => $totalDivergencias,
        ];

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