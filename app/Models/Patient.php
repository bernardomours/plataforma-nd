<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\IsolatesByUnit;
// CORREÇÃO: namespaces da v5 do spatie/laravel-activitylog. O código antigo apontava para
// Spatie\Activitylog\Traits\LogsActivity e Spatie\Activitylog\LogOptions, que são da v4 e
// NÃO EXISTEM no pacote instalado (5.0.0) — por isso o log estava desligado.
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Patient extends Model
{
    use HasFactory;
    use IsolatesByUnit;
    use SoftDeletes;
    use LogsActivity;

    /**
     * Só 'created' e 'updated' são registrados por este model.
     *
     * 'deleted' e 'restored' ficam DE FORA de propósito: a saída e o retorno de paciente
     * já são registrados via MovementHistory (que carrega o MOTIVO da saída e é o que a
     * tela de Controles sabe interpretar). Se logássemos aqui também, cada saída geraria
     * DUAS linhas na auditoria — a do MovementHistory, com motivo, e uma do Patient, vazia.
     *
     * A exclusão PERMANENTE (forceDelete) não dispara nenhum destes eventos e por isso é
     * registrada manualmente em Pacientes/Index::forceDeletePatient().
     */
    protected static $recordEvents = ['created', 'updated'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'birth_date',
        'cpf',
        'guardian_name',
        'guardian_phone',
        'unit_id',
        'agreement_id',
        'is_active',
        'agreement_number',
        'supervisor_id',
        'coordinator_id',
    ];

    /**
     * SEGURANÇA (LGPD / dado sensível de saúde): oculta identificadores pessoais em
     * serializações automáticas (toArray/toJson, respostas JSON, logs, dd()).
     *
     * NÃO afeta a exibição nas views: {{ $patient->cpf }} e {{ $patient->guardian_phone }}
     * continuam funcionando normalmente, pois $hidden só age na serialização do model.
     * Se algum dia for preciso serializar esses campos, use ->makeVisible([...]) no ponto exato.
     *
     * @var list<string>
     */
    protected $hidden = [
        'cpf',
        'guardian_name',
        'guardian_phone',
        'agreement_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'birth_date' => 'date',
            'unit_id' => 'integer',
        ];
    }

    /**
     * Campos do cadastro do paciente que entram na auditoria (tela Controles).
     *
     * Duas correções em relação à versão comentada anterior:
     *  - 'status' foi removido: essa coluna NÃO existe em patients (a real é 'is_active').
     *    Pedir um atributo inexistente gravava sempre null e poluía o diff.
     *  - dontSubmitEmptyLogs() virou dontLogEmptyChanges(): o método foi renomeado na v5.
     *    Com o nome antigo, ativar o trait daria erro fatal.
     *
     * 'unit_id' é logado de propósito: é o que permite auditar transferência de clínica.
     * O $hidden do model não interfere aqui — a v5 lê via getAttribute(), que ignora $hidden.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'birth_date',
                'cpf',
                'guardian_name',
                'guardian_phone',
                'unit_id',
                'agreement_id',
                'agreement_number',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function patientServices()
    {
        return $this->hasMany(PatientService::class);
    }

    public function movementHistories()
    {
        return $this->morphMany(MovementHistory::class, 'moveable');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'supervisor_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'coordinator_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function requestedServices(): HasMany
    {
        return $this->hasMany(RequestedService::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
