-- ElderCare Database Schema for PostgreSQL
-- Run this script to initialize the database

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS donations (
    id SERIAL PRIMARY KEY,
    donor_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    payment_method VARCHAR(50),
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visits (
    id SERIAL PRIMARY KEY,
    visitor_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    message TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory (
    id SERIAL PRIMARY KEY,
    item_id VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    stock_quantity INT DEFAULT 0,
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: 1234)
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@eldercare.com', '$2y$10$Cdzv8W1p2R8xY3q4sG0Qh.0JJ9ZxQvqVtG8G8G8G8G8G8G8G8G8G8') 
ON CONFLICT (username) DO NOTHING;

-- Insert default inventory items
INSERT INTO inventory (name, description, stock_quantity, image_url) VALUES 
('Wheelchairs', 'Comfortable mobility wheelchairs for residents', 5, 'https://images.unsplash.com/photo-1587745416684-47a6b380635b?w=400&q=80'),
('Medicines', 'Essential medical supplies and vitamins', 20, 'https://images.unsplash.com/photo-1587854692152-cbe660dbde0f?w=400&q=80'),
('Walking Aids', 'Canes, walkers, and balance support equipment', 8, 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&q=80'),
('Food & Nutrition', 'Healthy meals and nutritional supplements', 15, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80'),
('Bedding & Comfort', 'Quality pillows, blankets, and mattresses', 12, 'https://images.unsplash.com/photo-1586788944171-a1beba7f5a6b?w=400&q=80'),
('Entertainment', 'Books, games, and activity materials', 10, 'https://images.unsplash.com/photo-1507842217343-583f20270319?w=400&q=80')
ON CONFLICT DO NOTHING;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_donations_email ON donations(email);
CREATE INDEX IF NOT EXISTS idx_visits_email ON visits(email);
CREATE INDEX IF NOT EXISTS idx_donations_date ON donations(donation_date);
CREATE INDEX IF NOT EXISTS idx_visits_date ON visits(visit_date);

-- Payments table to record UPI donation payments
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    donation_id INT REFERENCES donations(id) ON DELETE SET NULL,
    order_id VARCHAR(128) UNIQUE,
    amount NUMERIC(10,2) NOT NULL,
    upi_uri TEXT,
    qr_url TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payer_vpa VARCHAR(255),
    payer_name VARCHAR(255),
    payer_email VARCHAR(255),
    payer_phone VARCHAR(50),
    payment_time TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
