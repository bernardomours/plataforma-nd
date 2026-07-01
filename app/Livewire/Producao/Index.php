<?php

namespace App\Livewire\Producao;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.producao')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.producao.index');
    }
}