<div>
    <x-slot name="header">
        <div class="flex items-center gap-4 mt-4">
            <a href="{{ route('profissionais.index') }}" wire:navigate class="p-2 -ml-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" title="Voltar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div class="flex flex-col">
                <nav class="flex text-xs text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li><a href="{{ route('profissionais.index') }}" wire:navigate class="hover:text-blue-600 transition-colors">Profissionais</a></li>
                        <li><span class="mx-1 text-gray-400">/</span></li>
                        <li aria-current="page" class="text-gray-700 font-medium">Visualizar Dados</li>
                    </ol>
                </nav>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $professional->name }}</h2>
            </div>
        </div>
    </x-slot>

    <style>
        .nd-prof {
            --money: var(--warning);
            --money-strong: var(--warning-strong);
            --money-soft: var(--warning-soft);
        }
        .nd-prof .ndp-hero {
            display: flex; align-items: flex-start; gap: 20px; flex-wrap: wrap;
            padding: 28px 28px 26px; background: var(--surface); border: 1px solid var(--line);
            border-radius: 14px;
        }
        .nd-prof .ndp-avatar {
            flex: none; width: 68px; height: 68px; border-radius: 16px;
            background: var(--accent-soft); color: var(--accent-strong);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 800; letter-spacing: -0.02em;
        }
        .nd-prof .ndp-name {
            font-size: 26px; font-weight: 800; letter-spacing: -0.015em; color: var(--ink);
            line-height: 1.15;
        }
        .nd-prof .ndp-role { font-size: 13px; font-weight: 600; color: var(--ink-2); margin-top: 2px; }
        .nd-prof .ndp-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
        .nd-prof .ndp-pill {
            display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px;
            border-radius: 999px; font-size: 11.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid transparent;
        }
        .nd-prof .ndp-pill.is-success { background: var(--success-soft); color: var(--success-strong); border-color: #b7ecd9; }
        .nd-prof .ndp-pill.is-danger { background: var(--danger-soft); color: var(--danger-strong); border-color: #fbd2d2; }
        .nd-prof .ndp-pill.is-neutral { background: var(--surface-2); color: var(--ink-2); border-color: var(--line); }
        .nd-prof .ndp-pill.is-accent { background: var(--accent-soft); color: var(--accent-strong); }

        .nd-prof .ndp-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px; }
        @media (min-width: 1024px) { .nd-prof .ndp-grid { grid-template-columns: 1.5fr 1fr; align-items: start; } }

        .nd-prof .ndp-card {
            background: var(--surface); border: 1px solid var(--line); border-radius: 14px;
            padding: 22px 24px;
        }
        .nd-prof .ndp-card + .ndp-card { margin-top: 20px; }
        .nd-prof .ndp-card-title {
            font-size: 13px; font-weight: 700; color: var(--ink); letter-spacing: -0.005em;
            display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
        }
        .nd-prof .ndp-card-title svg { width: 17px; height: 17px; color: var(--ink-3); }

        .nd-prof .ndp-facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px 20px; }
        .nd-prof .ndp-fact-label { font-size: 11px; font-weight: 600; color: var(--ink-3); text-transform: uppercase; letter-spacing: 0.03em; }
        .nd-prof .ndp-fact-value { font-size: 14.5px; font-weight: 600; color: var(--ink); margin-top: 3px; }
        .nd-prof .ndp-fact-value.is-muted { font-weight: 500; color: var(--ink-3); }

        .nd-prof .ndp-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .nd-prof .ndp-chip {
            font-size: 12px; font-weight: 600; color: var(--ink-2); background: var(--surface-2);
            border: 1px solid var(--line); border-radius: 7px; padding: 4px 9px;
        }

        .nd-prof .ndp-rule { border: 1px solid var(--line); border-radius: 12px; padding: 18px 20px; }
        .nd-prof .ndp-rule + .ndp-rule { margin-top: 14px; }
        .nd-prof .ndp-rule-scope { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
        .nd-prof .ndp-rule-amount { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
        .nd-prof .ndp-rule-amount .nd-num { font-size: 25px; font-weight: 800; color: var(--money-strong); letter-spacing: -0.01em; }
        .nd-prof .ndp-rule-amount .ndp-base { font-size: 12.5px; color: var(--ink-3); font-weight: 500; }
        .nd-prof .ndp-rule-warning {
            margin-top: 12px; display: flex; gap: 8px; align-items: flex-start;
            background: var(--danger-soft); border: 1px solid #fbd2d2;
            color: var(--danger-strong); font-size: 12px; font-weight: 600; padding: 9px 11px; border-radius: 9px;
        }
        .nd-prof .ndp-rule-warning svg { width: 15px; height: 15px; flex: none; margin-top: 1px; }

        .nd-prof .ndp-track { margin-top: 16px; }
        .nd-prof .ndp-track-label { font-size: 11px; font-weight: 600; color: var(--ink-3); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 10px; }
        .nd-prof .ndp-track-row { display: flex; align-items: center; }
        .nd-prof .ndp-track-node { flex: none; display: flex; flex-direction: column; align-items: center; width: 92px; text-align: center; }
        .nd-prof .ndp-track-dot {
            width: 15px; height: 15px; border-radius: 999px; border: 2.5px solid var(--line-2);
            background: var(--surface); display: flex; align-items: center; justify-content: center;
        }
        .nd-prof .ndp-track-dot.is-aplicado { border-color: var(--success); background: var(--success); }
        .nd-prof .ndp-track-dot.is-pendente { border-color: var(--money); background: var(--money-soft); }
        .nd-prof .ndp-track-dot.is-futuro { border-color: var(--line-2); background: var(--surface); }
        .nd-prof .ndp-track-dot.is-indefinido { border-color: var(--line); background: var(--surface-2); }
        .nd-prof .ndp-track-line { flex: 1 1 auto; height: 2.5px; background: var(--line-2); margin: 0 -2px; min-width: 12px; }
        .nd-prof .ndp-track-line.is-aplicado { background: var(--success); }
        .nd-prof .ndp-track-name { font-size: 11.5px; font-weight: 700; color: var(--ink); margin-top: 7px; }
        .nd-prof .ndp-track-status { font-size: 10.5px; font-weight: 600; color: var(--ink-3); margin-top: 1px; }
        .nd-prof .ndp-track-status.is-aplicado { color: var(--success-strong); }
        .nd-prof .ndp-track-status.is-pendente { color: var(--money-strong); }

        .nd-prof .ndp-empty {
            text-align: center; padding: 28px 16px; color: var(--ink-3); font-size: 13.5px;
        }
        .nd-prof .ndp-empty svg { width: 30px; height: 30px; margin: 0 auto 10px; color: var(--line-2); }
    </style>

    <div class="nd-ui nd-prof max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        @if (session()->has('message'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('message') }}
            </div>
        @endif

        {{-- CABEÇALHO --}}
        <div class="ndp-hero">
            <div class="ndp-avatar">
                @php
                    $iniciais = collect(explode(' ', trim($professional->name)))
                        ->filter()
                        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
                        ->take(2)
                        ->implode('');
                @endphp
                {{ $iniciais ?: '?' }}
            </div>

            <div class="flex-1 min-w-[220px]">
                <div class="ndp-role">{{ $professional->role?->getLabel() ?? 'Cargo não definido' }}</div>
                <h1 class="ndp-name">{{ $professional->name }}</h1>

                <div class="ndp-pills">
                    @if ($professional->trashed())
                        <span class="ndp-pill is-danger">Inativo</span>
                    @else
                        <span class="ndp-pill is-success">Ativo</span>
                    @endif

                    @if ($professional->formacao)
                        <span class="ndp-pill is-accent">{{ $professional->formacao->getLabel() }}</span>
                    @endif

                    @if ($professional->atendeAba())
                        <span class="ndp-pill is-neutral">ABA</span>
                    @endif
                    @if ($professional->atendeTerapiaNaoAba())
                        <span class="ndp-pill is-neutral">Multi-terapia</span>
                    @endif
                </div>
            </div>

            <div class="flex-none">
                @if (! $professional->trashed())
                    <a href="{{ route('profissionais.edit', $professional->id) }}" wire:navigate class="nd-btn-ghost inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Editar Cadastro
                    </a>
                @else
                    <p class="text-xs text-gray-400 max-w-[180px] text-right">Profissional inativo. Reative pela listagem para editar o cadastro.</p>
                @endif
            </div>
        </div>

        <div class="ndp-grid">
            {{-- COLUNA PRINCIPAL --}}
            <div>
                <div class="ndp-card">
                    <div class="ndp-card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Dados Gerais
                    </div>

                    <div class="ndp-facts">
                        <div>
                            <div class="ndp-fact-label">CPF</div>
                            <div class="ndp-fact-value">{{ $professional->cpf ?: 'Não informado' }}</div>
                        </div>
                        <div>
                            <div class="ndp-fact-label">Telefone</div>
                            <div class="ndp-fact-value">{{ $professional->phone ?: 'Não informado' }}</div>
                        </div>
                        <div>
                            <div class="ndp-fact-label">E-mail</div>
                            <div class="ndp-fact-value">{{ $professional->email ?: 'Não informado' }}</div>
                        </div>
                        <div>
                            <div class="ndp-fact-label">Data de Nascimento</div>
                            <div class="ndp-fact-value">{{ $professional->birth_date?->format('d/m/Y') ?? 'Não informada' }}</div>
                        </div>
                        <div>
                            <div class="ndp-fact-label">Número de Registro</div>
                            <div class="ndp-fact-value">{{ $professional->register_number ?: 'Não informado' }}</div>
                        </div>
                        <div>
                            <div class="ndp-fact-label">Data de Contrato</div>
                            @if ($professional->contract_date)
                                <div class="ndp-fact-value">
                                    {{ $professional->contract_date->format('d/m/Y') }}
                                    @php
                                        $meses = $professional->mesesDeEmpresa();
                                        $anos = intdiv($meses, 12);
                                        $restoMeses = $meses % 12;
                                        $partes = [];
                                        if ($anos > 0) { $partes[] = $anos . ' ' . ($anos === 1 ? 'ano' : 'anos'); }
                                        if ($restoMeses > 0 || $anos === 0) { $partes[] = $restoMeses . ' ' . ($restoMeses === 1 ? 'mês' : 'meses'); }
                                    @endphp
                                </div>
                            @else
                                <div class="ndp-fact-value is-muted">Não informada</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ndp-card">
                    <div class="ndp-card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0h-6m-4 0H5m7-14h.01M12 12h.01M12 8h.01M8 8h.01M8 12h.01M16 8h.01M16 12h.01"></path></svg>
                        Vínculos
                    </div>

                    <div class="ndp-fact-label mb-2">Unidades</div>
                    <div class="ndp-chips mb-4">
                        @forelse ($professional->units as $unidade)
                            <span class="ndp-chip">{{ $unidade->city ?? $unidade->name }}</span>
                        @empty
                            <span class="text-sm" style="color: var(--ink-3);">Nenhuma unidade vinculada</span>
                        @endforelse
                    </div>

                    <div class="ndp-fact-label mb-2">Especialidades</div>
                    <div class="ndp-chips">
                        @forelse ($professional->therapies as $terapia)
                            <span class="ndp-chip">{{ $terapia->name }}</span>
                        @empty
                            <span class="text-sm" style="color: var(--ink-3);">Nenhuma especialidade vinculada</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- COLUNA LATERAL: REGRA DE PAGAMENTO --}}
            <div>
                <div class="ndp-card">
                    <div class="ndp-card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Regra de Pagamento
                    </div>

                    @php
                        $rotuloMarco = fn ($estado) => match ($estado) {
                            'aplicado' => 'Aplicado',
                            'pendente' => 'Aguardando processamento',
                            'futuro' => 'Previsto',
                            default => 'Sem data de contrato',
                        };
                    @endphp

                    @forelse ($regras as $item)
                        @php [$regra, $marco9, $marco18] = [$item['regra'], $item['marco9'], $item['marco18']]; @endphp
                        <div class="ndp-rule">
                            <div class="ndp-rule-scope">
                                <span class="ndp-chip">{{ $regra->therapy?->name ?? 'Qualquer terapia' }}</span>
                                <span class="ndp-chip">{{ $regra->serviceType?->name ?? 'Qualquer tipo' }}</span>
                                <span class="ndp-chip">{{ $regra->agreement?->name ?? 'Qualquer convênio' }}</span>
                            </div>

                            <div class="ndp-rule-amount">
                                <span class="nd-num">R$ {{ number_format($regra->amount, 2, ',', '.') }}</span>
                                <span class="ndp-base">{{ ucfirst(str_replace('_', ' ', $regra->payment_type)) }}</span>
                            </div>
                            <div class="ndp-base mt-1">
                                Valor de contratação: R$ {{ number_format($regra->valor_base ?? $regra->amount, 2, ',', '.') }}
                                @if ($regra->valor_reajuste)
                                    · Reajuste de R$ {{ number_format($regra->valor_reajuste, 2, ',', '.') }} por marco
                                @endif
                            </div>

                            @unless (str_contains(strtolower($regra->payment_type), 'sess'))
                                <div class="ndp-rule-warning">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Este tipo de pagamento ainda não entra no fechamento de produção. A sessão fica registrada, mas o repasse fecha zerado até haver suporte a ele.
                                </div>
                            @endunless

                            @if ($regra->valor_reajuste)
                                <div class="ndp-track">
                                    <div class="ndp-track-label">Reajuste automático por tempo de contrato</div>

                                    <div class="ndp-track-row">
                                        <div class="ndp-track-node">
                                            <div class="ndp-track-dot is-aplicado"></div>
                                            <div class="ndp-track-name">Contratação</div>
                                            <div class="ndp-track-status">{{ $professional->contract_date?->format('d/m/Y') ?? 'Não informada' }}</div>
                                        </div>
                                        <div class="ndp-track-line {{ $marco9['estado'] === 'aplicado' ? 'is-aplicado' : '' }}"></div>
                                        <div class="ndp-track-node">
                                            <div class="ndp-track-dot is-{{ $marco9['estado'] }}"></div>
                                            <div class="ndp-track-name">9 meses</div>
                                            <div class="ndp-track-status is-{{ $marco9['estado'] }}">
                                                {{ $rotuloMarco($marco9['estado']) }}{{ $marco9['data'] ? ' · ' . $marco9['data']->format('d/m/Y') : '' }}
                                            </div>
                                        </div>
                                        <div class="ndp-track-line {{ $marco18['estado'] === 'aplicado' ? 'is-aplicado' : '' }}"></div>
                                        <div class="ndp-track-node">
                                            <div class="ndp-track-dot is-{{ $marco18['estado'] }}"></div>
                                            <div class="ndp-track-name">18 meses</div>
                                            <div class="ndp-track-status is-{{ $marco18['estado'] }}">
                                                {{ $rotuloMarco($marco18['estado']) }}{{ $marco18['data'] ? ' · ' . $marco18['data']->format('d/m/Y') : '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="ndp-empty">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3v-6m-3 6v-1m-2 4h10a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Nenhuma regra de pagamento cadastrada para este profissional.
                            @hasanyrole('admin|manager')
                                <div class="mt-3">
                                    <a href="{{ route('producao.regras') }}" wire:navigate class="nd-btn-primary inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white">
                                        Cadastrar em Regras de Pagamento
                                    </a>
                                </div>
                            @endhasanyrole
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
