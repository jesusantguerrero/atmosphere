# Loger — Punchlist

Ítems **abiertos** extraídos de los dos reportes de QA (*Bug de primera cuenta + PDF* y *Verificación final*). Lo que en esos reportes salió ✅ verde se omite (ya está resuelto). Los arreglos de esta sesión se rastrean en git, no aquí.

---

## 🔴 P0 — Bloqueadores

- [x] **Crear primera cuenta: `/finance/accounts/create` → 500.** _(fix en `26dadd14`: el controlador no tenía `create()`; ahora redirige a `/finance/transactions?newAccount=1` y el modal se auto-abre. Pendiente verificar en vivo cuando el server local esté arriba / en deploy.)_ Un usuario nuevo no puede crear su primera cuenta desde la UI. Es también el **paso 1 del onboarding ("Agregar cuentas")**. Rompe toda la cadena de finanzas: sin cuenta no hay presupuesto (ZBB), ni import, ni transacciones. **Sin salida alterna:** el modal de transacción ofrece `Create:` para el Beneficiario pero **no** para la Cuenta.
- [x] **`/meals/recipes` (URL directa) → 500.** _(fix en el commit de rutas: caía en `/meals/{meal}` show con `int $id="recipes"` → TypeError. Ahora `{meal}` está constreñido a numérico (`whereNumber`), así `/meals/recipes` y cualquier id no numérico dan **404 limpio** en vez de 500. Requiere `route:clear` en deploy si hay cache de rutas.)_

## 🧩 Huecos funcionales

- [ ] **Meal → Lista de compras (E2E) no se puede sembrar en un espacio vacío.** La pestaña **Ingredientes está en blanco, sin botón de agregar**; el select de ingrediente en la receta dice "Sin datos" y **no ofrece crear** → "Lo que necesitarás" queda vacío → no hay nada que empujar a la lista. El mecanismo ("Add to shopping list") existe, pero la tubería no arranca.
- [ ] **Inconsistencia de `Create:` en los selects.** El de **Beneficiario** permite crear al vuelo; los de **Cuenta** e **Ingrediente** no → no se puede sembrar desde su propio campo.

## 🌐 i18n (español)

- [ ] **Formulario "Create recipe" completo en inglés:** Title / Ingredients / Qty / Unit / Actions / Add ingredient / Tags / Link / Dish / Save.
- [ ] **Sueltos en inglés:** "New Meal", "Recipes", "No accounts found", "No accounts yet", "Add your first account", "Add to shopping list", "LOGGED", "Import / Export CSV / Export PDF".

## ⏳ Pendientes de verificar (necesitan input externo, no son bugs)

- [ ] **Parser de PDF de estado de cuenta.** Bloqueado por el 500 de crear cuenta (o usar un espacio que ya tenga cuenta). PDF de prueba listo para comparar fila por fila: **BHD ahorros, 28 movimientos, 01–31 marzo 2026** (02/03 → 31/03). *Nota:* el "Import" que shipeó en Transacciones es un importador **CSV** (Budget / Transactions / Occurrence Checks, en inglés) — **no** es el parser de PDF de estado de cuenta.
- [ ] **Notificaciones (disparadores).** Generar un bill / sobregiro y esperar el aviso para verificarlo.
