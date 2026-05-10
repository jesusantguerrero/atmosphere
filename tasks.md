# Tasks — Pending

> Last consolidated: 2026-05-04
> Completed items archived in [tasks-done.md](tasks-done.md)

---

## Versión y estrategia

Currently on `v2.0.0-alpha.19`. Versión-target completo en [roadmap.md](roadmap.md): v2.0 stable (bugs+polish) → v2.x marketing → v2.5 engagement → v3.0 monetización.

Orden de ataque dentro de v2.0:

1. **Matar bugs** existentes (LM-*, errores en módulos)
2. **Pulir y explotar** lo que ya existe — features construidas pero subutilizadas
3. **Engagement / marketing** con lo que ya tenemos → v2.x
4. **Después** módulos nuevos (Couples backend, etc.) → v2.5
5. Apps marketplace → v3.0 (no antes)

La apuesta: módulos existentes ya tienen ~80% del valor latente — sacarlo a flote rinde más que greenfield. Antes de proponer feature nuevo, pregunta: *¿hay una primitiva en el app que ya haga 70% de esto?* (Watchlist saved-filters, BudgetTarget, LogerProfile pivot...).

---

## 🎯 Watchlist — explotación (PRIORIDAD ALTA)

El módulo tiene la primitiva correcta (saved filter + aggregation) pero quedó atrapado en su propia página. Estos items convierten el módulo de "lo abro a ver" a motor transversal de alertas, reports, challenges y engagement.

### WL-5. Watchlists como Reports / templates de Trends ⭐⭐ medio (parcial)
Hoy en Trends filtras por categoría/payee/etc. cada vez. Guardar el filtro como watchlist convierte Trends en reports reutilizables.

- [x] **Sub-bullet 1**: botón "Save as report" en [Trends/Overview.vue](resources/js/Pages/Trends/Overview.vue) cuando section es `groups`/`categories`/`payees`. Click abre WatchlistModal pre-llenado con type+input del filtro actual. Reusa el modal existente — el usuario completa name + target + direction y submit
- [ ] **Sub-bullet 2 (deferido)**: pre-aplicar filtros cuando se carga Trends con `?watchlist={id}` — requiere que FinanceTrendController hidrate watchlist y mute el pageState client-side
- [ ] **Sub-bullet 3 (deferido)**: sidebar en Trends con "My Reports" (lista de watchlists) — requiere pasar `watchlists` prop desde el controller y agregar UI en TrendTemplate panel

### WL-7. Saving Challenges (Goals tipo "spend less than X") ⭐ medio (Hope alignment) — data + streak + chip ✅, notification deferida
- [x] Migration [add_watchlist_id_to_budget_targets_table](database/migrations/2026_05_04_120100_add_watchlist_id_to_budget_targets_table.php) + indexed FK
- [x] Migration [extend_budget_targets_target_type_enum](database/migrations/2026_05_04_120200_extend_budget_targets_target_type_enum.php) para incluir `challenge_under_amount` (MySQL ENUM silently coerced previously)
- [x] `BudgetTarget::TYPE_CHALLENGE_UNDER_AMOUNT` constante + `watchlist_id` en fillable + `watchlist()` belongsTo relation
- [x] `BudgetTarget::streakInMonths(?Carbon $endDate, int $maxLookback = 24)` — para target tipo challenge tied a watchlist, cuenta meses consecutivos terminando en endDate (default ahora) donde el monthly total se mantuvo `< amount`. 0 cuando current month rompió, 0 cuando no es challenge target
- [x] 4 tests en [WatchlistChallengeStreakTest](tests/Feature/Finance/WatchlistChallengeStreakTest.php): non-challenge → 0, current month rompió → 0, 3-month streak con seed real, 24 cuando no hay txs
- [x] **UI streak chip** en [WatchlistCard.vue](resources/js/domains/watchlist/components/WatchlistCard.vue) — [WatchlistService::list](Modules/Watchlist/Services/WatchlistService.php) hidrata `streak_months` por watchlist en una sola query (BudgetTarget::whereIn(watchlist_id) keyBy). Card muestra chip "🔥 N" en el header cuando `streak_months > 0` con tooltip "N months under target"
- [ ] **Deferido**: notification on streak completion/break (similar a WL-1, console command nuevo)

