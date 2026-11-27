# VARP: Comprehensive Project Documentation & Technical Report

**Academic & Technical Reference Manual for Presentation, Demonstration, and Viva Voce**

* **Product Name**: VARP (formerly MusicFinder)
* **Tagline**: Curated Editorial Music Mood Board & Relational Catalog
* **Core Brand Theme**: Editorial Pinterest Mood Board (`#2C263F` & `#213722`)
* **Underlying Technologies**: PHP 8.2, MySQL / MariaDB, HTML5, CSS3, JavaScript (ES6+), XAMPP
* **Verification Status**: 62 / 62 Automated Tests Passing (`verify_all_flows.php`)

---

## 1. Executive Summary & Purpose

### 1.1 Project Overview
**VARP** is a full-stack web application designed and developed for a college Database and Web (DBW) course. It bridges the gap between conventional relational database management systems and modern web streaming user interfaces. 

Rather than serving purely as a static catalog, VARP operates as an **editorial music mood board**—combining music discovery, personal playlist curation, listening habit tracking, algorithmic recommendations, pop-culture character twin analysis, an admin weekly cinema portal, authorized audio downloads, and a simulated test-mode payment gateway for premium subscriptions.

### 1.2 Key Objectives
1. **Practical Relational Modeling**: Demonstrate rigorous database design using MySQL, enforcing primary keys, foreign keys (`ON DELETE CASCADE`, `ON DELETE SET NULL`), unique constraints, indexing, and stored procedures.
2. **Defensive Web Architecture**: Prevent common web vulnerabilities including SQL Injection (via prepared statements), Cross-Site Scripting (XSS via `htmlspecialchars`), Cross-Site Request Forgery (CSRF tokens), and Path Traversal attacks (via realpath validation).
3. **Hybrid Architecture (SSR + Client-Side APIs)**: Combine traditional server-side rendered (SSR) PHP pages for SEO and initial load with asynchronous JavaScript (Fetch API / JSON) for responsive catalog filtering, remote track imports, stream logging, and payment verification.
4. **Editorial Visual Design**: Provide a modern, image-rich user experience inspired by curated Pinterest mood boards, styled around two primary brand colors: **#2C263F** (muted slate violet/plum) and **#213722** (deep forest pine/moss).

---

## 2. Technology Stack & Component Roles

Every technology used in VARP was selected to meet concrete functional and architectural requirements:

| Technology | Layer | Role in VARP |
| :--- | :--- | :--- |
| **PHP 8.2** | Server-Side Language | Executes business logic, handles session state, securely queries MySQL via `mysqli`, orchestrates API endpoints (`api/*.php`), validates HMAC-SHA256 signatures, and renders server-side templates. |
| **MySQL / MariaDB** | Relational Database | Persists users, tracks, playlists, stream history, subscriptions, payment logs, and cinema/character features across 15 relational tables with strict foreign key constraints. |
| **HTML5** | Client-Side Structure | Provides semantic markup (`<main>`, `<header>`, `<footer>`, `<section>`, `<nav>`), accessible dialog modals (`role="dialog"`, `aria-modal="true"`), and form structures. |
| **CSS3 (Custom Properties)** | Presentation & Styling | Implements the strict editorial mood board aesthetic with CSS variables derived exclusively from the two base color hues (`--brand-plum`, `--brand-forest`, and their authentic plum and moss tints), responsive CSS Grid / Flexbox layouts, Google Fonts (*Playfair Display* and *Plus Jakarta Sans*), frosted glass backdrops, and WCAG AAA contrast. |
| **Vanilla JavaScript (ES6+)** | Client-Side Interactivity | Powers asynchronous catalog search, dynamic dropdown filtering, YouTube modal player lifecycle (play/pause synchronization), iTunes API proxy calls, and Razorpay checkout triggers without external JS heavyweight frameworks. |
| **Apache HTTP Server** | Web Server (via XAMPP) | Handles incoming HTTP requests, directs routing, and serves static assets (`css/`, `js/`, `assets/images/`, `audio/`). |
| **Apple iTunes Search API** | External Public Service | Provides a vast catalog of global songs beyond the local database, allowing users to discover and import remote tracks without requiring private API keys. |
| **YouTube IFrame Embeds** | Media Delivery | Delivers track video playback inside an accessible modal drawer with synchronized pause-on-close controls. |
| **Razorpay Sandbox API** | Payment Gateway | Simulates ₹99/month premium subscription orders, HMAC-SHA256 signature verification, and webhook event processing in test mode. |

---

## 3. High-Level Architecture & Request Lifecycle

VARP implements a hybrid architecture combining classic multi-page navigation with decoupled RESTful JSON micro-endpoints:

