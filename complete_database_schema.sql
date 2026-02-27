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

-- Admin/Users authentication table
CREATE TABLE IF NOT EXISTS admin_users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
    name TEXT NOT NULL,
    brand_id INTEGER REFERENCES brands(id) ON DELETE CASCADE,
    brand TEXT, -- For direct brand name storage
    year INTEGER,
    availability TEXT,
    price DECIMAL(10,2),
    device_page_color TEXT, -- Hex color code (e.g., #ffffff)
    image TEXT,
    images TEXT[], -- Array of image paths
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    network TEXT,
    launch TEXT,
    body TEXT,
    display TEXT,
    hardware TEXT,
    memory TEXT,
    main_camera TEXT,
    selfie_camera TEXT,
    multimedia TEXT,
    connectivity TEXT,
    features TEXT,
    battery TEXT,
    general_info TEXT,
    weight TEXT,
    thickness TEXT,
    os TEXT,
    storage TEXT,
    card_slot TEXT,
    display_size TEXT,
    display_resolution TEXT,
    main_camera_resolution TEXT,
    main_camera_video TEXT,
    ram TEXT,
    chipset_name TEXT,
    battery_capacity TEXT,
    wired_charging TEXT,
    wireless_charging TEXT,
    -- SEO fields
    slug TEXT UNIQUE,
    meta_title TEXT,
    meta_desc TEXT
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
    UNIQUE(content_type, content_id, ip_address)
);

-- Reviews association table - One-to-one optional association between posts and phones
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    phone_id INTEGER REFERENCES phones(id) ON DELETE CASCADE UNIQUE,
    post_id INTEGER REFERENCES posts(id) ON DELETE CASCADE UNIQUE
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Admin users indexes
CREATE INDEX IF NOT EXISTS idx_admin_users_username ON admin_users(username);

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
CREATE INDEX IF NOT EXISTS idx_phones_name ON phones(name);
CREATE INDEX IF NOT EXISTS idx_phones_year ON phones(year);
CREATE INDEX IF NOT EXISTS idx_phones_price ON phones(price);
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

-- Reviews indexes
CREATE INDEX IF NOT EXISTS idx_reviews_phone_id ON reviews(phone_id);
CREATE INDEX IF NOT EXISTS idx_reviews_post_id ON reviews(post_id);

-- =====================================================
-- QUERIES TABLE (Contact Form Submissions)
-- =====================================================

CREATE TABLE IF NOT EXISTS queries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    query TEXT NOT NULL,
    query_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Queries indexes
CREATE INDEX IF NOT EXISTS idx_queries_email ON queries(email);
CREATE INDEX IF NOT EXISTS idx_queries_query_type ON queries(query_type);
CREATE INDEX IF NOT EXISTS idx_queries_created_at ON queries(created_at);

-- =====================================================
-- PUBLIC USERS TABLE (separate from admin_users)
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);

