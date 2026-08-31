# Plataforma ND

Sistema de gestão para clínicas de terapia infantil (Núcleo Desenvolve), multi-unidade.
Laravel 13 · Livewire 3 (+ Volt) · Tailwind · Alpine · MySQL · Spatie Permission · Spatie Activitylog v5.

Hospedagem: Hostinger, plano **compartilhado**. Sem VPS, sem root, cota de disco e de inodes.
**Deploy exige `npm run build` no servidor** — o Tailwind gera apenas as classes que encontra
varrendo o código, e correção em Blade que dependa de classe nova não surte efeito sem rebuild.

---

## Autorização

**Spatie é a única fonte de papel.** Rotas (`role:admin|manager`), `User::isAdmin()`, telas de
Usuários e todas as checagens de componente usam `hasAnyRole()`.

A coluna `users.role` é **legado morto**: nunca foi escrita pelas telas (que só chamam
`syncRoles()`), ficou congelada desde a migration de 04/2026 e não é mais lida por nada. Chegou a
divergir feio — dizia `administrative` para 235 usuários que no Spatie são `profissional`. A troca
das quatro leituras para Spatie não tirou acesso de ninguém e passou a aplicar o filtro de visitas
a 3 coordenadores que antes viam tudo. Pode ser removida por migration quando houver confiança.

Não confundir com **`professionals.role`**, que é outra coluna, legítima, com o enum
`ProfessionalRole` (supervisor/coordinator/therapist/uncategorized).

Papéis cadastrados no Spatie: `admin`, `manager`, `administrative`, `coordinator`, `profissional`,
`avaliador_neuro`. **`supervisor` não existe como papel de acesso** — só como `ProfessionalRole`.
As checagens que citam supervisor casam apenas com coordenadores hoje.

**`avaliador_neuro` é aditivo**, criado em 28/08/2026 pra resolver um caso específico: um
coordenador que também realiza avaliação neuro na prática precisava acessar a ferramenta, mas
abrir pra todo o papel `coordinator` liberaria os outros 15 que não avaliam. Em vez de um
mecanismo de exceção por usuário (que não existe em lugar nenhum do sistema e quebraria
"Spatie é a única fonte de papel"), criou-se um papel novo, atribuível a qualquer usuário pela
tela de Usuários — igual a qualquer outro papel — sem tocar no papel base da pessoa. Vale só
para `/avaliacoes-neuro*`: `role:admin|manager|avaliador_neuro` na listagem,
`role:admin|manager|administrative|avaliador_neuro` em criar/editar — grupo de rota próprio,
separado do grupo "FREQUENCIA" (que também tem Profissionais e Solicitação de CH) pra não
vazar acesso a outras telas. Quando aparecer o próximo caso, é só marcar o checkbox na edição
do usuário — não precisa mexer em código de novo.

17 usuários acumulam mais de um papel (o padrão é `coordinator` + `profissional`), então testar
autorização exige usuário com papel **exclusivo** — senão o resultado engana.

Checagem em componente **não é redundante** com o middleware da rota: as ações do Livewire vão
para `livewire/update`, que não reexecuta o middleware da rota original.

**`/pacientes/{patient}` é aberta a qualquer papel autenticado de propósito** — profissional
precisa ver a própria agenda de pacientes no dia a dia. Isso não estende às ações de escrita: até
08/2026, `Pacientes\Edit` (editar cadastro) e `Pacientes\CargaHoraria` (CH Solicitada/Planejada
por paciente) não tinham nenhuma checagem de papel em nenhum método — qualquer profissional
autenticado conseguia editar dados de paciente e criar/editar/excluir CH pela tela normal, sem
precisar de requisição forjada. Corrigido com `hasAnyRole(['admin','manager','administrative'])`
em todo método que abre modal ou grava (`abrirModal`/`update` em Edit;
`openModal`/`editRecord`/`processSave`/`deleteRecord` em CargaHoraria) — mesmo grupo de papéis já
usado em `/solicitacao-ch` e no `deleteAppointment` de Terapias Realizadas. Os botões também
somem da tela pra quem não tem o papel (`@hasanyrole`), mas isso é só UX — quem segura a porta é
o método. A aba "Laudos e Documentos" do Show, apesar de listada junto nesse relato, não tinha
nada a proteger: é um placeholder ("Em Breve"), sem componente nem dado por trás.

---

## Isolamento multi-unidade

Contrato único: `User::getAllowedUnitIds()` devolve `null` para admin/manager (acesso global)
ou um array de ids. Complementos: `User::canAccessUnit()` e `canAccessAnyUnit()`.

- `Patient` isola sozinho via trait `App\Traits\IsolatesByUnit` (global scope sobre `unit_id`).
- `Professional` **não tem** `unit_id` — o vínculo é a pivô `professional_unit`. Filtrar com
  `whereHas('units', ...)`.
- `Appointment`, `RequestedService`, `Schedule` e `NeuroAssessment` não têm scope próprio:
  a checagem é explícita, geralmente pela unidade do paciente.

Ao remover scopes, preferir `withoutGlobalScope(SoftDeletingScope::class)`.
`withoutGlobalScopes()` derruba o isolamento por unidade junto e abre vazamento entre clínicas.

Componentes Livewire re-hidratam **sem executar `mount()`**. Autorização feita só no `mount()`
não protege: repetir a checagem em todo método que grava ou exclui.

---

## Domínio

```
Unit --< Patient --< Appointment >-- Professional
                 |-< RequestedService (CH por competência)
                 |-< Schedule (grade semanal)
                 |-< PatientService (coordenador/supervisor por tipo)
                 |-< NeuroAssessment --< NeuroSession
                 |-< Visit (acompanhamento domiciliar/escolar)
                 |-< MovementHistory (saída/retorno, polimórfico)
```

