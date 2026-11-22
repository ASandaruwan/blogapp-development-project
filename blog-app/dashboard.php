<?php
$pageTitle = 'My Dashboard';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$userId = getCurrentUserId();
?>

<main class="main-content">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header" style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <img id="dashAvatar" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=3b82f6&color=ffffff&bold=true" 
                         alt="Avatar" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-light);">
                    <div>
                        <h2 style="margin: 0; font-size: 1.6rem;">Welcome back, <?php echo escape($_SESSION['username']); ?>!</h2>
                        <p style="margin: 0.25rem 0 0 0; color: var(--text-light); font-size: 0.95rem;">Manage your stories, drafts, and bookmarks</p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="<?php echo BASE_URL; ?>/profile.php" class="btn btn-secondary">
                        <i class="fas fa-user-circle"></i> Public Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>/editor.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Write Story
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                <div class="stat-card" style="background: var(--light-color); padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;"><i class="fas fa-file-alt" style="color: var(--primary-color);"></i> Published Stories</div>
                    <div id="statPublished" style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem; color: var(--dark-color);">-</div>
                </div>
                <div class="stat-card" style="background: var(--light-color); padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;"><i class="fas fa-pencil-alt" style="color: var(--warning-color);"></i> Drafts</div>
                    <div id="statDrafts" style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem; color: var(--dark-color);">-</div>
                </div>
                <div class="stat-card" style="background: var(--light-color); padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;"><i class="fas fa-eye" style="color: var(--accent-color);"></i> Total Views</div>
                    <div id="statViews" style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem; color: var(--dark-color);">-</div>
                </div>
                <div class="stat-card" style="background: var(--light-color); padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;"><i class="fas fa-heart" style="color: var(--danger-color);"></i> Total Likes</div>
                    <div id="statLikes" style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem; color: var(--dark-color);">-</div>
                </div>
            </div>
        </div>

        <!-- Dashboard Navigation Tabs -->
        <div class="dashboard-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color);">
            <button class="dash-tab active" data-tab="published" style="background: none; border: none; padding: 0.75rem 1.25rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: var(--primary-color); border-bottom: 3px solid var(--primary-color); margin-bottom: -2px;">
                <i class="fas fa-newspaper"></i> My Published (<span id="tabCountPublished">0</span>)
            </button>
            <button class="dash-tab" data-tab="drafts" style="background: none; border: none; padding: 0.75rem 1.25rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: var(--text-light); border-bottom: 3px solid transparent; margin-bottom: -2px;">
                <i class="fas fa-edit"></i> My Drafts (<span id="tabCountDrafts">0</span>)
            </button>
            <button class="dash-tab" data-tab="bookmarks" style="background: none; border: none; padding: 0.75rem 1.25rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: var(--text-light); border-bottom: 3px solid transparent; margin-bottom: -2px;">
                <i class="fas fa-bookmark"></i> Saved Bookmarks
            </button>
        </div>

        <!-- Tab Content Container -->
        <div id="tabContentArea" class="blogs-grid">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading dashboard...</div>
        </div>
    </div>
</main>

<script>
    const currentUsername = <?php echo json_encode($_SESSION['username']); ?>;
    const currentUserId = <?php echo $userId; ?>;
</script>
<script src="<?php echo assetUrl('/assets/js/dashboard.js'); ?>"></script>
<?php require_once 'includes/footer.php'; ?>