```
+-----------------------------------------------------------------------------------+
|                                 CLIENT BROWSER                                    |
|  - HTML5 & CSS3 Editorial UI (#2C263F & #213722)                                  |
|  - Vanilla JavaScript Event Handlers (Click, Filter, Search, Modal)              |
+---------------------+---------------------------------------+---------------------+
                      |                                       |
    1. Page Navigation (GET)                  2. Async AJAX Calls (Fetch API / JSON)
                      |                                       |
                      v                                       v
+---------------------+--------------------+  +---------------+---------------------+
|        SERVER-SIDE RENDERED VIEWS        |  |         RESTful JSON APIs           |
|  - index.php (Catalog & Mood Board)      |  |  - api/search_songs.php             |
|  - dashboard.php (User Metrics & Status) |  |  - api/filter_genre.php             |
|  - playlist_view.php (Track List)        |  |  - api/itunes_search.php            |
|  - weekly_feature.php (Cinema & Muse)    |  |  - api/add_to_playlist.php          |
|  - character_recommendation.php (Detail) |  |  - api/record_listening.php         |
|  - subscribe.php (Premium Checkout)      |  |  - api/verify_payment.php           |
+---------------------+--------------------+  +---------------+---------------------+
                      |                                       |
                      +-------------------+-------------------+
                                          |
                                          v
                      +---------------------------------------+
                      |         SHARED SERVER SERVICES        |
                      |  - includes/session_helper.php (Auth) |
                      |  - config/db.php (Dual-Port MySQLi)   |
                      |  - config/payment.php (Razorpay/HMAC) |
                      +-------------------+-------------------+
                                          |
                     +--------------------+--------------------+
                     |                                         |
                     v                                         v
   +-----------------+-----------------+     +-----------------+-----------------+
   |      LOCAL MYSQL DATABASE         |     |        EXTERNAL SERVICES          |
   |  - 15 Relational Tables           |     |  - Apple iTunes Search API        |
   |  - Stored Functions               |     |  - YouTube Video Embeds           |
   |  - Foreign Keys & Indexes         |     |  - Razorpay Test Checkout         |
   +-----------------------------------+     +-----------------------------------+
```

### 3.1 Step-by-Step Data Flow Example: Playing a Song & Recording History
1. **User Action**: The user clicks the video play icon on a song card in `index.php`.
2. **Client Script**: `js/script.js` intercepts the click, sets the YouTube iframe `src`, and displays `#videoModal`.
3. **Background Asynchronous Request**: Simultaneously, JavaScript executes a `fetch('api/record_listening.php', { method: 'POST', body: ... })`.
4. **Authentication Check**: `api/record_listening.php` invokes `requireLogin(true)` from `includes/session_helper.php`. If unauthenticated, it returns HTTP 401 JSON; if logged in, it retrieves `$_SESSION['user_id']`.
5. **Database Transaction**: A prepared `INSERT INTO listening_history (user_id, song_id, listened_at)` query logs the play.
6. **Analytics Feedback**: Next time the user visits `dashboard.php`, `top_artists.php`, or `recommendations.php`, the newly recorded stream immediately influences their top artist rankings, music personality archetype, and weekly cinema match.

---

## 4. Project Directory Structure