`Therapy`, `ServiceType` (Clínica/Escolar/Domiciliar) e `Agreement` (Humana, Unimed, Sulamérica,
Central Nacional, Particular) são tabelas de apoio, vinculadas a unidades por pivô.

---

## Regras de negócio

### Duração da sessão

| Convênio | Terapia | Duração |
|---|---|---|
| Humana | qualquer | 40 min |
| qualquer outro | ABA | 60 min |
| qualquer outro | demais | 40 min |

Conferido contra ~57 mil atendimentos: bate em 94–99% conforme o grupo.
Implementada em `PlannedSessionsFromSchedule::duracaoDaSessao()` e replicada em
`TerapiasRealizadas\Create|Edit::calculateSessions()`.

### Sessões realizadas

Vêm de `appointments.session_number`, gravado no lançamento e nas importações da Unimed
(onde o número vem da planilha do convênio, ou seja, é o que vale para faturamento).
**Não recalcular pela duração** — divergiria do faturamento.

Descartar do cálculo: `check_out` nulo e `check_out <= check_in` (há registros invertidos
na base que geravam duração negativa).

### Carga horária: os campos são SESSÕES, não horas

| Coluna | Significado |
|---|---|
| `requested_hours` | sessões pedidas ao convênio, no mês |
| `approved_hours` | sessões autorizadas, no mês |
| `planned_hours` | sessões por **SEMANA** (legado, `varchar` — converter no SQL) |
| `planned_sessions` | sessões no **MÊS**, congelado ao salvar |
| `planned_from_schedule` | se o valor veio da agenda ou foi digitado |

Leitura do planejado: `COALESCE(planned_sessions, planned_hours * 4)`.

### CH Planejada derivada da agenda

`App\Services\PlannedSessionsFromSchedule`. Três passos:

1. cada bloco da agenda vira sessões pela regra de duração (um bloco não é uma sessão —
   há blocos de 40 a 240 minutos);
2. conta as ocorrências daquele dia da semana no mês, **descontando feriados** (`holidays`);
3. multiplica.

Setembro/2026 tem 5 terças e 4 segundas; outubro tem 5 sextas. Multiplicar tudo por 4 erra
nos dois sentidos.

**Congelamento:** ao salvar a CH, `planned_sessions` é gravado e não muda mais. Alterar a agenda
depois não reescreve competência fechada; a tela mostra a divergência sem aplicá-la.

**`ScheduleObserver` mantém a CH em dia sozinho, para o mês vigente.** Criar, editar ou
excluir um horário na agenda (`schedules`) dispara o observer, que recalcula
`planned_sessions`/`planned_hours` do paciente para toda competência com `month_year >=`
início do mês atual — o mesmo corte do `ch:recalcular-planejada`. Mês fechado não é tocado por
nenhum dos dois: quando o calendário vira, a competência anterior sai do filtro sozinha e o
último valor gravado fica definitivo, sem precisar de lógica extra para "fechar o mês".

Registrado em `AppServiceProvider::boot()`, no mesmo padrão de `AppointmentObserver`/
`VisitObserver`. Só reage a mudança nos campos que afetam o cálculo (dia, horário, terapia,
tipo, paciente, bloqueio) — trocar o profissional de um bloco não recalcula nada. Trocar o
`patient_id` de um bloco recalcula os dois pacientes envolvidos, não só o atual.

**Nem o observer nem `--fix` sobrescrevem CH marcada como Manual.** Uma linha com
`planned_from_schedule = false` e um valor já preenchido foi digitada de propósito — convênio
autorizou menos sessões do que a agenda comporta, por exemplo. Antes dessa proteção, rodar
`--fix` (ou, com o observer, só editar QUALQUER horário do mesmo paciente) apagava essa decisão
silenciosamente, sem o autor saber; testado e confirmado antes da correção. O comando agora
reporta "Manuais, preservados" com a contagem de quantas linhas pulou por esse motivo — na base
local, 49.

**A CH não se atualiza sozinha quando a agenda é completada depois.** `preencherPelaAgenda()`
só é chamado quando o usuário troca terapia/tipo/mês no formulário — abrir "Editar" num
registro já congelado e salvar sem tocar nesses campos nunca dispara `updated()`, então uma
CH que ficou "sem agenda" continua assim para sempre, mesmo depois de alguém montar o horário
do paciente. Foi o caso real que motivou o botão "Agenda agora calcula X — clique para usar"
em `Pacientes\CargaHoraria`: a coordenação cadastra a CH antes de fechar a grade de horários
(comum em ABA, que costuma vir depois), e sem esse botão o único jeito de reconciliar era
editar terapia e tipo de novo (o que reseta a seleção) ou pedir para alguém rodar
`ch:recalcular-planejada` por SSH. O botão só aparece quando `agenda_mensal` (calculado ao
abrir a edição, sem sobrescrever nada) diverge do valor gravado — não aparece se os dois já
batem, nem se a linha já é `planned_from_schedule = true`.

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

A tabela `holidays` existe e o desconto está implementado, mas **está vazia e não há tela nem
seeder** — o desconto é inerte até alguém alimentá-la.

### Convênio e unidade do atendimento

`appointments.agreement_id` e `appointments.unit_id` guardam o que valia **no momento do
atendimento**. Antes só existiam no cadastro do paciente, então trocar o convênio ou transferir
de unidade reescrevia todo o histórico retroativamente (aconteceu em produção com dois pacientes).

