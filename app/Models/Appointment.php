<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// CORREÇÃO: namespaces da v5. O trait continua DESATIVADO (não há `use LogsActivity;` no
// corpo da classe) porque a auditoria pedida cobre paciente e profissional, não atendimento.
// Mas os imports apontavam para classes da v4 que não existem: o getActivitylogOptions()
// abaixo tem retorno tipado LogOptions e daria erro fatal no instante em que alguém
// ativasse o trait. Corrigido o namespace e o método renomeado na v5, para que ativar
// passe a ser questão de acrescentar uma linha.
use Spatie\Activitylog\Support\LogOptions;

class Appointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'guide',
        'appointment_date',
        'check_in',
        'check_out',
        'session_number',
        'patient_id',
        'professional_id',
        'therapy_id',
        'service_type_id',
        // Congelados no momento do lançamento (ver migration add_agreement_and_unit_to_appointments).
        // O padrão vem do cadastro do paciente, mas podem ser sobrescritos quando o
        // atendimento ocorre fora do convênio/unidade habituais.
        'agreement_id',
        'unit_id',
        // Horário fixo de origem, quando o atendimento nasce da Agenda Diária da Recepção
        // ("Sinalizar Realizada") — null pro lançamento manual/importação, que não tem
        // horário fixo por trás. Ver migration add_schedule_id_to_appointments_table.
        'schedule_id',
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
            'appointment_date' => 'date',
            'patient_id' => 'integer',
            'professional_id' => 'integer',
            'therapy_id' => 'integer',
            'service_type_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patient_id', 'professional_id', 'therapy_id', 'appointment_date'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /** Convênio sob o qual ESTE atendimento foi realizado (não o atual do paciente). */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class)->withTrashed();
    }

    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    /**
     * Unidade em que ESTE atendimento ocorreu (não a atual do paciente).
     *
     * Esta relação já existia no model, mas apontava para appointments.unit_id — coluna
     * que não existia no banco até a migration add_agreement_and_unit_to_appointments.
     * Era código morto: qualquer uso resultaria em "Unknown column". Agora é válida.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}