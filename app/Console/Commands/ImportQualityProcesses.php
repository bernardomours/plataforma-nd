<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QualityProcess;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportQualityProcesses extends Command
{
    protected $signature = 'import:quality-processes';

    protected $description = 'Importa processos de qualidade em massa a partir de um arquivo CSV';

    public function handle()
    {
        $filePath = storage_path('app/processos.csv');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado em: {$filePath}");
            return;
        }

        $this->info("Iniciando importação de processos...");

        $file = fopen($filePath, 'r');
        
        fgetcsv($file, 1000, ';');

        DB::beginTransaction();

        try {
            $count = 0;
            
            $defaultChecklists = [
                'Em elaboração',
                'Documento Aprovado',
                'Treinado',
                'Em implementação',
                'Implementado',
                'Auditado',
                'Processo Finalizado',
            ];

            while (($row = fgetcsv($file, 1000, ';')) !== FALSE) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $sector = $row[0] ?? null; 
                $process_name = $row[1] ?? null; 
                $responsables = $row[2] ?? null; 
                $procedure_code = $row[4] ?? null; 
                $dueDateRaw = $row[9] ?? null; 

                $dueDate = null;
                if (!empty($dueDateRaw) && trim($dueDateRaw) !== '?') {
                    $dueDate = Carbon::createFromFormat('d/m/Y', trim($dueDateRaw))->format('Y-m-d');
                }

                $process = QualityProcess::create([
                    'sector' => $sector,
                    'process_name' => $process_name,
                    'procedure_code' => $procedure_code,
                    'due_date' => $dueDate,
                    'created_by' => 1, 
                    'status' => 'pendente',
                    'progress' => 0,
                ]);

                $userIdsToAttach = [];
                
                if (!empty($responsables) && trim($responsables) !== '?') {
                    $nomes = array_map('trim', explode('/', $responsables));
                    
                    foreach ($nomes as $nome) {
                        if (empty($nome) || $nome === '?') {
                            continue;
                        }

                        $user = User::where('name', 'LIKE', "%{$nome}%")->first();
                        
                        if ($user) {
                            $userIdsToAttach[] = $user->id;
                        }
                    }
                }

                if (!empty($userIdsToAttach)) {
                    $process->users()->attach($userIdsToAttach);
                }

                $dataBase = $dueDate ? Carbon::parse($dueDate) : null;

                foreach ($defaultChecklists as $index => $description) {
                    $dataDaEtapa = $dataBase ? $dataBase->copy()->addDays($index * 30) : null;

                    $process->checklists()->create([
                        'description' => $description,
                        'due_date' => $dataDaEtapa ? $dataDaEtapa->format('Y-m-d') : null,
                    ]);
                }

                $count++;
            }

            DB::commit();
            fclose($file);
            $this->info("Sucesso! {$count} processos foram importados e configurados.");

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            $this->error("Erro durante a importação: " . $e->getMessage());
        }
    }
}