O padrão vem do paciente e é sobrescrevível no lançamento (restrito a `admin`, `manager` e
`administrative`). A regra de duração da sessão lê o convênio **do atendimento**, não o do cadastro.

Relatórios filtram por essas colunas com fallback para o paciente quando nulas. O **controle de
acesso** continua indo pela unidade do paciente — decisão consciente, não migrada.

### Requisições complementares

O convênio autoriza a CH em requisições parciais: o mesmo paciente pode ter duas linhas em
`requested_services` para a mesma terapia no mês, com números distintos. Não é duplicidade —
em julho/2026, 33 dos 35 grupos tinham requisições diferentes, e em 13 deles o realizado
ultrapassava a maior requisição isolada.

`ChSolicitada\Index` agrupa por **paciente + terapia + tipo + competência**:

| Campo | Como agrega |
|---|---|
| solicitadas, autorizadas, planejadas | `SUM` entre as requisições |
| realizado | `MAX` — já vem agregado por combinação; somar contaria o mesmo atendimento por requisição |

Sem o agrupamento, julho/2026 exibia 15.868 sessões contra 14.777 reais. O filtro de faixa usa
`HAVING` (compara agregados), não `WHERE`. `appointments.guide` **não** vincula atendimento a
requisição — numerações diferentes, zero correspondências.

**Extração para Excel** (`exportExcel()`, botão "Extrair para Excel" nos Filtros). CSV com BOM
e separador `;`, mesmo padrão do export de Terapias Realizadas. Colunas: paciente, CPF e carteira
(do cadastro do paciente, não do relatório do convênio), terapia, e as quatro agregações já
descritas na tabela acima (solicitada, autorizada, planejada, realizada) — sem aderência nem
"falta", que são derivadas, não CH. Usa a mesma
`baseQuery()` + `aplicarFiltroFaixa()` da tela, sem paginação: reflete exatamente os filtros
ativos (unidade, mês/ano, convênio, terapia, busca, faixa). Guardado com `admin|manager` —
mesmo grupo do middleware da rota `/ch-solicitada`, mas repetido no método porque o dado
exportado (CPF, carteira) é mais sensível que o que a tela já mostra, e a ação do Livewire não
passa pelo middleware da rota.

**CPF/carteira sujos na base — normalizados só no export, não no cadastro.** `patients.cpf`
tem 253 registros com máscara, 258 só dígitos e 8 com grafia solta (ponto no lugar do traço,
espaço perdido); 23 CPFs e 62 carteiras começam com zero. Sem tratamento, o Excel lê a coluna
"só dígitos" como número — alinha à direita e **derruba o zero à esquerda**, produzindo a
mistura visual (`162.818.934-76` ao lado de `71303330490`) que motivou a correção.
`formatarCpfParaExportacao()` remonta todo CPF de 11 dígitos no mesmo formato, não tenta
adivinhar os com menos de 11 (mostra os dígitos crus — mascarar um cadastro já errado seria
pior). `comoTextoNoExcel()` envolve CPF e carteira em `="valor"`: força Excel/Sheets/LibreOffice
a tratar como texto, preservando o zero à esquerda em qualquer tamanho — inclusive as carteiras,
que variam de 7 a 20 dígitos e não têm máscara natural para se apoiar. Índice de dados sujos do
CPF fica só aqui: a coluna em si continua exatamente como está no cadastro.

### Produção e pagamento

`App\Services\ProfessionalPayrollCalculator` é a **única** implementação do cálculo;
`Producao\Fechamento` e `Producao\Index` delegam a ele. `ProfessionalPaymentRule` pode filtrar
por `therapy_id`, `service_type_id` e `agreement_id` — qualquer um deles nulo funciona como
curinga. As regras são ordenadas por **especificidade** (quantos campos preenchidos) e vence a
primeira que casar. `payment_type` aceita `por_sessao` e `Por Sessão`.

Entram no cálculo apenas atendimentos com `check_in` preenchido e `is_glosado = false`.
O convênio considerado é o **do atendimento**, com fallback para o do paciente — mesma regra
já usada na duração da sessão. (O Fechamento lia só o do paciente; após o backfill os dois
caminhos dão idêntico, mas o congelado é o correto daqui em diante.)

**O buraco do repasse.** Só 63 dos 237 profissionais têm regra cadastrada. Em agosto/2026,
142 produziram sem nenhuma regra — 6.323 sessões que fecham em R$ 0,00 — e mais 3 têm regra
com `payment_type` `por_hora` ou `por_dia`, que o cálculo **não sabe converter em valor** e
ignora em silêncio. Total apurado: R$ 60.207,00 sobre 9.077 sessões, ou seja, 70% da produção
sai zerada. A Index expõe isso; corrigir os tipos não suportados exige decisão de negócio
(o que é um "dia" ou uma "hora" de trabalho) e mexe em dinheiro.

Profissional inativado continua produzindo até a data da saída, mas **some da tela de
Fechamento**, que lista só ativos. Em agosto foram 5 profissionais, 118 sessões, R$ 350,00.
A Index lista esses casos à parte, para o acerto de saída não passar batido.

### Painel da Produção

`Producao\Index` mostra a competência escolhida (12 meses no seletor) com KPIs, pendências,
ranking de repasses e volume dos últimos 6 meses. Mês corrente é comparado com o **mesmo
período** do mês anterior: em 19/08 a comparação contra julho inteiro daria −44,6%, contra
julho até o dia 19 dá −0,2%.

`check_in` nunca é nulo na base — a pendência útil é `check_out`, que não afeta o repasse
mas derruba o atendimento da CH (ver "Por que CH Solicitada e Relatórios Gerais divergem").

