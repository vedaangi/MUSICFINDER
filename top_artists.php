<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

require_once __DIR__ . '/fetch_top_artists.php';
$user_id = (int)$_SESSION['user_id'];
$top_artists = fetchTopArtists($conn, $user_id, 5);
$max_plays = !empty($top_artists) ? max(1, $top_artists[0]['play_count']) : 1;

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">YOUR TOP ARTISTS</h1>
        <p class="page-subtitle">Your 5 most-played artists based on your listening history.</p>
    </div>
    <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Premium Hub
    </a>
</div>

<?php if (empty($top_artists)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-headphones"></i>
        <h3>No Listening History Yet</h3>
        <p>Start playing songs in Music Finder to see your top artists ranked by play count.</p>
        <a href="index.php" class="btn btn-primary">Explore Songs</a>
    </div>
<?php else: ?>
    <div class="rank-list">
        <?php foreach ($top_artists as $index => $artist):
            $rank = $index + 1;
            $plays = $artist['play_count'];
            $percent = (int)round(($plays / $max_plays) * 100);
        ?>
            <div class="rank-row">
                <div class="rank-left">
                    <div class="rank-number"><?= $rank ?></div>
                    <span class="rank-name"><?= htmlspecialchars($artist['artist_name']) ?></span>
                </div>
                <div class="rank-bar-wrap">
                    <div class="rank-bar-fill" style="width: <?= $percent ?>%;"></div>
                </div>
                <span class="rank-plays"><?= $plays ?> <?= $plays === 1 ? 'play' : 'plays' ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
