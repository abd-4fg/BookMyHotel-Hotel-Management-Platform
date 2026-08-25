-- ============================================
-- TO CREATE DATABASE
-- ============================================
CREATE DATABASE IF NOT EXISTS bookmyhotel;
USE bookmyhotel;

-- ============================================
-- USERS TABLE (with all roles included)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin', 'hotel_staff', 'maintenance_staff') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- HOTELS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    star_rating INT DEFAULT 5,
    image VARCHAR(255),
    facilities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- ROOMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_night DECIMAL(10,2) NOT NULL,
    max_occupancy INT DEFAULT 2,
    total_rooms INT DEFAULT 10,
    available_rooms INT DEFAULT 10,
    image_url VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- ============================================
-- SERVICES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- ============================================
-- BOOKINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT DEFAULT 2,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- ============================================
-- REVIEWS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    booking_id INT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- ============================================
-- PROMOTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_percent INT DEFAULT 10,
    description VARCHAR(255),
    valid_from DATE,
    valid_to DATE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- ============================================
-- MAINTENANCE TICKETS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS maintenance_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    issue TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_by INT NOT NULL,
    completed_by INT DEFAULT NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- CUSTOMER TICKETS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS customer_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    resolution_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- ROOM AVAILABILITY LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS room_availability_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    date DATE NOT NULL,
    available_rooms INT NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_date (room_id, date)
);

-- ============================================
-- SAMPLE DATA - HOTELS
-- ============================================
INSERT IGNORE INTO hotels (id, name, description, location, star_rating, facilities) VALUES
(1, 'The Marriott', 'Luxury hotel with world-class amenities and exceptional service. Features stunning city views, multiple dining options, and a full-service spa.', 'Dubai, UAE', 5, 'Outdoor Pool, Spa, Fitness Center, Restaurant, Bar, Business Center, Concierge, Free WiFi'),
(2, 'Hilton', 'Elegant accommodation with modern comforts and stunning views. Perfect for both business and leisure travelers.', 'London, UK', 5, 'Indoor Pool, Gym, Restaurant, Bar, Meeting Rooms, Business Center, Free WiFi, Room Service'),
(3, 'Hyatt', 'Contemporary hotel offering premium services and prime location. Experience world-class hospitality in the heart of Paris.', 'Paris, France', 5, 'Spa, Restaurant, Bar, Conference Rooms, Fitness Center, Concierge, Free WiFi, Valet Parking'),
(4, 'Four Seasons', 'Iconic luxury resort with personalized service and breathtaking views. An oasis of tranquility and sophistication.', 'Bangkok, Thailand', 5, 'Outdoor Pool, Spa, Gym, Restaurant, Bar, Beach Access, Kids Club, Free WiFi, Butler Service');