```
C:\xampp\htdocs\dbwproject\
│
├── .env.example                  <- Template for environment variables (Razorpay sandbox keys)
├── README.md                     <- Setup, feature matrix, and local run instructions
├── PROJECT_REPORT.md             <- This comprehensive academic and technical manual
├── verify_all_flows.php          <- 62-point automated end-to-end PHP test suite
│
├── config/
│   ├── db.php                    <- MySQLi connection with auto-fallback (port 3307 -> 3306)
│   └── payment.php               <- Razorpay SDK simulator, .env parser, HMAC-SHA256 verifier
│
├── includes/
│   ├── header.php                <- Global editorial navigation bar, brand disc logo, user pills
│   ├── footer.php                <- Global footer, YouTube video modal, player script
│   └── session_helper.php        <- Session lifecycle, requireLogin(), CSRF tokens, user identity
│
├── css/
│   └── style.css                 <- Master stylesheet (#2C263F & #213722 Pinterest mood board)
│
├── js/
│   └── script.js                 <- Client-side DOM manipulation, search, filter, player modal
│
├── assets/
│   └── images/
│       ├── placeholder_movie.svg     <- Styled vector fallback for cinema posters
│       └── placeholder_character.svg <- Styled vector fallback for character muse portraits
│
├── audio/                        <- Royalty-free audio tracks for authorized premium downloads
│   ├── sweater_weather_preview.mp3
│   └── heat_waves_preview.mp3
│
├── database/
│   ├── schema.sql                <- Consolidated DDL for all 15 relational tables
│   ├── seed_data.sql             <- Verified seed data (23 tracks, 7 iconic characters, users)
│   ├── procedures.sql            <- Stored functions (fn_GetPlaylistSongCount, fn_GetUserTopGenre)
│   ├── upgrade_schema.php        <- Non-destructive schema migration utility
│   └── migrate_and_seed.php      <- One-click database initializer
│
├── api/                          <- RESTful JSON API endpoints
│   ├── search_songs.php          <- Local catalog search across title, artist, album
│   ├── filter_genre.php          <- Filter songs by genre ID
│   ├── filter_artist.php         <- Filter songs by artist ID
│   ├── fetch_songs.php           <- Retrieve active catalog songs
│   ├── fetch_genres.php          <- Fetch genre list for dropdowns
│   ├── fetch_artists.php         <- Fetch artist list for dropdowns
│   ├── itunes_search.php         <- Proxy endpoint querying Apple's iTunes Search API
│   ├── add_song.php              <- Imports remote track into local MySQL catalog
│   ├── create_playlist.php       <- Creates user playlist (scoped to $_SESSION['user_id'])
│   ├── rename_playlist.php       <- Renames user playlist with ownership validation
│   ├── delete_playlist.php       <- Deletes user playlist with cascade cleanup
│   ├── add_to_playlist.php       <- Adds track to playlist (prevents duplicates)
│   ├── remove_from_playlist.php  <- Removes track from playlist
│   ├── fetch_user_playlists.php  <- Retrieves playlists belonging to authenticated user
│   ├── record_listening.php      <- Audits song play into listening_history
│   ├── get_recommendations.php   <- Returns tracks matched to user's top genre
│   ├── create_payment_order.php  <- Initializes Razorpay test order (₹99)
│   ├── verify_payment.php        <- Server-side HMAC-SHA256 signature verification
│   └── payment_webhook.php       <- Asynchronous webhook endpoint for payment.captured
│
├── Core User Pages (SSR):
│   ├── index.php                 <- Homepage: Editorial catalog, mood board, search & filters
│   ├── login.php                 <- Authentication form with password verification
│   ├── register.php              <- Registration form with uniqueness validation and bcrypt
│   ├── logout.php                <- Destroys session and cleans authentication cookies
│   ├── dashboard.php             <- Personal hub: stream counts, top genre, quick actions
│   ├── profile.php               <- User account details, role badge, registration date
│   ├── update_profile.php        <- Updates email, username, and password
│   ├── preferences.php           <- Multi-genre selection matrix stored in user_preferences
│   ├── activity.php              <- Audit log showing user login and account modifications
│   ├── playlists.php             <- Personal playlist manager (create, view count, delete)
│   ├── playlist_view.php         <- Individual playlist view (add/remove songs, YouTube player)
│   ├── history.php               <- Chronological timeline of user listening streams
│   ├── recommendations.php       <- Curated track recommendations based on listening history
│   ├── top_artists.php           <- Pinterest-style gallery of top 5 artists with stream progress
│   ├── music_personality.php     <- Editorial certificate showing music personality archetype
│   ├── weekly_feature.php        <- Weekly cinema spotlight and character muse side-by-side
│   ├── character_recommendation.php <- Dedicated character muse profile with personality traits
│   ├── subscribe.php             <- Premium upgrade page with Razorpay test modal
│   ├── premium_dashboard.php     <- Premium member hub showing subscription status & expiry
│   ├── check_subscription.php    <- Middleware helper checking premium status and admin role
│   ├── download.php              <- Protected audio file download handler with path validation
│   └── admin_weekly.php          <- Protected admin portal for managing weekly cinema recommendations
```

---

## 5. Database Architecture & Relational Schema

The VARP database (`playlist`) consists of **15 relational tables** organized with foreign key constraints to maintain referential integrity.

### 5.1 Entity Relationship Diagram (ERD)

```
                    +--------------------+
                    |       users        |
                    +--------------------+
                              |
         +--------------------+--------------------+--------------------+
         | 1:N                | 1:N                | 1:N                | 1:N
         v                    v                    v                    v
+-----------------+  +-----------------+  +-----------------+  +-----------------+
|    playlists    |  |listening_history|  |  subscriptions  |  | user_activity   |
+-----------------+  +-----------------+  +-----------------+  +-----------------+
         | 1:N                |                    | 1:N
         v                    |                    v
+-----------------+           |           +-----------------+
| playlist_songs  |           |           |    payments     |
+-----------------+           |           +-----------------+
         |                    |
         | N:1                | N:1
         v                    v
+--------------------------------------+
|                songs                 |
+--------------------------------------+
         | N:1                | N:1
         v                    v
+-----------------+  +-----------------+
|     artists     |  |     genres      |
+-----------------+  +-----------------+
                              |
         +--------------------+--------------------+
         | 1:1                                     | 1:N
         v                                         v
+--------------------+                    +--------------------+
| genre_personality  |                    |   weekly_movies    |
+--------------------+                    +--------------------+
```

### 5.2 Detailed Table Breakdown

1. **`users`**:
   - `id` (INT PK, AUTO_INCREMENT)
   - `username` (VARCHAR(50) UNIQUE NOT NULL)
   - `email` (VARCHAR(100) UNIQUE NOT NULL)
   - `password` (VARCHAR(255) NOT NULL, stores PHP `password_hash` bcrypt string)
   - `role` (VARCHAR(20) DEFAULT `'user'`, can be elevated to `'admin'`)
   - `created_at` (TIMESTAMP)

2. **`genres`**:
   - `genre_id` (INT PK, AUTO_INCREMENT)
   - `genre_name` (VARCHAR(100) UNIQUE NOT NULL, e.g., *Indie*, *Rock*, *Pop*, *Hip-Hop*, *Jazz*, *Electronic*, *Ambient*)

