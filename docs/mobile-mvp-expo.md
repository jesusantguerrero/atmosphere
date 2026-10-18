# Loger Mobile MVP (Expo / React Native) — plan y contrato de API

> Estado: **backend Phase 0 iniciado y en buena parte hecho.** Falta que
> otro agente arranque la app Expo. Este doc es para que pueda retomarlo
> sin contexto previo.
> Branch de trabajo: `chore/laravel-13`. Autor del backend: sesión Claude.

## Objetivo

Llevar Loger al teléfono con Expo/RN, **no a paridad total** — solo lo que
hace la app usable en el bolsillo. El caso de uso central es **capturar y
ver rápido**: anotar un gasto en dos toques y ver saldos/próximos pagos.
Budgets, rollover, conciliación, housing, import se quedan en la web.

## Decisión de arquitectura

El backend Laravel es la API. El frontend Vue/Inertia **no se reusa** en RN
(Inertia no existe en RN); se reescribe la UI. Lo que SÍ se reusa: la
lógica de negocio (services), la DB, y los JSON de i18n
(`resources/lang/{es,en}.json`, mismas claves).

Decisiones a confirmar con Jesús antes de codear mobile:
- [ ] Repo: ¿repo aparte para el mobile, o carpeta `/mobile` junto al Laravel? (recomendado: repo aparte; el de Laravel es la API)
- [ ] UI kit RN (gluestack / Tamagui / RN Paper / NativeBase)
- [ ] Charts: victory-native / react-native-svg, o sin charts en el MVP
- [ ] Base URL por entorno + almacenamiento de token con `expo-secure-store`
- [ ] Push: `expo-notifications` (el backend ya tiene notificaciones; confirmar payloads luego)

## Restricciones de entorno (importante para quien continúe)

- La VM puente donde corrió el backend **no tiene php/MySQL y no puede
  hacer push**. Jesús hace `git push` y corre migraciones/builds.
- El backend se validó con `php -l` + inspección de endpoints en vivo
  (sesión web logueada), **no** con la query real contra MySQL. Verificar
  en deploy.
- Mobile: los builds corren en la Mac de Jesús (Expo/EAS, cuentas Apple/
  Google). El código se puede generar aquí; el loop de build/device es de él.

---

## Autenticación (token Sanctum — ya existe)

```
POST /api/sanctum/token
  body: { email, password, device_name, code?, recovery_code? }
  200 -> "<plainTextToken>"   (string JSON plano)
  402 -> { status:402, message }   (credenciales malas / 2FA requerido)
```
Luego: `Authorization: Bearer <token>` en todo.
`GET /api/user` -> usuario actual.

Soporta 2FA (TOTP `code` o `recovery_code`) igual que el login web.

### Quirks de routing (ya verificados en vivo)

- `routes/api.php` se prefija con `/api` (RouteServiceProvider). Sus rutas
  autentican por **Bearer token**; devuelven **401 a requests con solo
  cookie** — normal, el móvil usa token.
- Las rutas `/api/*` definidas en `routes/web.php` autentican por cookie
  **o** token; para el móvil se usan **solo GET**.
- Las multi-currency viejas quedaron **doble-prefijadas**
  (`/api/api/multi-currency/...`). **No usarlas**; usar `/api/mobile/*`.

---

## Contrato de API para el MVP (URLs y formas reales)

### Nuevo en esta sesión (`/api/mobile/*`, token)

**`GET /api/mobile/overview`** — payload de home en una sola llamada:
```jsonc
{
  "accounts": [{ "id","name","current_balance" (float),"balance_type",
                 "currency_code","account_detail_type_id",
                 "credit_closing_day","type" }],
  "netWorth": { "assets": float, "debts": float, "net": float },
  "nextPayments": [ /* ver /api/next-payments.data */ ],
  "cardsToPay":   [ /* subset type=credit_card_payment */ ]
}
```

**`GET /api/mobile/today`** — el "Hoy" de la web en una llamada (`TodayService::buildPayload`):
```jsonc
{
  "money":     { "today_spent": float, "daily_remaining": float, "month_remaining": float,
                 "days_in_month_left": int, "currency_code": string|null },
  "attention": [{ "id","type","message","cta",... }],       // alertas watchlist + metas sub-fondeadas (≤7 días)
  "today":     [{ "kind": "planner"|"relationship", "id","name","subtitle","status","total"? }],
  "upcoming":  [{ "kind": "billing_cycle"|"utility"|"planner", "id","name","account_id","total",
                  "due_at": "YYYY-MM-DD", "days_until": int }],   // days_until < 0 = vencido
  "meal":      [{ "id","meal_id","name","meal_type","is_liked","date","day_label" }],  // semana en curso
  "pending_review_count": 3   // borradores en bandeja (badge); usa TransactionService::getDraftCount
}
```
`money.currency_code` puede venir `null`: usar la moneda de las cuentas de `/api/mobile/overview`.
`pending_review_count` (int): cantidad de transacciones `status=draft` del team. Sirve para la insignia de la bandeja sin tener que traer las filas completas.

**`POST /api/mobile/transactions`** — quick-add (reusa MultiCurrency@store):
```jsonc
body: {
  "account_id": int (req),
  "total": number > 0 (req),
  "currency_code": "DOP" (req, size 3),
  "description": string (req),
  "date": "YYYY-MM-DD" (req),
  "direction": "WITHDRAW" | "DEPOSIT" (req),   // también acepta "credit" | "debit" (legacy, ver nota)
  "category_id": int?, "payee_id": int?, "counter_account_id": int?,
  "status": "draft" | "verified"?   // default verified
}
-> { success:true, data: <MultiCurrencyTransactionResource> }
```
> Semántica de `direction`: lo que guarda el modelo es `DIRECTION_DEBIT='DEPOSIT'`
> (entrada, type 1) y `DIRECTION_CREDIT='WITHDRAW'` (salida, type -1). **Enviar
> `WITHDRAW` para un gasto y `DEPOSIT` para un ingreso**; las lecturas
> (`GET /api/mobile/transactions`) devuelven esos mismos valores.
> `credit`/`debit` siguen siendo válidos solo por compatibilidad: el controlador
> los guarda tal cual, **sin convertirlos**, así que no equivalen a
> `WITHDRAW`/`DEPOSIT` en los cálculos. No usarlos en clientes nuevos.
> Transferencias: usar `counter_account_id` (fuera del MVP mínimo).

