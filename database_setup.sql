-- LOTUS Hotel Database Schema

CREATE DATABASE IF NOT EXISTS lotus_hotel;
USE lotus_hotel;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255),
    role ENUM('guest', 'admin') DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms Table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('deluxe', 'executive', 'presidential', 'ocean-view', 'suite', 'garden-villa') NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    capacity INT NOT NULL,
    bed_type VARCHAR(50),
    size VARCHAR(20),
    view VARCHAR(50),
    amenities JSON,
    images JSON,
    is_available BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT DEFAULT 1,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    gateway_response JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rate Limits Table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (identifier, created_at)
);

-- Initial Seed Data for Rooms
INSERT INTO rooms (name, type, description, price, capacity, bed_type, size, view, amenities, images) VALUES
('Grand Suite', 'suite', 'A palatial suite with private balcony overlooking the ocean.', 450.00, 2, 'King', '60m²', 'Ocean', '["WiFi", "TV", "Mini Bar", "AC", "Safe"]', '["room1.jpg"]'),
('Executive Suite', 'executive', 'Perfect for business travelers with separate living and work areas.', 350.00, 2, 'King', '45m²', 'City', '["WiFi", "TV", "Mini Bar", "AC", "Desk"]', '["room2.jpg"]'),
('Deluxe Room', 'deluxe', 'Elegant comfort with city or garden views and premium amenities.', 250.00, 2, 'Queen', '35m²', 'Garden', '["WiFi", "TV", "AC"]', '["room3.jpg"]'),
('Presidential Suite', 'presidential', 'The ultimate luxury experience with panoramic views and butler service.', 750.00, 4, '2 King', '120m²', 'Panoramic', '["WiFi", "TV", "Mini Bar", "AC", "Private Pool", "Butler"]', '["room4.jpg"]'),
('Garden Villa', 'garden-villa', 'Private villa surrounded by lush gardens with outdoor terrace.', 550.00, 3, 'King + Sofa', '80m²', 'Garden', '["WiFi", "TV", "Mini Bar", "AC", "Kitchenette"]', '["room5.jpg"]'),
('Ocean View Room', 'ocean-view', 'Breathtaking ocean views with floor-to-ceiling windows and balcony.', 320.00, 2, 'King', '40m²', 'Ocean', '["WiFi", "TV", "AC", "Balcony"]', '["room6.jpg"]');
