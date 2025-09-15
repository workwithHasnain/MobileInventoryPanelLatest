# Database Setup Guide for Mobile Phone Management System

## Overview

This project uses PostgreSQL database exclusively. All JSON dependencies have been removed.

## Quick Setup

### 1. Database Creation

```sql
-- Create database
CREATE DATABASE mobile_tech_hub;

-- Connect to the database
\c mobile_tech_hub;
```

### 2. Import Complete Schema

```bash
# Import the complete schema file
psql -U your_username -d mobile_tech_hub < complete_database_schema.sql
```

### 3. Environment Configuration

Create a `.env` file or set these environment variables:

```
DATABASE_URL=postgresql://username:password@localhost:5432/mobile_tech_hub
PGDATABASE=mobile_tech_hub
PGHOST=localhost
PGPORT=5432
PGUSER=your_username
PGPASSWORD=your_password
```

### 4. Verify Installation

The schema includes a data summary view. Run this to verify:

```sql
SELECT * FROM data_summary;
```

Expected output:

```
table_name        | record_count
------------------+-------------
Brands            | 2
Chipsets          | 7
Phones            | 4
Posts             | 4
Device Reviews    | 6
Device Views      | 10
Device Comparisons| 7
```

## Database Tables

### Core Tables

- `brands` - Device manufacturers (Samsung, Apple, etc.)
- `chipsets` - Processor information
- `phones` - Main devices table (phones & tablets)
- `posts` - Blog posts and articles

### Interaction Tables

- `post_comments` - Comments on blog posts
- `device_comments` - Comments on devices
- `device_reviews` - User ratings and reviews
- `device_views` - Daily view tracking
- `device_comparisons` - Comparison tracking
- `newsletter_subscriptions` - Email subscriptions
- `content_views` - General content tracking

## Sample Data Included

### Devices

- Samsung Galaxy S24 Ultra (Flagship phone)
- Samsung Galaxy S24 (Premium phone)
- Samsung Galaxy Tab S9 (Tablet)
- iPhone 15 Pro (Apple flagship)

### Posts

- Samsung Galaxy S24 Ultra Review
- Best Budget Smartphones of 2024
- 5G Revolution article
- Camera Technology Trends

### Interactive Data

- Sample reviews and ratings
- View tracking data
- Comparison tracking
- Comment examples

## File Structure (Database-Only)

```
project/
├── complete_database_schema.sql  # Complete DB setup
├── includes/
│   ├── database.php             # DB connection
│   └── database_functions.php   # DB operations
├── index.php                     # Public homepage
├── brands.php                   # Brands listing
├── phone_finder.php            # Device search
├── compare.php          # Device comparison
├── featured_posts.php          # Blog posts
├── login.php                   # Admin login
├── dashboard.php               # Admin dashboard
└── uploads/                    # Media files
```

## Key Features Working

- ✅ Interactive homepage with 5 sections
- ✅ Device statistics and rankings
- ✅ Brand filtering and exploration
- ✅ Device comparison functionality
- ✅ Blog post management
- ✅ Comment system with moderation
- ✅ View and interaction tracking
- ✅ Newsletter subscriptions
- ✅ Admin dashboard with analytics

## Production Deployment

### 1. Database Preparation

```sql
-- For production, consider additional optimizations
CREATE INDEX CONCURRENTLY idx_phones_search ON phones USING gin(to_tsvector('english', name || ' ' || COALESCE(description, '')));
```

### 2. Security Considerations

- Set strong database passwords
- Configure proper user permissions
- Enable SSL connections
- Regular backups

### 3. Performance Optimization

The schema includes pre-built indexes for:

- Brand and chipset lookups
- Device filtering and search
- Comment system queries
- Analytics and tracking

## Troubleshooting

### Common Issues

1. **Connection Error**: Verify DATABASE_URL format
2. **Permission Denied**: Check user permissions
3. **Missing Data**: Re-run the schema import
4. **Slow Queries**: Ensure indexes are created

### Reset Database

```sql
-- To start fresh, drop all tables
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
-- Then re-import the schema
```

## Support

This is a complete, production-ready database schema with all necessary data for the Mobile Phone Management System.