**`GET /api/mobile/transactions`** — recientes (MultiCurrency@index).

### Ya existían (reusar tal cual)

| Uso | Endpoint | Grupo auth | Forma |
|-----|----------|-----------|-------|
| Cuentas (completo) | `GET /api/accounts` | web (cookie+token) | modelos Account completos, incluye `current_balance`, `balance_type`, `currency_code`, `credit_closing_day` |
| Categorías (picker) | `GET /api/categories` | web | grupos top-level + `subCategories` (⚠️ estaba en 500, **arreglado**) |
| Payees (picker) | `GET /api/payees` | web | 429 filas `{id,name,account_id,...}` (pesado → ideal búsqueda server-side) |
| Recientes / lista | `GET /api/finance/transactions?filter[account]=ID` | web | lista con filtros (QuerifySlim) |
| Próximos pagos | `GET /api/next-payments` | web | `{summary:{total_amount,total_count,by_type}, data:[{id,type,description,title,total,due_date,date,category_id,category_name,status,source,metadata}]}` |
| Tarjetas (resumen) | `GET /credit-card-summary` | token/cookie | `{payInFull:[{account_id,account_name,total,due_at,days_until,is_overdue,...}], inactive:[...]}` |
| Dashboard rico (opcional) | `GET /api/dashboard` | token | `{netWorth, nextPayments, budgetTotal, transactionTotal, expenses, spendingSummary, meals, checks}` |

---

## Pantallas del MVP (5–6) → datos

1. **Login** → `POST /api/sanctum/token`
2. **Home** (saldos, patrimonio, próximos pagos, tarjetas a pagar) → **1 llamada** `GET /api/mobile/overview`
3. **Quick-add gasto/ingreso** → `POST /api/mobile/transactions`; pickers de `GET /api/accounts`, `GET /api/categories`, `GET /api/payees`
4. **Cuentas + saldos** → `accounts` de `/api/mobile/overview` (o `/api/accounts`)
5. **Detalle de cuenta / transacciones recientes** → `GET /api/finance/transactions?filter[account]=ID`
6. **Tarjetas** → `GET /credit-card-summary`

---

## Estado del backend

### Hecho en esta sesión (en `chore/laravel-13`, falta push + deploy, **sin migración**)
- [x] Fix 500 `GET /api/categories` (faltaba `index()`) — commit `28c4f49d`
- [x] Superficie `/api/mobile/*` (overview + GET/POST transactions) — commit `3b016ddd`

### Ya estaba
- [x] Login por token (`/api/sanctum/token`), `/api/user`, `/api/dashboard`
- [x] `/api/accounts`, `/api/next-payments`, `/credit-card-summary`, `/api/payees`, `/api/finance/transactions`

### Backend opcional / para después
- [ ] `AccountResource` slim (hoy `/api/accounts` manda el modelo completo)
- [ ] Búsqueda server-side de payees (429 filas; hay payees con `name` vacío)
- [ ] Soporte de transferencia en quick-add (`counter_account_id`) + validación
- [ ] i18n de mensajes de la API si hiciera falta
- [ ] Confirmar que `/api/mobile/overview` corre OK contra MySQL real (no se pudo testear desde la VM)

---

## Tareas Mobile (para el agente que retome)

### Phase 1 — Esqueleto Expo
- [ ] Proyecto Expo (TS), navegación (expo-router o react-navigation)
- [ ] Cliente API + base URL por entorno
- [ ] Flujo de auth: login → guardar token en `expo-secure-store` → interceptor Bearer → logout/expiración
- [ ] Reusar `resources/lang/{es,en}.json` para i18n

### Phase 2 — Pantallas core
- [ ] Home desde `/api/mobile/overview` (saldos, patrimonio, próximos, tarjetas a pagar)
- [ ] Quick-add (gasto/ingreso) con pickers → `POST /api/mobile/transactions`
- [ ] Lista de cuentas + saldos
- [ ] Transacciones recientes por cuenta

### Phase 3 — Tarjetas + patrimonio
- [ ] Pantalla de tarjetas (`/credit-card-summary`)
- [ ] Glance de patrimonio (de `overview.netWorth`)

### Phase 4 — Pulido + release
- [ ] Pruebas en device real, estados de error/empty/loading
- [ ] Config EAS build (iOS/Android)
- [ ] Push con `expo-notifications`
- [ ] Distribución interna (TestFlight / internal track)

---

## Para empezar el mobile (sugerencia de primer paso)
1. Confirmar con Jesús las decisiones de arquitectura de arriba.
2. Jesús despliega el backend (`git push` + `php artisan route:clear` / deploy; `yarn build` solo si tocó la web — aquí no).
3. Verificar en vivo con un token: `GET /api/mobile/overview`, `POST /api/mobile/transactions`.
4. Scaffoldear Expo (Phase 1).

---

## Esquema de payloads y respuestas (con ejemplos)

> Los **nombres y tipos de campo son reales** (capturados en vivo + del código).
> Los **valores son ilustrativos/sanitizados** (no son datos reales). Los
> endpoints `/api/mobile/*` aún no están deployados: sus ejemplos se derivan del
> controlador, no de una captura en vivo — confirmar formas exactas tras deploy.

### Errores comunes (rutas autenticadas)
```jsonc
401 { "message": "Unauthenticated." }               // token ausente/inválido/expirado
403 { "message": "This action is unauthorized." }   // Gate (p.ej. recurso de otro team)
422 { "message": "The given data was invalid.",
      "errors": { "campo": ["mensaje"] } }           // validación
404 { "message": "..." }                             // no encontrado
500 { "message": "Server Error" }                    // (debug oculto en prod)
```

