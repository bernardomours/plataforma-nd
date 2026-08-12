<?php

namespace App\Models;

use App\Enums\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
// CORREÇÃO: namespaces da v5 (ver comentário equivalente em Patient.php). Antes o trait
// era importado de Traits\LogsActivity (v4, inexistente) e nunca aplicado no corpo da
// classe — o getActivitylogOptions() abaixo era código morto.
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Professional extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    /**
     * Mesmo critério do Patient: saída e retorno de profissional já são registrados via
     * MovementHistory (com motivo), então 'deleted'/'restored' ficam fora para não
     * duplicar linhas na tela de Controles.
     */
    protected static $recordEvents = ['created', 'updated'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'cpf',
        'phone',
        'birth_date',
        'register_number',
        'email',
        'role',
        'deletion_reason',
        'user_id'
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
            'role' => ProfessionalRole::class,
        ];
    }

    public function paymentRules()
    {
        return $this->hasMany(ProfessionalPaymentRule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class);
    }

    public function therapies()
    {
        return $this->belongsToMany(Therapy::class);
    }

    public function movementHistories()
    {
        return $this->morphMany(MovementHistory::class, 'moveable');
    }


    /**
     * Campos do cadastro do profissional que entram na auditoria.
     *
     * Correções em relação à versão anterior (que nunca chegou a rodar):
     *  - 'status' removido: não existe essa coluna em professionals.
     *  - dontSubmitEmptyLogs() -> dontLogEmptyChanges() (renomeado na v5).
     *
     * NÃO usar useAttributeRawValues(['role']) aqui: 'role' tem cast para o enum
     * ProfessionalRole e a v5 é incompatível com essa combinação — o raw devolve a string
     * crua, mas formatAttributeValue() continua tratando o campo como enum e lança
     * ValueError ao salvar. O comportamento padrão já grava o valor escalar ('therapist').
     *
     * A unidade NÃO entra aqui porque professionals não tem coluna unit_id — o vínculo é
     * a pivô professional_unit. A clínica é resolvida na exibição (Controles/Index).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'cpf',
                'phone',
                'email',
                'birth_date',
                'register_number',
                'role',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }
}
