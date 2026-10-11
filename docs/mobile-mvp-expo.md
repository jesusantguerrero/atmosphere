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
  "attention": [{ "id","message","cta","link" }],          // alertas de watchlist sin leer (mes actual)
  "today":     [{ "kind": "planner"|"relationship", "id","name","subtitle","status","total"? }],
  "upcoming":  [{ "kind": "billing_cycle"|"utility"|"planner", "id","name","account_id","total",
                  "due_at": "YYYY-MM-DD", "days_until": int }],   // days_until < 0 = vencido
  "meal":      [{ "id","meal_id","name","meal_type","is_liked","date","day_label" }]  // semana en curso
}
```
`money.currency_code` puede venir `null`: usar la moneda de las cuentas de `/api/mobile/overview`.

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
      "balance_type": "debit",  "currency_code": "DOP",
      "account_detail_type_id": 2, "credit_closing_day": null, "type": 1 },
    { "id": 1735, "name": "Qik Visa Clásica", "current_balance": -20056.92,
      "balance_type": "credit", "currency_code": "DOP",
      "account_detail_type_id": 6, "credit_closing_day": 25, "type": -1 }
  ],
  "netWorth": { "assets": 1400000.00, "debts": -1034000.00, "net": 366000.00 },
  "nextPayments": [ /* items = /api/next-payments.data, ver §9 */ ],
  "cardsToPay":   [ /* subset de nextPayments con type == "credit_card_payment" */ ]
}
```
`balance_type`: `debit`=activo, `credit`=pasivo. `type`: 1=activo, -1=pasivo.
`credit_closing_day` solo en tarjetas. `net = assets + debts` (debts viene negativo).

### 3b. Hoy — `GET /api/mobile/today`  (Bearer)
```jsonc
{
  "money": { "today_spent": 1850.0, "daily_remaining": 2410.5, "month_remaining": 62673.0,
             "days_in_month_left": 26, "currency_code": null },
  "attention": [
    { "id": "9f1c…", "message": "Comida alcanzó el 90% del presupuesto", "cta": "Ver", "link": "/budgets" }
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
  ]
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