### 1. Login — `POST /api/sanctum/token`  (sin auth)
Request:
```jsonc
// headers: Accept: application/json
{ "email": "user@example.com", "password": "••••••",
  "device_name": "jesus-iphone-15",   // req: nombra el token
  "code": "123456",                    // opc: TOTP 2FA
  "recovery_code": "xxxx-xxxx" }       // opc: alterno al TOTP
```
200 — **string JSON plano** con el token (guardar en secure-store):
```json
"3|AbCdEf0123456789plainTextTokenHere"
```
402 — credenciales malas / 2FA requerido:
```json
{ "status": 402, "message": "The provided credentials are incorrect." }
```

### 2. Usuario — `GET /api/user`  (Bearer)
200 → objeto User (`id, name, email, current_team_id, …`).

### 3. Home — `GET /api/mobile/overview`  (Bearer)
```jsonc
{
  "accounts": [
    { "id": 101, "name": "BHD Cuenta Corriente", "current_balance": 15230.55,
      "balance_type": "DEBIT",  "currency_code": "DOP", "detail_type": "bank",
      "account_detail_type_id": 2, "credit_closing_day": null, "credit_limit": null,
      "type": 1, "reconciliation_last": { "status": "completed", "amount": 15230.55 } },
    { "id": 1735, "name": "Qik Visa Clásica", "current_balance": -20056.92,
      "balance_type": "CREDIT", "currency_code": "DOP", "detail_type": "credit_card",
      "account_detail_type_id": 6, "credit_closing_day": 25, "credit_limit": 290000.00,
      "type": -1, "reconciliation_last": null }
  ],
  "netWorth": { "assets": 1400000.00, "debts": -1034000.00, "net": 366000.00 },
  "nextPayments": [ /* items = /api/next-payments.data, ver §9 */ ],
  "cardsToPay":   [ /* subset de nextPayments con type == "credit_card_payment" */ ]
}
```
`balance_type` llega en **MAYÚSCULAS** (`DEBIT`=activo, `CREDIT`=pasivo). `type`: 1=activo, -1=pasivo.
`detail_type` ∈ `bank|cash|cash_on_hand|savings|credit_card|property|loan` (la app agrupa por él; cae a `balance_type` si falta).
`current_balance` viene de `$account->balance`. Las cuentas con `closed_at` quedan fuera. `net = assets + debts` (debts negativo).
`credit_limit` (float|null): si `> 0`, la cuenta es tarjeta de crédito. **Crédito disponible = credit_limit + current_balance** (el saldo es negativo).
`reconciliation_last` (`{status, amount}` | null): `status` ∈ `completed|pending`. Conciliada = `status === 'completed'` y `|amount − current_balance| < 0.01`.

### 3b. Hoy — `GET /api/mobile/today`  (Bearer)
```jsonc
{
  "money": { "today_spent": 1850.0, "daily_remaining": 2410.5, "month_remaining": 62673.0,
             "days_in_month_left": 26, "currency_code": null },
  "attention": [
    { "id": "9f1c…", "type": "watchlist_threshold", "message": "Comida alcanzó el 90% del presupuesto", "cta": "Ver", "link": "/budgets" },
    { "id": "target-1040", "type": "target_underfunded", "message": "Loan vehicle 27th: faltan 11,956.77 antes del 27 Oct",
      "cta": "Asignar", "category_id": 1040, "needed": 11956.77, "due_date": "2026-10-27" }
  ],
  "today": [
    { "kind": "planner", "id": "planner-12", "name": "Pagar internet", "subtitle": null,
      "status": "pending", "total": 1500.0 }
  ],
  "upcoming": [
    { "kind": "billing_cycle", "id": "cycle-77", "name": "Qik Visa Clásica", "account_id": 1735,
      "total": 20056.92, "due_at": "2026-10-15", "days_until": 5 }
  ],
  "meal": [
    { "id": 301, "meal_id": 18, "name": "Pollo al horno", "meal_type": "dinner",
      "is_liked": false, "date": "2026-10-10", "day_label": "Sábado" }
  ],
  "pending_review_count": 3
}
```
Cada lista puede venir vacía. Valores de ejemplo; los nombres y tipos de campo son los reales.

### 4. Quick-add — `POST /api/mobile/transactions`  (Bearer)
Request:
```jsonc
{
  "account_id": 101,          // req
  "total": 1850.00,           // req, > 0
  "currency_code": "DOP",     // req, 3 letras
  "description": "Supermercado Nacional",       // req
  "date": "2026-10-10",       // req, YYYY-MM-DD
  "direction": "WITHDRAW",    // req: "WITHDRAW"=salida | "DEPOSIT"=entrada ("credit"/"debit" legacy, sin conversión)
  "category_id": 153,         // opc
  "payee_id": 79,             // opc
  "counter_account_id": null, // opc (transferencias)
  "status": "verified"        // opc: "verified" (default) | "draft"
}
```
200/201:
```jsonc
{
  "success": true,
  "data": {
    "id": 98231, "description": "Supermercado Nacional", "total": 1850.0,
    "currency_code": "DOP", "date": "2026-10-10", "direction": "WITHDRAW",
    "status": "verified", "created_at": "…", "updated_at": "…",
    "account":  { "id": 101, "name": "BHD Cuenta Corriente", "currency_code": "DOP",
                  "is_multi_currency": false, "primary_currency": "DOP",
                  "secondary_currencies": [] },
    "category": { "id": 153, "name": "Comida" },                 // solo si se envió
    "payee":    { "id": 79,  "name": "Supermercado Nacional" },  // solo si se envió
    "multi_currency": { "is_converted": false, "secondary_currency_amount": 1850.0,
                        "is_secondary_currency": false, "display_currencies": { /* … */ } }
  }
}
```
Errores: `422` (campos), `403` (cuenta de otro team).

### 5. Recientes — `GET /api/mobile/transactions`  (Bearer)
Query: `account_id`, `currency_code`, `start_date`, `end_date`, `limit` (1–100, default 20), `page`.
200 →
```jsonc
{
  "success": true,
  "data": {
    "transactions": [ /* igual que data de §4 */ ],
    "pagination": { "current_page": 1, "last_page": 4, "per_page": 20 /* … */ }
  }
}
```