---

### Glosas do convênio

Origem: relatório `NAT_RELATORIO_CPLS_AAAAMM` / `MOS_RELATORIO_CPLS_AAAAMM` da Unimed, uma vez
por mês. `App\Services\GlosaReportImporter` + `glosas:importar` leem **dois formatos**,
detectados pelo separador:

| Formato | Separador | Datas | Cobre |
|---|---|---|---|
| `.xls` original (é TSV, ISO-8859-1) | tab | `dd/mm/aaaa`, comp. `mm/aaaa` | um prestador, um mês |
| CSV do pipeline do Drive | `;` | ISO, carteira já sem zeros | vários prestadores e meses |

O pipeline converte PDF+XLS em CSV e deixa em
`H:\Meu Drive\processed\unimed__demonstrativo_producao_{pdf,xls}`. O consolidado
`unimed__demonstrativo_producao_xls.csv` cresce a cada mês e tem o histórico inteiro. Em
24/08/2026: 79.246 linhas, **12 competências x 2 prestadores = 24 remessas**, 05/2025 a
07/2026 com buracos (faltam 02, 03 e 05/2026).

`--competencia=AAAA-MM` filtra **na leitura**. Sem ele, o consolidado inteiro consome ~550 MB
de pico e não passa em hospedagem compartilhada; com ele, 146 MB. É assim que se importa o mês
novo: apontar para o consolidado e filtrar, sem precisar fatiar o arquivo antes.

**Rotina mensal (em produção desde 24/08/2026).** Os arquivos ficam no servidor em
`storage/app/glosas/` (o consolidado, que é o que se importa) e `storage/app/glosas_pdf/` (os
CSV por competência, que só servem para conferir). As duas pastas não são alcançáveis pela web
— o domínio aponta para `public/` — e o `.gitignore` de `storage/app` já as cobre.

Quando chega a competência nova: substituir o consolidado, acrescentar os dois CSV do PDF, e

```
php artisan glosas:importar storage/app/glosas/unimed__demonstrativo_producao_xls.csv --competencia=AAAA-MM
php artisan glosas:importar ... --competencia=AAAA-MM --fix     # depois de conferir a simulação
php artisan glosas:conferir storage/app/glosas_pdf
```

Reemissão da Unimed exige `--substituir`: a remessa antiga é apagada em cascata e regravada.
O laço é seguro de repetir — competência já gravada é bloqueada, não duplica.

Três tabelas — `glosa_batches` (remessa), `glosa_items` (linha) e `glosa_reasons` (motivos) —
mais o catálogo `glosa_reason_codes`. Fica **fora de `appointments`** de propósito: o relatório
é documento do convênio, chega meses depois (competência 06/2026 emitida em 10/07 cobre
atendimentos de abril e maio), pode ser reemitido, e boa parte das linhas não tem atendimento
correspondente — precisam existir mesmo assim, senão o total nunca fecha com o RESUMO do PDF.

**A glosa é informativa.** Não marca `appointments.is_glosado` nem altera repasse: quando o
relatório chega, a competência já foi paga.

| Campo do arquivo | Liga em | Observação |
|---|---|---|
| `Guia` | `appointments.guide` | **1:1, nunca ambíguo** |
| cabeçalho do prestador | `units.unimed_code` | 21000430 -> LEAL E CARVALHO; 38000104 -> LIMEIRA |
| `Carteira` | `patients.agreement_number` | o `.xls` traz zeros à esquerda, o cadastro não |
| `Medico` | — | **não usar**: diverge do cadastro por acento e ordem do nome |

Não confundir com a outra ponta: `guide` liga atendimento ao relatório do convênio, mas **não**
liga atendimento a requisição (ver "Requisições complementares").

Só as unidades com Unimed têm `unimed_code`. CL INTERVENÇÃO COMPORTAMENTAL (João Câmara) e
CL II (Santa Cruz) ainda não atendem o convênio; arquivo de código desconhecido é recusado.

**Conciliação só existe de 02/2026 em diante**, que é quando a plataforma passa a ter
atendimentos. Todo 2025 importa com 0% conciliado — a glosa fica registrada, mas sem
profissional atribuível. 04/2026 concilia 97%, 06/2026 concilia 76%.

**Motivos.** Uma linha pode ter vários, separados por vírgula: `3145 - NAO AUTORIZADO POR
MOTIVO TECNICO, CM89 - Guia sem execução cirúrgica`. **Não dá para quebrar em toda vírgula** —
a descrição também tem vírgula ("Conforme prescrição e autorização, evidencias..."). O corte só
acontece antes de um novo código (`CM89`, `3145`, `INTADM120`) ou de um marcador
`Ocorrencia -`/`Parecer -`, que é como o PDF anota.

O catálogo `glosa_reason_codes` existe porque **o mesmo código chega com duas grafias**: a
conversão para CSV perdeu acentos em parte dos meses, então convivem "Cobrança de item..." e
"Cobran?a de item...". Sem catálogo o ranking parte o motivo em duas linhas. O código é ASCII e
nunca corrompe — ele é a chave; a descrição canônica é a grafia com menos caractere de
substituição, **não a mais frequente** (em vários códigos a corrompida é a que mais aparece).
`orientacao` está livre para a clínica anotar como evitar a glosa.

**O quadro geral:** R$ 9.452.378,00 apresentados, R$ 565.350,00 glosados (5,98%) em 12
competências. Por competência o índice oscila muito — 16,91% em 05/2025, 2,30% em 11/2025,
4,11% em 06/2026, 2,60% em 07/2026. Motivos mais frequentes: CM89, CM100, CM74, 3145, CM107.
São 22 códigos; 07/2026 trouxe o `1706 - Valor apresentado a menor`.

