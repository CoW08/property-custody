-- Supplies Inventory Reset using historical data (2026-02-06)
-- Objective:
-- - Archive all current live supplies (clean slate)
-- - Insert exactly 2 items per storage location for each department:
--   Clinic, Library, OSAS, School Event
-- - Ensure location values match allowed storage locations used by the API:
--   'Clinic Storage', 'Library Storage', 'Event Storage', 'OSAS Storage'
-- - Only new items appear in the live inventory (archived items are excluded)

START TRANSACTION;

-- Ensure archive columns exist (if schema is missing them)
ALTER TABLE supplies
  ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS archived_by INT NULL,
  ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS archive_notes TEXT NULL;

-- Archive all existing live supplies
UPDATE supplies
SET
  archived_at = NOW(),
  archived_by = 1,
  archive_reason = 'Inventory reset (historical import)',
  archive_notes = 'Reset on 2026-02-06'
WHERE archived_at IS NULL;

-- Insert new historical items
-- Library (4 items -> Library Storage)
INSERT INTO supplies (
  id, item_code, name, description, category, unit,
  current_stock, minimum_stock, unit_cost, total_value,
  location, status, created_at, updated_at
) VALUES
(1, 'LIB-LOUNGE', 'Library Lounge Chairs',
 'Comfort seating reserved for the library space.', 'library', 'pcs',
 2, 1, 4200.00, 8400.00,
 'Library Storage', 'active', NOW(), NOW()),
(2, 'LIB-PROJ', 'Library Mini Projectors',
 'Portable projectors assigned to the library.', 'library', 'units',
 2, 1, 8700.00, 17400.00,
 'Library Storage', 'active', NOW(), NOW()),
(11, 'LIB-TABLE', 'Library Study Tables',
 'Study tables for library reading areas.', 'library', 'pcs',
 6, 2, 3500.00, 21000.00,
 'Library Storage', 'active', NOW(), NOW()),
(12, 'LIB-CHAIR', 'Library Study Chairs',
 'Chairs paired with library study tables.', 'library', 'pcs',
 24, 6, 1200.00, 28800.00,
 'Library Storage', 'active', NOW(), NOW());

-- Clinic (4 items -> Clinic Storage)
INSERT INTO supplies (
  id, item_code, name, description, category, unit,
  current_stock, minimum_stock, unit_cost, total_value,
  location, status, created_at, updated_at
) VALUES
(3, 'CLN-COT', 'Clinic Recovery Cots',
 'Lightweight cots stationed in the clinic.', 'clinic', 'pcs',
 2, 1, 9800.00, 19600.00,
 'Clinic Storage', 'active', NOW(), NOW()),
(4, 'CLN-MON', 'Clinic Vital Monitors',
 'Portable monitors dedicated to clinic staff.', 'clinic', 'units',
 2, 1, 15500.00, 31000.00,
 'Clinic Storage', 'active', NOW(), NOW()),
(9, 'CLN-GAUZE', 'Gauze Pads',
 'Sterile gauze pads for wound dressing.', 'clinic', 'boxes',
 50, 10, 120.00, 6000.00,
 'Clinic Storage', 'active', NOW(), NOW()),
(10, 'CLN-TAPE', 'Medical Tape',
 'Adhesive medical tape for securing dressings.', 'clinic', 'rolls',
 40, 10, 80.00, 3200.00,
 'Clinic Storage', 'active', NOW(), NOW());

-- OSAS (2 items -> OSAS Storage)
INSERT INTO supplies (
  id, item_code, name, description, category, unit,
  current_stock, minimum_stock, unit_cost, total_value,
  location, status, created_at, updated_at
) VALUES
(5, 'OSAS-LANY', 'OSAS ID Card Lanyards',
 'Student ID lanyards for issuance and events.', 'osas', 'pack',
 2, 1, 35.00, 70.00,
 'OSAS Storage', 'active', NOW(), NOW()),
(6, 'OSAS-FORM', 'OSAS Permit Forms',
 'Printed forms for student activity permits.', 'osas', 'ream',
 2, 1, 250.00, 500.00,
 'OSAS Storage', 'active', NOW(), NOW());

-- School Event (2 items -> Event Storage)
INSERT INTO supplies (
  id, item_code, name, description, category, unit,
  current_stock, minimum_stock, unit_cost, total_value,
  location, status, created_at, updated_at
) VALUES
(7, 'EVT-KIT', 'Event Kits',
 'Prepared kits containing standard event materials.', 'event', 'kits',
 2, 1, 550.00, 1100.00,
 'Event Storage', 'active', NOW(), NOW()),
(8, 'EVT-SPEAK', 'Portable Speakers',
 'Compact audio speakers for school events.', 'event', 'units',
 2, 1, 4800.00, 9600.00,
 'Event Storage', 'active', NOW(), NOW());

COMMIT;