### 6. Cuentas (completo) — `GET /api/accounts`  (Bearer o cookie)
200 → array de modelos Account completos. Campos útiles:
```jsonc
[ { "id": 101, "name": "BHD Cuenta Corriente",
    "current_balance": "15230.55", "opening_balance": "0.00",   // ⚠️ string
    "balance_type": "debit", "currency_code": "DOP",
    "account_detail_type_id": 2, "credit_closing_day": null, "credit_min_payment": null,
    "is_multi_currency": false, "type": 1, "status": "active",
    "display_id": "…", "bank_code": "BHD", "index": 0,
    "created_at": "…", "updated_at": "…" } ]
```

### 7. Categorías (picker) — `GET /api/categories`  (Bearer o cookie)
200 → grupos top-level con `subCategories` (los seleccionables son las hijas):
```jsonc
[ { "id": 10, "name": "Gastos del hogar", "parent_id": null, "index": 0,
    "resource_type": "transactions", "color": "#…", "icon": "…",
    "subCategories": [
      { "id": 153, "name": "Comida",   "parent_id": 10, "index": 0 },
      { "id": 154, "name": "Alquiler", "parent_id": 10, "index": 1 } ] } ]
```

### 8. Payees (picker) — `GET /api/payees`  (Bearer o cookie)
200 → array grande (~400+). ⚠️ hay payees con `name` vacío.
```jsonc
[ { "id": 79, "team_id": 2, "user_id": 2, "account_id": 100,
    "name": "Supermercado Nacional", "created_at": "…", "updated_at": "…" } ]
```

### 9. Próximos pagos — `GET /api/next-payments`  (Bearer o cookie)
```jsonc
{
  "summary": { "total_amount": 44056.92, "total_count": 10,
               "by_type": { "budget_category": 7, "credit_card_payment": 3 } },
  "data": [
    { "id": "budget_3", "type": "budget_category", "description": "Alquiler",
      "title": "Alquiler", "total": "24000.00", "due_date": "2026-10-07",
      "date": "2026-10-07", "category_id": 154, "category_name": "Alquiler",
      "status": "pending", "source": "budget_target",
      "metadata": { "target_id": 3, "frequency": "MONTHLY" } },
    { "id": "cc_payment_1735_2026-11", "type": "credit_card_payment",
      "title": "Credit Card Payment - Qik Visa Clásica", "total": 20056.92,
      "due_date": "2026-11-10", "account_id": 1735, "status": "pending",
      "source": "dynamic_calculation", "cut_date": "2026-10-25",
      "statement_unpaid": true, "metadata": { /* … */ } }
  ]
}
```
Tipos de item: `budget_category`, `credit_card_payment`, `planned_transaction`.
`status` ∈ { `pending`, `overdue` }.

### 10. Tarjetas — `GET /credit-card-summary`  (Bearer o cookie)
```jsonc
{
  "payInFull": [   // tarjetas con estado sin pagar y vencimiento cerca/pasado
    { "account_id": 1735, "account_name": "Qik Visa Clásica", "total": 20056.92,
      "due_at": "2026-11-10", "days_until": 30, "is_overdue": false,
      "statement_unpaid": true, "cut_date": "2026-10-25", "days_since_cut": 5 } ],
  "inactive": [    // candidatas a cancelar: saldo 0 y sin actividad 6+ meses
    { "account_id": 1800, "account_name": "Tarjeta vieja", "total": 0,
      "months_inactive": 8 } ]
}
```
(Campos de ciclo como `days_since_cut`/`months_inactive` son ilustrativos — confirmar en deploy.)

### 11. Lista por cuenta — `GET /api/finance/transactions?filter[account]=ID`  (Bearer o cookie)
200 → lista filtrada (QuerifySlim). Soporta `filter[...]`, `page`, etc. Item ≈ transacción con `id, date, description, total/amount, direction, status, category, payee, account_id`. (Confirmar forma exacta en deploy.)

### 12. Presupuesto — `GET /api/mobile/budget?month=YYYY-MM`  (Bearer)
```jsonc
{
  "month": "2026-10",
  "ready_to_assign": -1000.00,
  "groups": [
    { "id": 10, "name": "Gastos del hogar", "kind": "expense",
      "budgeted": 35000.0, "activity": -18500.0, "available": 16500.0,
      "children": [
        { "id": 153, "name": "Comida", "budgeted": 20000.0, "activity": -12000.0, "available": 8000.0,
          "left_from_last_month": 0.0, "funded_spending": 0.0, "payments": 0.0, "has_target": true,
          "target": { "amount": 20000.0, "needed": 12000.0, "due_date": "2026-10-15", "frequency": "MONTHLY", "target_type": "spending" } },
        { "id": 154, "name": "Alquiler", "budgeted": 15000.0, "activity": -6500.0, "available": 8500.0,
          "left_from_last_month": 0.0, "funded_spending": 0.0, "payments": 0.0, "has_target": false,
          "target": null }
      ] },
    { "id": 99, "name": "Credit Card Payments", "kind": "credit_card_payments",
      "budgeted": 110506.59, "activity": -90000.0, "available": 20506.59,
      "children": [
        { "id": 500, "name": "Qik Visa Clásica", "budgeted": 110506.59, "activity": -90000.0, "available": 20506.59,
          "left_from_last_month": 0.0, "funded_spending": 0.0, "payments": 0.0, "has_target": false, "target": null }
      ] }
  ],
  "summary": {
    "available": 1509751.30,
    "to_assign": -1000.00,
    "budget_total": 1508751.30,
    "cash_in_accounts": 1477084.79,
    "difference": 31666.51
  }
}
```
RTA = `inflow.activity + inflow.left_from_last_month - totalAssigned` (misma fórmula que la web).
`totalAssigned` = sum of `budgeted` across ALL outflow groups (including Credit Card Payments).
Each group has `kind`: `"expense"` or `"credit_card_payments"`. Excludes only `Ready to Assign` and `Inflow`.
`summary`: reconciliation data matching `BudgetReconcileBanner.vue`. `cash_in_accounts` = sum of on-budget account balances (excludes credit cards and resource-type accounts).

### 13. Asignar presupuesto — `POST /api/mobile/budget/assign`  (Bearer)
```jsonc
{ "category_id": 153, "month": "2026-10", "budgeted": 20000.0 }
```
`budgeted` es el **total del mes** (se fija, no se suma). → `{ "success": true }`

