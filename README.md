# MusicFinder 🎵

A full-stack personalized music discovery and playlist management web application built for our college Database & Web project.
Developed using **PHP, MySQL, HTML5, CSS3, and JavaScript** on **XAMPP**.

---

## 👥 Team Modules & Integration Architecture

| Member | Focus Area | Implemented Features & Deliverables |
| :--- | :--- | :--- |
| **Member 1** | **User Authentication & Profiles** | User registration, login, logout, account settings (`update_profile.php`), music preferences (`preferences.php`), account activity logs (`activity.php`), and session management (`dashboard.php`). |
| **Member 2** | **Music Discovery & Catalog** | Live song search, genre filtering, artist filtering, iTunes search integration (`itunes_search.php`), adding and managing songs (`add_song.php`, `edit_song.php`, `delete_song.php`), and track cards. |
| **Member 3** | **Playlists & Recommendation Engine** | Playlist CRUD (create, list, view, rename, delete), playlist song management with duplicate prevention, listening history timeline, automatic top-genre calculation, and personalized genre recommendations. |
| **Member 4** | **Music Player & Features** | Music playback controls with fixed pause/resume, YouTube embed drawer, audio previews, and subscription/personality features. |

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

### 3. Database Import (phpMyAdmin)
1. Open `http://localhost/phpmyadmin` in your web browser.
2. Create a new database named `playlist` with collation `utf8mb4_general_ci`.
3. Select `playlist` and import the files in the `database/` folder in the following order:
   - `database/schema.sql`
   - `database/procedures.sql`
   - `database/seed_data.sql`

### 4. Database Configuration (`config/db.php`)
`config/db.php` includes smart port auto-detection:
- Checks port `3307` first (XAMPP custom port).
- If `3307` is unavailable, automatically falls back to `3306`.
- No manual code changes required.

### 5. Launch the Application
Navigate to the application in your browser:
```
http://localhost/dbwproject/index.php
```

---

## 🎬 How to Run & Demo

1. **Music Discovery**: Go to `index.php` to search songs, filter by genre or artist, and preview audio.
2. **Account Management**: Log in or register (`login.php` / `register.php`), update your profile (`update_profile.php`), choose your music preferences (`preferences.php`), and view your activity (`activity.php`).
3. **Playlists**: Create and manage playlists (`playlists.php`), add songs with duplicate prevention, and view playlist tracks (`playlist_view.php`).
4. **Recommendations & History**: View your personalized recommendations based on listening history (`recommendations.php`) and stream timeline (`history.php`).
