
## Session 4 - 10 System Enhancement Requirements

### 1. OTP Expiry Changed to 1 Minute
- **config/config.php**: `OTP_EXPIRY_SECONDS` changed from `300` (5 min) to `60` (1 min)
- **api/auth.php**: Email text body and HTML template updated from "5 minutes" to "1 minute"

### 2. Report Export: PDF & CSV Format Options
- **api/reports.php**: Added `exportToCsv()` (UTF-8 BOM, proper escaping), `exportToPdf()` (print-friendly HTML with Save as PDF button), and `handleExport()` dispatcher. All 6 report types now support `?export=pdf`, `?export=csv`, and `?export=excel`
- **js/reports.js**: Single export button replaced with dropdown menu offering PDF, CSV, and Excel options

### 3. Item Verification Report
- **api/reports.php**: New `item_verification` action — searches both `assets` and `supplies` tables by code or name, returns existence confirmation with full item details (type, code, name, category, status, location, monetary value)
- **js/reports.js**: New `renderItemVerificationReport()` with search input, `searchItemVerification()` with green/red verification badges and results table
- **reports.php**: Added "Item Verification" button to report type tabs

### 4. Dashboard Graphs Clickable with Module Redirects
- **js/dashboard.js**: All 5 Chart.js charts now have `onClick` handlers:
  - Asset Status (doughnut) → `asset-registry.php`
  - Supply Stock Levels (bar) → `supplies-inventory.php`
  - Supply Categories (pie) → `supplies-inventory.php`
  - Asset Categories (pie) → `asset-registry.php`
  - Monthly Transactions (line) → `supplies-inventory.php`
  - Added `cursor: pointer` and tooltip hints ("Click to view...")
- **components/dashboard.php**: Stat cards (Total Items, Available, Maintenance, Damaged) wrapped in `<a>` tags linking to their respective module pages

### 5. Monetary Value Display on All Items
- **supplies-inventory.php**: Added "Total Value" column header and data cell showing `₱` formatted `total_value` (stock × unit_cost) alongside existing Unit Cost column
- **components/supplies-inventory.php**: Added matching "Total Value" table header
- Assets already display `current_value` in `js/asset_management.js`; supplies already show `unit_cost` — both confirmed working

### 6. Equipment Consumable Records
- **sql/add_equipment_consumables.sql**: New migration — `equipment_consumables` table with `asset_id`, `supply_id`, `quantity_per_use`, `notes`, unique constraint on (asset_id, supply_id)
- **api/assets.php**: Three new actions — `get_consumables` (JOIN with supplies), `add_consumable` (POST, 409 on duplicate), `remove_consumable` (DELETE). Auto-creates table via `ensureConsumablesTable()`
- **js/detail_handlers.js**: Asset detail modal now shows "Linked Consumables" section with add/remove functionality, supply dropdown, quantity input

### 7. Custodian Assignment Department Filter
- **api/custodian_assignments.php**: New `get_departments` action returning distinct departments. `getCustodians()`, `getAssignments()`, and `getAssignmentRequests()` now accept `?department=` query parameter for server-side filtering
- **custodian-assignment.php**: Department filter dropdown added to toolbar (custodian/admin view only)
- **js/assignment_requests.js**: `loadDepartments()` populates dropdown, change event triggers list reload with department filter

### 8. Purchase Order Deletion Restriction
- **api/purchase_orders.php**: Delete now requires admin role (HTTP 403 otherwise), `confirmation_code` matching PO number, and performs soft-delete (`archived_at` timestamp) instead of hard delete. Auto-adds `archived_at` column. All list/detail queries exclude archived records
- **js/purchase_orders.js**: Delete button only visible to admin users. Two-step confirmation modal requires typing the PO number. `injectDeletePOModal()` creates the modal on page load

