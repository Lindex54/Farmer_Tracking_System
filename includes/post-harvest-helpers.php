<?php
if (!function_exists('postHarvestDryingMethods')) {
    function postHarvestDryingMethods()
    {
        return array('Sun Drying', 'Mechanical Dryer', 'Raised Bed Drying', 'Hybrid Method');
    }
}

if (!function_exists('postHarvestStageMethods')) {
    function postHarvestStageMethods()
    {
        return array(
            'Drying' => postHarvestDryingMethods(),
            'Cleaning' => array('Manual', 'Mechanical'),
            'Sorting' => array('Manual', 'Mechanical'),
            'Shelling' => array('Manual', 'Mechanical Sheller'),
            'Bagging' => array('Manual')
        );
    }
}

if (!function_exists('postHarvestStorageTypes')) {
    function postHarvestStorageTypes()
    {
        return array('Silo', 'PICS Bag', 'Hermetic Bag', 'Crib', 'Warehouse');
    }
}

if (!function_exists('postHarvestPestLevels')) {
    function postHarvestPestLevels()
    {
        return array('None', 'Low', 'Medium', 'High');
    }
}

if (!function_exists('ensurePostHarvestTables')) {
    function ensurePostHarvestTables($con)
    {
        if (!$con) {
            return false;
        }

        $queries = array(
            "CREATE TABLE IF NOT EXISTS post_harvest_stages (
                id INT(11) NOT NULL AUTO_INCREMENT,
                batch_id INT(11) NOT NULL,
                stage_type VARCHAR(50) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                method VARCHAR(100) NOT NULL,
                result_score DECIMAL(5,2) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_post_harvest_batch_id (batch_id),
                KEY idx_post_harvest_stage_type (stage_type),
                CONSTRAINT fk_post_harvest_batch
                    FOREIGN KEY (batch_id) REFERENCES batches (batch_id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS storage_records (
                id INT(11) NOT NULL AUTO_INCREMENT,
                batch_id INT(11) NOT NULL,
                storage_type VARCHAR(80) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                moisture_level DECIMAL(5,2) DEFAULT NULL,
                temperature DECIMAL(5,2) DEFAULT NULL,
                pest_infestation_level VARCHAR(30) NOT NULL DEFAULT 'None',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_storage_batch_id (batch_id),
                CONSTRAINT fk_storage_batch
                    FOREIGN KEY (batch_id) REFERENCES batches (batch_id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS quality_logs (
                id INT(11) NOT NULL AUTO_INCREMENT,
                batch_id INT(11) NOT NULL,
                test_date DATE NOT NULL,
                mold_presence TINYINT(1) NOT NULL DEFAULT 0,
                aflatoxin_reading DECIMAL(8,2) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_quality_batch_id (batch_id),
                KEY idx_quality_test_date (test_date),
                CONSTRAINT fk_quality_batch
                    FOREIGN KEY (batch_id) REFERENCES batches (batch_id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        foreach ($queries as $sql) {
            if (!mysqli_query($con, $sql)) {
                return false;
            }
        }

        return true;
    }
}
?>
