<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Schedule extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * Diferente de Patient/Professional (só created/updated), aqui 'deleted' entra de
     * propósito: agora que profissional multi-terapia edita a própria agenda em
     * Pacientes\Agenda, a coordenação precisa saber quando um horário é removido, não só
     * criado/alterado — ver "Controle de atividades da Agenda" no CLAUDE.md. Schedule não
     * usa SoftDeletes, então delete() é exclusão de verdade e dispara o evento normalmente
     * (diferente do forceDelete() de Patient, que não dispara nada).
     */
    protected static $recordEvents = ['created', 'updated', 'deleted'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'patient_id',
        'professional_id',
        'therapy_id',
        'service_type_id',
        'is_blocked',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'day_of_week',
                'start_time',
                'end_time',
                'patient_id',
                'professional_id',
                'therapy_id',
                'service_type_id',
                'is_blocked',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the patient that owns the schedule.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the professional that owns the schedule.
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Get the therapy that owns the schedule.
     */
    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    /**
     * Get the service type that owns the schedule.
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /** Atendimentos lançados a partir deste horário fixo (via Agenda Diária da Recepção). */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Faltas registradas para este horário fixo. */
    public function faltas(): HasMany
    {
        return $this->hasMany(Falta::class);
    }

    /**
     * [nome, nome-sem-acento] do dia da semana de uma data — mesmo par usado pra casar
     * com day_of_week (que na base aparece tanto "terça" quanto "terca"). Centraliza o
     * que antes só existia duplicado dentro de Profissionais\MinhaAgenda.
     */
    public static function nomesDoDiaDaSemana(\Carbon\CarbonInterface $data): array
    {
        $dias = [0 => 'domingo', 1 => 'segunda', 2 => 'terça', 3 => 'quarta', 4 => 'quinta', 5 => 'sexta', 6 => 'sábado'];
        $nome = $dias[$data->dayOfWeek];

        return [$nome, str_replace('ç', 'c', $nome)];
    }
}
