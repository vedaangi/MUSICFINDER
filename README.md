# MusicFinder 🎵

A full-stack personalized music discovery and playlist management web application built for our college Database & Web project.
Developed using **PHP, MySQL, HTML5, CSS3, and JavaScript** on **XAMPP**.
Styled with a clean, cohesive, accessible **Pink & White** aesthetic.

---

## 👥 Team Modules & Integration Architecture

| Member | Focus Area | Implemented Features & Deliverables |
| :--- | :--- | :--- |
| **Member 1** | **User Authentication & Profiles** | Secure registration, password hashing (`bcrypt`), session handling (`$_SESSION['user_id']`, `$_SESSION['username']`), login, logout, profile dashboard with activity metrics, and 1-click college presentation demo user switching. |
| **Member 2** | **Music Catalog & Search** | Track library catalog, keyword search by title and artist, multi-criteria sorting (Title A-Z/Z-A, Artist A-Z, Genre), interactive genre pill filtering (Indie, Rock, Pop, Hip-Hop, Jazz, Electronic, Lo-Fi), and catalog cards. |
| **Member 3** | **Playlists & Recommendation Engine** | Playlist CRUD (create, list, view, rename, delete), playlist track management (with duplicate prevention and strict user ownership verification), listening history timeline, automatic top-genre calculation, and personalized genre recommendations with fallback discovery. |
| **Member 4** | **Music Player & UI Integration** | Docked bottom music player with real YouTube IFrame API bidirectional control, verified Pause/Resume from paused position, track scrubber, volume control, YouTube modal drawer, toast notification system, and responsive layout. |

---

## 🌸 New Enhancements & Polished Features

### 1. Robust Player Control & Pause/Resume Fix
- **YouTube IFrame API Integration**: Embeds the official YouTube IFrame API script (`YT.Player`), connecting directly to the player lifecycle rather than superficial CSS state changes.
- **Real Pause & Resume**: Clicking **Pause** commands `ytPlayer.pauseVideo()` (or `contentWindow.postMessage('pauseVideo')` / HTML5 `<audio>.pause()`), instantly stopping audio/video. Clicking **Play/Resume** continues playback from the exact paused timestamp.
- **Bi-directional Sync**: Player button icon, spinning disc animation, track title, and time scrubber remain perfectly synchronized with playback events (`onStateChange`).
- **Graceful Error Handling**: If a YouTube video is unavailable or embedding is restricted (error codes 101/150), an in-app notice appears with an instant "Open on YouTube" button.

### 2. Pink & White Design Refresh
- **Cohesive Light Theme**: A light, modern pink and crisp white aesthetic (`--bg-main: #fff5f8`, `--bg-card: #ffffff`, `--accent: #ec4899`, `--accent-hover: #db2777`).
- **High Contrast & Readability**: Deep charcoal text (`--text-primary: #1e1b20`) meeting WCAG AA accessibility standards.
- **Polished UI Elements**: Smooth card hover elevation, pill badges, docked player bar with glowing controls, animated playback disc, and responsive navigation drawer.

### 3. Liked Songs / Favorites (Feature 1)
- Dedicated `favorites` table (`user_id`, `song_id`, `created_at`) scoped per user.
- Interactive heart button on all song cards (Catalog, Recommendations, Playlists) and in the docked player bar.
- Dedicated **Liked Songs** page (`favorites.php`) with **Play All**, individual track streaming, add-to-playlist action, and quick removal.

### 4. Shuffle & Repeat Playback Modes (Feature 2)
- **Shuffle Mode**: Toggles randomized playback order across active song queues.
- **Repeat Mode**: Three-state toggle (`Off` ➔ `Repeat All` ➔ `Repeat One`) with active visual indicators.

### 5. Multi-Criteria Sorting & Live Filters (Feature 3)
- **Catalog Sorting**: Sort tracks by Title (A-Z / Z-A), Artist (A-Z), or Genre.
- **Playlists Sorting**: Sort playlists by Newest, Oldest, Name (A-Z / Z-A), or Song Count.
- **Instant Client-Side Filtering**: Live search filter on My Playlists and Listening History for instant navigation without page reloads.

