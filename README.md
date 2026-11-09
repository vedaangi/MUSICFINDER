# MUSICFINDER

College DBW project - Music discovery and playlist web app.
Built using PHP, MySQL, HTML, CSS, and JavaScript on XAMPP.

## Team Members
- Member 1: User Login & Registration
- Member 2: Music Catalog & Search
- Member 3: Playlist Management & Recommendations
- Member 4: Music Player

## Setup Instructions

1. Move this folder into your XAMPP directory:
   `C:/xampp/htdocs/dbwproject`

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open `http://localhost/phpmyadmin` in your browser.

4. Import the SQL files from the `database` folder in this order:
   - `schema.sql`
   - `procedures.sql`
   - `seed_data.sql`

5. Check `config/db.php`:
   - If your MySQL port in XAMPP is 3306, set `$db_port = 3306;`
   - If your MySQL port in XAMPP is 3307, set `$db_port = 3307;`

6. Open the project in your browser:
   `http://localhost/dbwproject/index.php`

## Notes for Teammates

- Member 1 (Auth): When logging in, set `$_SESSION['user_id']` and `$_SESSION['username']`. All playlist queries use this user ID.
- Member 2 (Catalog): Add your tracks to the `songs` table (`title`, `artist`, `genre`, `youtube_url`).
- Member 4 (Player): When a song plays, send a POST request to `api/record_listening.php` with `song_id` so listening history updates automatically.
