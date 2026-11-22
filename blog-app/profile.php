<?php
$pageTitle = 'Author Profile';
require_once 'includes/header.php';

$profileUsername = isset($_GET['username']) ? trim($_GET['username']) : (isLoggedIn() ? $_SESSION['username'] : '');
if (empty($profileUsername)) {
    redirect(BASE_URL . '/index.php');
}
?>

<main class="main-content">
    <div class="container">
        <!-- Profile Card -->
        <div class="profile-card" style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 2.5rem 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg); text-align: center; position: relative;">
            
            <!-- Avatar & Online Indicator -->
            <div style="position: relative; display: inline-block; margin-bottom: 1rem;">
                <img id="authorAvatar" src="https://ui-avatars.com/api/?name=<?php echo urlencode($profileUsername); ?>&background=3b82f6&color=ffffff&bold=true" 
                     alt="Author Avatar" 
                     style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-light); box-shadow: var(--shadow);">
                <?php if (isLoggedIn() && $_SESSION['username'] === $profileUsername): ?>
                    <button id="avatarUploadBtn" type="button" 
                            style="position: absolute; bottom: 0; right: 0; background: var(--primary-color); color: white; border: 2px solid white; border-radius: 50%; width: 34px; height: 34px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"
                            title="Upload new profile picture">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                <?php endif; ?>
            </div>

            <!-- Author Name & Bio -->
            <h1 id="authorUsername" style="margin: 0; font-size: 2rem; color: var(--dark-color);"><?php echo escape($profileUsername); ?></h1>
            <p id="authorBio" style="max-width: 650px; margin: 0.75rem auto 1.5rem auto; color: var(--text-light); font-size: 1.05rem; line-height: 1.6;">
                Loading bio...
            </p>

            <!-- Edit Bio Form (Hidden toggle for own profile) -->
            <?php if (isLoggedIn() && $_SESSION['username'] === $profileUsername): ?>
                <div id="editBioSection" style="display: none; max-width: 500px; margin: 0 auto 1.5rem auto; text-align: left;">
                    <textarea id="bioInput" class="form-control" rows="3" placeholder="Tell readers about yourself..."></textarea>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
                        <button type="button" id="cancelBioBtn" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">Cancel</button>
                        <button type="button" id="saveBioBtn" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">Save Bio</button>
                    </div>
                </div>
                <button type="button" id="toggleEditBioBtn" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.35rem 0.85rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-edit"></i> Edit Profile Bio
                </button>
            <?php endif; ?>

            <!-- Stats Bar -->
            <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                <div>
                    <div id="profileTotalPosts" style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">-</div>
                    <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px;">Articles</div>
                </div>
                <div>
                    <div id="profileTotalViews" style="font-size: 1.5rem; font-weight: 700; color: var(--accent-color);">-</div>
                    <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px;">Total Views</div>
                </div>
                <div>
                    <div id="profileTotalLikes" style="font-size: 1.5rem; font-weight: 700; color: var(--danger-color);">-</div>
                    <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px;">Likes Received</div>
                </div>
            </div>
        </div>

        <!-- Author Articles Feed -->
        <div class="page-header" style="margin-top: 2rem;">
            <h2>Stories by <?php echo escape($profileUsername); ?></h2>
        </div>

        <div id="authorStoriesContainer" class="blogs-grid">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading articles...</div>
        </div>
    </div>
</main>

<script>
    const profileUsername = <?php echo json_encode($profileUsername); ?>;
</script>
<script src="<?php echo assetUrl('/assets/js/profile.js'); ?>"></script>
<?php require_once 'includes/footer.php'; ?>