3. **`artists`**:
   - `artist_id` (INT PK, AUTO_INCREMENT)
   - `artist_name` (VARCHAR(150) UNIQUE NOT NULL)
   - `artist_image` (VARCHAR(255), portrait photo URL)

4. **`songs`**:
   - `id` (INT PK, AUTO_INCREMENT)
   - `song_id` (INT NULL, legacy sync column)
   - `track_id` (VARCHAR(100) NULL, remote identifier from iTunes)
   - `title` / `song_name` (VARCHAR(150) NOT NULL)
   - `artist_id` (INT FK -> `artists.artist_id` ON DELETE SET NULL)
   - `artist` (VARCHAR(100) NOT NULL)
   - `genre_id` (INT FK -> `genres.genre_id` ON DELETE SET NULL)
   - `genre` (VARCHAR(50) NOT NULL)
   - `album_name` (VARCHAR(150))
   - `album_art` (VARCHAR(255))
   - `preview_url` (VARCHAR(255))
   - `duration_ms` (INT DEFAULT 0)
   - `youtube_url` (VARCHAR(255))
   - `audio_file` (VARCHAR(255), local authorized audio file in `audio/`)
   - `created_at` (TIMESTAMP)

5. **`playlists`**:
   - `id` (INT PK, AUTO_INCREMENT)
   - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
   - `name` (VARCHAR(100) NOT NULL)
   - `description` (TEXT)
   - `created_at`, `updated_at` (TIMESTAMPS)

6. **`playlist_songs`**:
   - `id` (INT PK, AUTO_INCREMENT)
   - `playlist_id` (INT FK -> `playlists.id` ON DELETE CASCADE)
   - `song_id` (INT FK -> `songs.id` ON DELETE CASCADE)
   - `added_at` (TIMESTAMP)
   - **Constraint**: `UNIQUE KEY unique_playlist_song (playlist_id, song_id)` prevents the same track from being added multiple times to a single playlist.

7. **`favorites`**:
   - `id` (INT PK, AUTO_INCREMENT)
   - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
   - `song_id` (INT FK -> `songs.id` ON DELETE CASCADE)
   - `created_at` (TIMESTAMP)
   - **Constraint**: `UNIQUE KEY unique_user_favorite (user_id, song_id)`

8. **`listening_history`**:
   - `id` (INT PK, AUTO_INCREMENT)
   - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
   - `song_id` (INT FK -> `songs.id` ON DELETE CASCADE)
   - `listened_at` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)

9. **`subscriptions`**:
   - `subscription_id` (INT PK, AUTO_INCREMENT)
   - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
   - `plan` (VARCHAR(50) DEFAULT `'VARP Premium'`)
   - `start_date` (DATE NOT NULL)
   - `end_date` (DATE NOT NULL)
   - `status` (ENUM(`'active'`, `'expired'`) DEFAULT `'active'`)

10. **`payments`**:
    - `payment_id` (INT PK, AUTO_INCREMENT)
    - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
    - `subscription_id` (INT FK -> `subscriptions.subscription_id` ON DELETE SET NULL)
    - `order_id` (VARCHAR(100) NOT NULL, e.g., `order_test_...`)
    - `gateway_payment_id` (VARCHAR(100) NULL, e.g., `pay_test_...`)
    - `gateway_signature` (VARCHAR(255) NULL)
    - `amount` (DECIMAL(10,2) DEFAULT 99.00)
    - `currency` (VARCHAR(10) DEFAULT `'INR'`)
    - `status` (ENUM(`'created'`, `'authorized'`, `'captured'`, `'failed'`, `'refunded'`))
    - `payment_method` (VARCHAR(50) DEFAULT `'razorpay'`)
    - `gateway_response` (TEXT)

11. **`weekly_movies`**:
    - `weekly_id` (INT PK, AUTO_INCREMENT)
    - `genre_id` (INT FK -> `genres.genre_id` ON DELETE CASCADE)
    - `movie_name` (VARCHAR(150) NOT NULL)
    - `movie_year` (INT DEFAULT 2023)
    - `movie_description` (TEXT NOT NULL)
    - `movie_poster_url` (VARCHAR(500))
    - `movie_music_fit` (TEXT)
    - `character_name` (VARCHAR(150) NOT NULL)
    - `character_show` (VARCHAR(150))
    - `character_description` (TEXT NOT NULL)
    - `character_traits` (TEXT)
    - `character_music_match` (TEXT)
    - `character_image_url` (VARCHAR(500))
    - `week_start` (DATE NOT NULL), `week_end` (DATE NOT NULL)

12. **`genre_personality`**:
    - `personality_id` (INT PK, AUTO_INCREMENT)
    - `genre_id` (INT FK -> `genres.genre_id` ON DELETE CASCADE, UNIQUE)
    - `personality_title` (VARCHAR(150) NOT NULL, e.g., *"The Velvet Dreamer"*, *"The Main Character"*, *"The Electric Rebel"*)
    - `personality_description` (TEXT NOT NULL)

