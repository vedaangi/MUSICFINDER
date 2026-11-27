<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

require_once __DIR__ . '/fetch_top_artists.php';
$user_id = (int)$_SESSION['user_id'];
$top_artists = fetchTopArtists($conn, $user_id, 5);
$max_plays = !empty($top_artists) ? max(1, $top_artists[0]['play_count']) : 1;

// Curated artist mood board images (Unsplash verified portraits)
$artistVisuals = [
    'The Neighbourhood' => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=600&q=80',
    'Glass Animals'     => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=600&q=80',
    'Arctic Monkeys'    => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=600&q=80',
    'Dua Lipa'          => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=600&q=80',
    'Queen'             => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=600&q=80',
    'The Weeknd'        => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=600&q=80',
    'Billie Eilish'     => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80',
    'Taylor Swift'      => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=600&q=80',
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">TOP 5 ARTISTS</h1>
        <p class="page-subtitle">A curated countdown of your most-streamed musical influences in VARP.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="music_personality.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-masks-theater"></i> Personality
        </a>
        <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Premium Hub
        </a>
    </div>
</div>

<?php if (empty($top_artists)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-headphones"></i>
        <h3>No Listening History Yet</h3>
        <p>Start streaming songs in VARP to populate your personalized top artist gallery.</p>
        <a href="index.php" class="btn btn-primary">Discover Music</a>
    </div>
<?php else: ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--forest-100); font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-ranking-star"></i> VIP Analytics // Audio Hall of Fame
            </div>
            <h2 class="insight-title">Your #1 Artist: <?= htmlspecialchars($top_artists[0]['artist_name']) ?></h2>
            <p class="insight-text">Leading your soundtrack with <?= (int)$top_artists[0]['play_count'] ?> streams.</p>
        </div>
        <div class="insight-badge">#1 ICON</div>
    </div>

    <!-- Editorial Artist Cards Mood Board -->
    <div class="grid-container" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); margin-bottom: 40px;">
        <?php foreach ($top_artists as $index => $artist):
            $rank = $index + 1;
            $name = $artist['artist_name'];
            $plays = (int)$artist['play_count'];
            $percent = (int)round(($plays / $max_plays) * 100);
            $img = $artistVisuals[$name] ?? 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=600&q=80';
        ?>
            <div class="card" style="padding: 22px; position: relative;">
                <div style="position: relative; width: 100%; height: 200px; border-radius: var(--radius-md); overflow: hidden; margin-bottom: 16px;">
                    <img src="<?= htmlspecialchars($img) ?>" 
                         alt="<?= htmlspecialchars($name) ?>" 
                         style="width: 100%; height: 100%; object-fit: cover; margin-bottom: 0;"
                         onerror="this.onerror=null; this.src='assets/images/placeholder_movie.svg';">
                    <span class="badge-year" style="position: absolute; top: 12px; left: 12px; background: rgba(30, 26, 41, 0.9); border: 1px solid var(--border-card); color: var(--forest-200); font-size: 0.82rem; font-weight: 800;">
                        #0<?= $rank ?>
                    </span>
                </div>

                <h3 style="font-size: 1.3rem; margin: 0 0 6px 0; color: #F7F5F0;"><?= htmlspecialchars($name) ?></h3>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 0.86rem; color: var(--text-secondary);"><?= $plays ?> <?= $plays === 1 ? 'stream' : 'streams' ?></span>
                    <span class="genre-tag" style="margin-top: 0; padding: 2px 10px; font-size: 0.72rem;">RANK <?= $rank ?></span>
                </div>

                <div class="rank-bar-wrap" style="width: 100%; height: 6px; margin-bottom: 18px; background: rgba(20, 18, 26, 0.7);">
                    <div class="rank-bar" style="width: <?= $percent ?>%; background: var(--gradient-brand);"></div>
                </div>

                <div style="margin-top: auto;">
                    <a href="index.php?q=<?= urlencode($name) ?>" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-magnifying-glass"></i> Find <?= htmlspecialchars($name) ?> Songs
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
