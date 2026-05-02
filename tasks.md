# Tasks — Savings Flow (Hope's request #2)

**Source:** [.claude/worktrees/vigorous-wilson-597930/friend-requirements.md](.claude/worktrees/vigorous-wilson-597930/friend-requirements.md) — Módulo de Ahorro
**Goal:** After paycheck + scheduled bills, let the user split the leftover into Ahorro / Gasto Personal in one action.

---

## 1. Seed default categories — `Ahorro` and `Gasto Personal` ✅

- [x] Add `Savings` group with `Ahorro` child to `config/journal.php`
- [x] Add `Personal` group with `Gasto Personal` child to `config/journal.php`
- [x] Verify `TransactionCategoriesCreate::create` picks them up on team creation
- [x] Test: a freshly registered user has both categories available

**Files:** `config/journal.php`, `app/Domains/Journal/Actions/TransactionCategoriesCreate.php`

---

## 2. Surface "After scheduled payments" on the Budget page ✅

- [x] Inject `NextPaymentsService::getNextPayments()` into `BudgetCategoryController::index`
- [x] Compute `availableAfterScheduled = readyToAssign - sum(nextPayments due this month)`
- [x] Pass as Inertia prop to `Finance/Budget.vue`
- [x] Render as a second pill in `BudgetBalanceAssign.vue` (or alongside it)
- [x] Translate label: "Después de pagos" / "After scheduled payments"

**Files:** `app/Domains/Budget/Http/Controllers/BudgetCategoryController.php`, `resources/js/Pages/Finance/Budget.vue`, `resources/js/domains/budget/components/BudgetBalanceAssign.vue`

---

## 3. One-shot split UI — Ahorro vs Gasto Personal ✅

- [x] `BudgetMovementService::registerSplit($sourceId, array $splits)` that loops `registerMovement` in a transaction
- [x] New endpoint `POST /budgets/{id}/months/{month}/split` (or extend the existing assign endpoint)
- [x] Popover/modal in `BudgetBalanceAssign.vue` with two amount inputs (or % sliders) for Ahorro and Gasto Personal
- [x] Validation: total of splits ≤ ready-to-assign
- [x] Feature test: split RTA into 2 destinations leaves the correct balances on each

**Files:** `app/Domains/Budget/Services/BudgetMovementService.php`, `app/Domains/Budget/Http/Controllers/BudgetMovementController.php` (or similar), `resources/js/domains/budget/components/BudgetBalanceAssign.vue`

---

# Tasks — Zero-Based Budget Bugs (legacy)