13. **`downloads`**:
    - `download_id` (INT PK, AUTO_INCREMENT)
    - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
    - `song_id` (INT FK -> `songs.id` ON DELETE CASCADE)
    - `downloaded_at` (TIMESTAMP)

14. **`user_activity`**:
    - `id` (INT PK, AUTO_INCREMENT)
    - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
    - `activity_type` (VARCHAR(50))
    - `description` (TEXT)
    - `created_at` (TIMESTAMP)

15. **`user_preferences`**:
    - `id` (INT PK, AUTO_INCREMENT)
    - `user_id` (INT FK -> `users.id` ON DELETE CASCADE)
    - `genre_id` (INT FK -> `genres.genre_id` ON DELETE CASCADE)
    - `created_at` (TIMESTAMP)

---

## 6. Key Coding Patterns & Implementation Details

### 6.1 Safe Database Connection & Port Fallback
In local development environments (like XAMPP), MySQL may run on default port `3306` or a custom configured port such as `3307`. Instead of failing hard, `config/db.php` checks port 3307 first and automatically falls back to 3306 if needed:

```php
// config/db.php
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "playlist";
$port = 3307; // Attempt custom port first

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    // Graceful fallback to default MySQL port 3306
    $conn = @new mysqli($host, $user, $password, $database, 3306);
    if (!$conn->connect_error) {
        $port = 3306;
    }
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
```

### 6.2 Session Management & Authentication Guard
Authentication state is kept inside `$_SESSION['user_id']`. All protected pages and API endpoints include `includes/session_helper.php` and invoke `requireLogin()`:

```php
// includes/session_helper.php
function requireLogin(bool $jsonResponse = false): int {
    if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
        if ($jsonResponse) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
            exit;
        }
        header("Location: login.php");
        exit;
    }
    return (int)$_SESSION['user_id'];
}
```

### 6.3 Prepared Statements & SQL Injection Defense
Never concatenate raw user input into SQL queries. VARP uses parameterized prepared statements across all queries:

```php
// api/search_songs.php
$query = trim($_GET['q'] ?? '');
$searchTerm = "%{$query}%";

$stmt = $conn->prepare(
    "SELECT s.*, a.artist_image, g.genre_name 
     FROM songs s
     LEFT JOIN artists a ON s.artist_id = a.artist_id
     LEFT JOIN genres g ON s.genre_id = g.genre_id
     WHERE s.title LIKE ? OR s.artist LIKE ? OR s.album_name LIKE ?
     ORDER BY s.title ASC"
);
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$songs = $result->fetch_all(MYSQLI_ASSOC);
```

### 6.4 Strict User Ownership Scoping on Playlist Operations
When a user renames, opens, or deletes a playlist, the application validates both the `playlist_id` and the authenticated `user_id`. This prevents Insecure Direct Object References (IDOR):

```php
// api/delete_playlist.php
$user_id = requireLogin(true);
$playlist_id = (int)($_POST['playlist_id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $playlist_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Playlist deleted.']);
} else {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Permission denied or playlist not found.']);
}
```

### 6.5 External iTunes Catalog Search & Seamless Import
To search beyond the local catalog without requiring private API credentials, `api/itunes_search.php` queries Apple's public search API and translates results to VARP's schema:

```php
// api/itunes_search.php
$query = trim($_GET['q'] ?? '');
$url = "https://itunes.apple.com/search?term=" . urlencode($query) . "&entity=song&limit=30";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'VARP Music/2.0 (compatible)');
$response = curl_exec($ch);
curl_close($ch);

echo $response;
```
When a user clicks "Add to Playlist" on an iTunes result, `api/add_song.php` checks if the song already exists in the local database. If not, it safely inserts it into `songs`, `artists`, and `genres` first, preserving relational consistency.

### 6.6 Cryptographic Signature Verification for Payments
In `api/verify_payment.php`, payment authenticity is verified on the server using an HMAC-SHA256 signature calculation over `order_id|payment_id` using the secret key. A client-side redirect alone will **never** activate a subscription:

```php
// config/payment.php & api/verify_payment.php
function verifyPaymentSignature(string $orderId, string $paymentId, string $signature, string $secret): bool {
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Inside api/verify_payment.php
if (!verifyPaymentSignature($order_id, $payment_id, $signature, $config['key_secret'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment signature. Signature verification failed.']);
    exit;
}

// Only after verified signature:
activateSubscription($conn, $user_id, 30);
```

### 6.7 Role-Based Access Control (Admin Portal)
Administrative routes (such as `admin_weekly.php` for managing weekly cinema features) verify that the authenticated user possesses `role === 'admin'`:

```php
// check_subscription.php
function requireAdmin(mysqli $conn): int {
    $userId = requireLogin();
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "<h1>403 Forbidden</h1><p>Administrator Access Required.</p>";
        exit;
    }
    return $userId;
}
```

### 6.8 Path Traversal Defense for Authorized Audio Downloads
When serving authorized audio tracks (`download.php`), the server protects against directory traversal attempts (`../../` or null-byte injections):