### WL-8. Auto-suggestions (descubrimiento) ⭐ medio (engagement) ✅
- [x] Service [WatchlistAutoSuggestService::getTopUntrackedPayees](Modules/Watchlist/Services/WatchlistAutoSuggestService.php) — detecta top-N payees del mes (default 3) excluyendo los que ya están en algún watchlist tipo `payees`. SQL groupBy + orderByDesc(SUM total)
- [x] Notification [WatchlistAutoSuggestionAlert](app/Notifications/WatchlistAutoSuggestionAlert.php) — payload con `suggestions[]`, `month`, deep-link a `/finance/watchlist?suggest={ids}` para pre-llenar el modal
- [x] Console command [SuggestUntrackedPayees](app/Console/Commands/SuggestUntrackedPayees.php) — itera teams, dispatch notification al owner, dedupes por mes via DatabaseNotification query
- [x] Scheduled `weekly()` en [Console/Kernel.php](app/Console/Kernel.php)
- [x] 3 tests en [WatchlistAutoSuggestTest](tests/Feature/Finance/WatchlistAutoSuggestTest.php): top by spend, excludes already-tracked, respects limit
- [x] **Frontend deep-link** ✅ — [Watchlist.vue](resources/js/Pages/Finance/Watchlist.vue) lee `?suggest=ids` en `onMounted`, parsea IDs, abre el WatchlistModal con `type=payees`, `input=[ids]`, `name="Untracked spending"` pre-llenos. El usuario solo escoge target y submit. Cierra el loop email → click → 2-tap-to-track

### WL-MARKETING. Engagement / shareable (parcial) — sub-bullets 2 y 3 son **v2.x marketing**, no v2.0
- [x] **Sub-bullet 1**: Public share token. Migration [add_share_token_to_watchlists_table](Modules/Watchlist/Database/Migrations/2026_05_04_120300_add_share_token_to_watchlists_table.php) — nullable unique varchar(64). `Watchlist::ensureShareToken()` (idempotente, Str::random(32)) + `revokeShareToken()`. Nuevo [SharedWatchlistController](Modules/Watchlist/Http/Controllers/SharedWatchlistController.php) con `store`/`destroy` (auth, ownership check) y `show($token)` (no-auth). Public route `GET /share/watchlist/{token}` renderiza Blade view standalone [shared-watchlist.blade.php](resources/views/shared-watchlist.blade.php) — Tailwind CDN, monthly total + target + ratio + variance + 12-month bar chart, no transactions ni payee names exposed (privacy). Dropdown en WatchlistShow gana "Enable public link" / "Copy share link" / "Disable public link" con copy-to-clipboard. 7 tests en [WatchlistShareTokenTest](tests/Feature/Finance/WatchlistShareTokenTest.php): enable/idempotent/revoke/non-owner forbidden/public show ok/404 invalid/404 revoked
- [ ] **Sub-bullet 2 (deferido)**: Free landing tool "Spending Watchlist Calculator" — public no-login page que toma categorías y proyecta threshold. Scope: nueva ruta pública + Vue page con cálculo client-side, SEO meta tags
- [ ] **Sub-bullet 3 (deferido)**: Email mensual con resumen ("este mes gastaste 30% más en delivery vs el promedio"). Scope: Mailable class + console command monthly + opt-out + variance text generator

---

## 🍽️ Food — explotación

Mismo principio que Watchlist: las primitivas existen (`MealMenu.is_template` + `duplicate(targetStartDate)`, `Meal.is_liked`, `Ingredient → Insane\Journal\Models\Product\Product` con pricing) pero no se descubren desde la UI. Detalle del pilar en [.planning/family-os-structure.md](.planning/family-os-structure.md).

### FD-1. Favorite meals surface ✅ (shipped 2026-05-06)
**Por qué:** `is_liked` ya existe en `Meal` y `Planner` — sin UI antes. Engagement del meal planner.

