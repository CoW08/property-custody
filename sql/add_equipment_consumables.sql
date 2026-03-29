CREATE TABLE IF NOT EXISTS equipment_consumables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    supply_id INT NOT NULL,
    quantity_per_use DECIMAL(10,2) DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asset_supply (asset_id, supply_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
