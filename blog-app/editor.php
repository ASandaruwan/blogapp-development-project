<?php
$pageTitle = 'Editor';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!-- EasyMDE and Prism.js Stylesheets -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">

<main class="main-content">
    <div class="container">
        <div class="editor-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
                <h1 style="margin: 0;"><?php echo $editId ? 'Edit Blog Post' : 'Create New Blog Post'; ?></h1>
                <span style="font-size: 0.85rem; color: var(--text-light); background: rgba(59, 130, 246, 0.08); padding: 0.35rem 0.75rem; border-radius: 20px;">
                    <i class="fas fa-magic"></i> Live Markdown & Split-Preview
                </span>
            </div>
            
            <form id="blogForm" class="blog-form">
                <input type="hidden" id="blogId" value="<?php echo $editId; ?>">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="Enter an engaging blog title...">
                </div>
                
                <div class="form-group">
                    <label for="content">Story Content (Markdown) *</label>
                    <textarea id="content" name="content" class="form-control" 
                              placeholder="Write your story here with full Markdown support..."></textarea>
                </div>
                
                <div class="form-actions" style="margin-top: 1rem;">
                    <button type="button" id="cancelBtn" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editId ? 'Update Post' : 'Publish Story'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    const editId = <?php echo $editId; ?>;
    const baseUrl = '<?php echo BASE_URL; ?>';
</script>

<!-- EasyMDE & Prism scripts -->
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="<?php echo assetUrl('/assets/js/editor.js'); ?>"></script>
<?php require_once 'includes/footer.php'; ?>
