<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · Plataforma ND</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: #f9fafb;
            color: #111827;
            font-family: Figtree, ui-sans-serif, system-ui, -apple-system, "Segoe UI",
                         Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .caixa { width: 100%; max-width: 35rem; }

        .marca {
            display: flex; align-items: center; justify-content: center; gap: .75rem;
        }
        .marca img { height: 7rem; width: auto; }
        .marca span {
            font-size: .75rem; font-weight: 700; letter-spacing: .18em;
            text-transform: uppercase; color: #1e3a8a;
        }

        .cartao {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .icone {
            width: 3.5rem; height: 3.5rem;
            margin: 0 auto 1.25rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 9999px;
        }
        .icone svg { width: 1.75rem; height: 1.75rem; }

        .icone-azul     { background: #eff6ff; color: #2563eb; }
        .icone-ambar    { background: #fffbeb; color: #d97706; }
        .icone-vermelho { background: #fef2f2; color: #dc2626; }

        .codigo {
            font-size: .6875rem; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: #9ca3af; margin-bottom: .5rem;
        }
        h1 { font-size: 1.375rem; font-weight: 700; margin: 0 0 .5rem; letter-spacing: -.01em; }
        .texto { font-size: .875rem; line-height: 1.6; color: #6b7280; margin: 0; }

        .detalhe {
            margin-top: 1.25rem; padding: .75rem 1rem;
            background: #f9fafb; border: 1px solid #e5e7eb; border-radius: .5rem;
            font-size: .8125rem; color: #374151; text-align: left;
        }
        .detalhe strong { display: block; font-size: .6875rem; text-transform: uppercase;
                          letter-spacing: .06em; color: #9ca3af; margin-bottom: .25rem; }

        .acoes {
            margin-top: 1.75rem; display: flex; flex-wrap: wrap;
            gap: .75rem; justify-content: center;
        }
        .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .625rem 1.25rem; border-radius: .5rem;
            font-size: .875rem; font-weight: 600; text-decoration: none;
            border: 1px solid transparent; cursor: pointer;
            font-family: inherit; transition: background-color .15s, border-color .15s, color .15s;
        }
        .btn-primario { background: #2563eb; color: #fff; }
        .btn-primario:hover { background: #1d4ed8; }
        .btn-secundario { background: #fff; color: #374151; border-color: #d1d5db; }
        .btn-secundario:hover { background: #f9fafb; color: #111827; }

        .rodape { margin-top: 1.5rem; text-align: center; font-size: .75rem; color: #9ca3af; }

        @media (prefers-color-scheme: dark) {
            body { background: #111827; color: #f9fafb; }
            .cartao { background: #1f2937; border-color: #374151; }
            .texto { color: #9ca3af; }
            .detalhe { background: #111827; border-color: #374151; color: #d1d5db; }
            .btn-secundario { background: #1f2937; color: #d1d5db; border-color: #4b5563; }
            .btn-secundario:hover { background: #374151; color: #fff; }
            .marca span { color: #93c5fd; }
        }
    </style>
</head>
<body>
    <div class="caixa">
        <div class="marca">
            <img src="{{ asset('images/icon-nd.png') }}" alt="">
            <span>Plataforma ND</span>
        </div>

        <div class="cartao">
            <div class="icone icone-@yield('cor', 'azul')">
                @yield('icone')
            </div>

            <p class="codigo">Erro @yield('codigo')</p>
            <h1>@yield('titulo')</h1>
            <p class="texto">@yield('mensagem')</p>

            @hasSection('detalhe')
                <div class="detalhe">@yield('detalhe')</div>
            @endif

            <div class="acoes">
                @yield('acoes')
                    <a href="{{ url('/') }}" class="btn btn-primario">Ir para o início</a>
                    <button type="button" onclick="history.back()" class="btn btn-secundario">Voltar</button>
                @show
            </div>
        </div>

        <p class="rodape">Núcleo Desenvolve</p>
    </div>
</body>
</html>