A conciliação melhora conforme a plataforma amadurece: 97% em 04/2026, 76% em 06/2026,
**91% em 07/2026**.

**Glosa previsível.** Em 06/2026, os 45 glosados por `CM107 - Tempo de execução insuficiente`
que casaram por guia têm duração de exatamente 1 minuto na plataforma
(`check_out = check_in + 60s`), criados em lote pela importação da Unimed; dos 2.828 não
glosados que casaram, a duração mínima é 21 minutos. **07/2026 repete o padrão em amostra
independente: 75 de 75.** São ~435 atendimentos nessa condição na base, 39 a 82 por mês.
Continua sendo correlação, não causalidade provada — o 1 minuto e a glosa provavelmente têm a
mesma origem, o que foi enviado ao convênio —, mas com dois meses batendo 100% vale tratar
como alerta antes de faturar.

**Conferência cruzada.** O pipeline gera duas leituras independentes do mesmo relatório:
`unimed__demonstrativo_producao_xls/` traz o consolidado do XLS (é o que se importa — tem
`Medico`, `Carteira`, `Item`, `Lote`) e `unimed__demonstrativo_producao_pdf/` traz um CSV por
competência extraído do PDF (não tem `Medico`, serve só para conferir). `glosas:conferir`
compara as duas. Em 21/08/2026: **20 das 22 remessas batem item a item e no valor**; 01/2026
não tem arquivo do PDF, então ficou sem conferência externa.

A ordem das colunas do CSV do PDF **muda de arquivo para arquivo** — `Ocorrencia_1` e
`Parecer_1` trocam de lugar entre os meses. Mapear por nome, nunca por posição.

O nome de outubro/2025 do NAT está trocado: `NAT_RELATORIO_CPLS_102025.csv` em vez de `202510`.
Não atrapalha, porque a competência é lida de dentro do arquivo.

**A tela.** `Producao\Glosas\Index` (`/producao/glosas`) tem **duas faixas de filtro com
escopos diferentes**, e isso é deliberado: competência e unidade recortam a página inteira
(KPIs, gráfico, rankings, lista); motivo, busca e situação recortam **só a lista**. Filtrar KPI
por motivo produziria número sem sentido — "valor apresentado do CM89" não existe, porque o
apresentado é do item, não do motivo. O gráfico de evolução responde só à unidade, já que a
competência é o próprio eixo x.

**Competência é Mês + Ano separados**, não um único seletor de mês/ano combinado. Mês vazio =
ano inteiro — é o que permite, já em janeiro do ano seguinte, escolher "ano inteiro" e ver os
12 meses do ano anterior somados de uma vez, sem precisar somar mês a mês. Filtragem sempre por
intervalo (`>= início AND < fim` da competência), nunca `whereYear()`/`whereMonth()` na coluna —
anularia o índice (ver "Armadilhas conhecidas"). Padrão ao abrir a tela: **mês anterior ao
vigente**, não o mês corrente — o relatório da Unimed chega cerca de dois meses depois do
atendimento, então o mês vigente quase nunca tem dado ainda. Clicar numa barra do gráfico de
evolução filtra por aquele mês; clicar de novo na mesma barra amplia para o ano inteiro dela
(`filtrarPorCompetencia()`).

Este componente não tinha nenhuma checagem de papel própria até 28/08/2026 — só o middleware
da rota (`role:admin|manager`), que não é reexecutado pelas ações do Livewire. Ganhou `mount()`
com o mesmo guard ao mexer no filtro de competência.

**Ranking por profissional usa `medico_nome`, não o profissional conciliado.** Em 06/2026 só
59 dos 198 glosados acham atendimento pela guia; restringir a eles escondia o maior caso do mês
(DIAENE JOAQUINA, 13 guias, R$ 3.264,00). O nome do relatório existe em toda linha, então é ele
que agrupa — normalizado, porque as grafias divergem entre relatório e cadastro. O nome exibido
é o do cadastro quando há vínculo. Quem não tem nenhuma guia conciliada leva o selo "sem
vínculo": aparece no ranking, mas não dá para abrir o atendimento.

Conferido contra o BI antigo em 06/2026: os sete primeiros batem exatamente (DIAENE 13,
MARIA CLARA 10, DEBORA 10, BARBARA 7, WILLIAN 7, JOAO VICTOR 6, FERNANDA 6).

**O BI antigo conta motivo em dobro.** Em 06/2026 o Looker mostra CM100=172, CM107=125,
CM121=4; o arquivo tem 86, 111 e 2. A conta fecha se as linhas do prestador 38000104 forem
contadas duas vezes: CM107 = 97 (21000430) + 14x2 = 125; CM100 = 86x2 = 172; CM121 = 2x2 = 4.
Os rankings de beneficiário e profissional do Looker não sentem a duplicata porque contam
`DISTINCT` guia; o de motivo é `COUNT` simples. Os KPIs também dobram a parte da Limeira e
batem nos quatro: 689.198 + 264.582 = 953.780 (Looker "953,8 mil"); glosa 28.358 + 16.206 =
44.564 ("44,6 mil"); liberado 660.852 + 248.388 = 909.240 ("909,2 mil"); 4,67% ("4,7%").

A contagem de linhas do Looker (5.695 contra 5.698) é outra coisa, e não é erro: são 3 pares de
linhas que só diferem em colunas que a tabela dele não exibe, e o Looker Studio agrupa a tabela
pelas dimensões mostradas. Note que a tabela dele mostra 5.695 enquanto os KPIs somam como se
houvesse 7.752 — **o BI está inconsistente consigo mesmo**. Se alguém comparar as duas telas,
é isto.

