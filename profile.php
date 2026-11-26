<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$email = getCurrentEmail();

// Fetch user record
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$pl_count = $conn->query("SELECT COUNT(*) FROM playlists WHERE user_id = $user_id")->fetch_row()[0] ?? 0;
$lh_count = $conn->query("SELECT COUNT(*) FROM listening_history WHERE user_id = $user_id")->fetch_row()[0] ?? 0;
$fav_count = count(getUserFavoriteSongIds($conn, $user_id));
$genre_info = getUserTopGenreInfo($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Profile</h1>
        <p class="page-subtitle">Account overview, preferences, and module integration details.</p>
    </div>
    <a href="logout.php" class="btn btn-danger btn-sm">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
    </a>
</div>

<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 28px;">
    
    <!-- Profile Card -->
    <div class="card">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--accent-light); border: 2px solid var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--accent);">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; color: var(--text-primary); margin-bottom: 2px;"><?= htmlspecialchars($user['username'] ?? $username) ?></h2>
                <span style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($user['email'] ?? $email) ?></span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem; color: var(--text-secondary); border-top: 1px solid var(--border-color); padding-top: 14px;">
            <div><strong style="color: var(--text-primary);">User ID:</strong> <?= $user_id ?></div>
            <div><strong style="color: var(--text-primary);">Member Since:</strong> <?= isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'Active' ?></div>
            <div><strong style="color: var(--text-primary);">Session Status:</strong> <span style="color: var(--accent); font-weight: 600;">Authenticated (Member 1 Integration)</span></div>
        </div>
    </div>

    <!-- Activity Metrics -->
    <div class="card">
        <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 14px;"><i class="fa-solid fa-chart-simple" style="color: var(--accent);"></i> Activity Metrics</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
            <div style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius); text-align: center;">
                <span style="font-size: 0.78rem; color: var(--text-muted);">Playlists</span>
                <div style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;"><?= $pl_count ?></div>
            </div>
            <div style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius); text-align: center;">
                <span style="font-size: 0.78rem; color: var(--text-muted);">Liked Songs</span>
                <div style="font-size: 1.4rem; font-weight: 700; color: var(--accent); margin-top: 4px;"><?= $fav_count ?></div>
            </div>
            <div style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius); text-align: center;">
                <span style="font-size: 0.78rem; color: var(--text-muted);">Streams</span>
                <div style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;"><?= $lh_count ?></div>
            </div>
        </div>

        <div style="margin-top: 14px; padding: 12px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius);">
            <span style="font-size: 0.8rem; color: var(--text-muted);">Top Listened Genre</span>
            <div style="font-size: 1.1rem; font-weight: 600; color: var(--accent); margin-top: 4px;">
                <?= htmlspecialchars($genre_info['top_genre'] ?? 'None yet') ?>
                <?php if ($genre_info['top_count'] > 0): ?>
                    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400;">(<?= $genre_info['top_count'] ?> plays)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Demo Mode Switcher Box for Grading / Presentation -->
<div class="card" style="border-left: 4px solid var(--accent); margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
        <h3 style="font-size: 1.05rem; color: var(--text-primary);">
            <i class="fa-solid fa-flask" style="color: var(--accent);"></i> Local Demo Switcher (Member 1 Integration)
        </h3>
        <span style="font-size: 0.78rem; padding: 4px 12px; background: var(--accent-light); color: var(--accent); border-radius: 12px; font-weight: 600;">
            College Presentation Mode
        </span>
    </div>
    <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 14px;">
        Easily switch active session between test users to verify that playlists, listening history, liked songs, and recommendation engines are strictly scoped to the signed-in user:
    </p>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <form method="POST" action="login.php">
            <input type="hidden" name="demo_user_id" value="1">
            <button type="submit" class="btn <?= $user_id === 1 ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-user"></i> Switch to User 1: alex_music (Indie Fan)
            </button>
        </form>
        <form method="POST" action="login.php">
            <input type="hidden" name="demo_user_id" value="2">
            <button type="submit" class="btn <?= $user_id === 2 ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-user"></i> Switch to User 2: sarah_grooves (Rock Fan)
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