### 14. Mover presupuesto — `POST /api/mobile/budget/move`  (Bearer)
```jsonc
{ "source_category_id": 153, "destination_category_id": 154, "month": "2026-10", "amount": 5000.0 }
```
`amount` se recorta a lo disponible en origen. → `{ "success": true }`

### 15. Calendario — `GET /api/mobile/calendar?month=YYYY-MM`  (Bearer)
```jsonc
{
  "month": "2026-10",
  "events": [
    { "id": 12, "planner_id": 12, "title": "Pagar internet", "start": "2026-10-15",
      "end": "2026-10-15", "kind": "billing", "status": "pending", "total": 1500.0,
      "source": "billing_cycle", "notes": null, "completed_at": null }
  ]
}
```
Agrega 3 fuentes: planners, billing cycles, occurrences. `kind` ∈ `planner|billing|occurrence|relationship`.

### 16. Rutina semanal — `GET /api/mobile/routine`  (Bearer)
```jsonc
{
  "plan": { "id": 5, "days": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
    "blocks": [
      { "id": 88, "day": 0, "title": "Ejercicio", "start": "06:00", "end": "07:00",
        "color": "#6E9BE6", "member_id": null, "note": "" }
    ] },
  "current": { /* bloque activo ahora o null */ },
  "next":    { /* próximo bloque hoy o null */ }
}
```
`day` = 0–6 (lunes–domingo). `current`/`next` calculados contra la hora local del team.

### 17. Crear evento — `POST /api/mobile/events`  (Bearer)
```jsonc
{ "name": "Cita médico", "date": "2026-10-20", "end_date": null, "notes": null, "total": null, "source": null }
```
→ `201 { "success": true, "data": { "id", "name", "date", "status" } }`

### 18. Completar evento — `PATCH /api/mobile/events/{planner_id}/complete`  (Bearer)
```jsonc
{ "notes": "Pagado con tarjeta" }   // opcional
```
→ `{ "success": true }`

### 19. Transferencia — `POST /api/mobile/transfers`  (Bearer)
```jsonc
{ "from_account_id": 101, "to_account_id": 1735, "total": 20000.0, "description": "Pago tarjeta", "date": "2026-10-10" }
```
→ `201 { "success": true, "data": { "id", "description", "total", "date" } }`

### 20. Tareas del hogar — `GET /api/mobile/chores?done=today|all`  (Bearer)
```jsonc
{
  "chores": {
    "id": 1, "name": "CHORES",
    "fields": [ /* board field definitions */ ],
    "labels": [ /* board labels */ ],
    "stages": [
      { "id": 1, "board_id": 1, "name": "Main",
        "items": [
          { "id": 42, "title": "Lavar platos", "resource_type": null, "is_done": false,
            "rrule": "DTSTART:20261017\nRRULE:FREQ=DAILY;INTERVAL=1;UNTIL=30991231",
            "points": null, "commit_date": null, "color": null, "stage": "Main", "board_id": 1,
            "fields": [
              { "id": 10, "name": "Owner", "value": "Jesus", "type": "text", "options": null }
            ],
            "owner": "Jesus" }
        ] }
    ]
  },
  "members": [ { "id": 1, "name": "Jesus" }, { "id": 2, "name": "Karla" } ]
}
```
`done=today` (default): pendientes + completadas hoy. `done=all`: todas.
Cada item lleva campos dinámicos aplanados (`owner`, etc.) además del array `fields`.
`is_done` + `commit_date` determinan el estado. `rrule` indica recurrencia (daily/weekly/once).
`members` = perfiles del team, para filtrar por persona.

### 21. Toggle tarea — `PATCH /api/mobile/chores/{item_id}/toggle`  (Bearer)
→ `{ "success": true, "is_done": true, "commit_date": "2026-10-17" }`
Flip `is_done`. Para tareas recurrentes, el modelo auto-estampa `commit_date` al completar
y la limpia al deshacer. El cron `chores:reset-recurring` resetea las diarias.

### 22. Crear tarea — `POST /api/mobile/chores`  (Bearer)
```jsonc
{ "title": "Barrer terraza", "owner": "Karla", "recurrence": "daily" }
```
`recurrence` ∈ `daily|weekdays|weekly|once` (default: `daily` vía boot del modelo).
`owner` se guarda como campo dinámico del plan item.
→ `201 { "success": true, "data": { "id", "title", "is_done", "rrule" } }`

### 23. Plan de comidas — `GET /api/mobile/meals?start=YYYY-MM-DD&end=YYYY-MM-DD`  (Bearer)
```jsonc
{
  "start": "2026-10-12", "end": "2026-10-18",
  "meals": [
    { "id": 301, "mealId": 18, "name": "Pollo al horno", "mealTypeName": "Almuerzo",
      "is_liked": false, "is_meal_liked": true, "mealTypeId": 2, "date": "2026-10-15" }
  ],
  "mealTypes": [ { "id": 1, "name": "Desayuno" }, { "id": 2, "name": "Almuerzo" }, { "id": 3, "name": "Cena" } ]
}
```
Defaults: semana en curso (lunes–domingo). `meals` usa `PlannedMealResource`.
`mealTypes` para que la app sepa qué tipos de comida hay configurados.

### 24. Recetas — `GET /api/mobile/meals/recipes`  (Bearer)
```jsonc
{
  "recipes": [
    { "id": 18, "name": "Pollo al horno", "meal_type": "Almuerzo", "meal_type_id": 2, "is_liked": true }
  ]
}
```
Todas las recetas del team. `is_liked` para filtrar favoritas client-side.

### 25. Shopping list — `GET /api/mobile/shopping?plan=ID`  (Bearer)
```jsonc
{
  "plan": {
    "id": 7, "name": "Shopping List", "share_url": "https://loger.app/shared/list/abc-uuid" | null,
    "stages": [
      { "id": 15, "name": "Frutas",
        "items": [
          { "id": 100, "title": "Manzanas", "state": "pending", "is_done": false, "order": 0 },
          { "id": 101, "title": "Bananos", "state": "buy", "is_done": true, "order": 1 }
        ] }
    ]
  },
  "lists": [
    { "id": 7, "name": "Shopping List", "total": 12, "pending": 8, "shared": false },
    { "id": 9, "name": "Costco trip", "total": 5, "pending": 5, "shared": true }
  ],
  "activeListId": 7
}
```
`?plan=ID` switches lists (omit for default). `state` ∈ `pending|buy|skip`.
Items cycle: pending → buy → skip → pending. `is_done` syncs with `state=buy`.

