-- VARP Database Schema
-- Complete consolidated schema matching all 9 application modules

CREATE DATABASE IF NOT EXISTS playlist;
USE playlist;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS downloads;
DROP TABLE IF EXISTS weekly_movies;
DROP TABLE IF EXISTS genre_personality;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS user_subscriptions;
DROP TABLE IF EXISTS user_activity;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS playlist_songs;
DROP TABLE IF EXISTS playlists;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS listening_history;
DROP TABLE IF EXISTS songs;
DROP TABLE IF EXISTS artists;
DROP TABLE IF EXISTS genres;
DROP TABLE IF EXISTS users;

-- 1. Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Genres table
CREATE TABLE genres (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Artists table
CREATE TABLE artists (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(150) NOT NULL UNIQUE,
    artist_image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Songs table
CREATE TABLE songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    song_id INT DEFAULT NULL,
    track_id VARCHAR(100) DEFAULT NULL,
    song_name VARCHAR(150) DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    artist_id INT DEFAULT NULL,
    artist VARCHAR(100) NOT NULL,
    genre_id INT DEFAULT NULL,
    genre VARCHAR(50) NOT NULL,
    album_name VARCHAR(150) DEFAULT NULL,
    album_art VARCHAR(255) DEFAULT NULL,
    preview_url VARCHAR(255) DEFAULT NULL,
    duration_ms INT DEFAULT 0,
    youtube_url VARCHAR(255) DEFAULT NULL,
    audio_file VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES artists(artist_id) ON DELETE SET NULL,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Playlists table
CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Playlist songs table
CREATE TABLE playlist_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_playlist_song (playlist_id, song_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Favorites table
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_favorite (user_id, song_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Listening history table
CREATE TABLE listening_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    listened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Subscriptions table (Feature 5: Premium ₹99/month)
CREATE TABLE subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) NOT NULL DEFAULT 'VARP Premium',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Downloads table (Feature 8: Premium downloads logging)
CREATE TABLE downloads (
    download_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Genre personality table (Feature 6: Predefined personality profiles)
CREATE TABLE genre_personality (
    personality_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_id INT NOT NULL UNIQUE,
    personality_title VARCHAR(150) NOT NULL,
    personality_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Weekly movies and characters table (Feature 7)
CREATE TABLE weekly_movies (
    weekly_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_id INT NOT NULL,
    movie_name VARCHAR(150) NOT NULL,
    movie_year INT DEFAULT 2023,
    movie_description TEXT NOT NULL,
    movie_poster_url VARCHAR(500) DEFAULT NULL,
    movie_music_fit TEXT DEFAULT NULL,
    character_name VARCHAR(150) NOT NULL,
    character_show VARCHAR(150) DEFAULT NULL,
    character_description TEXT NOT NULL,
    character_traits TEXT DEFAULT NULL,
    character_music_match TEXT DEFAULT NULL,
    character_image_url VARCHAR(500) DEFAULT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Payments & Gateway transactions table (Feature 5)
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT DEFAULT NULL,
    order_id VARCHAR(100) NOT NULL,
    gateway_payment_id VARCHAR(100) DEFAULT NULL,
    gateway_signature VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    status ENUM('created', 'authorized', 'captured', 'failed', 'refunded') NOT NULL DEFAULT 'created',
    payment_method VARCHAR(50) DEFAULT 'razorpay',
    gateway_response TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_gateway_payment (gateway_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. User activity table
CREATE TABLE user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. User preferences table
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    genre_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
