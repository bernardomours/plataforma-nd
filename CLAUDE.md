# Plataforma ND

Sistema de gestão para clínicas de terapia infantil (Núcleo Desenvolve), multi-unidade.
Laravel 13 · Livewire 3 (+ Volt) · Tailwind · Alpine · MySQL · Spatie Permission · Spatie Activitylog v5.

Hospedagem: Hostinger, plano compartilhado. **Deploy exige `npm run build` no servidor** — o
Tailwind gera apenas as classes que encontra varrendo o código, e correções em Blade que
dependem de classes novas não surtem efeito sem rebuild.

---

## Isolamento multi-unidade

Contrato único: `User::getAllowedUnitIds()` devolve `null` para admin/manager (acesso global)
ou um array de ids. Toda consulta que traz dado de paciente respeita isso.

- `Patient` isola sozinho via trait `App\Traits\IsolatesByUnit` (global scope sobre `unit_id`).
- `Professional` **não tem** `unit_id` — o vínculo é a pivô `professional_unit`. Filtrar com
  `whereHas('units', ...)`. Usar `User::canAccessAnyUnit()`.
- `Appointment`, `RequestedService`, `Schedule` e `NeuroAssessment` não têm scope próprio:
  a checagem é explícita, geralmente pela unidade do paciente.

Ao remover scopes, preferir `withoutGlobalScope(SoftDeletingScope::class)`.
`withoutGlobalScopes()` derruba o isolamento por unidade junto e abre vazamento entre clínicas.

Componentes Livewire re-hidratam **sem executar `mount()`**. Autorização feita só no `mount()`
não protege: repetir a checagem em todo método que grava ou exclui.

---

## Regras de negócio

### Duração da sessão

| Convênio | Terapia | Duração |
|---|---|---|
| Humana | qualquer | 40 min |
| qualquer outro | ABA | 60 min |
| qualquer outro | demais | 40 min |

Conferido contra ~32 mil atendimentos: bate em 94–99% conforme o grupo.

### Sessões realizadas

Vêm de `appointments.session_number`, gravado no lançamento e nas importações da Unimed
(onde o número vem da planilha do convênio, ou seja, é o que vale para faturamento).
**Não recalcular pela duração** — divergiria do faturamento.

Descartar do cálculo: `check_out` nulo e `check_out <= check_in` (há registros invertidos
na base que geravam duração negativa).

### Carga horária: os campos são SESSÕES, não horas

Apesar dos nomes, `requested_hours`, `approved_hours` e `planned_hours` contam sessões.

| Coluna | Significado |
|---|---|
| `requested_hours` | sessões pedidas ao convênio, no mês |
| `approved_hours` | sessões autorizadas, no mês |
| `planned_hours` | sessões por **SEMANA** (legado, `varchar` — converter explicitamente no SQL) |
| `planned_sessions` | sessões no **MÊS**, congelado ao salvar |
| `planned_from_schedule` | se o valor veio da agenda ou foi digitado |

Leitura do planejado usa `COALESCE(planned_sessions, planned_hours * 4)` — o fallback atende
os registros anteriores à derivação pela agenda.

### CH Planejada derivada da agenda

`App\Services\PlannedSessionsFromSchedule`. Três passos:

1. cada bloco da agenda vira sessões pela regra de duração acima (um bloco não é uma sessão —
   há blocos de 40 a 240 minutos);
2. conta as ocorrências daquele dia da semana no mês, **descontando feriados** (`holidays`);
3. multiplica.

O passo 2 é o ponto: setembro/2026 tem 5 terças e 4 segundas, outubro tem 5 sextas.
Multiplicar tudo por 4 erra nos dois sentidos.

**Congelamento:** ao salvar a CH, `planned_sessions` é gravado e não muda mais. Alterar a agenda
depois não reescreve competência fechada; a tela mostra a divergência sem aplicá-la.

O campo continua editável: sobrescrever marca `planned_from_schedule = false` e a tela exibe
"Manual". É a saída para exceções, e deixa rastreável quem fugiu do padrão.

**Limpeza histórica.** Antes da derivação pela agenda o campo era digitado à mão, e o resultado
ficou inconsistente: numa amostra de 400 registros, 269 eram valores semanais, 2 mensais e 90 não
correspondiam a nenhum dos dois. `php artisan ch:recalcular-planejada` regrava tudo pela agenda,
mas **só do mês corrente em diante** (`--desde=YYYY-MM` muda o corte). Competência anterior
permanece com o que foi gravado à época — a agenda não tem histórico, então recalcular o passado
aplicaria a grade de hoje a um mês fechado. Sem `--fix` o comando apenas simula.

