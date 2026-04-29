CREATE TABLE IF NOT EXISTS `post_harvest_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `stage_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `method` varchar(100) NOT NULL,
  `result_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post_harvest_batch_id` (`batch_id`),
  KEY `idx_post_harvest_stage_type` (`stage_type`),
  CONSTRAINT `fk_post_harvest_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `storage_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `storage_type` varchar(80) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `moisture_level` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `pest_infestation_level` varchar(30) NOT NULL DEFAULT 'None',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_storage_batch_id` (`batch_id`),
  CONSTRAINT `fk_storage_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `quality_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `mold_presence` tinyint(1) NOT NULL DEFAULT 0,
  `aflatoxin_reading` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quality_batch_id` (`batch_id`),
  KEY `idx_quality_test_date` (`test_date`),
  CONSTRAINT `fk_quality_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