-- ============================================
-- SAMPLE DATA - ROOMS (ALL IMAGES FIXED)
-- ============================================
INSERT IGNORE INTO rooms (id, hotel_id, room_type, description, price_per_night, max_occupancy, total_rooms, available_rooms, image_url) VALUES
-- Marriott Hotels (hotel_id = 1)
(1, 1, 'Deluxe Suite', 'Spacious suite with city view, king bed, marble bathroom, and separate living area.', 450.00, 2, 20, 20, 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400'),
(2, 1, 'Executive Room', 'Comfortable room with work desk, express check-in, and access to executive lounge.', 350.00, 2, 30, 30, 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=400'),
(3, 1, 'Presidential Suite', 'Ultimate luxury with panoramic views, private terrace, and 24/7 butler service.', 850.00, 4, 5, 5, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400'),

-- Hilton Hotels (hotel_id = 2)
(4, 2, 'Luxury Room', 'Elegant room with panoramic views and premium amenities including bathrobe and slippers.', 400.00, 2, 25, 25, 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=400'),
(5, 2, 'Family Suite', 'Large suite with two bedrooms, separate living area, and kitchenette.', 600.00, 4, 10, 10, 'https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=400'),
(6, 2, 'Executive Suite', 'Business-friendly suite with work area, meeting space, and city views.', 500.00, 2, 15, 15, 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=400'),

-- Hyatt Hotels (hotel_id = 3)
(7, 3, 'Premium Suite', 'Stylish suite with Eiffel Tower view and spa access. Features king bed and luxury bath.', 500.00, 2, 15, 15, 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400'),
(8, 3, 'Standard Room', 'Comfortable room with all essential amenities and city views.', 250.00, 2, 35, 35, 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=400'),
(9, 3, 'Honeymoon Suite', 'Romantic suite with private balcony, jacuzzi, and champagne service.', 550.00, 2, 8, 8, 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=400'),

-- Four Seasons Hotels (hotel_id = 4)
(10, 4, 'Royal Suite', 'Exclusive suite with private pool, butler service, and ocean views.', 750.00, 2, 8, 8, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400'),
(11, 4, 'Deluxe Room', 'Spacious room with garden view, luxury bath, and premium bedding.', 380.00, 2, 20, 20, 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400'),
(12, 4, 'Beach Villa', 'Private villa with direct beach access, private pool, and outdoor shower.', 900.00, 4, 6, 6, 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=400');

-- ============================================
-- SAMPLE DATA - SERVICES
-- ============================================
INSERT IGNORE INTO services (hotel_id, service_name, description, price) VALUES
(1, 'Car Rental', 'Luxury car rental service available for guests.', 150.00),
(1, 'Spa', 'Full-service spa with massage, facial, and wellness treatments.', 200.00),
(1, 'Local Tours', 'Guided tours to local attractions and landmarks.', 100.00),
(1, 'Airport Transfer', 'Private luxury transfer to and from airport.', 80.00),
(2, 'Restaurant', 'Fine dining with international cuisine and Michelin-starred chefs.', NULL),
(2, 'Bar', 'Premium bar with signature cocktails and live music.', NULL),
(2, 'Business Center', 'Full-service business center with meeting rooms and secretarial services.', 50.00),
(3, 'Spa', 'Traditional and modern spa treatments in a serene setting.', 180.00),
(3, 'Car Rental', 'Rent a car for your Paris adventure.', 120.00),
(3, 'City Tours', 'Explore Paris with guided tours to famous landmarks.', 90.00),
(4, 'Beach Activities', 'Water sports and beach activities including snorkeling and kayaking.', 80.00),
(4, 'Spa', 'Luxury spa with ocean view and traditional Thai treatments.', 220.00),
(4, 'Cooking Classes', 'Learn Thai cuisine with our master chefs.', 150.00),
(4, 'Island Tours', 'Explore nearby islands with private boat tours.', 200.00);

-- ============================================
-- SAMPLE DATA - PROMOTIONS
-- ============================================
INSERT IGNORE INTO promotions (hotel_id, code, discount_percent, description, valid_from, valid_to) VALUES
(1, 'MARRIOTT10', 10, 'Welcome discount for Marriott guests', '2026-01-01', '2026-12-31'),
(1, 'SUMMER20', 20, 'Summer special discount', '2026-06-01', '2026-08-31'),
(2, 'HILTON15', 15, 'Hilton loyalty discount', '2026-01-01', '2026-12-31'),
(3, 'HYATT10', 10, 'Hyatt welcome discount', '2026-01-01', '2026-12-31'),
(4, 'FOURSEASONS25', 25, 'Luxury escape discount', '2026-05-01', '2026-09-30'),
(1, 'WELCOME10', 10, 'Welcome discount for new customers', '2026-01-01', '2026-12-31'),
(2, 'HILTON20', 20, 'Hilton loyalty discount', '2026-01-01', '2026-12-31'),
(3, 'PARIS15', 15, 'Paris city break discount', '2026-03-01', '2026-10-31'),
(4, 'THAILAND30', 30, 'Thai hospitality special', '2026-05-01', '2026-09-30');

-- ============================================
-- CREATE ADMIN USER (PLAIN TEXT PASSWORD: admin123)
-- ============================================
INSERT IGNORE INTO users (full_name, email, password, role, phone, address) VALUES 
('System Administrator', 'admin@bookmyhotel.com', 'admin123', 'admin', '+971 50 123 4567', 'Dubai, UAE');

-- ============================================
-- CREATE SAMPLE CUSTOMER USER (PLAIN TEXT PASSWORD: customer123)
-- ============================================
INSERT IGNORE INTO users (full_name, email, password, role, phone, address) VALUES 
('Customer Account', 'customer@bookmyhotel.com', 'customer123', 'customer', '+971 50 123 8573', 'Dubai, UAE');

-- ============================================
-- CREATE HOTEL STAFF USER (PLAIN TEXT PASSWORD: staff123)
-- ============================================
INSERT IGNORE INTO users (full_name, email, password, role, phone, address) VALUES 
('Staff Account', 'staff@bookmyhotel.com', 'staff123', 'hotel_staff', '+971 50 234 5678', 'Dubai, UAE');

-- ============================================
-- CREATE MAINTENANCE STAFF USER (PLAIN TEXT PASSWORD: maintenance123)
-- ============================================
INSERT IGNORE INTO users (full_name, email, password, role, phone, address) VALUES 
('Maintenance Account', 'maintenance@bookmyhotel.com', 'maintenance123', 'maintenance_staff', '+971 50 456 7890', 'Dubai, UAE');

-- ============================================
-- SAMPLE DATA - MAINTENANCE TICKETS
-- ============================================
INSERT IGNORE INTO maintenance_tickets (hotel_id, room_number, issue, status, created_by, completed_by, resolution_notes, completed_at) VALUES
(1, '101', 'Air conditioning not working', 'completed', 1, 1, 'Replaced thermostat', NOW()),
(2, '205', 'Toilet leaking', 'in_progress', 1, NULL, NULL, NULL),
(3, '312', 'WiFi not connecting', 'pending', 1, NULL, NULL, NULL);

-- ============================================
-- FIX ANY ROOMS THAT MIGHT HAVE MISSING IMAGES
-- ============================================
UPDATE rooms SET image_url = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400' WHERE image_url IS NULL AND hotel_id = 1;
UPDATE rooms SET image_url = 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=400' WHERE image_url IS NULL AND hotel_id = 2;
UPDATE rooms SET image_url = 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=400' WHERE image_url IS NULL AND hotel_id = 3;
UPDATE rooms SET image_url = 'https://images.unsplash.com/photo-1584132967331-10e028bd69f7?w=400' WHERE image_url IS NULL AND hotel_id = 4;

-- ============================================
-- VERIFY ALL ROOMS HAVE IMAGES
-- ============================================
SELECT id, hotel_id, room_type, image_url FROM rooms ORDER BY hotel_id, id;

-- ============================================
-- VERIFY ALL USERS
-- ============================================
SELECT id, full_name, email, role FROM users;