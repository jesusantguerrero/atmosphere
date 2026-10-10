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

**`POST /api/mobile/transactions`** — quick-add (reusa MultiCurrency@store):
```jsonc
body: {
  "account_id": int (req),
  "total": number > 0 (req),
  "currency_code": "DOP" (req, size 3),
  "description": string (req),
  "date": "YYYY-MM-DD" (req),
  "direction": "credit" | "debit" (req),
  "category_id": int?, "payee_id": int?, "counter_account_id": int?,
  "status": "draft" | "verified"?   // default verified
}
-> { success:true, data: <MultiCurrencyTransactionResource> }
```
> Semántica de `direction`: en Loger `DIRECTION_DEBIT='DEPOSIT'` (entrada,
> type 1) y `DIRECTION_CREDIT='WITHDRAW'` (salida, type -1). Para un gasto
> normal: `direction=credit`. Confirmar el mapeo exacto al construir el form.
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
