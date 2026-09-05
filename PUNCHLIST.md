# Loger — Punchlist

Ítems **abiertos** extraídos de los dos reportes de QA (*Bug de primera cuenta + PDF* y *Verificación final*). Lo que en esos reportes salió ✅ verde se omite (ya está resuelto). Los arreglos de esta sesión se rastrean en git, no aquí.

---

## 🔴 P0 — Bloqueadores

- [x] **Crear primera cuenta: `/finance/accounts/create` → 500.** _(fix en `26dadd14`: el controlador no tenía `create()`; ahora redirige a `/finance/transactions?newAccount=1` y el modal se auto-abre. Pendiente verificar en vivo cuando el server local esté arriba / en deploy.)_ Un usuario nuevo no puede crear su primera cuenta desde la UI. Es también el **paso 1 del onboarding ("Agregar cuentas")**. Rompe toda la cadena de finanzas: sin cuenta no hay presupuesto (ZBB), ni import, ni transacciones. **Sin salida alterna:** el modal de transacción ofrece `Create:` para el Beneficiario pero **no** para la Cuenta.
- [x] **`/meals/recipes` (URL directa) → 500.** _(fix en el commit de rutas: caía en `/meals/{meal}` show con `int $id="recipes"` → TypeError. Ahora `{meal}` está constreñido a numérico (`whereNumber`), así `/meals/recipes` y cualquier id no numérico dan **404 limpio** en vez de 500. Requiere `route:clear` en deploy si hay cache de rutas.)_

## 🧩 Huecos funcionales

- [x] **Meal → Lista de compras (E2E): sembrar ingredientes.** _(fix: la pestaña Ingredientes ahora tiene un "add ingredient" inline (header + empty state) que crea vía `/api/ingredients` y recarga; ya no queda en blanco. El select de la receta usa modo `tag` (permite escribir un nombre nuevo), y con ingredientes sembrados deja de decir "Sin datos".)_
- [ ] **Inconsistencia de `Create:` en los selects.** El de **Beneficiario** permite crear al vuelo; los de **Cuenta** e **Ingrediente** no → no se puede sembrar desde su propio campo.

## 🌐 i18n (español)

- [x] **Formulario "Create recipe" en español** _(MealForm/MealFormLine/Create: Title/Ingredients/Tags/Link/Dish/Time/Qty/Name/Unit/Actions/Add ingredient/Create recipe/Save/Update/"and keep" ahora vía `$t`.)_
- [x] **Sueltos en inglés** _(New Meal, Recipes, Add to shopping list, "Ingredients this week", Import/Export CSV/Export PDF ahora vía `$t`; "Logged" y "No accounts found." ya usaban `$t`, solo faltaba la traducción — agregada. "Add your first account"/"No accounts yet" viven en `AccountsLedger` y ya usan `$t`.)_

## ⏳ Pendientes de verificar (necesitan input externo, no son bugs)

- [ ] **Parser de PDF de estado de cuenta.** Bloqueado por el 500 de crear cuenta (o usar un espacio que ya tenga cuenta). PDF de prueba listo para comparar fila por fila: **BHD ahorros, 28 movimientos, 01–31 marzo 2026** (02/03 → 31/03). *Nota:* el "Import" que shipeó en Transacciones es un importador **CSV** (Budget / Transactions / Occurrence Checks, en inglés) — **no** es el parser de PDF de estado de cuenta.
- [ ] **Notificaciones (disparadores).** Generar un bill / sobregiro y esperar el aviso para verificarlo.