- [x] Toggle ❤️ en grid card [MealItemCard.vue](resources/js/domains/meal/components/MealItemCard.vue) — botón flotante top-left con `fas/far fa-heart`. Pattern existente en [MealItem.vue](resources/js/domains/meal/components/MealItem.vue) (list view) reusable
- [x] Tab/filtro "Favorites" en `/meals` — `StatusButtons` con `?filter[is_liked]=1` ya existía en [Meals/Index.vue](resources/js/Pages/Meals/Index.vue)
- [x] Bias del random meal generator hacia favorites cuando existen — ya en place
- [x] Heart en detalle de receta ([Meals/View.vue](resources/js/Pages/Meals/View.vue)) — `onToggleLike()` handler + `@toggle-like` event wired ✅

### FD-2. Reusable menu template gallery ✅ (shipped 2026-05-10)
**Por qué:** `MealMenu::is_template` + `duplicate(targetStartDate)` (auto-shifts dates) ya funcionan. Galería + load CTA + save as template — todo wired.

- [x] Página "Template menus" ([Meals/Templates.vue](resources/js/Pages/Meals/Templates.vue)) listando `MealMenu::where('is_template', true)` con count
- [x] CTA "Use this template" en [MealMenuCard.vue](resources/js/domains/meal/components/MealMenuCard.vue) → date picker → POST `meals.menus.load` que duplica planes con offset correcto
- [x] "Save as Menu" en Planner guarda con `is_template=true` via [MealMenuController::store](app/Http/Controllers/Meal/MealMenuController.php)
- [x] Feature test: load template crea `MealPlan` rows con fechas corridas, foreign team 403, empty menu warning — 6 tests en [MenuTemplatesPageTest](tests/Feature/Meal/MenuTemplatesPageTest.php)

### FD-SHOP-1. Shared Shopping List (chat-style + Mercure live sync) ✅ MVP shipped (2026-05-07)
**Por qué:** Workflow real de Jesus + esposa: una lista persiste semana a semana, ella marca ✓/✗ en el supermercado, él ve cambios live mientras compra. Reusa Plan module (PlanType `SHOPPING_LIST` + `share_token`) en lugar de construir un módulo paralelo. v2.5 keystone marketing-worthy.

- [x] Migration [add_state_to_plan_items_table](database/migrations/2026_05_07_051915_add_state_to_plan_items_table.php) — `state` enum (pending/buy/skip) con backfill `is_done=true → buy`
- [x] [PlanItem](Modules/Plan/Entities/PlanItem.php) — `STATE_*` constantes, `cycleState()`, `setState()`, two-way sync entre `is_done` y `state` para no romper kanban legacy callers
- [x] Mercure event [ShoppingListItemUpdated](app/Events/ShoppingListItemUpdated.php) — `ShouldBroadcastNow` keyed por share_token. Subscribers en `/shared/list/{token}` reciben push real-time vía EventSource
- [x] [ShoppingListController](app/Http/Controllers/ShoppingListController.php) authed — index (auto-creates SHOPPING_LIST plan if missing), cycleItem, addItem, destroyItem, reset, toggleShare. `team_id` guard en cada mutación
- [x] [SharedShoppingListController](app/Http/Controllers/Meal/SharedShoppingListController.php) refactor — Inertia chat-style page (era Blade), 3-state toggle, addItem mid-trip para que el spouse meta items olvidados, resetTrip
- [x] Vue [ShoppingChatList.vue](resources/js/domains/shopping/components/ShoppingChatList.vue) — single component reusado por authed `/shopping` y public `/shared/list/{token}`. Chat-style mobile-first: sticky bottom input WhatsApp-style, tap-to-cycle, optimistic updates, EventSource subscription para sync live, `pb-safe` para iOS notch
- [x] Sidebar nav `/shopping` con `fa-cart-shopping` icon
- [x] 10 feature tests en [ShoppingListChatTest](tests/Feature/System/ShoppingListChatTest.php): 3-state cycling, sync `state↔is_done`, add, reset, destroy, broadcast dispatched, foreign team forbidden, toggleShare mint/revoke, public no-auth flow, 404 invalid token
- [x] phpunit.xml: `BROADCAST_DRIVER=null` para tests no toquen el hub real

