-- =====================================================
-- Mobile Phone Management System - Complete Database Schema
-- PostgreSQL Database Schema
-- =====================================================

-- Create database (run this separately if needed)
-- CREATE DATABASE mobile_tech_hub;

-- Connect to database
-- \c mobile_tech_hub;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Brands table - Device manufacturers
CREATE TABLE IF NOT EXISTS brands (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    logo_url VARCHAR(255),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chipsets table - Processor information
CREATE TABLE IF NOT EXISTS chipsets (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    manufacturer VARCHAR(100),
    architecture VARCHAR(50),
    cores INTEGER,
    frequency DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Posts table - Blog posts and articles
CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    author VARCHAR(100) NOT NULL,
    publish_date DATE,
    featured_image VARCHAR(255),
    short_description TEXT,
    content_body TEXT,
    media_gallery TEXT[],
    categories VARCHAR(100)[],
    tags VARCHAR(100)[],
    meta_title VARCHAR(255),
    meta_description TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Post categories table
CREATE TABLE IF NOT EXISTS post_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- DEVICES TABLE (PHONES & TABLETS)
-- =====================================================

-- Main devices table (phones & tablets)
CREATE TABLE IF NOT EXISTS phones (
    id SERIAL PRIMARY KEY,
    
    -- Launch Information
    release_date DATE,
    name VARCHAR(255) NOT NULL,
    brand_id INTEGER REFERENCES brands(id) ON DELETE CASCADE,
    year INTEGER,
    availability VARCHAR(50),
    price DECIMAL(10,2),
    image VARCHAR(255),
    
    -- Network
    network_2g BOOLEAN DEFAULT FALSE,
    network_3g BOOLEAN DEFAULT FALSE,
    network_4g BOOLEAN DEFAULT FALSE,
    network_5g BOOLEAN DEFAULT FALSE,
    dual_sim BOOLEAN DEFAULT FALSE,
    esim BOOLEAN DEFAULT FALSE,
    sim_size VARCHAR(20),
    
    -- Dimensions & Weight
    dimensions_length DECIMAL(5,2),
    dimensions_width DECIMAL(5,2),
    dimensions_thickness DECIMAL(4,2),
    weight DECIMAL(5,2),
    
    -- Display
    display_type VARCHAR(50),
    display_size DECIMAL(4,2),
    display_resolution VARCHAR(50),
    display_technology VARCHAR(50),
    display_notch BOOLEAN DEFAULT FALSE,
    refresh_rate INTEGER,
    hdr BOOLEAN DEFAULT FALSE,
    billion_colors BOOLEAN DEFAULT FALSE,
    
    -- Platform
    os VARCHAR(50),
    chipset_id INTEGER REFERENCES chipsets(id) ON DELETE SET NULL,
    cpu_cores INTEGER,
    cpu_frequency DECIMAL(5,2),
    gpu VARCHAR(100),
    
    -- Memory
    ram_internal VARCHAR(50),
    storage_internal VARCHAR(50),
    card_slot BOOLEAN DEFAULT FALSE,
    storage_expandable VARCHAR(50),
    
    -- Camera
    main_camera_count INTEGER DEFAULT 1,
    main_camera_resolution VARCHAR(100),
    main_camera_features TEXT[],
    main_camera_video VARCHAR(100),
    main_camera_ois BOOLEAN DEFAULT FALSE,
    main_camera_telephoto BOOLEAN DEFAULT FALSE,
    main_camera_ultrawide BOOLEAN DEFAULT FALSE,
    main_camera_macro BOOLEAN DEFAULT FALSE,
    main_camera_flash BOOLEAN DEFAULT FALSE,
    
    selfie_camera_count INTEGER DEFAULT 1,
    selfie_camera_resolution VARCHAR(100),
    selfie_camera_features TEXT[],
    selfie_camera_video VARCHAR(100),
    
    -- Sound
    loudspeaker BOOLEAN DEFAULT FALSE,
    audio_jack_35mm BOOLEAN DEFAULT FALSE,
    
    -- Communications
    wifi VARCHAR(100),
    bluetooth VARCHAR(50),
    gps BOOLEAN DEFAULT FALSE,
    nfc BOOLEAN DEFAULT FALSE,
    infrared_port BOOLEAN DEFAULT FALSE,
    radio BOOLEAN DEFAULT FALSE,
    usb VARCHAR(50),
    
    -- Features
    fingerprint BOOLEAN DEFAULT FALSE,
    face_unlock BOOLEAN DEFAULT FALSE,
    sensors TEXT[],
    
    -- Battery
    battery_type VARCHAR(50),
    battery_capacity INTEGER,
    battery_removable BOOLEAN DEFAULT FALSE,
    charging_wired DECIMAL(5,2),
    charging_wireless DECIMAL(5,2),
    charging_reverse DECIMAL(5,2),
    
    -- Colors
    colors TEXT[],
    
    -- Additional
    form_factor VARCHAR(50),
    keyboard BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- INTERACTION TABLES
-- =====================================================

-- Post comments table
CREATE TABLE IF NOT EXISTS post_comments (
    id SERIAL PRIMARY KEY,
    post_id INTEGER REFERENCES posts(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    parent_id INTEGER REFERENCES post_comments(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Device comments table
CREATE TABLE IF NOT EXISTS device_comments (
    id SERIAL PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL, -- Using VARCHAR to match existing queries
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    parent_id INTEGER REFERENCES device_comments(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Device reviews table
CREATE TABLE IF NOT EXISTS device_reviews (
    id SERIAL PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Device views tracking table
CREATE TABLE IF NOT EXISTS device_views (
    id SERIAL PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL,
    ip_address INET,
    view_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(device_id, ip_address, view_date)
);

-- Device comparisons tracking table
CREATE TABLE IF NOT EXISTS device_comparisons (
    id SERIAL PRIMARY KEY,
    device1_id VARCHAR(50) NOT NULL,
    device2_id VARCHAR(50) NOT NULL,
    ip_address INET,
    comparison_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(device1_id, device2_id, ip_address, comparison_date)
);

-- Newsletter subscriptions table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(100),
    status VARCHAR(20) DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content views tracking table (general)
CREATE TABLE IF NOT EXISTS content_views (
    id SERIAL PRIMARY KEY,
    content_type VARCHAR(20) NOT NULL, -- 'device', 'post', etc.
    content_id VARCHAR(50) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(content_type, content_id, ip_address, DATE(viewed_at))
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Brands indexes
CREATE INDEX IF NOT EXISTS idx_brands_name ON brands(name);
CREATE INDEX IF NOT EXISTS idx_brands_lower_name ON brands(LOWER(name));

-- Chipsets indexes
CREATE INDEX IF NOT EXISTS idx_chipsets_name ON chipsets(name);
CREATE INDEX IF NOT EXISTS idx_chipsets_lower_name ON chipsets(LOWER(name));

-- Posts indexes
CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug);
CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_posts_publish_date ON posts(publish_date);
CREATE INDEX IF NOT EXISTS idx_posts_is_featured ON posts(is_featured);
CREATE INDEX IF NOT EXISTS idx_posts_categories ON posts USING GIN(categories);
CREATE INDEX IF NOT EXISTS idx_posts_tags ON posts USING GIN(tags);

-- Phones indexes
CREATE INDEX IF NOT EXISTS idx_phones_brand_id ON phones(brand_id);
CREATE INDEX IF NOT EXISTS idx_phones_chipset_id ON phones(chipset_id);
CREATE INDEX IF NOT EXISTS idx_phones_name ON phones(name);
CREATE INDEX IF NOT EXISTS idx_phones_year ON phones(year);
CREATE INDEX IF NOT EXISTS idx_phones_price ON phones(price);
CREATE INDEX IF NOT EXISTS idx_phones_network_5g ON phones(network_5g);
CREATE INDEX IF NOT EXISTS idx_phones_network_4g ON phones(network_4g);
CREATE INDEX IF NOT EXISTS idx_phones_os ON phones(os);

-- Comments indexes
CREATE INDEX IF NOT EXISTS idx_post_comments_post_id ON post_comments(post_id);
CREATE INDEX IF NOT EXISTS idx_post_comments_status ON post_comments(status);
CREATE INDEX IF NOT EXISTS idx_device_comments_device_id ON device_comments(device_id);
CREATE INDEX IF NOT EXISTS idx_device_comments_status ON device_comments(status);

-- Views and tracking indexes
CREATE INDEX IF NOT EXISTS idx_device_views_device_id ON device_views(device_id);
CREATE INDEX IF NOT EXISTS idx_device_views_date ON device_views(view_date);
CREATE INDEX IF NOT EXISTS idx_device_comparisons_devices ON device_comparisons(device1_id, device2_id);
CREATE INDEX IF NOT EXISTS idx_content_views_content ON content_views(content_type, content_id);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample brands
INSERT INTO brands (id, name, description) VALUES
(1, 'Samsung', 'South Korean multinational electronics company'),
(2, 'Apple', 'American multinational technology company'),
(3, 'Xiaomi', 'Chinese electronics company'),
(4, 'OnePlus', 'Chinese smartphone manufacturer'),
(5, 'Google', 'American multinational technology company'),
(6, 'Nothing', 'London-based consumer technology company')
ON CONFLICT (id) DO NOTHING;

-- Insert sample chipsets
INSERT INTO chipsets (id, name, description, manufacturer) VALUES
(1, 'Snapdragon 8 Gen 3', 'Qualcomm''s latest flagship mobile platform', 'Qualcomm'),
(2, 'Apple A17 Pro', 'Apple''s most powerful chip for iPhone', 'Apple'),
(3, 'Dimensity 9300', 'MediaTek''s flagship mobile platform', 'MediaTek'),
(4, 'Exynos 2400', 'Samsung''s flagship mobile processor', 'Samsung'),
(5, 'Tensor G3', 'Google''s custom mobile processor', 'Google'),
(6, 'Helio G99', 'MediaTek''s gaming-focused processor', 'MediaTek'),
(7, 'Snapdragon 7+ Gen 2', 'Qualcomm''s premium mid-range platform', 'Qualcomm')
ON CONFLICT (id) DO NOTHING;

-- Insert sample posts
INSERT INTO posts (title, slug, author, publish_date, short_description, content_body, status, is_featured) VALUES
('Samsung Galaxy S24 Ultra Review', 'samsung-galaxy-s24-ultra-review', 'Tech Reviewer', '2024-01-15', 'Comprehensive review of Samsung''s latest flagship', 'Full review content here...', 'published', TRUE),
('Best Budget Smartphones of 2024', 'best-budget-smartphones-2024', 'Tech Expert', '2024-01-10', 'Top affordable smartphones worth considering', 'Budget phone recommendations...', 'published', TRUE),
('5G Revolution: What''s Next?', '5g-revolution-whats-next', 'Network Specialist', '2024-01-05', 'Exploring the future of 5G technology', '5G technology analysis...', 'published', FALSE),
('Camera Technology Trends', 'camera-technology-trends', 'Photography Expert', '2024-01-01', 'Latest developments in smartphone photography', 'Camera technology overview...', 'published', FALSE)
ON CONFLICT (slug) DO NOTHING;

-- Insert sample phones
INSERT INTO phones (name, brand_id, chipset_id, release_date, year, availability, price, os, network_5g, network_4g, dual_sim, display_size, display_resolution, battery_capacity) VALUES
('Galaxy S24 Ultra', 1, 1, '2024-01-17', 2024, 'Available', 1199.99, 'Android', TRUE, TRUE, TRUE, 6.8, '3120 x 1440', 5000),
('Galaxy S24', 1, 1, '2024-01-17', 2024, 'Available', 799.99, 'Android', TRUE, TRUE, TRUE, 6.2, '2340 x 1080', 4000),
('iPhone 15 Pro', 2, 2, '2023-09-22', 2023, 'Available', 999.99, 'iOS', TRUE, TRUE, FALSE, 6.1, '2556 x 1179', 3274),
('Galaxy Tab S9', 1, 4, '2023-08-01', 2023, 'Available', 799.99, 'Android', TRUE, TRUE, TRUE, 11.0, '2560 x 1600', 8400)
ON CONFLICT DO NOTHING;
