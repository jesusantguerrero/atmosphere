# Loger — Family OS Structure

Loger is a **Family Operating System**: one app to run the financial, logistical, and relational machinery of a household. It's organized around **5 pillars**, each enabled per team via the `core_modules` registry and `loger.concerns:<alias>` middleware.

---

## 💵 Finance — *mostly built*

The most mature pillar today. YNAB-style envelope budgeting, multi-currency, and net worth tracking.

**Built:**
- Monthly Budget (envelope/zero-based) — `app/Domains/Budget`
- Accounts & Transactions, multi-currency (per-profile base currency, USD native + base column) — `app/Models/Account.php`, `app/Domains/Transaction`
- Bank statement import (PDF, BHD)
- Account reconciliation
- Emergency funds & savings goals
- Net Worth tracking & trend dashboards (`/trends/net-worth`, year-summary, income-vs-expenses)
- Credit card management (capacity/utilization-focused, not APR)
- Watchlists (`Modules/Watchlist`, with target-price tracking)
- Bill cycles / linked transactions

**Gaps / polish:**
- Bug-fix and UX polish per the v2.0 stable line
- Marketing/use-case content for engagement (v2.x → v2.5)

---

## 🍽️ Food — *renamed from Meal Planner; mostly built*

**Built (keep):**
- Recipes & Ingredients — `app/Domains/Meal/Models/{Meal,Ingredient,MealType,Product}.php`
- Weekly Meal Planner — `MealPlan` model, `/meal-planner` routes
- Random Meal Generator — `/meals-random`
- Shopping Lists with shareable public links — `MealShoppingListController`, `SharedShoppingListController`
- **Reusable weekly menus** — `MealMenu` already supports `is_template` and `duplicate(targetStartDate)` (auto-shifts dates)
- Cost-per-recipe foundation: `Ingredient` links to `Insane\Journal\Models\Product\Product`, which has pricing in the journal package

**New work:**
- Pantry / inventory tracking (current stock per ingredient/product)
- Expiration tracking (notify before items expire)
- Favorite meals surface (`is_liked` exists on `Planner` and `Meal` — needs a dedicated UI)
- Cost-per-recipe rollup (sum ingredient × product price → recipe cost; aggregate to weekly menu cost)
- Polish reusable-menu UX (template gallery, "load this menu starting Monday")

---

## 🏡 Home — *renamed from Housing; partial*

**Built (keep):**
- Chores — `Pages/Housing/Chores.vue`
- Occurrence Checks (track "last time X happened" with avg/last-duration reminders) — `app/Domains/Housing/Models/Occurrence.php`
- Equipment tracking — `Pages/Housing/Equipment.vue`
- Plans (events / repairs / activities) — backed by the `Modules/Plan` package (PlanType, PlanStage, PlanItem, PlanTemplate)

**New work:**
- Maintenance schedule (recurring services: HVAC, fumigation, water tank cleaning) — likely a `Planner`/`Occurrence` hybrid
- Warranty docs (file/image attachments tied to Equipment)
- Service provider contacts (plumber, electrician, AC tech) — could share infrastructure with Family member profiles
- Home inventory (rooms → items, value, photos) — extends Equipment
- Recurring bills / utilities — link `Occurrence` + `BillingCycle` (`app/Domains/Transaction/Models/BillingCycle.php`) for water/power/internet etc.

---

## 👨‍👩‍👧 Family — *renamed from Relationships; mostly stub*

**Built (keep):**
- Profiles concept — `app/Domains/LogerProfile` (`LogerProfile`, `LogerProfileEntity`) is the per-member profile system used to scope finance data; it can serve as the family-member registry
- Activity tracking infrastructure — `Planner` morphs to anything via `dateable`, and supports completion tracking (`completed_at`, `completed_by`, `completion_notes`, status enum)
- Reminders infrastructure — `Occurrence` + `OccurrenceAutomation` already wires reminders

**Stub:**
- `/relationships` page is "Coming soon" — `Pages/Relationships/Index.vue` is a placeholder; `RelationshipController` returns the empty page
- `CoupleSupportMock.vue` exists as a UI sketch only

**New work:**
- Real family-member profiles UI (extend `LogerProfileEntity`)
- Important dates (birthdays, anniversaries) — feeds Calendar
- Health notes (allergies, medications, doctor visits)
- School / work activities log
- Personal preferences (foods they hate, sizes, gift ideas)
- One-on-one time reminders (use `Occurrence` engine — "haven't had a date with partner in N days")
- Responsibilities per member (chore/task assignment — extend Chores with `assigned_to`)

---

## 📅 Calendar & Routines — *NEW core module*

The integrating layer. Today there is **no calendar module** — just Google Calendar mentioned in `Integration/Actions/*` for sending events out. The pieces to assemble it already exist:

- `Planner` (polymorphic `dateable`, frequency, status) — perfect base for routines/appointments/events
- `Occurrence` — already does recurring reminders
- `core_modules` middleware — register a new alias (e.g. `loger.concerns:calendar`)
- `MealPlan`, chore plans, `Plan` items all have dates → unify them into one timeline

**New work:**
- Unified weekly schedule view (aggregates meal plans, chores, occurrence reminders, family events, bills due)
- Appointments (doctor, school, work) — likely a new `Plan` type or a thin model
- Recurring routines (morning/evening checklists)
- Shared family agenda — visible to all team members
- Two-way Google Calendar sync (read in + write out; today only write-out exists)
- Reminders dispatcher (push notifications, email, WhatsApp via existing `Integration/Actions/Whatsapp`)

---

## Cross-cutting infrastructure (already in place)

- **Modules system**: `core_modules` table + `EnsureCoreModulePermissions` middleware + `modules_statuses.json` for nanoModules (Plan, Watchlist). New pillars register here.
- **Multi-currency**: per-profile base currency, USD-native + DOP-converted columns. Extend to all pillars (e.g. recipe cost in DOP).
- **Polymorphic dating**: `Planner.dateable_type` already lets any model be scheduled. Calendar reuses this.
- **Polymorphic completion**: `Planner.completed_resource_type` lets a chore be marked done by a transaction, a meal by a meal-plan entry, etc.
- **Automation/Integrations**: Google Calendar, Gmail, WhatsApp wiring exists in `app/Domains/Integration` — Calendar pillar plugs in here.

---

## Strategic ordering (per project memory)

1. Kill bugs in Finance (v2.0 stable)
2. Polish/exploit Finance + Food + Home (v2.x marketing line)
3. Engagement use-cases on existing features (v2.5)
4. **Then** new pillars: Family build-out, Calendar & Routines (v3.0+)

Modules marketplace lives in v3.0, not before.