**Deferido (v2.5+ polish):**
- [ ] **Web push notifications** — VAPID keys + service worker + subscribe endpoint. Cuando spouse marca ✓ y app no está abierta, ping al phone del otro. Mercure cubre el caso "ambos miran la app"; web push cubre "uno está fuera"
- [ ] **Sections / aisles auto-grouping** — `aisle` column en plan_items, agrupado por sección en el UI para route-the-supermarket. Auto-categorizable con lookup table o LLM call
- [ ] **"Last week you bought X" suggestions** — query de items que estaban en `buy` el reset anterior, surface en top del trip nuevo como "quick add" chips
- [ ] **Multi-list support** — hoy cada team tiene UN solo SHOPPING_LIST. Profile-scoped lists (athlete, school, family) requieren `profile_id` en plans + selector UI. Plan module ya tiene PlanType.team_id+name, así que es agregar UI sin migration mayor
- [ ] **Voice input** — el OS keyboard del input ya tiene mic; cero código. Documentar al usuario
- [ ] **Receipt photo OCR** — match against list, auto-check ✓ lo que se compró. Big feature por sí mismo, no MVP
- [ ] **Quantity column** — actualmente la convención es freeform en `title` ("2 Confle"). Si se quiere campo aparte, agregar `quantity` a plan_items + parser

---

### FD-3. Cost-per-recipe rollup ⭐ medio (v2.x marketing angle)
**Por qué:** El link `Ingredient → Product` ya tiene precio. Sumar `ingredient × product.price` rinde costo por receta y por menú semanal — encaja como calculadora pública en v2.x (ver `marketing-tools-and-shareable`).

- [ ] Helper `Meal::estimatedCost()` (sum `ingredients.quantity × product.price`)
- [ ] Helper `MealMenu::estimatedWeekCost()`
- [ ] Mostrar en MealCard + planner header
- [ ] Unit tests cubren múltiples ingredientes, missing prices (defaults)

---

## 🏡 Household — explotación

Igual: primitivas (`Occurrence`, `Planner`, `BillingCycle`, `Equipment`) existen y no están wired. Detalle en [.planning/family-os-structure.md](.planning/family-os-structure.md).

### HM-1. Recurring utilities via Occurrence + BillingCycle ⭐⭐ alto (primitives ready) — backend ✅, UI deferida
**Por qué:** `Occurrence` ya hace recordatorios recurrentes con avg-duration. `BillingCycle` ya modela ciclos de pago. Falta wirelo: "agua/luz/internet vencen los días X" + alerta cuando se acerca. Reusa el motor de notificaciones de WL-1.

- [x] `Occurrence::TYPE_UTILITY` constante + `scopeOfType($type)` en [Occurrence.php](app/Domains/Housing/Models/Occurrence.php)
- [x] Helper `Occurrence::daysUntilNext(): ?int` — calcula `last_date + avg_days_passed - now`. Negativo cuando overdue. Null cuando primera vez. Power users marcan una occurrence como `type='utility'` y aparece en TODAY-1's UPCOMING widget
- [x] **Wire into TODAY-1 UPCOMING widget** ([TodayService::upcoming](app/Domains/Today/Services/TodayService.php)) — merge BillingCycle + Occurrence-utility en un solo array tagged con `kind`. Ordena por `days_until` asc. Overdue se mantiene visible (no se oculta cuando `days_until < 0`) hasta que el usuario logea el siguiente pago. UI distingue por icon (credit-card vs bolt) y color (warning vs secondary)
- [x] 3 tests adicionales en [TodayPageTest](tests/Feature/System/TodayPageTest.php): includes utility within window + excludes inactive, keeps overdue visible, excludes non-utility occurrences
- [ ] **Deferido**: UI en Housing para crear/listar utilities (página dedicada `/housing/utilities` con form). Por ahora se crean via tinker o se manejan manualmente. Notification N días antes (puede reusar el patrón de `CheckWatchlistThresholds` con un nuevo command `housing:check-utilities`)
- [ ] **Deferido**: monto promedio (de últimos N pagos) en la UI — requiere link Occurrence ↔ Transaction history

**Files:** [app/Domains/Housing/Models/Occurrence.php](app/Domains/Housing/Models/Occurrence.php), [app/Domains/Today/Services/TodayService.php](app/Domains/Today/Services/TodayService.php), [resources/js/Pages/Today/Index.vue](resources/js/Pages/Today/Index.vue)