### 26. Add shopping item — `POST /api/mobile/shopping/{plan_id}/items`  (Bearer)
```jsonc
{ "title": "Leche", "stage_id": 15 }
```
`stage_id` optional (defaults to first stage).
→ `201 { "id", "title", "state": "pending", "order", "stage_id" }`

### 27. Cycle shopping item — `POST /api/mobile/shopping/{plan_id}/items/{item_id}/cycle`  (Bearer)
```jsonc
{ "state": "buy" }   // optional: explicit state; omit to auto-cycle
```
→ `{ "id", "state": "buy", "is_done": true }`

### 27b. Stages CRUD — shopping list categories

**Create stage:** `POST /api/mobile/shopping/{plan_id}/stages` (Bearer)
```jsonc
{ "name": "Lácteos" }
```
→ `201 { "id", "name", "items": [] }`

**Rename stage:** `PATCH /api/mobile/shopping/{plan_id}/stages/{stage_id}` (Bearer)
```jsonc
{ "name": "Dairy" }
```
→ `{ "id", "name" }`

**Delete stage:** `DELETE /api/mobile/shopping/{plan_id}/stages/{stage_id}` (Bearer)
→ `{ "deleted": true }` (items move to the first remaining stage)

### 27c. Item update & delete

**Update item:** `PATCH /api/mobile/shopping/{plan_id}/items/{item_id}` (Bearer)
```jsonc
{ "title": "Leche deslactosada", "stage_id": 16 }
```
Both fields optional. → `{ "id", "title", "state", "stage_id" }`

**Delete item:** `DELETE /api/mobile/shopping/{plan_id}/items/{item_id}` (Bearer)
→ `{ "deleted": true }`

### 27d. Bulk operations

**Reset list:** `POST /api/mobile/shopping/{plan_id}/reset` (Bearer)
All items → `pending` / `is_done=false`. → `{ "reset": true }`

**Check all:** `POST /api/mobile/shopping/{plan_id}/check-all` (Bearer)
All `pending` items → `buy` / `is_done=true`. → `{ "checked": true }`

### 27e. Compartir lista

**Activar sharing:** `POST /api/mobile/shopping/{plan_id}/share` (Bearer)
Idempotente — si ya tiene token, devuelve la misma URL.
→ `{ "shared": true, "url": "https://loger.app/shared/list/uuid" }`

**Desactivar sharing:** `DELETE /api/mobile/shopping/{plan_id}/share` (Bearer)
→ `{ "shared": false }`

`GET /shopping` incluye `share_url` en el plan activo (o `null` si no está compartida).

### 28. Aprobar borrador — `POST /api/mobile/transactions/{transaction_id}/approve`  (Bearer)
Sin body. Cambia `status` de `draft` a `verified`.
→ `{ "success": true }`
Errores: `403` (otro team), `404` (no existe).

### 29. Borrar transacción — `DELETE /api/mobile/transactions/{transaction_id}`  (Bearer)
Elimina una transacción (borra entries de conciliación asociadas).
→ `{ "success": true }`
Errores: `403` (otro team / Gate), `404`.

### 30. Borrar en lote — `POST /api/mobile/transactions/bulk-delete`  (Bearer)
```jsonc
{ "ids": [98231, 98232, 98240] }
```
→ `{ "success": true, "deleted": 3 }`
Solo borra las que pertenecen al team del usuario. Las que no existen o son de otro team se ignoran silenciosamente.

### 31. Ocurrencias — `GET /api/mobile/occurrences`  (Bearer)
```jsonc
{
  "occurrences": [
    { "id": 5, "name": "Lavar carro", "type": "utility",
      "last_date": "2026-10-01", "avg_days_passed": 14.0,
      "occurrence_count": 8, "days_since_last": 16, "is_overdue": true,
      "days_until_next": -2 }
  ]
}
```
`is_overdue` = `days_since_last >= avg_days_passed + 3` (misma regla que la web).
`days_until_next`: positivo = faltan días, negativo = vencida, `null` = sin datos.
`type` ∈ `utility|relationship|null`. Solo activas (`is_active=true`) con `last_date`.

### 32. Registrar ocurrencia — `POST /api/mobile/occurrences/{occurrence_id}/instances`  (Bearer)
Sin body. Registra "pasó ahora" (fecha de hoy). Recalcula `avg_days_passed`, `last_date`, etc.
→ `{ "success": true, "last_date": "2026-10-17", "occurrence_count": 9 }`
Errores: `403` (otro team).

### 33. Deshacer última ocurrencia — `DELETE /api/mobile/occurrences/{occurrence_id}/instances`  (Bearer)
Sin body. Elimina la última entrada del log y recalcula estadísticas.
→ `{ "success": true, "last_date": "2026-10-01", "occurrence_count": 8 }`
Errores: `403` (otro team).