Barra do gráfico e linha do ranking de motivos são clicáveis e aplicam o filtro
correspondente; clicar de novo na mesma limpa.

**Dado sujo na origem:** 6 linhas em 79.246 têm `apresentado - glosa != liberado` — uma com
apresentado 180, glosa 180 e liberado 180 ao mesmo tempo, e duas em 07/2026 onde o convênio
liberou 136 tendo sido apresentados 130. O comando avisa e não corrige: o erro é do relatório.

### Acompanhamento de Recurso de Glosa

Tela irmã do Relatórios Mensais, sob o novo agrupamento de menu "Acompanhamento de Glosas"
(que separou de "Apuração", renomeado "Produção"). Cobre a etapa depois da glosa: a clínica
recorre junto ao convênio, parte do valor volta ("recursado"), e desse recursado, parte é
efetivamente paga ("acatado"). Preenchimento 100% manual pela coordenação — nada disso vem de
importação.

`glosa_recursos` permite **mais de um registro por lote de glosa** — raro (reenvio, nova
tentativa), mas acontece, então `glosa_batch_id` não é único (era, até 28/08/2026; o unique
saiu numa migration própria, criando o índice substituto antes de dropar o antigo, por causa
da FK — ver "Armadilhas conhecidas"). `GlosaBatch::recursos()` é `hasMany`. Cada registro não
guarda histórico de mudanças, só o estado atual daquele recurso específico. Campos digitados:
`lote` (número do lote do recurso junto ao convênio — **não é** `glosa_items.lote`, que é o
lote de faturamento original do item, coisa diferente), `valor_recursado`, `valor_acatado` e
`status` (lista fechada por enquanto: Em Análise, Pagamento Efetuado —
`App\Models\GlosaRecurso::STATUS_OPTIONS`).

O formulário em `Producao\Glosas\Recursos` é um **repeater** — mesmo padrão das requisições
complementares de CH em `Pacientes\CargaHoraria` (`terapias[]`/`adicionarTerapia()`): um lote
abre com todos os recursos já registrados, "Adicionar recurso" inclui outra linha, e cada linha
tem seu próprio "remover". Remover uma linha já gravada (tem `id`) **exclui de verdade** na
hora, não espera o "Salvar" — mesma UX de `CargaHoraria::deleteRecord()`. A listagem soma os
recursos de cada lote com `withSum()`/`withCount()`, nunca com `leftJoin` direto — um `leftJoin`
multiplicaria as linhas de `glosa_batches` por recurso e contaria `vl_glosa` em dobro quando um
lote tem mais de um recurso (mesma classe de bug do "BI antigo conta motivo em dobro", mais
acima). Na tabela, lote com 0 recursos mostra "—" e botão "Registrar"; 1 recurso mostra os
valores normalmente e "Editar"; mais de 1 soma os valores automaticamente e mostra "Vários (N)"
/ "N recursos" com tooltip listando os lotes e status individuais, botão "Gerenciar".

Os percentuais de conversão **não são gravados**: `% conversão recursado` = soma dos
recursados / glosa do lote, `% conversão acatado` = soma dos acatados / soma dos recursados,
calculados na tela. Período inicial/final (colunas que aparecem numa planilha de referência do
time) ficaram de fora de propósito — não existe essa granularidade em `glosa_batches` hoje, e
decidiu-se não adicionar.

**Única tela de glosa que `administrative` acessa.** Relatórios Mensais continua
`admin|manager`; Acompanhamento de Recursos é `admin|manager|administrative` — tanto na rota
quanto checado de novo no componente (`autorizarAcesso()` no `mount()` e repetido em
`abrirModal`/`salvar`, mesmo motivo de sempre: ação do Livewire não passa pelo middleware da
rota). Como `administrative` não é admin/manager, `getAllowedUnitIds()` devolve array, não
`null` — a tela aplica esse filtro em toda consulta (`escopoUnidade()`), coisa que a tela de
Relatórios Mensais nunca precisou fazer por ser só admin|manager. **Isso é ortogonal ao
`can_access_production`** (coluna em `users`, middleware `CheckProductionAccess`, guarda toda
a área `/producao`): liberar o papel Spatie não basta se o usuário administrative individual
não tiver essa flag — em 28/08/2026 só 2 dos 12 administrative tinham.

Lista só lotes com `vl_glosa > 0` — sem glosa não há o que recorrer. Mesma separação de escopo
de filtro do Relatórios Mensais: competência e unidade recortam KPIs + lista, busca e status
recortam só a lista.

### Sistema de tokens `nd-ui`

Nasceu nos Relatórios Gerais como `<style>` local (`.nd-relatorios`) e virou classe
compartilhada em `resources/css/app.css` (`.nd-ui`) para não repetir o mesmo bloco em cada
tela redesenhada. Primeira reutilização: `Terapias Realizadas`. Cores, espaçamento e a
tipografia (`nd-eyebrow`, `nd-title`, `nd-num` para números tabulares) ficam em variáveis CSS
dentro do escopo `.nd-ui`, então nada vaza para o resto do sistema — uma tela só adota o
padrão novo envolvendo o componente em `<div class="nd-ui">`.

`@tailwindcss/forms` só remove a aparência nativa do navegador; quem desenha borda, padding
e o anel de foco dos campos são as classes `.nd-input`/`.nd-select`/`.nd-btn-primary`/
`.nd-btn-ghost` do mesmo arquivo — sem elas, um `<select>` sem outras classes Tailwind fica
sem contorno visível.