### 6. Listening Activity Analytics Dashboard (Feature 4)
- Analytics summary card on `history.php` and `profile.php` displaying:
  - **Total Streams Count**
  - **Unique Songs Explored**
  - **Top Listened Genre**
  - **Most Streamed Track & Play Count**

---

## 🚀 Setup & Installation Instructions

### 1. Project Directory in XAMPP
Place or clone the project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\dbwproject
```

### 2. Start Services via XAMPP Control Panel
- Start **Apache** (default port: `80` / `443`).
- Start **MySQL** (default port: `3306` or `3307`).

### 3. Database Setup (phpMyAdmin or CLI)
1. Open `http://localhost/phpmyadmin` in your web browser.
2. Create a new database named `playlist` with collation `utf8mb4_general_ci`.
3. In phpMyAdmin, select `playlist` and import the files in the `database/` folder in the following order:
   - `database/schema.sql` (Creates `users`, `songs`, `playlists`, `playlist_songs`, `listening_history`, and `favorites`)
   - `database/procedures.sql` (Creates stored functions `fn_GetPlaylistSongCount`, `fn_GetUserTopGenre` and stored procedures)
   - `database/seed_data.sql` (Seeds demo users, track catalog, initial playlists, and history)

*Alternatively via MySQL Command Line:*
```powershell
mysql -u root --port=3307 playlist < C:\xampp\htdocs\dbwproject\database\schema.sql
mysql -u root --port=3307 playlist < C:\xampp\htdocs\dbwproject\database\procedures.sql
mysql -u root --port=3307 playlist < C:\xampp\htdocs\dbwproject\database\seed_data.sql
```

### 4. Database Configuration (`config/db.php`)
`config/db.php` includes smart port auto-detection:
- Checks port `3307` first (XAMPP custom port).
- If `3307` is unavailable, automatically falls back to `3306`.
- No manual code changes are required for standard setups.

### 5. Launch the Application
Navigate to the application in your browser:
```
http://localhost/dbwproject/index.php
```

---

## 🎬 Presentation & Demo Walkthrough

### Step 1: User Sign In & Demo Switcher (Member 1)
- Go to `http://localhost/dbwproject/login.php`.
- Notice the **Evaluation Demo Mode** box:
  - Click **Alex (User 1)** to sign in immediately (Indie fan with pre-seeded data).
  - Or click **Sarah (User 2)** to sign in as a user with different taste.
  - Or register a new account via `register.php`.

### Step 2: Personalized Dashboard (Members 1, 3, 4)
- The **Home & Discover** dashboard shows:
  - Metric cards: Playlists Created, Liked Songs, Top Genre, and Total Streams.
  - **Recommended For You**: Tracks dynamically matching the user's top genre.
  - **Recently Played**: Most recent history entries.
  - **Featured Catalog Tracks**: Quick track discovery.

### Step 3: Browse Catalog & Sorting (Member 2)
- Click **Catalog & Search** (`catalog.php`).
- Test search by title or artist (e.g. `Queen`, `The Weeknd`).
- Click genre pills to filter by Indie, Rock, Pop, etc.
- Change the **Sort By** dropdown (Title A-Z, Artist A-Z, Genre).
- Click the heart icon to add/remove songs from **Liked Songs**.

### Step 4: Player & Pause/Resume Control (Members 3 & 4)
- Click the pink **Play** button on any song card.
- The **Docked Bottom Player Bar** appears:
  - Spinning disc animation begins.
  - Track title, artist, and progress scrubber update.
  - A play record is sent to `api/record_listening.php` (recorded only once per track play).
- Click **Pause**: the music audio/video immediately stops; disc stops spinning; icon switches to Play.
- Click **Play**: music resumes exactly from the paused timestamp.
- Test **Shuffle** and **Repeat** toggles in the player bar.
- Click **Video** to expand the YouTube video player modal.