**Context:** ZBB module is ~6 years old. User flagged 5 long-standing issues. After diagnosis, 5 reports collapse into 3 distinct fixes (#4 / #6 share root cause; #5 and #7 stand alone; #8 is independent).

## 4. Stale balances after moving money between categories (covers original #1 + #3) ✅

**Root cause:** [useBudget.ts:280](resources/js/domains/budget/useBudget.ts#L280) — `moveBudget` posts via plain `axios.post(...)` (NOT Inertia router), so no props are re-fetched after the server runs the rollover. The optimistic local update at [useBudget.ts:222-227](resources/js/domains/budget/useBudget.ts#L222-L227) only mutates `budgeted` on source/destination — never touches `activity`, `available`, `left_from_last_month`, or RTA totals.

- [x] After `axios.post(...)` resolves, call `router.reload({ only: ['budgets'], preserveScroll: true })`. Applied to both `moveBudget` and `assignBudget` (which had the same pattern).
- [ ] Manual verification: move money between two categories and confirm balances reflect server-recomputed state without manual refresh.

**Files changed:** [resources/js/domains/budget/useBudget.ts](resources/js/domains/budget/useBudget.ts)

**Known follow-up:** the `axios.post(...)` calls have no `.catch()` — if the request fails, the optimistic update sticks. Separate issue, not in scope here.

## 5. Backend: every money movement should be undoable

- [ ] Add `BudgetMovementService::revertMovement(BudgetMovement $movement)` that reverses the two `updateBalances` calls atomically (swap signs) + re-runs `BudgetRolloverService->startFrom`
- [ ] New endpoint `DELETE /budget-movements/{movement}` calling the service
- [ ] UI: list of recent movements per month with an undo affordance (or undo button on the just-completed movement)
- [ ] Feature test: create a movement, revert it, assert source/destination balances and RTA return to pre-movement state

**Files:** `app/Domains/Budget/Services/BudgetMovementService.php`, new controller action, new Vue component for movement history/undo

## 6. ~~General fix pass: money movement between categories~~ → SAME AS #4

**Same root cause as #4.** Resolved by the same fix.

## 7. Mobile: assign-money modal hides the input

- [ ] On mobile, tapping a category to assign money opens a "details" modal that covers the input field
- [ ] Likely `BudgetMoneyLine` / `BudgetDetailForm` opens unconditionally; needs to gate the details modal on `!isSmaller('md')`, leaving the inline input visible on mobile so the keyboard doesn't fight the modal

**Files:** `resources/js/domains/budget/components/BudgetMoneyLine.vue`, `resources/js/domains/budget/components/BudgetDetailForm.vue`, `resources/js/Pages/Finance/Budget.vue` (already uses `useBreakpoints`)

## 8. RTA `available` mangled in the "fix this" dropdown ⚠️ re-fixed

**Regression discovered (2026-05-02):** my earlier fix made `parseAvailable` pass through the server's raw `BudgetMonth.available` for RTA — but that's the GROSS value (`inflow + leftover`, before assignments), NOT the same balance shown on the main card. With $1,050 inflow and $1,100 assigned, main card showed `-$50` but dropdown showed `$1,050`. Also the option label `<span class="">` had no text color and was nearly invisible.

**Re-fix (2026-05-02):**
- [x] Override `available` for RTA at the `categoryOptions` level using `props.toAssign.balance` (same value the main card uses) — bypasses parseAvailable entirely for RTA in the dropdown
- [x] Show negative values in red (`text-error`), positive in green (`text-success`); hide only when exactly 0
- [x] **Same bug existed in [BalanceInput.vue](resources/js/Components/atoms/BalanceInput.vue) (per-category move popover, the "Search Category" multiselect).** Fixed by injecting `readyToAssign` from Budget.vue and using `.balance` for the RTA option. Side effect: in "overspent" mode the existing filter `category.available > 0` will now exclude RTA from the source dropdown when its balance is ≤ 0 (correct UX — can't take from negative).
- [x] **2nd round visibility fix:** `text-body-1` resolves to `slate[200]` in `defaultDark` theme — invisible on Naive UI's white popover background. Switched to theme-independent `text-gray-800` for items, `text-gray-500 font-bold` for group labels (Inflow/Savings/Personal). Applied to both BudgetBalanceAssign and BalanceInput dropdowns.
- [ ] Manual verification: with RTA overspent (-$50), open Fix this → RTA option shows `-$50` in red, matching main card. Group headers and item labels readable.

**Files changed:** [BudgetBalanceAssign.vue](resources/js/domains/budget/components/BudgetBalanceAssign.vue), [BalanceInput.vue](resources/js/Components/atoms/BalanceInput.vue), [Budget.vue](resources/js/Pages/Finance/Budget.vue)

## 14. Configurable split targets (option B) ✅

**Why:** Original implementation gated the "Asignar sobrante" button on hardcoded category names (`Ahorro`, `Gasto Personal`). Existing teams (Jesus's prod team) didn't have those names, so the button never appeared. Refactored to a flag-based system the user controls.

**Architecture decision:** instead of adding `default_role` to `categories` (which lives in the journal accounting package — generic and reused across apps), created a pivot table in the Budget domain. Keeps journal pristine; budget concern stays in budget land.

**Applied:**
- [x] New migration `2026_05_02_050330_create_budget_default_categories_table` — pivot `(team_id, role, category_id)` with `unique(team_id, role)` and `unique(team_id, category_id)`
- [x] New model [BudgetDefaultCategory](app/Domains/Budget/Models/BudgetDefaultCategory.php) with `ROLE_SAVINGS`, `ROLE_SPENDING` constants
- [x] Service method `BudgetCategoryService::setDefaultRole()` does atomic swap inside a transaction (deletes any existing row for the same `(team, role)` before inserting)
- [x] Endpoint `PATCH /budgets/{category}/default-role` body `{role: 'savings'|'spending'|null}`
- [x] Budget index now passes `budgetDefaults` Inertia prop: `{savings: catId, spending: catId}`
- [x] [BudgetBalanceAssign.vue](resources/js/domains/budget/components/BudgetBalanceAssign.vue) `splitTargets` now reads from `budgetDefaults` prop, not category names. Popover labels show whichever name the user picked.
- [x] [BudgetItem.vue](resources/js/domains/budget/components/BudgetItem.vue) 3-dot menu has new options: "Use as default Savings target" / "Use as default Spending target" / "Remove default role" (only visible based on current state)
- [x] `CreateTeamSettings::setBudgetSplitDefaults()` auto-marks the seeded `Ahorro` (display_id=`savings_general`) and `Gasto Personal` (display_id=`personal_spending`) categories — new users have it pre-wired, no UI step needed
- [x] 6 feature tests in [BudgetDefaultCategoryTest](tests/Feature/Finance/BudgetDefaultCategoryTest.php): seed sets defaults, user can set role, atomic swap, clear, validation, Inertia prop exposed
- [x] Existing tests still pass (BudgetSplitTest, BudgetTest, DefaultCategoriesTest)

**For Jesus's existing prod team:** open `/budgets`, click 3-dot on a category, "Use as default Savings target". Repeat for spending. Now "Asignar sobrante" appears with those two as the targets.

**Files changed:** [BudgetDefaultCategory.php](app/Domains/Budget/Models/BudgetDefaultCategory.php) (new), [BudgetCategoryService.php](app/Domains/Budget/Services/BudgetCategoryService.php), [BudgetCategoryController.php](app/Domains/Budget/Http/Controllers/BudgetCategoryController.php), [routes.php](app/Domains/Budget/routes.php), [CreateTeamSettings.php](app/Listeners/CreateTeamSettings.php), [BudgetBalanceAssign.vue](resources/js/domains/budget/components/BudgetBalanceAssign.vue), [BudgetItem.vue](resources/js/domains/budget/components/BudgetItem.vue), migration (new), test (new)

---

## 13. Budget export — RTA Available wrong ✅

**Symptom:** [BudgetExport.php](app/Domains/Budget/Exports/BudgetExport.php) dumps `BudgetMonth.available` as-is. For Ready-to-Assign that column stores the gross "assigned out" total, not the actual usable balance. Export showed `Available: DOP$1050` while the UI correctly showed `-DOP$50`.

**Fix (2026-05-02):**
- [x] Added `c.display_id as categoryDisplayId` to the export query
- [x] In `map()`, use `resolveAvailable()` which for RTA computes `activity + left_from_last_month - budgeted` (same formula as UI's main card), and returns the stored `available` for any other category
- [x] Feature test `BudgetExportTest::test_rta_available_in_export_matches_ui_balance` reproduces the user's scenario (1050 budgeted, 1000 activity, 0 leftover → expects -DOP$50, not DOP$1050)

**Files changed:** [app/Domains/Budget/Exports/BudgetExport.php](app/Domains/Budget/Exports/BudgetExport.php), [tests/Feature/Finance/BudgetExportTest.php](tests/Feature/Finance/BudgetExportTest.php) (new)

---

## 12. Polish "Asignar sobrante" popover ✅

**Why:** Raw `<input type="number">` looked out of place against the design system. No live total/remaining indicator, no quick presets, browser default focus border.

**Applied:**
- [x] Added live "Sin asignar / Unassigned" indicator that updates as user types (green when 0, red if over, yellow if under)
- [x] Three quick-split presets: `50/50`, `Todo a Ahorro`, `Todo a Gasto`
- [x] `$` prefix inside inputs, design-system-aligned border + focus ring
- [x] Cleaner action buttons (text Cancel + filled green Save) with proper disabled state
- [x] Translations for new strings (`Unassigned`, `All to Savings`, `All to Spending`)

**Files changed:** [BudgetBalanceAssign.vue](resources/js/domains/budget/components/BudgetBalanceAssign.vue), [en.json](resources/lang/en.json), [es.json](resources/lang/es.json)

### Original fix (kept for reference) ✅

**Root cause:** [useBudget.ts:154-162](resources/js/domains/budget/useBudget.ts#L154-L162) — `parseAvailable` overrides the server-provided `available` for every non-`account` category. For Ready-to-Assign (`account_id` is null), it discards the correctly-rolled `BudgetMonth.available` and replaces it with `budgeted + left_from_last_month - |activity|`, which is meaningless for RTA.

- [x] In `parseAvailable`, skip the override for RTA: `(subCat.account_id || subCat.display_id === 'ready_to_assign') ? subCat.available : (...)`. Verified `display_id` flows from DB through [CategoryCollection.php:26](app/Http/Resources/CategoryCollection.php#L26) to the frontend payload.
- [ ] Manual verification: render a budget with non-zero RTA + a negative-balance category, click "Fix this", confirm the RTA option shows the same available value as the main balance card.

**Files changed:** [resources/js/domains/budget/useBudget.ts](resources/js/domains/budget/useBudget.ts)

**Note:** the cleaner long-term fix is to drop `parseAvailable` entirely (since `getBudgetData` already returns DB-correct `available` for every category), but that's a bigger blast radius — deferred until the other consumers are audited.

---

---

# Tasks — Test-Plan Findings (2026-05-02 agent run)

## 9. Profile Save was a no-op (Step 6 finding) ✅

**Root cause (pre-existing):** [resources/js/Pages/Profile/UpdateProfileInformationForm.vue](resources/js/Pages/Profile/UpdateProfileInformationForm.vue) referenced `photo.value` in 4 functions but **never declared `const photo = ref(null)`** — only the template ref `ref="photo"` on the `<input>`. Click on Save → `updateProfileInformation` runs → `if (photo.value)` throws `ReferenceError` → `form.post(...)` never reached → no XHR.

The Jetstream stub assumed `photo` would be picked up via Vue 2 `$refs`, but the file was migrated to script-setup without declaring the ref. The PHPUnit `ProfileInformationTest` didn't catch it because it bypasses the button (uses `$this->put(...)` directly). Adding the language picker just made the latent bug visible.

- [x] Add `const photo = ref<HTMLInputElement | null>(null)`
- [x] Fix the related `form.photo.value = ...` syntax (assigns to a property of `null`) → `form.photo = photo.value.files[0]`
- [ ] Manual verification: change Idioma → Save → see XHR fire → reload page → setting persists.

**Files changed:** [resources/js/Pages/Profile/UpdateProfileInformationForm.vue](resources/js/Pages/Profile/UpdateProfileInformationForm.vue)

## 10. Asignar sobrante "doesn't persist" (Step 8 finding) — likely test misdiagnosis

**Investigation:** the `BudgetSplitTest` feature test confirms the controller creates movements correctly. The route is registered. The frontend payload shape matches what the test sends. Most likely the agent checked `$page.props.categories[].budget` — that's the `BudgetTarget` relation (monthly goals), which is intentionally null for these categories. The split persists `BudgetMovement` rows, not `BudgetTarget`s.

Defensive changes applied so the next run will surface any real failure instead of silently closing:

- [x] `onError` handler in `submitSplit` displays the first validation/server error inline in the popover
- [x] `@click.stop` on Save and Cancel to ensure clicks don't bubble past the popover
- [x] Save button now disables on `splitForm.processing` to prevent double-submit

- [ ] Re-run test plan with stricter assertions: after clicking Save, query `budget_movements` table directly OR inspect `categories[].budgeted` (NOT `.budget`) for Ahorro and Gasto Personal. Screenshot the popover after submission to see if `splitError` is rendered.

**Files changed:** [resources/js/domains/budget/components/BudgetBalanceAssign.vue](resources/js/domains/budget/components/BudgetBalanceAssign.vue)

## 11. Reactive sidebar after language/module changes (Steps 2/5 nit) — deferred

**Symptom:** after saving language or toggling modules, the change persists but the sidebar doesn't update without a hard reload. The Inertia request returns and props are refreshed for the visited page, but the navigation menu is computed from `$page.props.modules` / `app()->getLocale()` shared by `HandleInertiaRequests` — that share runs on every Inertia visit, so a `router.reload({ only: [...] })` after success should already update both.

**Plan when picked up:**
- After successful module update → call `router.reload({ only: ['modules'], preserveScroll: true })` from `UpdateModulesForm.vue`
- After successful language update → call `router.reload({ only: ['locale'] })` plus reset the `vue-i18n` instance's `locale.value` (currently locale is read once at mount in [resources/js/app.ts](resources/js/app.ts))
- This is "polish UX" — the data IS persisted; only the menu lags until a refresh

Not blocking for friend-onboarding. Re-evaluate if Hope mentions it.

---

---

# Budget Module Polish — Lo Malo (deuda técnica) + Lo Mejorable (UX/UX)

**Plan:** terminar TODO "Lo malo" primero (2/día), luego pasar a "Lo mejorable" (2/día). Organizado por impacto descendente.

---

## 🔴 Lo malo — orden de ataque por impacto

### LM-1. Unificar paths de actualización (axios.post vs Inertia router) ⭐ alto

**Problema:** `useBudget.ts` usa `axios.post(...).then(reloadBudgets)` — patché así para mitigar #4, pero el patrón sigue siendo dual: el resto de la app usa `router.post`/`useForm.post`. Cualquier feature nueva podría volver a caer en el bug original. Además, la actualización optimista local (que toca `budgeted` sin tocar `available`/`activity`) es inconsistente con lo que devuelve el server.

- [ ] Reemplazar `axios.post` en `assignBudget` y `moveBudget` con `router.post(url, data, { preserveScroll: true, preserveState: true, only: ['budgets'] })`
- [ ] Borrar el bloque de "optimistic update" (ya no aporta — el roundtrip de Inertia trae estado correcto en una sola llamada)
- [ ] Verificar manualmente: mover/asignar dinero refresca balances correctamente
- [ ] Borrar el helper `reloadBudgets` si queda inútil

**Files:** [resources/js/domains/budget/useBudget.ts](resources/js/domains/budget/useBudget.ts)

### LM-2. `AtButton` `:disabled` no bloquea clicks ⭐ alto

**Problema:** AtButton (de atmosphere-ui) sólo usa `disabled` para estilos — no lo pasa al `<button>` real. Cualquier `:disabled="form.processing"` no previene doble-submit. Riesgo de duplicar movimientos si el usuario clickea dos veces rápido.

- [ ] En [LogerButton.vue](resources/js/Components/atoms/LogerButton.vue), agregar `pointer-events-none` (y `opacity-50`) al `:class` cuando `processing || disabled` — bloquea clicks sin tocar AtButton upstream
- [ ] Igual fix en lugares que usen `AtButton` directo con `:disabled` y donde el doble-submit sea peligroso (BudgetBalanceAssign.vue split popover)
- [ ] Tests: ya cubierto por `BudgetSplitTest` (validación rechaza dobles), pero confirmar que segundo click durante `processing` no dispara segundo POST

**Files:** [resources/js/Components/atoms/LogerButton.vue](resources/js/Components/atoms/LogerButton.vue), [BudgetBalanceAssign.vue](resources/js/domains/budget/components/BudgetBalanceAssign.vue)

### LM-3. Errores silenciados en flujos de movimientos ⭐ alto

**Problema:** Si el server devuelve 422 o 500, ningún UI feedback. El popover se cierra como si todo estuviera bien. Patché defensivamente `submitSplit` (Tarea 10) pero falta hacerlo de forma sistemática en `assignBudget`, `moveBudget`, los handlers de `BudgetMoneyLine`, etc.

- [ ] Agregar `onError` global a las llamadas `router.post` en `useBudget.ts` que muestre un toast/banner con el primer error
- [ ] Considerar un componente `<BudgetErrorBanner>` arriba del budget que recoja `$page.props.errors` y los presente
- [ ] Test: enviar payload inválido → confirma que UI muestra algo distinto a "todo OK"

**Files:** `useBudget.ts`, posiblemente nuevo `Components/molecules/BudgetErrorBanner.vue`, `Finance/Budget.vue`

### LM-4. `parseAvailable` machaca datos del server (riesgo de miscalc silencioso) ⭐ alto

**Problema:** Patché Ready-to-Assign para esquivar el override (Tarea 8), pero la función entera sigue siendo deuda. El backend ya devuelve `available` correcto vía `BudgetMonth`. Cada categoría no-cuenta lo recalcula incorrectamente con `budgeted + left_from_last_month - |activity|`.

- [ ] Auditar consumidores: grep `category.available`, `subCat.available`, `option.available` en `resources/js`
- [ ] Borrar la función `parseAvailable` y dejar que `available` venga directo del payload
- [ ] Comparar valores antes/después en una página real (RTA, categorías regulares, categorías linked-to-account)
- [ ] Si rompe algo (ej. categorías linked-to-account muestran null), patchar caso por caso
- [ ] Test: feature test que renderiza budget y verifica `available` matches `BudgetMonth.available`

**Files:** [resources/js/domains/budget/useBudget.ts](resources/js/domains/budget/useBudget.ts) y consumidores

### LM-5. Movimientos no son deshacibles (originalmente #5) ⭐ medio-alto

**Problema:** Click malo te desbalancea sin vuelta atrás.

- [ ] `BudgetMovementService::revertMovement(BudgetMovement $movement)` que invierte los `updateBalances` y reruns rollover
- [ ] Endpoint `DELETE /budget-movements/{movement}`
- [ ] UI: dropdown/menu en cada categoría mostrando últimos 5 movimientos con botón Undo, o un toast "Asignación guardada — Deshacer" tras cada acción (estilo Gmail)
- [ ] Feature test: crear movimiento, deshacer, asegurar balances vuelven al estado previo

**Files:** `BudgetMovementService.php`, nuevo controller action, nuevo Vue component

### LM-6. Modules section del onboarding TeamForm es código muerto ⭐ bajo

**Problema:** [TeamForm.vue:97-130](resources/js/Pages/Onboarding/TeamForm.vue#L97-L130) itera `modules` pero `CreateTeam.vue` nunca lo pasa.

- [ ] Decidir: conectar (pasar lista de módulos disponibles para que el usuario elija en onboarding) o borrar
- [ ] Recomendación: BORRAR — ya tenemos default Finance-only + toggle en Profile, no necesitamos otra UI más

**Files:** `TeamForm.vue`, `CreateTeam.vue`

### LM-7. Watchlist está roto ⭐ TBD

**Problema:** Reportado por el usuario, sin detalles aún. Investigar `/finance/watchlist` (index + show), confirmar síntoma, identificar root cause antes de proponer fix.

- [ ] Reproducir y documentar el síntoma exacto (qué página, qué acción, qué error)
- [ ] Identificar root cause
- [ ] Fix + test

**Files probables:** [Pages/Finance/Watchlist.vue](resources/js/Pages/Finance/Watchlist.vue), [Pages/Finance/WatchlistShow.vue](resources/js/Pages/Finance/WatchlistShow.vue), `app/Domains/Watchlist/`

---

## ✨ Lo mejorable — orden de ataque por impacto

(Después de terminar Lo Malo. 2/día.)

### LMJ-1. "Usar plan del mes pasado" (1 botón) ⭐⭐⭐ máximo ROI

**Por qué:** El 80% de las categorías quedan iguales mes a mes. Un botón al inicio del mes que copie todos los `budgeted` del mes anterior cambia "tedioso" por "1 click + 3 ajustes". Probablemente lo más impactante para retención.

- [ ] Endpoint `POST /budgets/months/{month}/copy-from-previous`
- [ ] Service: `BudgetCategoryService::copyMonthFromPrevious($teamId, $targetMonth)` — itera categorías, lee `budget_months` del mes anterior, crea nuevos `budget_months` para el mes actual con mismos `budgeted`
- [ ] UI: botón "Usar plan del mes pasado" en `Finance/Budget.vue` cuando el mes actual está vacío (todas las categorías con `budgeted=0`)
- [ ] Idempotente: si el mes ya tiene plan, mostrar "Sobrescribir plan?" con confirmación
- [ ] Feature test

### LMJ-2. Pill de gasto promedio últimos 3 meses por categoría ⭐⭐⭐ alto

**Por qué:** Cero esfuerzo cognitivo del usuario. El sistema le sugiere el monto. **El cálculo ya existe** en [TransactionService::getIncomeVsExpenses](app/Domains/Transaction/Services/TransactionService.php#L304-L364) (devuelve `avg` por categoría sobre N meses, usado en Trends → Income vs Expenses). Solo falta cablearlo al Budget.

- [x] ~~Calcular promedio por categoría~~ — ya existe en `TransactionService`
- [ ] Extraer / reusar para devolver mapa `[category_id => avg_3_months]` (envolver `getIncomeVsExpenses` o crear shortcut `getCategoryAverages($teamId, 3)`)
- [ ] Pasar como prop `categoryAverages` al [BudgetCategoryController::index](app/Domains/Budget/Http/Controllers/BudgetCategoryController.php) → `Finance/Budget`
- [ ] Renderizar pill `Promedio: $X` al lado del input de `budgeted` en [BudgetItem.vue](resources/js/domains/budget/components/BudgetItem.vue) / [BudgetMoneyLine.vue](resources/js/domains/budget/components/BudgetMoneyLine.vue)
- [ ] Click en el pill → autofill el input con ese promedio
- [ ] Feature test

### LMJ-3. Barra de progreso visual por categoría ⭐⭐ alto

**Por qué:** Lectura emocional rápida ("estoy en rojo"), no aritmética mental.

- [ ] Reusar `BudgetProgress.vue` que ya existe — hoy sólo se usa para RTA
- [ ] Por categoría: `progress = abs(activity) / budgeted`, color verde (<70%) → amarillo (70-100%) → rojo (>100%)
- [ ] Renderizar dentro de cada fila de `BudgetItem.vue`

### LMJ-4. Inline editing en mobile (también resuelve `#7`) ⭐⭐ alto

**Por qué:** UX mobile hoy está roto. La solución correcta es repensar la fila para small-screen, no parchar el modal.

- [ ] En `BudgetItem.vue` / `BudgetMoneyLine.vue`, detectar `isSmaller('md')` y renderizar input inline en vez de abrir modal de detalles
- [ ] Bottom-sheet style: tap en monto → input crece desde abajo, teclado iOS/Android no tapa
- [ ] El modal de detalles queda sólo para web (>= md)

### LMJ-5. Auto-cálculo "necesitas $X/mes" para metas con fecha ⭐⭐ medio (cierra pedido de Hope)

**Por qué:** La solicitud #4 de Hope ("ahorrar Y para enero 2027") sólo está parcialmente cubierta — falta el cálculo del aporte mensual.

- [ ] En `BudgetTargetForm.vue`, cuando `frequency = DATE` y `target_amount` y `frequency_date`: calcular `monthsRemaining = diff(frequency_date, today, 'months')` y mostrar "Necesitas $Y/mes para llegar"
- [ ] Si tiene `current_balance` (saldo acumulado), restar para mostrar lo que falta
- [ ] Sin migration — todo client-side derivable

### LMJ-6. Notas por categoría ⭐ medio

**Por qué:** Memoria contextual. "Por qué presupuesté esto", "ojo: anual cae en abril".

- [ ] Migration: `categories.notes text nullable`
- [ ] Form en `BudgetCategory.vue` — textarea
- [ ] Mostrar como tooltip/expand en `BudgetItem.vue`

### LMJ-7. Vista "Goals tracker" agregada ⭐ medio

**Por qué:** Hoy las metas viven sueltas dentro de cada categoría. Una página dedicada con timeline + ETAs es lo que pide el módulo de metas de Hope.

- [ ] Ruta `GET /finance/goals` → controller → `Finance/Goals/Index.vue`
- [ ] Lista TODAS las `BudgetTarget` activas con barra de progreso, fecha objetivo, días restantes, ETA proyectada
- [ ] Filtros: monthly / dated / overdue
- [ ] **Limpiar sistema huérfano de `goals`:** existe una tabla `goals` (migration `2022_03_28_121030_create_goals_table.php`) y componentes Vue [Pages/Finance/Goal.vue](resources/js/Pages/Finance/Goal.vue) + [GoalForm.vue](resources/js/Pages/Finance/GoalForm.vue) sin controller, routes ni queries. Decisión al construir esta página: o reusar los componentes Vue (renombrar y conectar al backend de `BudgetTarget`) o borrar la migration + componentes. Recomendado: BORRAR — el modelo correcto es `BudgetTarget` (ya tiene frequency MONTHLY/DATE, target_type, etc.), la tabla `goals` duplicaría conceptos.

### LMJ-8. Drag categorías entre grupos ⭐ medio

**Por qué:** Reorganización fluida. Hoy `Draggable` sólo reordena dentro del mismo grupo.

- [ ] Permitir cross-group drop usando `Draggable`'s `group` option
- [ ] Endpoint `PATCH /categories/{id}/move-to-group/{groupId}`
- [ ] Service updates `parent_id`

### LMJ-9. Filtros sticky en query string ⭐ bajo ✅ ya estaba implementado

**Verificación (2026-05-02):** el cableado ya existe.

- [x] [Budget.vue:128-132](resources/js/Pages/Finance/Budget.vue) — `toggleFilter` invoca `toggleCustomFilter('mode', status, Replace, false)` que escribe `?custom[mode]=<filter>` con `history.replaceState`
- [x] [Budget.vue:134-138](resources/js/Pages/Finance/Budget.vue) — `onMounted` lee `pageState.custom.mode` y reaplica el filtro
- [x] [useServerSearchV2.ts:165-194](resources/js/composables/useServerSearchV2.ts) parsea `custom[...]` del URL al cargar
- [x] La fecha persiste via `filter[date]` query param

**Pendiente (deuda menor, no parte de LMJ-9 estricto):** `defaultSearchInertia` compara `finalUrl` (path+query) vs `window.location.toString()` (full URL incl. origin) — nunca matchean, así que dispara un `router.get` en cada init. No afecta stickiness pero hace un roundtrip extra. Dejar para una pasada de LM.

### LMJ-10. Reglas de auto-asignación de income ⭐ alto-impacto / mucho esfuerzo

**Por qué:** "Cada vez que entre un sueldo, asigna 20% a Ahorro, 30% a Renta..." — feature de "set-and-forget" que YNAB no tiene. Liga superior.

- [ ] Modelo nuevo `IncomeRule` (team_id, percent_or_amount, target_category_id, source_account_id?)
- [ ] Job/observer en `Transaction::created` que detecta inflows y aplica reglas activas
- [ ] UI en `/finance/automation-rules` para CRUD
- [ ] **Esto es 1-2 días, dejarlo último.**

### LMJ-11. Mostrar bank name junto al account name en Financial Overview ⭐ bajo

**Por qué:** En el panel de Financial Overview (Trends), los goals fijados muestran las cuentas vinculadas solo por nombre. La columna `accounts.bank_code` ya existe (se usa en `buildBankBreakdown`) — exponerla junto al nombre evita ambigüedad cuando hay varias cuentas del mismo nombre en distintos bancos.

- [ ] [FinancialOverviewController::buildPinnedGoals](app/Http/Controllers/Finance/FinancialOverviewController.php) — agregar `bank_code` al `accountIndex` y al objeto `linked_accounts`
- [ ] [FinancialOverview.vue](resources/js/Pages/Trends/FinancialOverview.vue) — renderizar `bank_code` (si existe) al lado del nombre, ej. `Cuenta Corriente · BHD`

### LMJ-12. Goals con categoría de otro team del usuario ⭐ medio

**Por qué (caso real de Jesus):** recibe sueldo en el budget "company" donde tiene la categoría `Emergency Fund` con saldo. En su team personal también tiene `Emergency Fund` pero a $0. La meta vive en el team personal pero el dinero vive en el otro team. Hoy `BudgetTarget.team_id` es estricto al team actual, así que la meta personal muestra $0.

- [ ] Decidir modelo: (a) agregar columna `linked_team_id` + `linked_category_id` opcional a `budget_targets`, o (b) tabla pivot `budget_target_links (target_id, team_id, category_id)` para soportar N categorías
- [ ] Validación: solo permitir vincular a teams donde `$user->allTeams()` incluye el team destino
- [ ] [GoalsController](app/Domains/Budget/Http/Controllers/GoalsController.php) — al calcular `current_amount`, si la meta tiene linked categories, sumar el `available` de la(s) `BudgetMonth` de la(s) categoría(s) externa(s)
- [ ] UI: el modal de creación de meta debe permitir también elegir categorías de otros teams del usuario (agrupar `availableCategories` por team)
- [ ] Test: goal con categoría enlazada a otro team refleja correctamente el balance combinado

---

## Out of scope (deferred)

- Rollover que descuenta obligaciones programadas del RTA — refactor grande
- Per-person attribution para módulo de pareja (requerimiento separado)