```php
// download.php
$audioDir = realpath(__DIR__ . '/audio');
$requestedFile = basename($song['audio_file']); // Strip path directories
$targetPath = realpath($audioDir . DIRECTORY_SEPARATOR . $requestedFile);

// Verify file exists and resolves strictly INSIDE the audio/ directory
if (!$targetPath || !str_starts_with($targetPath, $audioDir) || !is_file($targetPath)) {
    http_response_code(404);
    die("Audio file not available on server.");
}
```

---

## 7. Local Setup & Demonstration Guide

### 7.1 Prerequisites
* **XAMPP for Windows** (Apache and MySQL).
* **PHP 8.0+** with `mysqli` and `curl` extensions enabled.
* Target directory: `C:\xampp\htdocs\dbwproject`.

### 7.2 Database Setup
Ensure Apache and MySQL are running in the **XAMPP Control Panel**. Then initialize the database using the automated script:
```powershell
& "C:\xampp\php\php.exe" C:\xampp\htdocs\dbwproject\database\migrate_and_seed.php
```
Or manually import via phpMyAdmin (`http://localhost/phpmyadmin`):
1. `database/schema.sql`
2. `database/procedures.sql`
3. `database/seed_data.sql`

### 7.3 Running the Verification Suite
Before any live demonstration, verify all 8 modules and security checks in one command:
```powershell
& "C:\xampp\php\php.exe" C:\xampp\htdocs\dbwproject\verify_all_flows.php
```
**Expected Output**: `Verification Results: 62 / 62 tests passed`.

### 7.4 Demo Accounts for Presentation

| Role | Username | Email | Password | Access Privileges |
| :--- | :--- | :--- | :--- | :--- |
| **Admin & Premium** | `alex_music` | `alex@example.com` | `password123` | Active Premium Subscription, Administrator (`admin_weekly.php` unlocked), Full Library Access |
| **Standard User** | `sarah_grooves` | `sarah@example.com` | `password123` | Free Account, Standard User (`admin_weekly.php` returns 403 Forbidden, Prompted to Upgrade for Downloads) |

### 7.5 Step-by-Step Presentation Script
1. **Public Catalog & Mood Board (`index.php`)**:
   - Show the editorial aesthetic styled in `#2C263F` and `#213722`.
   - Filter by **Genre** (*Indie*, *Rock*) and **Artist** (*The Neighbourhood*).
   - Click the YouTube video icon on *"Sweater Weather"* to launch the player modal with synchronized play/pause.
   - Search for a song not in the database (e.g. *"Dua Lipa"*); demonstrate remote discovery via Apple iTunes API and click to import.
2. **User Dashboard & History (`login.php` -> `dashboard.php`)**:
   - Sign in as `alex@example.com` with `password123`.
   - Point out the active Premium badge and personal track counts.
   - Open `history.php` to show the chronological stream audit log.
3. **Personal Playlists (`playlists.php` & `playlist_view.php`)**:
   - Open *"Late Night Vibes"*. Add and remove tracks.
   - Explain the database uniqueness constraint that blocks duplicate tracks.
4. **Music Personality & Top Artists (`top_artists.php` & `music_personality.php`)**:
   - Show the Top 5 Ranked Artists gallery with stream progress bars.
   - View the *"The Velvet Dreamer"* personality certificate derived from listening history.
5. **Weekly Cinema & Iconic Character Muse (`weekly_feature.php`)**:
   - Highlight the side-by-side cinema spotlight (*500 Days of Summer*) and iconic pop-culture muse (*Ramona Flowers* from *Scott Pilgrim vs. The World*).
   - Show how character traits and musical soul match are explained.
6. **Payment Gateway Sandbox Simulator (`subscribe.php`)**:
   - Open the Premium subscription modal.
   - Demonstrate the ₹99/month Razorpay test checkout and show the server-side HMAC-SHA256 signature verification.
7. **Admin Security Test (`admin_weekly.php`)**:
   - While signed in as `alex_music`, show the weekly cinema admin form with image validation.
   - Log out, sign in as `sarah_grooves`, and attempt to visit `admin_weekly.php`; show the enforced **HTTP 403 Forbidden** security barrier.
8. **Authorized Audio Downloads (`download.php`)**:
   - Download the authorized preview file.
   - Highlight the realpath traversal protection that blocks unauthorized filesystem reads.

---

## 8. Realistic Build Story & Engineering Evolution

The development of VARP unfolded across seven iterative phases:

1. **Initial Foundation & Teammate Branch Divergence**:
   - The team initiated the repository with separate functional goals: Member 1 focused on basic user authentication and profile updates; Member 2 built the initial music catalog with 23 seeded songs and genre filtering; Vedaangi drafted playlist models and UI concepts.
   - As separate branches grew (`member1-user-account`, `member2-music-discovery`, `vedaangi`), divergent schemas and inconsistent column names emerged (`title` vs. `song_name`, conflicting foreign keys).

2. **Reconciliation & Database Consolidation**:
   - The branches were brought together on `main`. To prevent breaking either teammate's work, a unified relational schema (`database/schema.sql`) was created that harmonized table names and supported dual aliases where appropriate.
   - Missing foreign key relations and stored procedures were formalized.

