# VARP 🎵 (Editorial Music Mood Board & Catalog)

A cohesive, fully integrated full-stack music discovery, personal playlist manager, recommendation engine, and premium features prototype built for our college Database & Web project.
Developed using **PHP, MySQL, HTML5, CSS3, and JavaScript** running on **XAMPP**.

---

## 🎨 Design Direction: Strict Editorial Palette (#2C263F & #213722)

VARP features a curated, high-contrast **editorial mood board** aesthetic limited strictly to two base color hues:
- **Core Brand Palette**: Muted Plum / Slate Violet (`#2C263F`) and Deep Forest Pine / Moss (`#213722`).
- **Surfaces & Shadows**: Deep dark canvas (`#100E17`, `#171420`, `#201B2E`) layered with subtle plum and forest radial backdrops and translucent borders.
- **Strict Derived Tints**: Crisp, high-contrast alabaster white (`#F5F3FA`), readable plum tints (`#C0B6DE`, `#E3DFF2`), and soft moss tints (`#A3C9A7`, `#D3E7D5`) with zero extraneous accent colors.
- **Responsive Song Cards**: Square-cropped, high-resolution track artwork and official thumbnails with graceful vector SVG fallbacks on missing images.
- **Accurate Character Muses**: Handcrafted, verified vector muse portraits for each pop-culture character (*Ramona Flowers*, *Barbie*, *Eddie Munson*, *Miles Morales*, *Sebastian Wilder*, *Trinity*, *Chihiro*).
- **Accessibility**: Strict WCAG AAA contrast ratios (>10:1), clear hover/focus outlines (`outline: 2px solid #A3C9A7`), and fluid grid layouts that work on mobile and desktop.

---

## 👥 Integrated Feature Checklist & Modules

| # | Feature Module | Implemented Files | Description & Key Functionality |
| :-: | :--- | :--- | :--- |
| **1** | **User Authentication & Profiles** | `register.php`, `login.php`, `logout.php`, `dashboard.php`, `profile.php`, `update_profile.php`, `preferences.php`, `activity.php`, `includes/session_helper.php` | Secure password hashing (`password_hash`), session lifecycle management, personal data scoping to `$_SESSION['user_id']`, user activity auditing, and role-based permissions (`'user'` and `'admin'`). |
| **2** | **Music Discovery & Catalog** | `index.php`, `js/script.js`, `api/fetch_songs.php`, `api/search_songs.php`, `api/filter_genre.php`, `api/filter_artist.php`, `api/fetch_artists.php`, `api/fetch_genres.php`, `api/itunes_search.php`, `api/add_song.php` | Search tracks across title, artist, or album; dynamic genre filter dropdown; artist filter dropdown; online iTunes API integration to import new tracks into the local database; accessible YouTube video icon on all cards/rows with fallback query search; and YouTube preview drawer with genuine play/pause controls. |
| **3** | **Playlist Management** | `playlists.php`, `playlist_view.php`, `api/create_playlist.php`, `api/rename_playlist.php`, `api/delete_playlist.php`, `api/add_to_playlist.php`, `api/remove_from_playlist.php`, `api/fetch_user_playlists.php` | Create multiple personal playlists, view playlist songs, add songs from catalog or remote discovery with duplicate prevention, remove songs, and enforce strict server-side ownership (`WHERE id = ? AND user_id = ?`). |
| **4** | **Listening History & Recommendations** | `history.php`, `recommendations.php`, `api/record_listening.php`, `api/get_recommendations.php` | Automatic stream logging whenever a song is played, timeline history display, and personalized genre recommendation algorithm based on user's top-listened genre and preferences. |
| **5** | **Razorpay Sandbox Payment Gateway** | `subscribe.php`, `config/payment.php`, `api/create_payment_order.php`, `api/verify_payment.php`, `api/payment_webhook.php`, `.env.example`, `premium_dashboard.php` | Real test-mode payment gateway integration for the ₹99/month VARP Premium plan. Official Razorpay checkout and order creation, strict server-side HMAC-SHA256 signature verification before activating subscriptions, dedicated `payments` ledger table, asynchronous webhook signature verification (`api/payment_webhook.php`), and developer sandbox simulator mode. |
| **6** | **Top Artists & Music Personality** | `top_artists.php`, `fetch_top_artists.php`, `music_personality.php`, `fetch_personality.php` | Ranks user's **top 5 artists** as a curated Pinterest gallery with high-resolution portraits, rank pills (`#01`–`#05`), and play count progress bars; matches user's top genre to an editorial archetype (e.g. *The Velvet Dreamer*, *The Main Character*, *The Electric Rebel*); handles zero-history gracefully. |
| **7** | **Weekly Cinema & Character Recommendations** | `weekly_feature.php`, `character_recommendation.php`, `fetch_weekly_movie.php`, `fetch_character.php`, `admin_weekly.php` | Rich weekly cinema and universally recognizable pop-culture character pairings (Ramona Flowers, Barbie, Eddie Munson, Miles Morales, Sebastian Wilder, Trinity, Chihiro) based on top genre and personality. Displays posters, release year, genre, synopsis, music taste fit, character traits, origin show/movie badge, and musical twin explanation with graceful SVG fallbacks. Authorized admin portal (`admin_weekly.php`) with image upload/URL validation and attribution. |
| **8** | **Authorized Premium Audio Downloads** | `download.php`, `audio/` | Available only to active premium members; serves authorized audio tracks in `audio/` (`.mp3`, `.wav`, `.ogg`, `.m4a`, `.flac`); strict path traversal protection (`basename()`, realpath inside approved directory, null-byte checks); logs to `downloads` table; clean fallback messages when audio file is unavailable (never extracts from YouTube). |
| **9** | **Database & Setup Synchronization** | `database/schema.sql`, `database/seed_data.sql`, `database/procedures.sql`, `database/upgrade_schema.php`, `database/update_iconic_characters.php`, `database/migrate_and_seed.php`, `verify_all_flows.php` | Consolidated relational schema covering all 15 tables (including `payments`), stored functions (`fn_GetPlaylistSongCount`, `fn_GetUserTopGenre`), seed data, and 100% passing automated test suite. |

