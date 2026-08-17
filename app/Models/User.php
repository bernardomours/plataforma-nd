<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'unit_id',
        'birth_date',
        'role',
        'can_access_production',
        // Marca contas que ainda estão com a senha padrão e precisam trocá-la no
        // primeiro acesso (middleware EnsurePasswordIsChanged).
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'must_change_password' => 'boolean',
        ];
    }

    public function units()
    {
        return $this->belongsToMany(Unit::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isAdministrative(): bool
    {
        return $this->hasRole('administrative');
    }

    public function isCoordinator(): bool
    {
        return $this->hasRole('coordinator');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function professional()
    {
        return $this->hasOne(Professional::class, 'user_id');
    }

    public function getAllowedUnitIds(): ?array
    {
        if ($this->isAdmin() || $this->isManager()) {
            return null;
        }

        return $this->units()->pluck('units.id')->toArray();
    }

    /**
     * SEGURANÇA (multi-tenant): valida se o usuário pode operar sobre UMA unidade.
     * Usado para models que possuem a coluna unit_id (ex.: Patient).
     *
     * Mantém exatamente o mesmo contrato de getAllowedUnitIds(): null = acesso global
     * (admin/manager), array = restrito. Assim a regra de "quem enxerga o quê" continua
     * definida em um único lugar.
     */
    public function canAccessUnit(?int $unitId): bool
    {
        $allowed = $this->getAllowedUnitIds();

        if ($allowed === null) {
            return true;
        }

        return $unitId !== null && in_array((int) $unitId, array_map('intval', $allowed), true);
    }

    /**
     * SEGURANÇA (multi-tenant): valida vínculos multi-unidade, onde o relacionamento é
     * por tabela pivô e não por coluna unit_id — caso do Professional (professional_unit).
     * Basta UMA unidade em comum para liberar, que é a regra já usada nas listagens
     * (whereHas('units', fn($q) => $q->whereIn('units.id', $allowed))).
     */
    public function canAccessAnyUnit(array $unitIds): bool
    {
        $allowed = $this->getAllowedUnitIds();

        if ($allowed === null) {
            return true;
        }

        return count(array_intersect(
            array_map('intval', $unitIds),
            array_map('intval', $allowed)
        )) > 0;
    }

    public function qualityProcesses()
    {
        return $this->belongsToMany(QualityProcess::class, 'quality_process_user');
    }
}