---

## 👨‍👩‍👧 Family — quick win

El grueso del pilar Family es v2.5 (ver roadmap). Hay un quick win cuya primitiva ya existe.

### FM-1. One-on-one time reminders ⭐ medio (primitive ready)
**Por qué:** `Occurrence` ya trackea "última vez que X pasó" con duración promedio. Aplicar a "última date con la pareja" → reminder cuando pasa el threshold. Patrón idéntico al de chores/maintenance, sin código nuevo de motor.

- [ ] Permitir `Occurrence` tipo "relationship" tied a un `LogerProfileEntity` (miembro)
- [ ] UI mínima en `/relationships` (hoy "Coming soon" placeholder) con lista de relationships activos + último gap
- [ ] Notification al pasar el avg gap
- [ ] Feature test del threshold + dedupe

**Files:** [app/Domains/Housing/Models/Occurrence.php](app/Domains/Housing/Models/Occurrence.php), [app/Domains/LogerProfile](app/Domains/LogerProfile), [resources/js/Pages/Relationships/Index.vue](resources/js/Pages/Relationships/Index.vue)

---

## House Buyer Planner — follow-ups (**v2.5**, no v2.0)

> Reasoning: lo construido es preview frontend-only. Persistir escenarios, agregar "Convertir en Goal", jalar ahorro real de txs y modo préstamo genérico es **construir el módulo desde cero**, no pulir un primitivo existente. Cae en orden #4 (módulos nuevos / engagement).

Página implementada en [resources/js/Pages/Finance/Planners/HouseBuyer.vue](resources/js/Pages/Finance/Planners/HouseBuyer.vue), accesible vía `/finance/planners/house-buyer`. Lo construido es preview frontend-only.

- [ ] Persistir escenarios guardados (modelo + migration + controller)
- [ ] Botón "Convertir en Goal" → crea saving goal del inicial en Finance/Goals
- [ ] Jalar el ahorro mensual real desde transacciones (vs input manual hoy)
- [ ] Modo "Préstamo genérico" — generalizar para cubrir construcción y otros préstamos

---

## 🎯 TODAY-1. `/today` Command Center — **v0.1 ✅ HECHO, v0.2+ pending**

> 💡 **Dogfooding-driven.** Jesus es primary user — la cohesión "feels like a system, not separate tools" es el valor #1, no la completitud de feeders. Por eso **v0.1 cierra v2.0 stable**: hace que las features que ya existen aterricen en una superficie diaria visible en vez de páginas separadas. Cada feeder nuevo (FD-*, HM-*, FM-*, Couples backend, Calendar) layeredea después como widget adicional en v2.5 — no bloquean el ship de v0.1.

Pantalla nueva `/today` — convergencia de Finance widgets, Watchlist alerts, planner items, billing cycles. Reframe del "unified weekly schedule view" del Calendar pillar ([.planning/family-os-structure.md](.planning/family-os-structure.md)) como **daily-first**.

### Today v0.1 — ✅ HECHO (shipped 2026-05-05)

