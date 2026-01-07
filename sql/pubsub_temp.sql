-- ckg_pubsub_incoming definition

CREATE TABLE `ckg_pubsub_incoming` (
  `id` varchar(100) NOT NULL COMMENT 'Message ID from Pub/Sub',
  `data` TEXT NOT NULL COMMENT 'Message data in JSON format',
  `attributes` TEXT NOT NULL COMMENT 'Message attributes in JSON format',
  `received_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Message received timestamp',
  `processed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Message received timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_received_at` (`received_at`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB COMMENT='Pub/Sub Incoming Messages Table';

-- ckg_pubsub_outgoing definition

CREATE TABLE `ckg_pubsub_outgoing` (
  `terduga_id` varchar(100) NOT NULL COMMENT 'Message ID from Pub/Sub',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record create timestamp',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB COMMENT='API Outgoing Messages Table';