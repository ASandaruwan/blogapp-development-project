<?php
$pageTitle = 'Blog Post';
require_once 'includes/header.php';

$blogId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$blogSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!$blogId && empty($blogSlug)) {
    redirect(BASE_URL . '/index.php');
}
?>
<!-- Top Reading Progress Bar -->
<div id="readingProgressBar" class="reading-progress-bar"></div>

<!-- Prism Syntax Highlighting Styles -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">

<main class="main-content">
    <div class="container">
        <!-- Main Blog Post Container -->
        <article id="blogContainer" class="blog-single">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading story...
            </div>
        </article>

        <!-- Comments & Discussion Section -->
        <section id="commentsSection" class="comments-section" style="max-width: 800px; margin: 2rem auto; display: none;">
            <div class="comments-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.4rem;">
                    <i class="fas fa-comments" style="color: var(--primary-color);"></i> Discussion (<span id="commentsCount">0</span>)
                </h3>
            </div>

            <!-- Comment Form (Logged in vs Logged out) -->
            <div id="commentFormContainer" style="margin-bottom: 2rem;">
                <?php if (isLoggedIn()): ?>
                    <form id="commentForm" class="comment-form" style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; box-shadow: var(--shadow);">
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <textarea id="commentContent" class="form-control" rows="3" required 
                                      placeholder="What are your thoughts? Share in the discussion..." style="resize: vertical;"></textarea>
                        </div>
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" id="submitCommentBtn" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">
                                <i class="fas fa-paper-plane"></i> Post Comment
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="background: var(--surface-bg); border: 1px dashed var(--border-color); border-radius: 10px; padding: 1.5rem; text-align: center;">
                        <p style="margin-bottom: 0.75rem; color: var(--text-light);">Log in to join the conversation and leave a comment.</p>
                        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Comment
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Comments List -->
            <div id="commentsList" class="comments-list">
                <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading comments...</div>
            </div>
        </section>
    </div>
</main>

<script>
    const blogId = <?php echo $blogId; ?>;
    const blogSlug = <?php echo json_encode($blogSlug); ?>;
    const isUserLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
</script>
<!-- Markdown parser, sanitizer and syntax highlighter -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="<?php echo assetUrl('/assets/js/blog.js'); ?>"></script>
<?php require_once 'includes/footer.php'; ?>
