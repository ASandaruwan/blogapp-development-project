<?php
$pageTitle = 'Blog Post';
require_once 'includes/header.php';

$blogId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$blogId) {
    redirect(BASE_URL . '/index.php');
}
?>
<!-- Top Reading Progress Bar -->
<div id="readingProgressBar" class="reading-progress-bar"></div>

<!-- Prism Syntax Highlighting Styles -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">

<main class="main-content">
    <div class="container">
        <div id="blogContainer" class="blog-single">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading blog...
            </div>
        </div>
    </div>
</main>

<script>
    const blogId = <?php echo $blogId; ?>;
</script>
<!-- Markdown parser, sanitizer and syntax highlighter -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="<?php echo assetUrl('/assets/js/blog.js'); ?>"></script>
<?php require_once 'includes/footer.php'; ?>
