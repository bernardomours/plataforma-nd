<?php

namespace App\Livewire\Profissionais;

use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Aniversariantes extends Component
{
    private const ROTULOS_PAPEL = [
        'admin'          => 'Administrativo',
        'manager'        => 'Direção',
        'administrative' => 'Administrativo',
    ];

    public $unit_id = '';

    public function mount()
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager', 'administrative'])) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }
    }

    public function render()
    {
        $allowedUnitIds = auth()->user()->getAllowedUnitIds();

        $profissionais = Professional::with('units')
            ->whereNotNull('birth_date')
            ->when($allowedUnitIds !== null, fn ($q) => $q->whereHas('units', fn ($u) => $u->whereIn('units.id', $allowedUnitIds)))
            ->when($this->unit_id, fn ($q) => $q->whereHas('units', fn ($u) => $u->where('units.id', $this->unit_id)))
            ->get()
            ->map(fn ($p) => (object) [
                'name'       => $p->name,
                'birth_date' => $p->birth_date,
                'units'      => $p->units,
                'rotulo'     => null,
            ]);

        $usuarios = User::with(['units', 'roles'])
            ->whereNotNull('birth_date')
            ->whereDoesntHave('professional', fn ($q) => $q->withTrashed())
            ->when($allowedUnitIds !== null, fn ($q) => $q->whereHas('units', fn ($u) => $u->whereIn('units.id', $allowedUnitIds)))
            ->when($this->unit_id, fn ($q) => $q->whereHas('units', fn ($u) => $u->where('units.id', $this->unit_id)))
            ->get()
            ->map(fn ($u) => (object) [
                'name'       => $u->name,
                'birth_date' => $u->birth_date,
                'units'      => $u->units,
                'rotulo'     => self::ROTULOS_PAPEL[$u->roles->first()->name ?? ''] ?? null,
            ]);

        $todos = $profissionais->concat($usuarios)->sortBy(fn ($p) => $p->birth_date->day);

        $porMes = $todos->groupBy(fn ($p) => $p->birth_date->month);

        $meses = collect(range(1, 12))->mapWithKeys(fn ($m) => [
            $m => [
                'nome'          => ucfirst(Carbon::create(2000, $m, 1)->translatedFormat('F')),
                'profissionais' => $porMes->get($m, collect()),
            ],
        ]);

        return view('livewire.profissionais.aniversariantes', [
            'meses'          => $meses,
            'mesAtual'       => now()->month,
            'unidadesFiltro' => $allowedUnitIds === null ? Unit::all() : Unit::whereIn('id', $allowedUnitIds)->get(),
        ]);
    }
}