- [x] **MONEY widget**: gasto de hoy via `TransactionService::getExpensesTotal` (range = today) + month assigned/spent/remaining via `BudgetMonth::getMonthAssignmentTotal`. Progress bar coloreada (green ≤70%, amber ≤100%, red >100% o overspent). Quick-add expense button abre el TransactionModal con `mode=WITHDRAW`
- [x] **ATTENTION widget**: pulla `DatabaseNotification` con `type = WatchlistThresholdAlert`, `read_at IS NULL`, `data->month = current month`. Cap a 5. Click navega al `link` del payload. Empty state: ✅ "Nothing crossing thresholds"
- [x] **TODAY widget**: `Planner` records con `date = today` y `completed_at IS NULL`. Cap a 10. Render genérico ya que cubre cualquier `dateable_type`. Empty state: 🎯 "No scheduled items today" + copy "reminders, planned txs, chores show up here"
- [x] **UPCOMING widget (bills)**: `BillingCycle::whereBetween(due_at, [today, today+7])` excluye `STATUS_PAID`. Cap a 10. Click navega a `/finance/accounts/{id}`. Empty state: 📅 "No bills due this week"
- [x] Service [TodayService](app/Domains/Today/Services/TodayService.php) agrega los 4 widgets en `buildPayload(teamId, userId)`
- [x] Controller [TodayController](app/Http/Controllers/System/TodayController.php) + ruta `GET /today`
- [x] Vue page [Today/Index.vue](resources/js/Pages/Today/Index.vue) — header con fecha + "Add expense" CTA, grid 2-col responsive con 4 widgets. Empty states honestos en cada uno
- [x] Side nav: Today va PRIMERO (antes de Dashboard) en [ShowInApp listener](app/Listeners/Menu/ShowInApp.php) — discovery
- [x] **Decisión confirmada**: Today coexiste con Dashboard (glance vs deep view), NO reemplaza
- [x] **Action vs state differentiation** ✅ (2026-05-05) — MONEY widget trimmed: removed `month_assigned/spent/remaining` + progress bar. Now just `today_spent` + "Log an expense" CTA. Today asks "did you log it?", Dashboard asks "where am I in the month?". Same primitives, different question — eliminates perceived overlap
- [x] 6 feature tests en [TodayPageTest](tests/Feature/System/TodayPageTest.php): payload shape, empty state, money con tx hoy, attention surface unread + skip read, today filtra completed/future, upcoming filtra >7d
- [x] Toggle "Set Today as landing page" ✅ — `PATCH /settings/landing-page` persists setting, custom `LoginResponse` + `RedirectIfAuthenticated` + `/` route read it. Toggle button in Today header shows current state. 4 tests passing

### Today v0.2+ — feeders layereados conforme se construyen

Cada feeder agrega/enriquece un widget existente, no bloquea la entrega de v0.1:

- [x] **HM-1 utilities → UPCOMING** ✅ (2026-05-05) — UPCOMING muestra credit cards + utilities en una sola lista ordenada por proximidad. Overdue stays visible
- [x] **FM-1 relationships → TODAY** ✅ (2026-05-05) — TODAY widget ahora incluye relationships con `daysUntilNext() <= 0` (overdue + due today). Tagged como `kind: 'relationship'`, heart icon rojo + "X days overdue" en text-error. Header del widget gana link a `/relationships`. FM-1 también shipped UI standalone en `/relationships` (lista + add modal + "Just saw them" — 7 tests passing)
- [x] **FD-1 favorites → MEAL HOY** ✅ (2026-05-05) — Nuevo 5to widget MEAL HOY. Pulla `MealService::getMealSchedule(teamId)` (Planner records con `dateable_type=MealPlan` para hoy). Renderiza name + meal_type + heart si is_liked. Empty state con link a `/meal-planner`. Random meal generator (`/meals-random`) ahora hace bias hacia favorites cuando existen
- [x] Calendar pillar ✅ — `/calendar` page with month grid, Planner CRUD, BillingCycle + Occurrence events. 10 tests passing. Nav entry added between Today and Dashboard
- [ ] Couples backend → per-member spending como widget
- [x] FD-2 → "This week's menu" widget ✅ — meal widget widened from today-only to Mon→Sun, grouped by day label in UI

### UX requirements (todas las versiones)

- [x] Una mirada = entender el día — MONEY widget now shows today_spent + daily_remaining, all 5 widgets in grid
- [x] Un click = log expense (modal o quick-add inline) — "Log an expense" button opens TransactionModal
- [x] Un scroll = ver semana — meal widget shows full week grouped by day, UPCOMING shows 30-day window

### Pre-work (antes de empezar v0.1)

- [x] Quick-add expense reusable ✅ — `useTransactionModal()` wired in Today/Index.vue "Log an expense" CTA
- [x] API `daily_remaining` ✅ — `(month_budgeted - month_spent) / days_left` via BudgetMonth join, excludes savings targets. Shows in MONEY widget as "{amount}/day remaining (Xd left)"

### Behavior layer (v2.x paralelo, alimenta el contenido de Today)

- [ ] Daily/weekly digest emails con los mismos payloads que renderiza Today (ver roadmap v2.x — "Behavior layer / digests"). El payload se diseña una vez y sirve a Today + email.