### 34. Insights (Tendencias) — `GET /api/mobile/insights?months=6&end=2026-10&range=6M`  (Bearer)
```jsonc
{
  "period": { "start": "2026-05-01", "end": "2026-10-31", "months": 6,
              "anchor": "2026-10", "is_current": true },
  "months": [
    { "month": "2026-05-01", "income": 85000.0, "expense": 62000.0,
      "assets": 1200000.0, "debts": -950000.0 }
  ],
  "expense_categories": [ { "id": 153, "name": "Comida", "total": 120000.0 } ],
  "income_categories":  [ { "id": 200, "name": "Salario", "total": 510000.0 } ],
  "expense_payees":     [ { "id": 79, "name": "Supermercado Nacional", "total": 45000.0 } ],
  "income_payees":      [ { "id": 90, "name": "Empresa SA", "total": 510000.0 } ],
  "expense_accounts":   [ { "id": 101, "name": "BHD Cuenta Corriente", "total": 180000.0 } ],
  "credit_cards": {
    "has_credit_cards": true,
    "credit_total": 45000.0,           // total del último ciclo (todas las tarjetas)
    "credit_total_previous": 38000.0,  // ciclo anterior
    "credit_total_delta": 7000.0,      // diferencia
    "credit_capacity": 290000.0,       // suma de credit_limit
    "credit_line_usage": 15.52,        // % utilización
    "cards": [
      { "id": 5, "name": "BHD Visa Gold", "credit_limit": 290000.0,
        "cycle_total": 45000.0, "cycle_from": "2026-09-15", "cycle_until": "2026-10-15" }
    ],
    "top_categories": [
      { "card_name": "BHD Visa Gold", "category": "Comida", "total": 18000.0 }
    ],
    "top_payees": [
      { "card_name": "BHD Visa Gold", "payee": "Supermercado Nacional", "total": 12000.0 }
    ]
  }
}
```
Query params:
- `months` (1–12, default 6): ventana de meses.
- `end` (YYYY-MM, default mes actual): ancla final del periodo. No permite futuro.
- `range` ∈ `1M|3M|6M|YTD|1Y`: si `YTD`, `months` se calcula automáticamente (ene..ancla).

`months[].income/expense` = flujo mensual. `assets/debts` = patrimonio a cierre de mes (debts negativo).
Categorías/payees/cuentas son totales del periodo completo, ordenados de mayor a menor.
`credit_cards` incluye resumen de tarjetas de crédito: total del ciclo actual vs anterior, utilización, y top categorías/payees por tarjeta (misma fuente que la web: `CreditCardReportService`).
Misma fuente de datos que `FinanceTrendController::insights` (web), reformateado para mobile.

### 35. Borrar cuenta — `DELETE /api/mobile/account`  (Bearer)
```jsonc
{ "password": "••••••" }   // requerido solo si el usuario tiene contraseña (no social-only)
```
Borra al usuario, sus tokens, foto de perfil, y — si es el único miembro — sus equipos y todos los datos (cuentas, transacciones, presupuesto, etc.). Usa la misma acción que la web (`DeleteUser` + `DeleteTeam`).
→ `{ "success": true }`
Errores:
- `422 { password: ["incorrect"] }` — contraseña incorrecta.
- `422 { message: "You own a team with other members…" }` — debe transferir el equipo primero.

### 36. Login con Google — `POST /api/mobile/auth/google`  (sin auth)
```jsonc
{ "id_token": "<Google id_token>", "device_name": "jesus-iphone-15", "name": "Jesus" }
```
Verifica el `id_token` contra `oauth2.googleapis.com/tokeninfo`. Vincula por `email` a un usuario existente o crea uno nuevo (con equipo personal). `name` es opcional (solo para registro nuevo).
→ `"3|AbCdEf0123456789plainTextTokenHere"` (string plano, igual que `/api/sanctum/token`)
Errores: `401` (token inválido).
Config requerida: `GOOGLE_AUTH_WEB_CLIENT_ID`, `GOOGLE_AUTH_ANDROID_CLIENT_ID`, `GOOGLE_AUTH_IOS_CLIENT_ID` en `.env`.

### 37. Login con Apple — `POST /api/mobile/auth/apple`  (sin auth)
```jsonc
{ "id_token": "<Apple id_token>", "device_name": "jesus-iphone-15", "name": "Jesus" }
```
Verifica el JWT contra Apple JWKS (`appleid.apple.com/auth/keys`). Vincula por `apple_id` (sub) o `email` a usuario existente, o crea uno nuevo. Apple solo envía email la primera vez; puede ser relay privado.
→ `"3|AbCdEf0123456789plainTextTokenHere"` (string plano)
Errores: `401` (token inválido), `422` (email requerido en primer login).
Config requerida: `APPLE_AUTH_BUNDLE_ID` en `.env` (default: `com.neatlancer.neathome`).
Migración: `google_id` y `apple_id` añadidos a tabla `users` (`2026_10_18_001509`).

### 38. Share-to-app (parsear email bancario) — `POST /api/mobile/parse-email`  (Bearer)
```jsonc
{ "from": "alertas@bhd.com.do", "subject": "Alerta de transacción", "body": "<html>...</html>" }
```
El usuario comparte un email bancario desde la bandeja del teléfono a la app. El backend detecta el banco (BHD, APAP, BSC, Qik) y parsea el email con los mismos parsers que usa el sync de Gmail. Si lo reconoce, crea una transacción borrador.
→ `{ "parsed": true, "transaction": { "id": 123, "date": "2026-10-17", "payee": "Supermercado Nacional", "amount": 1500.00, "currency_code": "DOP", "description": "Visa Gold BHD", "status": "draft" } }`
Si el email no es reconocido: `{ "parsed": false, "message": "Could not recognize the bank email format." }`

### 39. Dirección de reenvío de emails — `GET /api/mobile/inbound-email/address`  (Bearer)
Sin body. Devuelve la dirección única del usuario para configurar reenvío automático de emails bancarios.
→ `{ "address": "txns-42-a1b2c3d4@parse.loger.app" }`
El usuario añade esta dirección como regla de reenvío en Gmail/Outlook. Los emails reenviados llegan al webhook §40.
Config requerida: `INBOUND_EMAIL_DOMAIN` en `.env` (default: `parse.loger.app`).

### 40. Webhook de email reenviado — `POST /api/mobile/inbound-email`  (sin auth — usuario en recipient)
Endpoint público que recibe emails de Mailgun/Postmark inbound routing. El usuario se resuelve del recipient (`txns-{userId}-{hmac}@domain`). Acepta los campos estándar de Mailgun:
```jsonc
{ "from": "alertas@bhd.com.do", "recipient": "txns-42-a1b2c3d4@parse.loger.app", "subject": "...", "body-html": "<html>..." }
```
Mismo pipeline de parsing que §38. → `{ "parsed": true, "transaction": {...} }` o `{ "parsed": false, ... }`.
Errores: `404` (recipient desconocido), `422` (faltan from o body).

