-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id SERIAL PRIMARY KEY,
    restaurant_id INT NOT NULL REFERENCES restaurants(restaurant_id) ON DELETE CASCADE,
    company_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    gst_number VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Inventory Items Table
CREATE TABLE IF NOT EXISTS inventory_items (
    item_id SERIAL PRIMARY KEY,
    restaurant_id INT NOT NULL REFERENCES restaurants(restaurant_id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(50) DEFAULT NULL,
    barcode VARCHAR(50) DEFAULT NULL,
    purchase_price DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    selling_price DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    unit VARCHAR(20) NOT NULL, -- e.g. kg, gram, piece, liter
    opening_stock DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    current_stock DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    minimum_stock DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    supplier_id INT REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Inventory Transactions Table
CREATE TABLE IF NOT EXISTS inventory_transactions (
    transaction_id SERIAL PRIMARY KEY,
    item_id INT NOT NULL REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL, -- e.g. 'stock_in', 'stock_out', 'waste', 'adjustment'
    quantity DECIMAL(10,2) NOT NULL,
    reference VARCHAR(100) DEFAULT NULL, -- e.g. invoice no, PO no, order ID
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