---

## 🚀 Setup & Local Run Instructions

### 1. Requirements
- **XAMPP for Windows** (Apache and MySQL).
- PHP 8.0+ with `mysqli` extension enabled.

### 2. Location
Ensure this folder is located in your XAMPP web root:
```
C:\xampp\htdocs\dbwproject
```

### 3. Start Apache and MySQL in XAMPP
Open the **XAMPP Control Panel**:
- Start **Apache** (runs on port 80).
- Start **MySQL** (configured for port 3307 or default 3306).

> **Database Port Fallback**: `config/db.php` checks port `3307` first (XAMPP custom port) and automatically falls back to `3306` if `3307` is unavailable. No manual code changes are required.

### 4. Database Setup
You can set up or synchronize the database using either phpMyAdmin or the included automated script:

#### Option A: Automated Script (Recommended)
Run the migration script from your terminal:
```powershell
& "C:\xampp\php\php.exe" C:\xampp\htdocs\dbwproject\database\migrate_and_seed.php
```

#### Option B: phpMyAdmin Import
1. Open `http://localhost/phpmyadmin` in your browser.
2. Create database `playlist` (collation: `utf8mb4_general_ci`).
3. Import in order:
   - `database/schema.sql`
   - `database/procedures.sql`
   - `database/seed_data.sql`

---

## 🧪 Automated Verification Suite

An end-to-end verification script tests all 8 checklist features against the live database:
```powershell
& "C:\xampp\php\php.exe" C:\xampp\htdocs\dbwproject\verify_all_flows.php
```
**Results**: `62 / 62 tests passed` (covers authentication, discovery, playlist CRUD & security, stream logging, subscription validation, payment gateway sandbox signatures, top artists, personalities, weekly cinema, character recommendations, admin role enforcement, and download path-traversal prevention).

---

## 🎬 Demo Walkthrough Guide

### Demo User Accounts

| Role | Username | Email | Password | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Admin & Premium** | `alex_music` | `alex@example.com` | `password123` | Active Premium, Administrator |
| **Standard User** | `sarah_grooves` | `sarah@example.com` | `password123` | Free Account, Standard User |

### Step-by-Step Demo Flow

1. **Music Discovery & Search**:
   - Open `http://localhost/dbwproject/index.php`.
   - Browse the 23 catalog tracks with curated editorial mood board cards.
   - Filter by **Genre** (e.g. *Indie*, *Rock*, *Pop*) or **Artist** (*The Neighbourhood*, *Queen*, *The Weeknd*).
   - Click the YouTube video icon on any song to launch the YouTube modal; click **"Close & Pause"** to stop playback (listening activity is recorded automatically).
   - Click **"Search iTunes"** to query the live iTunes catalog and import new songs directly into your database.

2. **User Authentication & Dashboard**:
   - Click **Login** (`login.php`) and sign in as `alex@example.com` with `password123`.
   - You land on `dashboard.php`, displaying personal track stats and an active Premium badge.
   - Click **Profile** (`profile.php`), **Account Settings** (`update_profile.php`), and **Account Activity** (`activity.php`) to see the audit trail.

3. **Playlist Management**:
   - Navigate to **Playlists** (`playlists.php`).
   - Click **"Create Playlist"** to create a collection (e.g., *"Late Night Vibes"*).
   - Click **"Open Playlist"** (`playlist_view.php`) to see tracks, add songs from the catalog modal, or remove tracks.
   - Duplicate track additions are blocked by database constraints; other users cannot view or modify your playlists.

4. **Listening History & Personalized Recommendations**:
   - Open **History** (`history.php`) to view the chronological stream log.
   - Open **For You** (`recommendations.php`) to view tracks recommended specifically for your top genre (*Indie*).

5. **Premium Hub & Subscription**:
   - Navigate to **Premium Hub** (`premium_dashboard.php` or `subscribe.php`).
   - Plan details show **₹99 / month** with current status and expiry date.
   - Try the Razorpay test modal to simulate sandbox order creation and signature verification.

6. **Top Artists & Music Personality**:
   - Click **Top Artists** (`top_artists.php`) to view your top 5 ranked artists in an editorial portrait gallery with play count progress bars.
   - Click **Personality** (`music_personality.php`) to view your entertainment archetype certificate (*"The Velvet Dreamer"* for Indie listeners).

7. **Weekly Movie & Character**:
   - Click **Weekly** (`weekly_feature.php`) to view the weekly movie (*500 Days of Summer*) and popular culture muse (*Ramona Flowers* from *Scott Pilgrim vs. The World*) matched to your top genre.
   - Click **Character** (`character_recommendation.php`) to view a dedicated character profile card with traits, soundtrack soul match, and origin show details.

8. **Admin Weekly Portal**:
   - As `alex_music` (admin), click **Admin** in the navigation bar (`admin_weekly.php`).
   - View, add, edit, or delete scheduled weekly movie recommendations.
   - If signed in as `sarah_grooves` (standard user), accessing `admin_weekly.php` returns HTTP 403 Forbidden (*Administrator Access Required*).

9. **Authorized Audio Downloads**:
   - Open **Downloads** (`download.php`).
   - Download approved royalty-free audio tracks (`sweater_weather_preview.mp3`, `heat_waves_preview.mp3`).
   - Download activity is logged in the `downloads` table; songs without valid audio files show clear error messaging.