### Relatórios Gerais: cor, tipo e a fita do mês

`Relatorios\Geral` tem três abas — geral, comparativo e **frequência e ocupação** — todas sob
os mesmos filtros. A de ocupação sai de `HOUR(check_in)` numa consulta agregada só; os quatro
painéis são recortes dela em PHP, para não divergirem entre si.

**Todo quantitativo do sistema soma `session_number`, nunca conta atendimentos.** A sessão é o
que vale para faturamento e para a CH, e a razão varia muito por unidade: em agosto/2026 Natal
teve 1,75 sessão por atendimento e Mossoró 2,03, contra 1,00 em João Câmara e Santa Cruz —
contar atendimento subestimaria justamente as duas unidades que faturam Unimed. `COUNT(*)` só
aparece como métrica secundária, rotulada como "registros lançados".

Cuidado com rótulo: os rankings por terapia, unidade e convênio somam `session_number` desde
sempre, mas se chamavam "Ranking de Atendimentos". Título e nome de série têm que dizer
**Sessões** quando é isso que está no eixo.

**Regra de cor.** Ranking de série única (terapia, unidade, convênio, beneficiários) usa **uma
cor só** — a categoria já está no eixo, e pintar cada barra de um jeito não codifica nada. A
paleta categórica só entra onde há mais de uma série no mesmo gráfico. Ela é validada para
daltonismo (pior par adjacente ΔE 9,1) e tem 8 posições, na ordem:

```
#2a78d6  #eb6834  #1baf7a  #eda100  #e87ba4  #008300  #4a3aa7  #e34948
```

São 12 terapias para 8 posições, então o empilhado por dia da semana tem **teto de 7 séries +
"Outras"**. Sem o teto o ApexCharts recicla cor e duas terapias viram a mesma — era o que
acontecia com o array de 6 cores que existia antes. Nunca acrescentar uma nona cor.

`distributed: true` no ApexCharts pinta cada barra de uma cor: só usar quando a cor carregar
significado próprio, o que num ranking não é o caso.

**A fita do mês** é o elemento de destaque da aba geral: cada dia do mês vira uma barra, fins
de semana entram esmaecidos e dias sem lançamento aparecem como traço. Ela substituiu quatro
cards de KPI mais um gráfico de "Sessões por Dia" que repetia a mesma série. As iniciais dos
dias somem abaixo de `sm` — a 5px de largura não se leem.

Os tokens da página vivem num `<style>` com escopo `.nd-relatorios`, então nada vaza para o
resto do sistema. A fonte é a Figtree que já era do projeto, com 700/800 acrescentados ao link
do `layouts/app` para o display; nenhuma outra tela usa esses pesos ainda.

## Por que CH Solicitada e Relatórios Gerais divergem

São perguntas diferentes, não erro de cálculo. `Relatorios\Geral` faz `Appointment::query()`
puro: conta tudo que foi lançado. `ChSolicitada\Index` parte de `requested_services` e só
enxerga o atendimento que tem CH cadastrada.

Restam dois fatores, ambos de processo:

| Fator | Efeito na CH |
|---|---|
| `check_out` nulo ou anterior ao `check_in` | descartado — o sistema não sabe a duração |
| Atendimento sem CH cadastrada | não aparece (é o maior fator) |

Julho/2026: 15.862 sessões atendidas − 221 sem check-out − 652 sem CH = 14.989 na CH.
Reconciliação exata, resíduo zero. Medir com `php artisan ch:conferir-divergencia --mes=YYYY-MM`.

Dois fatores foram corrigidos e deixaram de existir: requisições complementares contadas em
dobro, e paciente com saída registrada — a alta apagava retroativamente as competências
anteriores. A tela usa `withTrashed()` em todos os filtros por paciente, **inclusive no
isolamento por unidade**; o mês seguinte se resolve sozinho, já que ninguém cadastra CH para
quem saiu.

---

## Auditoria

Activitylog v5. Namespaces mudaram em relação à v4:
`Spatie\Activitylog\Models\Concerns\LogsActivity` e `Spatie\Activitylog\Support\LogOptions`.
`dontSubmitEmptyLogs()` virou `dontLogEmptyChanges()`. `tapActivity()` não existe.
`useAttributeRawValues()` é **incompatível com cast de enum** (lança `ValueError` ao salvar).

`Patient` e `Professional` logam apenas `created` e `updated`: saída e retorno já são
registrados via `MovementHistory`, que carrega o motivo. `Appointment` e `Visit` têm o
`getActivitylogOptions()` pronto mas o trait **desativado**.

Exclusão permanente (`forceDelete`) não dispara evento — registrar manualmente com `activity()`
antes de apagar, com snapshot dos dados.

A tela `Controles\Index` resolve a unidade de cada registro por caminhos diferentes conforme o
tipo (paciente por `unit_id`, profissional pela pivô, movimentação pelo `moveable`).

---

## Contas de acesso

Cadastrar profissional com e-mail cria um `User` (senha inicial `mudar123`,
`must_change_password = true`). O middleware `EnsurePasswordIsChanged` prende na tela
`/trocar-senha` até a troca — ele libera `livewire/*`, senão o próprio formulário e o logout
ficariam bloqueados.

Inativar profissional **revoga o acesso** (soft delete do `User`). Não vale o inverso: excluir
usuário não inativa o profissional — são coisas diferentes, decisão consciente.

Guardas na revogação: não desativa a própria conta de quem executa, nem conta compartilhada por
outro profissional ativo (`User::firstOrCreate` por e-mail permite compartilhamento).