### 41. Notificaciones — `GET /api/mobile/notifications?filter=unread|all&page=1`  (Bearer)
Lista paginada (20/pág) de notificaciones del usuario. Cada item:
```jsonc
{
  "unread_count": 3,
  "data": [
    {
      "id": "uuid",
      "type": "EntryGenerated",           // class_basename (sin namespace)
      "message": "New transactions have been imported",
      "cta": "Review now",
      "link": "/finance/transactions?filter[status]=draft",
      "data": { "billing_cycle_id": 5 },  // campos extra según el tipo
      "read_at": null,                     // null = no leída
      "created_at": "2026-10-17T14:30:00+00:00"
    }
  ],
  "current_page": 1,
  "last_page": 2
}
```
Tipos posibles: `EntryGenerated`, `BillingCycleCutAlert`, `PlannedAlert`, `OccurrenceAlert`, `TransactionsImported`, `WatchlistThresholdAlert`, `WatchlistStreakAlert`, `WatchlistAutoSuggestionAlert`, `CreditCardRenewalAlert`, `LoanPaymentAlert`, `AccountOverdraftAlert`.

### 42. Marcar notificación leída — `PUT /api/mobile/notifications/{id}/read`  (Bearer)
Sin body. → `{ "success": true }`

### 43. Marcar todas leídas — `PATCH /api/mobile/notifications/read-all`  (Bearer)
Sin body. → `{ "success": true }`

---

## Estado real de `/api/mobile/*` — qué ya está y qué falta

> La lógica de negocio vive en Actions/Services (compartida con el web); los
> endpoints mobile la **reusan** — exponer una pantalla es, en esencia, añadir
> la ruta JSON. La app Expo vive en otro repo (`loger-mobile`). El handoff de
> backend al día (y los pendientes con prioridad) está en
> `.planning/mobile-backend.md` (local, no viaja en git).

### Ya expuesto en `/api/mobile/*` (la app ya lo consume)

**Auth:**
- `POST /api/sanctum/token` — login (2FA)
- `POST /auth/google` — login/registro con Google (sin auth, devuelve token)
- `POST /auth/apple` — login/registro con Apple (sin auth, devuelve token)
- `DELETE /account` — borrar cuenta permanentemente (destructivo, con confirmación de password)

**Tab Hoy:**
- `GET /today` — payload de la página Today (`money, attention, today, upcoming, meal, pending_review_count`)
- `GET /overview` — cuentas (agrupables por `detail_type`), `netWorth`, próximos pagos, tarjetas a pagar
- `GET /occurrences` — lista de ocurrencias con `days_since_last`, `is_overdue`, `avg_days_passed`
- `POST /occurrences/{id}/instances` — registrar "pasó ahora"
- `DELETE /occurrences/{id}/instances` — deshacer última ocurrencia

**Tab Agenda:**
- `GET /calendar?month=YYYY-MM` — agenda (Planner + ciclos + occurrences)
- `GET /routine` — rutina semanal (bloques de tiempo, actual + siguiente)
- `POST /events` · `PATCH /events/{planner}/complete` — crear/completar evento

**Tab Comida:**
- `GET /meals?start=YYYY-MM-DD&end=YYYY-MM-DD` — plan de comidas de la semana + meal types
- `GET /meals/recipes` — todas las recetas del team
- `GET /shopping?plan=ID` — lista de compras activa (stages + items con estado pending/buy/skip)
- `POST /shopping/{plan}/items` — agregar item a la lista
- `POST /shopping/{plan}/items/{item}/cycle` — ciclar estado (pending→buy→skip)

**Tab Tareas:**
- `GET /chores?done=today|all` — tareas del hogar (plan CHORES con `PlanItemResource`) + members
- `PATCH /chores/{item}/toggle` — marcar hecha/deshacer (respeta recurrencia)
- `POST /chores` — crear tarea (title, owner, recurrence)

**Tab Dinero:**
- `GET /budget?month=YYYY-MM` — grupos + categorías (`budgeted/activity/available`), `ready_to_assign`
- `POST /budget/assign` — fijar el asignado del mes de una categoría
- `POST /budget/move` — mover dinero entre categorías
- `GET /transactions` · `POST /transactions` — listar (paginado) + quick-add
- `POST /transfers` — transferencia entre cuentas
- `POST /transactions/{id}/approve` — aprobar borrador (draft → verified)
- `DELETE /transactions/{id}` — borrar transacción
- `POST /transactions/bulk-delete` — borrar en lote
- `GET /insights?months=6&end=YYYY-MM` — tendencias (gasto/ingreso/patrimonio por mes, desglose por categoría/payee/cuenta)

**Notificaciones:**
- `GET /notifications?filter=unread|all` — lista paginada con `unread_count`, tipo, mensaje, link, datos extra
- `PUT /notifications/{id}/read` — marcar una como leída
- `PATCH /notifications/read-all` — marcar todas como leídas

### Aún NO expuesto (el web sí lo tiene; falta endpoint mobile)
- **Transacciones: editar / recategorizar / mark-as-paid** (aprobar/borrar ya expuestos).
- **Conciliación** — hub, detalle, ajustar, sync.
- **Cuentas (escritura)** — crear/editar, link-bank, sync-emails, cerrar, import PDF/CSV.
- **Comida (escritura)** — crear receta, agregar al plan.
- **Integraciones** (Gmail full sync), **metas / fondo de emergencia / watchlists**, **ajustes / perfiles**.

**Parseo de emails bancarios (alternativa a Gmail sync):**
- `POST /parse-email` — share-to-app: parsea email bancario compartido desde el teléfono
- `GET /inbound-email/address` — dirección única del usuario para reenvío automático
- `POST /inbound-email` — webhook de email reenviado (público, user en recipient)
- **Búsqueda de payees** (`GET /api/payees?search=`, backlog).

**Sesión & perfil:**
- `GET /me` — perfil del usuario (`id, name, email, profile_photo_url, has_password, auth_provider, language, current_team_id`)
- `DELETE /session` — logout (revoca el token actual)
- Token expiration: **90 días** (configurable via `SANCTUM_TOKEN_EXPIRATION` env)
- `DELETE /account` — ahora requiere re-verificación: `password` para cuentas con contraseña, `{provider, id_token}` para social-only
