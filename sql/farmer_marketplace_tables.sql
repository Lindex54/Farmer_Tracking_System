CREATE TABLE IF NOT EXISTS farmer_products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    farmer_id INT(11) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    unit_label VARCHAR(50) NOT NULL DEFAULT 'kg',
    quantity_available DECIMAL(12,2) NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) DEFAULT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_farmer_products_farmer (farmer_id),
    KEY idx_farmer_products_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS marketplace_products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    farmer_product_id INT(11) NOT NULL,
    farmer_id INT(11) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    unit_label VARCHAR(50) NOT NULL DEFAULT 'kg',
    quantity_available DECIMAL(12,2) NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) DEFAULT NULL,
    availability VARCHAR(32) NOT NULL DEFAULT 'In Stock',
    status VARCHAR(32) NOT NULL DEFAULT 'published',
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_market_farmer_product (farmer_product_id),
    KEY idx_market_products_farmer (farmer_id),
    KEY idx_market_products_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS marketplace_orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    farmer_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    shipping_charge DECIMAL(12,2) NOT NULL DEFAULT 0,
    order_status VARCHAR(64) DEFAULT NULL,
    order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_orders_user (user_id),
    KEY idx_market_orders_product (product_id),
    KEY idx_market_orders_farmer (farmer_id),
    KEY idx_market_orders_status (order_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