Risco residual conhecido: `mudar123` é pública, então um atacante pode chegar antes do
profissional e definir a senha. Fechar isso exige senha aleatória no cadastro.

---

## Comandos artisan

| Comando | O que faz |
|---|---|
| `ch:conferir-divergencia --mes=` | decompõe a diferença entre CH Solicitada e Relatórios Gerais |
| `ch:recalcular-planejada [--desde=] [--fix]` | regrava a CH planejada pela agenda, do mês corrente em diante |
| `glosas:conferir {pasta}` | confere as glosas importadas contra a extração do PDF |
| `glosas:importar {arquivo} [--competencia=] [--fix] [--substituir]` | importa o relatório de glosas da Unimed e concilia pela guia |
| `usuarios:revogar-inativos [--fix]` | revoga contas de profissionais já inativados |
| `usuarios:marcar-senha-padrao [--fix]` | marca contas que ainda usam `mudar123` |
| `app:send-birthday-emails` | agendado às 08:00; e-mail de aniversariantes ao RH |

Todos com modo diagnóstico por padrão — sem `--fix` apenas simulam.

---

## Armadilhas conhecidas

**Blade grudado em palavra.** `Manual@if(...)` não compila a diretiva (exige que não haja
caractere de palavra antes do arroba), mas o `@endif` compila — sobra `endif` órfão e o parser
quebra.

**`wire:click` dentro de `<x-slot name="header">` não funciona.** O `layouts.app` renderiza
esse slot dentro de `<header>`, que é **irmão** do `<main>{{ $slot }}</main>` onde fica a raiz
`wire:id` do componente. O Livewire só liga diretivas dentro da própria raiz, então o botão
aparece na tela mas o clique não dispara nada — sem erro, sem requisição. Foi o bug do
"Excluir" da Avaliação Neuro. Ação interativa vai no corpo do componente, nunca no slot de
layout. Não confundir com `x-slot` de componente Blade usado inline (`<x-dropdown>`), que
renderiza no mesmo ponto e funciona normalmente.

**Tailwind com classe dinâmica.** `bg-{$cor}-500` é removido no purge. Gravar por extenso.

**`wire:model` é adiado no Livewire 3.** Hooks `updated()` só disparam na requisição seguinte.
Campo que alimenta cálculo em tempo real precisa de `wire:model.live`.

**MySQL não faz rollback de DDL.** `ALTER TABLE` commita na hora. Migration deve ser rápida e
idempotente (`Schema::hasColumn`); operação pesada de dados vai para comando artisan, que pode
ser interrompido e repetido. Backfill com `Hash::check` numa migration travou o deploy em produção.

**Alias igual a coluna física.** Com `select('tabela.*')`, aliasar uma expressão com o mesmo nome
de uma coluna existente quebra `fromSub` com "Duplicate column name".

**Tooltip em tabela com `overflow-x-auto`.** O `overflow-y` passa a computar como `auto` e recorta
elementos posicionados. Usar `title` nativo.

**`whereYear` + `whereMonth` anulam o índice.** Envolver a coluna numa função impede o uso de
`appointments_appointment_date_index`: `type=ALL`, 57.320 linhas varridas contra 5.672 com
`where >= inicio AND < fim`. Filtrar competência por intervalo de datas.

**`pluck` com `DB::raw` na chave.** A coluna-chave tem que existir no resultado; com uma
expressão crua o Laravel procura uma propriedade com o nome do SQL inteiro, avisa
`Undefined property` e devolve tudo nulo — o gráfico fica zerado sem erro. Dar `as alias` no
`selectRaw` e usar o alias.

**Índice usado por foreign key não pode ser dropado.** MySQL 1553: a FK se apoia no índice
cuja coluna dela é a mais à esquerda. Criar o substituto **antes** de remover o antigo.

**`iconv` com `//TRANSLIT` depende do locale.** Neste servidor "DÉBORA CÂMARA" vira
`D'EBORA C^AMARA`, não `DEBORA CAMARA` — agrupar nomes por essa chave parte a mesma pessoa em
dois grupos. Usar `Str::ascii()`, que tem tabela própria e resultado estável.

**`Carbon::createFromFormat` lança exceção** quando o formato não casa, em vez de devolver
`false`. Testar vários formatos em sequência exige `try/catch` em cada tentativa.

**`orWhere` sem agrupamento.** `AND` tem precedência sobre `OR`; sem o closure a pesquisa vaza
para fora do filtro pretendido.

---

## Verificação

- `php artisan view:cache` **não valida** o PHP gerado. Lintar o compilado:
  `for f in storage/framework/views/*.php; do php -l "$f"; done`
- `Livewire::test()->set()` atribui a propriedade direto no servidor e sempre dispara `updated()`,
  **independente do binding no HTML**. Não serve para validar `wire:model` vs `.live` — isso só
  se confirma na tela.
- Testes que gravam devem rodar em transação com rollback: a suíte PHPUnit está quebrada
  (migrations falham no sqlite) e os testes são feitos via tinker contra o banco local.

---

## Dados sujos conhecidos

- 1 atendimento com data no ano **0202**
- 4 atendimentos com `check_out` anterior ao `check_in`; ~336 sem `check_out`
- 1 bloco de agenda com duração negativa
- Pacientes #492 (unidade) e #299 (convênio) tiveram troca que reescreveu histórico antes da
  correção; o backfill não reconstrói
- `professionals.deletion_reason` existe, está no fillable e **nunca é preenchido**
- Motivo e observação de saída são concatenados num só campo; o bloco "Obs" do Blade é código morto