### Step 5: Liked Songs & Playlists (Member 3)
- Click **Liked Songs** (`favorites.php`):
  - View all favorited tracks. Click **Play All** to stream sequentially.
- Click **My Playlists** (`playlists.php`):
  - Create a new playlist (e.g., `Exam Prep Lofi`).
  - Open the playlist, click **Add Songs**, and pick tracks from the catalog modal.
  - Notice duplicate prevention (cannot add the same song twice).
  - Remove a song, rename the playlist, or delete the playlist.
  - Ownership is strictly enforced: users can only manage their own playlists.

### Step 6: Dynamic Recommendations & History (Member 3)
- Click **Recommendations** (`recommendations.php`):
  - Affinity banner shows top genre and play count breakdown.
  - Recommendations filter out songs already added to the user's playlists.
- Click **History** (`history.php`):
  - Review the **Listening Activity Analytics** summary card.
  - Inspect the timeline of streamed songs.

---

## 🔒 Security & Quality Standards

- **Prepared SQL Statements**: All database queries use `$conn->prepare()` with typed parameter binding (`bind_param`) to prevent SQL injection.
- **Ownership Verification**: All playlist modification, deletion, and favorite endpoints verify that `$_SESSION['user_id']` matches the record owner.
- **Input Sanitization & Output Escaping**: All dynamic text is sanitized and passed through `htmlspecialchars()` to prevent Cross-Site Scripting (XSS).
- **Session Scoping**: Unauthenticated requests to protected pages are redirected to `login.php`; API endpoints return HTTP 401 JSON.
- **Duplicate Prevention**: Database unique constraints enforce single entries for playlist songs and favorites.

---

## 📁 Repository Structure

```
dbwproject/
├── api/
│   ├── add_to_playlist.php        # POST: Add song to playlist (verifies owner & dups)
│   ├── create_playlist.php        # POST: Create a new playlist
│   ├── delete_playlist.php        # POST: Delete playlist & cascade songs
│   ├── fetch_playlists.php        # GET: Fetch user playlists with song counts
│   ├── get_recommendations.php    # GET: Personalized or fallback recommendations
│   ├── get_top_genre.php          # GET: User top genre and genre play breakdown
│   ├── record_listening.php       # POST: Log track play event to history
│   ├── remove_from_playlist.php   # POST: Remove song from playlist
│   ├── rename_playlist.php        # POST: Rename existing playlist
│   └── toggle_favorite.php        # POST: Add/remove song from Liked Songs
├── assets/
│   ├── css/
│   │   └── style.css              # Pink & white design system, player bar, modals
│   └── js/
│       └── main.js                # YouTube IFrame API, pause/resume, shuffle, repeat, AJAX
├── config/
│   └── db.php                     # MySQL connection with 3307 -> 3306 auto-fallback
├── database/
│   ├── procedures.sql             # Stored procedures & functions for genres/counts
│   ├── schema.sql                 # Complete DDL tables for all 4 team members
│   └── seed_data.sql              # Demo users, track catalog, playlists, and history
├── includes/
│   ├── db_helper.php              # Standardized compatibility layer bridging all modules
│   ├── footer.php                 # Docked player bar, video modal, scripts
│   ├── header.php                 # Navigation bar, Liked Songs link, demo pill
│   └── session_helper.php         # Authentication & session utilities
├── catalog.php                    # Member 2: Music catalog, search & multi-sort
├── favorites.php                  # Dedicated Liked Songs page with Play All
├── history.php                    # Member 3: Listening history & analytics dashboard
├── index.php                      # Home discovery dashboard connecting all modules
├── login.php                      # Member 1: User login & 1-click demo switcher
├── logout.php                     # Member 1: Session destruction & logout
├── playlists.php                  # Member 3: Playlists grid, search & sort
├── playlist_view.php              # Member 3: Playlist song list & picker modal
├── profile.php                    # Member 1: User profile & activity metrics
├── recommendations.php            # Member 3: Personalized genre recommendation engine
└── register.php                   # Member 1: New user registration
```