### 9. Technician Specialization Categories
- **api/maintenance.php**: Added 6 new technician seeds (Plumbing, Carpentry, General Maintenance). `getTechnicians()` returns `specialization` alias. `getMaintenanceList()` returns `assigned_specialization`. New `get_specializations` action
- **js/maintenance.js**: Technician dropdowns show "Name (Specialization)". Color-coded specialization badges in task list (Electrical=yellow, HVAC=blue, IT/Networking=purple, Plumbing=cyan, Carpentry=amber, General=gray)
- **maintenance.php**: Added Specialization column to table header

### 10. AI Forecasting Data Analytics & Critical Alerts
- **api/forecasting.php**: `getForecastOverview()` enhanced with `analytics` object (trend_direction, percentage_change, confidence_score). `getForecastAlerts()` now includes severity_level, recommended_action, impact, projected_runout_days. New `analytics_summary` action with top consuming supplies, cost trend analysis, savings opportunities, and turnover rates
- **js/api.js**: Added `getForecastAnalytics()` method
- **js/forecasting.js**: New `renderCriticalAlertsBanner()` (red banner for critical alerts at top), `renderAnalyticsSection()` (four-card grid: top consumers, cost trends, savings, turnover rates), `renderTrendIndicator()` helper
- **forecasting.php**: Added `#forecastCriticalAlertsBanner` and `#forecastAnalyticsSection` containers with CSS styles
- **api/reports.php**: New `critical_alerts` action returning low stock supplies, overdue maintenance, and damaged asset alerts with severity levels
- **js/reports.js**: Overview report now loads and displays critical alerts banner with color-coded severity badges (critical=red, warning=amber, info=blue)

### Database Changes (All Auto-Created — No Manual Migration Required)
- `equipment_consumables` table — auto-created by `ensureConsumablesTable()` on first use
- `purchase_orders.archived_at` column — auto-added via `ALTER TABLE` on first page load
- New maintenance technician users — auto-seeded on first maintenance API call

---

## Session 3 - Comprehensive Error Audit

### ROOT CAUSE FIX
- **config/config.php**: Changed `display_errors` from `1` to `0` — this was the **master cause** of ALL "Unexpected token '<'" JSON parse errors. Every API file loads config.php via database.php, and config.php was overriding all individual API `display_errors=0` settings back to `1`.

### Missing Tables Auto-Created
- **purchase_orders** + **purchase_order_items**: Missing from both SQL dumps, added to `api/purchase_orders.php` and migration
- **vendors**: Missing from newer SQL dump, added to `api/vendors.php`
- **custodians**, **property_assignments**, **assignment_requests**, **assignment_history**, **custodian_transfers**, **assignment_maintenance_links**: Added to `api/custodian_assignments.php`
- **property_issuances**, **property_audits**, **audit_findings**, **system_logs**: Added to migration SQL

### Global Exception Handlers Added (11 API files)
All API files now have `set_exception_handler()` + `set_error_handler()` that convert any uncaught PHP error into a JSON response instead of HTML:
- assets.php, asset_categories.php, asset_tags.php, assets_simple.php
- damaged_items.php, dashboard.php, forecasting.php
- maintenance.php, procurement.php, property_audit.php
- property_issuance.php, purchase_orders.php, reports.php
- supplies.php, users.php, vendors.php, waste_management.php, auth.php

### Display Errors Disabled Across All Files
- config/config.php: `display_errors=0` (THE FIX)
- login.php: `display_errors=0`
- All 14 API files that were missing it: auth.php, procurement.php, purchase_orders.php, supplies.php, users.php, vendors.php, etc.

### API Resilience Improvements
- **api/assets.php**: Queries now check if `asset_categories`, `asset_tags`, `asset_tag_relationships` tables exist before JOINing; handles missing `archived_at` column
- **api/custodian_assignments.php**: All GET functions wrapped in try-catch returning empty arrays on failure
- **api/purchase_orders.php**: Auto-creates tables on first request
- **api/vendors.php**: Auto-creates vendors table on first request

### Migration SQL Updated
- `sql/migration_system_fixes.sql` now creates ALL tables referenced by ANY API file