---

## Couples / Relationships backend — **DIFERIDO (después de TODAY-1)**

> ⚠️ Va **después de TODAY-1**. Couples es un use case (per-member tracking); Today es la capa que lo hace visible diariamente. Sin Today, Couples backend aterriza en una página enterrada en nav que la gente abre una vez. Adicionalmente: varios use cases del módulo Couples (per-member spending) se pueden implementar como watchlists per-miembro reusando esa primitiva — antes de duplicar lógica de saved filters, agotemos esa exploración.

Página visual ya pulida en [resources/js/Pages/Trends/Relationships.vue](resources/js/Pages/Trends/Relationships.vue) pero es **mock**. El plan de implementación está en [.planning/features/couple-support.md](.planning/features/couple-support.md) — la mayor parte del scaffolding ya existe (`LogerProfile` model, polymorphic pivot, service `getTransactionsByProfileId`, rutas, controllers).

- [ ] **Antes de empezar:** revisar si watchlists per-member (con `LogerProfileEntity` apuntando a `Watchlist`) cubre 70% del use case
- [ ] Construir agregador per-member sobre transacciones del team
- [ ] Wire "Proportional view" cuando hay datos de ingreso por miembro
- [ ] Reemplazar mocks de `members` y `categoryRows` por datos reales del backend
- [ ] Wire como widget feeder de `/today` cuando ambos existan (per-member spending hoy)

---

## 🔴 Lo Malo — deuda técnica (orden de impacto)

### LM-5. Movimientos no son deshacibles — backend ✅, UI deferida
- [x] `BudgetMovementService::revertMovement(BudgetMovement $movement)` que invierte `updateBalances` y reruns rollover (transaccional)
- [x] Endpoint `DELETE /budget-movements/{movement}` con auth check (`allTeams` ownership)
- [x] 3 feature tests en [BudgetMovementUndoTest](tests/Feature/Finance/BudgetMovementUndoTest.php): revert restaura balances + borra fila, foreign user 403, unauth redirect
- [ ] **Deferido (UI)**: toast "Asignación guardada — Deshacer" tras cada acción (estilo Gmail), o dropdown con últimos 5 movimientos por categoría. Backend listo — UI puede consumirlo via DELETE cuando se decida el patrón

---

## ✨ Lo Mejorable — UX (orden de impacto)

### LMJ-4. Inline editing en mobile — quick win ✅ (full bottom-sheet deferida)
**Por qué:** En mobile, click sobre el input abría el side panel encima del input, peleando con el teclado iOS/Android. Fix de propagación + click handler condicional resuelve la fricción diaria.

- [x] `@click.stop` en el input container de [BudgetItem.vue](resources/js/domains/budget/components/BudgetItem.vue) — clicks en el input no burbujean al row
- [x] `onRowClick` condicional: en mobile (`useAppContextStore().isMobile`) toggleEditing en lugar de emit('edit'); en desktop mantiene el patrón row→side-panel
- [ ] **Deferido**: bottom-sheet style nativo (input crece desde abajo) — el fix actual elimina la pelea con el teclado al no abrir modal en mobile, así que es lower priority

### LMJ-6. Notas por categoría ✅
**Por qué:** Memoria contextual. "Por qué presupuesté esto", "ojo: anual cae en abril".

- [x] Usa columna `description` existente en `categories` (no migration needed)
- [x] Textarea en `BudgetDetailForm.vue` — editable con blur-save
- [x] Icono indicador en `BudgetItemHeader.vue` con tooltip nativo

### LMJ-7. Vista "Goals tracker" agregada ✅
**Por qué:** Hoy las metas viven sueltas dentro de cada categoría. Una página dedicada con timeline + ETAs es lo que pide el módulo de metas de Hope.

- [x] Ruta `GET /finance/goals` → controller → `Finance/Goals/Index.vue`
- [x] Lista TODAS las `BudgetTarget` activas con barra de progreso, fecha objetivo, días restantes, ETA proyectada
- [x] Filtros: monthly / dated / overdue
- [x] **Limpiar sistema huérfano de `goals`:** existe una tabla `goals` (migration `2022_03_28_121030_create_goals_table.php`) y componentes Vue [Pages/Finance/Goal.vue](resources/js/Pages/Finance/Goal.vue) + [GoalForm.vue](resources/js/Pages/Finance/GoalForm.vue) sin controller, routes ni queries. Recomendado: BORRAR — el modelo correcto es `BudgetTarget`

