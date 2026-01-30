<?php
/**
 * Shared helpers for archiving entities and recording waste management entries.
 */

if (!function_exists('ensureArchiveInfrastructure')) {
    function ensureArchiveInfrastructure(PDO $db, string $tableName): void
    {
        ensureArchiveColumns($db, $tableName);
        ensureWasteManagementTable($db);
    }
}

if (!function_exists('ensureArchiveColumns')) {
    function ensureArchiveColumns(PDO $db, string $tableName): void
    {
        static $checkedTables = [];
        if (isset($checkedTables[$tableName])) {
            return;
        }

        $columns = [
            'archived_at' => 'DATETIME NULL',
            'archived_by' => 'INT NULL',
            'archive_reason' => 'VARCHAR(255) NULL',
            'archive_notes' => 'TEXT NULL'
        ];

        foreach ($columns as $column => $definition) {
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE :column");
                $stmt->execute([':column' => $column]);
                if ($stmt->rowCount() === 0) {
                    $db->exec("ALTER TABLE `{$tableName}` ADD COLUMN `{$column}` {$definition}");
                }
            } catch (Throwable $throwable) {
                // Silently ignore if we cannot alter the schema, but continue to other columns.
            }
        }

        $checkedTables[$tableName] = true;
    }
}

if (!function_exists('ensureWasteManagementTable')) {
    function ensureWasteManagementTable(PDO $db): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        try {
            $db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `waste_management_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(40) NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `identifier` VARCHAR(120) DEFAULT NULL,
    `status` ENUM('archived', 'restored', 'disposed') NOT NULL DEFAULT 'archived',
    `archived_at` DATETIME NOT NULL,
    `archived_by` INT UNSIGNED DEFAULT NULL,
    `archive_reason` VARCHAR(255) DEFAULT NULL,
    `archive_notes` TEXT DEFAULT NULL,
    `metadata` LONGTEXT DEFAULT NULL,
    `disposed_at` DATETIME DEFAULT NULL,
    `disposed_by` INT UNSIGNED DEFAULT NULL,
    `disposal_method` VARCHAR(120) DEFAULT NULL,
    `disposal_notes` TEXT DEFAULT NULL,
    `restored_at` DATETIME DEFAULT NULL,
    `restored_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
            );
        } catch (Throwable $throwable) {
            // Best effort only. If this fails we cannot record waste entries.
        }

        $ensured = true;
    }
}

if (!function_exists('recordWasteEntry')) {
    function recordWasteEntry(PDO $db, string $entityType, int $entityId, array $payload): void
    {
        ensureWasteManagementTable($db);

        $fields = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'name' => $payload['name'] ?? '',
            'identifier' => $payload['identifier'] ?? null,
            'archived_at' => $payload['archived_at'] ?? date('Y-m-d H:i:s'),
            'archived_by' => $payload['archived_by'] ?? null,
            'archive_reason' => $payload['archive_reason'] ?? null,
            'archive_notes' => $payload['archive_notes'] ?? null,
            'metadata' => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
        ];

        $sql = <<<SQL
INSERT INTO waste_management_records (
    entity_type, entity_id, name, identifier, archived_at, archived_by, archive_reason, archive_notes, metadata, status
) VALUES (
    :entity_type, :entity_id, :name, :identifier, :archived_at, :archived_by, :archive_reason, :archive_notes, :metadata, 'archived'
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    identifier = VALUES(identifier),
    archived_at = VALUES(archived_at),
    archived_by = VALUES(archived_by),
    archive_reason = VALUES(archive_reason),
    archive_notes = VALUES(archive_notes),
    metadata = VALUES(metadata),
    status = 'archived',
    updated_at = CURRENT_TIMESTAMP;
SQL;

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($fields);
        } catch (Throwable $throwable) {
            // Ignore failures – archiving should still succeed even if waste logging fails.
        }
    }
}

if (!function_exists('clearArchiveState')) {
    function clearArchiveState(PDO $db, string $tableName, int $id): void
    {
        ensureArchiveColumns($db, $tableName);
        $sql = "UPDATE `{$tableName}` SET archived_at = NULL, archived_by = NULL, archive_reason = NULL, archive_notes = NULL WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
