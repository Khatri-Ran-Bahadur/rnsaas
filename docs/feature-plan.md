# SathiSaaS feature plan

Last updated: 13 September 2026

This is the live build checklist: what already ships, what was just hardened, and what still needs to be built before production. Pick work from **Next (P0 / P1)** first; later items are planned, not in progress.

## Status key

| Status | Meaning |
| --- | --- |
| Done | Usable in the current codebase |
| Partial | UI or data exists, but not production-complete |
| Missing | Not built or not safe to sell yet |

## What is done

### Platform / tenancy
- Multi-tenant orgs, memberships, invitations, roles/permissions, branch/department/staff
- SuperAdmin impersonation with audit log
- Subscription plans, bank-transfer approval, tenant subscription gate
- Organization login at `/admin/login`, Fortify auth, 2FA/passkeys via Fortify where configured
- Shared UI kit: `Select`, `DatePicker`, `DataTable`, `MediaPicker`, charts

### Accounting
- Chart of accounts, journals, sales invoices, purchase bills
- Customer/vendor payments, P&amp;L, balance sheet, trial balance, GL, aging
- Invoice/bill create-edit screens using shared `Select` + `DatePicker`

### HRM / Payroll
- Employees, attendance, leave, overtime, holidays, documents
- Payroll groups/runs/reports (core flows)
- HRM already uses shared `DatePicker`

### Inventory / POS / MRP / Tax
- Inventory items, categories, units, locations, stock ledger/adjustment/transfer UI
- POS register, shifts, tables, kitchen tickets, orders
- MRP BOM, work orders, quality, planning, settings screens
- Tax rates, types, categories, reports, settings

### Admin dashboard
- Executive tiles for accounting, POS, inventory, and operations
- Missing module tables no longer crash login (schema guard)
- Module Inertia pages no longer 500 after login because Vite looked for `resources/js/pages/Admin/Dashboard.vue`

## What was completed in this pass (13 Sep 2026)

| Item | Why |
| --- | --- |
| Vite root template only preloads **core** pages | Module pages (`Admin/*`, `Inventory/*`, …) live under `Modules/*/resources/js` and are loaded via `app.ts` glob. Passing them to `@vite` caused HTTP 500 after admin login. |
| Dashboard queries skip missing tables | New POS/Inventory/Accounting tables must not take down login if a migrate has not run yet. |
| Tenant ID fallback `?? 1` removed | Inventory/POS/MRP/Tax/Payroll no longer silently read tenant 1 when tenancy is unresolved. |
| SMTP password not sent to the browser; stored encrypted | Company settings was leaking mail passwords in Inertia props. |
| Products / stock screens use shared `Select` + `DatePicker` | Inventory items index, identification, stock ledger, transfer, and adjustment now match HRM/Accounting. |

**After deploy:** run `php artisan migrate --force` and `npm run build` (or keep `npm run dev` running). Login 500 will persist until the Vite build is refreshed.

## Security: done vs missing

| Area | Status | Notes |
| --- | --- | --- |
| CSRF, session, Fortify login throttle | Done | Keep enabled; do not disable CSRF to “fix” tokens |
| Tenant isolation on org admin routes | Partial | Middleware resolves tenant; controllers now abort if none. POS still has `user_id ?? 1` style fallbacks to clean up |
| Policies on every write | Partial | HRM/Accounting stronger; Inventory/POS/MRP mostly auth-only |
| Mass assignment | Partial | New models use `Fillable`; audit unguarded models |
| SMTP / secrets | Partial | Tenant SMTP password encrypted. Rotate any password that was saved in plain JSON before this change |
| File uploads | Partial | Media module exists; keep MIME + size validation on every new upload |
| Rate limits on POS/API | Missing | Add limiters on POS checkout and invitation/email test |
| 2FA enforced for org admins | Missing | Fortify supports it; make it policy for live tenants |
| Backup / encryption at rest | Missing | Hosting/DB backups, not in-app |
| Dependency audit in CI | Missing | Run `composer audit` in pipeline |
| Cross-tenant 404 (not 403) on IDs | Missing | Prevent ID enumeration |

## Feature gaps by module (pick order)

### P0 — must ship before calling this live

1. **Run migrations + Vite build on the server** after every release.
2. **POS checkout persistence** — confirm orders, payments, stock decrement, and accounting journal all commit in one DB transaction; no demo/hardcoded cart leftovers.
3. **Inventory stock transfer persist** — transfer screen posted to a real endpoint that moves qty between locations (adjustment exists; transfer may still be UI-only).
4. **Authorization gates** on inventory/POS/MRP writes (`inventory.manage`, `pos.operate`, etc.).
5. **Backup + monitoring** — failed job alerts, 5xx log drain.

### P1 — next sprint (select these)

| ID | Feature | Module | Effort |
| --- | --- | --- | --- |
| F-01 | Location-level stock (qty per warehouse, not only item `on_hand_stock`) | Inventory | M |
| F-02 | Item barcode/SKU unique per tenant + POS scan | Inventory/POS | S |
| F-03 | Invoice → stock out / bill → stock in | Accounting/Inventory | M |
| F-04 | POS receipt print + reprint + void with reason | POS | M |
| F-05 | Shift open/close cash count vs system | POS | M |
| F-06 | Journal reverse / period lock | Accounting | M |
| F-07 | Tenant 2FA enforcement | Admin | S |
| F-08 | Activity log on inventory qty changes | Audit | S |

### P2 — later (do not start until P0/P1 are green)

- Lot/batch and expiry for F&amp;B/pharma
- Serial numbers
- Recipe/BOM explosion from inventory item (MRP already has BOM screens)
- Multi-currency documents
- Customer display / KDS hardware pairing
- Helpdesk tickets (company-settings already has notification toggles for tickets that do not exist)
- Mobile apps
- Ecommerce storefront

## Shared components — usage rule

Use `@/components` `Select` and `DatePicker` on every new filter or form. Do not add native `<select>` or `type="date"` on product/inventory screens.

| Screen | Select | DatePicker |
| --- | --- | --- |
| Accounting invoices/bills/reports | Done | Done |
| HRM leave/overtime/holidays | Done | Done |
| Inventory items list + identification | Done (this pass) | N/A on list |
| Stock adjustment / transfer | Done (this pass) | Done (this pass) |
| Stock ledger filters | Done (this pass) | Missing (add from/to dates when ledger is date-filtered) |
| Categories/units/locations modals | Still native `<select>` | N/A |
| POS / MRP forms | Mixed | Mixed |

## Suggested 4-week sequence

| Week | Select |
| --- | --- |
| 1 | P0 ops (migrate, build), POS transaction integrity, transfer persist, policies |
| 2 | F-01 location stock, F-03 invoice/bill stock, F-08 qty audit |
| 3 | F-04/F-05 POS receipt + shifts, F-06 period lock |
| 4 | F-07 2FA, rate limits, `composer audit`, remaining native selects |

Do not open a new module (ecommerce, helpdesk, mobile) until week 1–2 P0 items are done. That keeps Cursor/agent time on live-risk work instead of new surface area.