### LMJ-8. Drag categorías entre grupos ✅
**Por qué:** Reorganización fluida. Hoy `Draggable` sólo reordena dentro del mismo grupo.

- [x] Cross-group drop via `group="budget-categories"` en inner Draggable
- [x] Endpoint `PATCH /budgets/{category}/move-to-group/{group}` en `BudgetCategoryController`
- [x] `@change` handler actualiza `parent_id` + reordena índices

### LMJ-10. Reglas de auto-asignación de income ⭐ alto-impacto / mucho esfuerzo
**Por qué:** "Cada vez que entre un sueldo, asigna 20% a Ahorro, 30% a Renta..." — feature de "set-and-forget" que YNAB no tiene. Liga superior.

- [ ] Modelo nuevo `IncomeRule` (team_id, percent_or_amount, target_category_id, source_account_id?)
- [ ] Job/observer en `Transaction::created` que detecta inflows y aplica reglas activas
- [ ] UI en `/finance/automation-rules` para CRUD
- [ ] **1-2 días, dejarlo último.**

---

## Verificaciones manuales pendientes (solo QA, no código)

- [x] **LM-7** Visitar `/finance/watchlist` → no error, lista renderiza
- [ ] **LM-1** Mover/asignar dinero refresca balances correctamente sin refresh manual
- [ ] **LM-3** Enviar payload inválido → confirmar que el banner aparece con el mensaje del server
- [ ] **LM-4** Visualmente comparar balances en una página real (cubierto por regression test, but worth a browser check)
- [ ] **#4** Mover dinero entre dos categorías → balances reflejan estado server-recomputed sin refresh manual
- [ ] **#8** RTA overspent (-$50) → en Fix this dropdown opción RTA muestra `-$50` rojo, group headers e item labels legibles
- [ ] **#9** Idioma → Save → XHR fire → reload → setting persiste
- [ ] **#10** Re-correr test plan del split con assertions estrictas (queriar `budget_movements` directamente o `categories[].budgeted` no `.budget`)

---

## Reconciliation UX — manual QA pendiente

Navegar a `/finance/accounts/28/reconciliations` y verificar:

- [ ] Stats row muestra balances correctos
- [ ] Timeline nodes son clickables y navegan al detalle
- [ ] Hover muestra delete en pending nodes
- [ ] "Reconcile new transactions" abre el form modal
- [ ] Unreconciled transactions preview se muestra debajo del timeline
- [ ] Pending reconciliation banner aparece cuando aplica
- [ ] Pluralization correcta ("0 transactions", "1 transaction", "5 transactions")

---

## Spec deferrals (Credit Card Report)

Tres ítems marcados deferred — tienen costo no trivial, dejados para después:

- [ ] **Category filter wiring** en `getTopCategoriesByCreditCard` — requiere SQL changes
- [ ] **Apply usage thresholds a per-card utilization bars** — deferido a Task 3.1
- [ ] **Event-based cache invalidation** en `TransactionSaved` / `BillingCycle` writes — TTL es aceptable; event wiring tocaría múltiples domain events

---

## Sellability polish manual ([.kiro/tasks.md](.kiro/tasks.md))

- [ ] **Screenshot fresco del dashboard** → reemplazar `public/images/full-sized-dashboard.png`
- [ ] **Empty state del meal Planner.vue** → review (item 6 de Quick Wins quedó pendiente)

---

## Out of scope (deferred)

- Rollover que descuenta obligaciones programadas del RTA — refactor grande
- Per-person attribution para módulo de pareja — ver "Couples backend" arriba (scope separado pero relacionado)

---

## Mapeo de IDs anteriores

- Bug #5 (backend reversibility) → mismo scope que **LM-5**
- Bug #7 (mobile modal tapa input) → mismo scope que **LMJ-4**
- Bug #6 (general fix pass) → mismo root cause que #4, ya resuelto