`schedules` é grade semanal vigente, **sem histórico**. Ignorar blocos `is_blocked`, sem
`therapy_id`/`service_type_id`, ou com `end_time <= start_time`.

### Convênio e unidade do atendimento

`appointments.agreement_id` e `appointments.unit_id` guardam o que valia **no momento do
atendimento**. Antes só existiam no cadastro do paciente, então trocar o convênio ou transferir
de unidade reescrevia todo o histórico retroativamente (aconteceu em produção com dois pacientes).

O padrão vem do paciente e é sobrescrevível no lançamento (restrito a `admin|manager|administrative`).
A regra de duração da sessão lê o convênio **do atendimento**, não o do cadastro.

Relatórios filtram por essas colunas com fallback para o paciente quando nulas (registros antigos).
O **controle de acesso** continua indo pela unidade do paciente — decisão consciente, não migrada.

---

## Auditoria

Activitylog v5. Namespaces mudaram em relação à v4:
`Spatie\Activitylog\Models\Concerns\LogsActivity` e `Spatie\Activitylog\Support\LogOptions`.
`dontSubmitEmptyLogs()` virou `dontLogEmptyChanges()`. `tapActivity()` não existe.
`useAttributeRawValues()` é **incompatível com cast de enum** (lança `ValueError` ao salvar).

Logam apenas `created` e `updated`: saída e retorno já são registrados via `MovementHistory`,
que carrega o motivo. Logar `deleted`/`restored` duplicaria cada movimentação na tela.

Exclusão permanente (`forceDelete`) não dispara evento — registrar manualmente com `activity()`
antes de apagar, com snapshot dos dados.

---

## Contas de acesso

Cadastrar profissional com e-mail cria um `User` (senha inicial `mudar123`, `must_change_password = true`).
O middleware `EnsurePasswordIsChanged` prende na tela `/trocar-senha` até a troca.

Inativar profissional **revoga o acesso** (soft delete do `User`). Não vale o inverso: excluir
usuário não inativa o profissional — são coisas diferentes.

Guardas na revogação: não desativa a própria conta de quem executa, nem conta compartilhada por
outro profissional ativo (`User::firstOrCreate` por e-mail permite compartilhamento).

Comandos: `usuarios:revogar-inativos`, `usuarios:marcar-senha-padrao` (ambos com `--fix`;
sem a flag apenas diagnosticam).

---

## Armadilhas conhecidas

**Blade grudado em palavra.** `Manual@if(...)` não compila a diretiva (exige que não haja
caractere de palavra antes do `@`), mas o `@endif` compila — sobra `endif` órfão e o parser quebra.
Sempre separar com espaço ou quebra de linha.

**Tailwind com classe dinâmica.** `bg-{$cor}-500` é removido no purge. Gravar a classe por extenso.

**`wire:model` é adiado no Livewire 3.** Hooks `updated()` só disparam na requisição seguinte.
Campos que alimentam cálculo em tempo real precisam de `wire:model.live`.

**MySQL não faz rollback de DDL.** `ALTER TABLE` commita na hora. Migration deve ser rápida e
idempotente (`Schema::hasColumn`); operação pesada de dados vai para comando artisan, que pode ser
interrompido e repetido. Backfill com `Hash::check` numa migration travou o deploy em produção.

**Alias igual a coluna física.** Com `select('tabela.*')`, aliasar uma expressão com o mesmo nome
de uma coluna existente quebra `fromSub` com "Duplicate column name".

**Tooltip em tabela com `overflow-x-auto`.** O `overflow-y` passa a computar como `auto` e recorta
elementos posicionados. Usar `title` nativo.

---

## Verificação

- `php artisan view:cache` **não valida** o PHP gerado. Lintar o compilado:
  `for f in storage/framework/views/*.php; do php -l "$f"; done`
- `Livewire::test()->set()` atribui a propriedade direto no servidor e sempre dispara `updated()`,
  **independente do binding no HTML**. Não serve para validar `wire:model` vs `.live` — isso só
  se confirma na tela.
- Testes que gravam devem rodar em transação com rollback: a suíte PHPUnit do projeto está
  quebrada (migrations falham no sqlite) e os testes são feitos via tinker contra o banco local.