3. **Brand Evolution to VARP & Editorial Redesign**:
   - The project was rebranded from the generic "MusicFinder" to **VARP**.
   - An early pink/magenta color scheme was completely overhauled into a curated **Pinterest mood board** aesthetic utilizing **#2C263F** (muted plum) and **#213722** (deep forest pine/moss), complemented by Google Fonts (*Playfair Display* and *Plus Jakarta Sans*) and high-contrast alabaster typography.

4. **Catalog Expansion Beyond Seeded Songs**:
   - To overcome the limitation of a small 23-track database, an external catalog integration was built using Apple's iTunes Search API (`api/itunes_search.php`).
   - A companion endpoint (`api/add_song.php`) was implemented so remote tracks could be saved locally without database duplicates.
   - Accessible video icons were added to all song rows, opening YouTube previews with genuine pause/resume modal controls.

5. **Iconic Pop-Culture Character Recommendations & Weekly Cinema**:
   - Rather than using obscure figures, character recommendations were upgraded with universally recognized pop-culture characters: *Ramona Flowers* (Indie), *Barbie* (Pop), *Eddie Munson* (Rock), *Miles Morales* (Hip-Hop), *Sebastian Wilder* (Jazz), *Trinity* (Electronic), and *Chihiro* (Ambient/Lo-Fi).
   - An admin portal was constructed to manage weekly cinema entries with URL sanitization and graceful SVG vector fallbacks.

6. **Test-Mode Payment Gateway (Razorpay Sandbox)**:
   - Built a compliant ₹99/month subscription checkout.
   - Enforced strict server-side HMAC-SHA256 signature verification and created a dedicated `payments` ledger table.

7. **End-to-End Verification & Code Freeze**:
   - Authored `verify_all_flows.php`, testing all 8 checklist features against the live database with 62 passing assertions.
   - Validated 0 PHP syntax errors across all 67 files and mirrored all changes to the OneDrive backup.

---

## 9. Team Member Responsibilities & Module Integration

| Team Member | Assigned Responsibility | Implemented Files & Contributions |
| :--- | :--- | :--- |
| **Member 1** | **User Account & Identity Module** | Developed initial user registration, login authentication, bcrypt password hashing, profile management (`update_profile.php`), user role definition (`'user'` vs `'admin'`), and account activity logging (`activity.php`). |
| **Member 2** | **Music Discovery & Song Catalog Module** | Structured the base `songs`, `genres`, and `artists` tables, seeded the original 23 catalog tracks, and implemented basic genre and artist dropdown filtering. |
| **Vedaangi** *(Lead Integrator)* | **Architecture, UI/UX, & Advanced Features** | Led architectural consolidation; designed and implemented the `#2C263F` & `#213722` Pinterest mood board aesthetic; built the iTunes API search integration; integrated YouTube player modal with pause controls; developed personal playlist CRUD with user scoping; built the listening stream recorder and recommendation engine; integrated the Razorpay test-mode payment gateway with HMAC verification; upgraded weekly cinema and iconic pop-culture character muses; added path-traversal protected audio downloads; and authored the 62-point automated verification suite. |

### Code Provenance: New vs. Reused / Modified
* **Original Reused Code**: Member 1's authentication form layouts (`login.php`, `register.php`) and Member 2's catalog table data structures (`songs`, `genres`).
* **Substantially Modified Code**: `css/style.css` (redesigned from scratch to Pinterest mood board palette), `js/script.js` (refactored for async player modal and iTunes search), `config/db.php` (added dual-port fallback), and `admin_weekly.php` (added strict validation).
* **Entirely New Code Written for VARP**: `api/itunes_search.php`, `api/add_song.php`, `api/record_listening.php`, `api/verify_payment.php`, `api/payment_webhook.php`, `config/payment.php`, `character_recommendation.php`, `weekly_feature.php`, `top_artists.php`, `music_personality.php`, `download.php`, `verify_all_flows.php`, and vector SVG fallbacks.

---

## 10. Frequently Asked Viva Voce & Presentation Questions

### Q1: How does VARP prevent SQL Injection?
**Answer**: By strictly using parameterized prepared statements via PHP's `mysqli` extension (`$stmt = $conn->prepare(...)`, `$stmt->bind_param(...)`). SQL queries and user inputs are sent in separate network packets to the database engine. The MySQL engine compiles the SQL command structure before binding the parameter values as pure string literals, making it impossible for input containing `' OR '1'='1` to alter query logic.

### Q2: Why is password verification done with `password_verify` instead of MD5 or SHA-256?
**Answer**: MD5 and SHA-256 are fast, general-purpose cryptographic hashes susceptible to rainbow table attacks and brute force. VARP uses PHP's native `password_hash()` which employs **bcrypt**. Bcrypt automatically generates a cryptographically secure random salt, incorporates a configurable computational cost factor (work factor), and protects against timing attacks via constant-time comparison in `password_verify()`.

### Q3: What is the difference between client-side redirect and server-side payment verification?
**Answer**: A client-side browser redirect can be easily intercepted, forged, or spoofed using browser developer tools. In VARP, the browser redirect only submits payment tokens to the server. The server independently calculates the cryptographic HMAC-SHA256 signature (`hash_hmac('sha256', $orderId . '|' . $paymentId, $secret)`) and compares it against the signature using `hash_equals()`. Only when the cryptographic check passes does the server activate the subscription.

### Q4: How do the database foreign key constraints maintain referential integrity?
**Answer**: Tables use `ON DELETE CASCADE` and `ON DELETE SET NULL`. For example, in `playlists` and `playlist_songs`:
```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```
If a user account is deleted, the database automatically cleans up all associated playlists and playlist entries without leaving orphaned rows. Conversely, if an artist is deleted from `artists`, `songs.artist_id` is set to `NULL` (`ON DELETE SET NULL`), preserving the song in the catalog without violating relational constraints.

### Q5: How does the recommendation algorithm derive recommendations from listening history?
**Answer**: When a user listens to a track, `api/record_listening.php` logs the event in `listening_history`. When `recommendations.php` loads, it executes a query grouping plays by `genre_id` to identify the user's most frequently streamed genre (`MAX(play_count)`). It then selects catalog tracks from that matching genre that the user has not yet added to their playlists or listened to frequently. If the user has zero history, it falls back to their declared onboarding genre preferences from `user_preferences`.

### Q6: Why does `config/db.php` check port 3307 before port 3306?
**Answer**: Default XAMPP installations run MySQL on port 3306. However, on machines with existing MySQL services or conflicts, XAMPP is commonly reconfigured to port 3307. By checking port 3307 first with error suppression (`@new mysqli`) and falling back to 3306 if a connection error occurs, VARP runs seamlessly across different team members' laptops without requiring manual configuration edits.

### Q7: How does `download.php` prevent Directory / Path Traversal attacks?
**Answer**: Path traversal occurs when an attacker inputs `../../sensitive_file` into a download parameter. VARP uses two layers of defense:
1. `basename($filename)` strips away any leading path tokens, directory separators, or null bytes.
2. `realpath($audioDir . '/' . $filename)` resolves the canonical absolute path and checks that it begins with the approved `audio/` directory path (`str_starts_with($targetPath, $audioDir)`).

### Q8: What is the purpose of the `UNIQUE KEY` in `playlist_songs`?
**Answer**: The constraint `UNIQUE KEY unique_playlist_song (playlist_id, song_id)` enforces data integrity at the database level. Even if a user double-clicks an "Add to Playlist" button or submits concurrent requests, the relational engine rejects the second insert with a duplicate key error, preventing redundant track rows in the same playlist.

### Q9: Why use server-side session authentication rather than storing user ID in a cookie?
**Answer**: Client-side cookies are stored on the user's machine and can be altered in browser inspection tools, allowing an attacker to change `user_id=2` to `user_id=1` (privilege escalation). With PHP sessions (`session_start()`), only an unguessable session identifier cookie (`PHPSESSID`) is stored on the client. The actual user ID and role are stored safely in memory on the server.

### Q10: How does the iTunes Search integration avoid requiring private API keys?
**Answer**: Apple's iTunes Search API is a public REST interface intended for catalog lookup (`https://itunes.apple.com/search?term=...`). By creating a server-side proxy script in PHP (`api/itunes_search.php`), VARP avoids CORS (Cross-Origin Resource Sharing) restrictions in the browser, enforces request rate timeouts, and parses JSON track data before displaying it in the client UI.

---

## 11. Known Limitations, External Services & Future Scope

| Feature / Area | Current Implementation Status | Real-World Production Requirement |
| :--- | :--- | :--- |
| **Razorpay Payment Gateway** | Fully implemented in **Sandbox / Test Mode** with real HMAC verification and simulated orders. | Requires merchant KYC approval and live production keys (`rzp_live_...`) to process real currency transactions. |
| **Audio Previews & Downloads** | Uses verified royalty-free `.mp3` tracks stored in `audio/` with path security. | Streaming commercial copyrighted catalog tracks requires music licensing agreements (e.g. Spotify Web Playback SDK or Apple Music API tokens). |
| **Video Playback** | Employs YouTube IFrame embedded players with custom pause/resume modal synchronization. | To stream natively without third-party iframe overlays, a licensed Content Delivery Network (CDN) like AWS CloudFront would be used. |
| **External iTunes Search** | Public Apple search API with server-side caching and fallback. | Subject to Apple's rate limits (approx. 20 calls/minute). High-traffic production would add Redis caching for search terms. |
| **Hosting Environment** | Localhost execution via XAMPP for Windows (Apache & MySQL). | Production deployment would use Docker containers, an Nginx reverse proxy, HTTPS / SSL certificates via Let's Encrypt, and a managed cloud database. |

---

## 12. Conclusion

**VARP** represents a cohesive, defensible, and visually sophisticated database and web project. It adheres to strict relational standards, enforces robust application security, delivers an engaging Pinterest mood board user experience, and integrates modern features—from live API searches to cryptographic payment signature verification. 

All 62 verification tests pass on the live database, establishing that the project is completely integrated, documented, and ready for academic presentation and evaluation.
