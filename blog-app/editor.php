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
                <h1 style="margin: 0;"><?php echo $editId ? 'Edit Story' : 'Create New Story'; ?></h1>
                <span style="font-size: 0.85rem; color: var(--text-light); background: rgba(59, 130, 246, 0.08); padding: 0.35rem 0.75rem; border-radius: 20px;">
                    <i class="fas fa-magic"></i> Live Markdown & Split-Preview
                </span>
            </div>
            
            <form id="blogForm" class="blog-form">
                <input type="hidden" id="blogId" value="<?php echo $editId; ?>">
                <input type="hidden" id="coverImageUrl" value="">
                
                <!-- Title -->
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="Enter an engaging story title...">
                </div>

                <!-- Category and Tags Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label for="categoryId"><i class="fas fa-folder"></i> Category</label>
                        <select id="categoryId" name="category_id" class="form-control">
                            <option value="">-- Select a Category --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tagsInput"><i class="fas fa-tags"></i> Tags (comma separated)</label>
                        <input type="text" id="tagsInput" name="tags" class="form-control" 
                               placeholder="e.g. PHP, MySQL, Web Dev">
                    </div>
                </div>

                <!-- Featured Cover Image Upload -->
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Featured Cover Image</label>
                    <div class="cover-upload-area" id="coverUploadArea">
                        <input type="file" id="coverFileInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;">
                        <div id="coverUploadPrompt" style="padding: 1.5rem; text-align: center; border: 2px dashed var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem; display: block;"></i>
                            <span style="font-weight: 600; color: var(--text-color);">Click or drop an image here</span>
                            <span style="display: block; font-size: 0.8rem; color: var(--text-light); margin-top: 0.25rem;">Supported: JPG, PNG, WebP (Max 5MB)</span>
                        </div>
                        <div id="coverPreviewContainer" style="display: none; position: relative; margin-top: 0.75rem;">
                            <img id="coverPreviewImg" src="" alt="Cover Preview" style="width: 100%; max-height: 280px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">
                            <button type="button" id="removeCoverBtn" class="btn btn-danger" style="position: absolute; top: 10px; right: 10px; padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Story Content -->
                <div class="form-group">
                    <label for="content">Story Content (Markdown) *</label>
                    <textarea id="content" name="content" class="form-control" 
                              placeholder="Write your story here with full Markdown support..."></textarea>
                </div>
                
                <!-- Form Actions: Draft & Publish -->
                <div class="form-actions" style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <button type="button" id="cancelBtn" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" id="saveDraftBtn" class="btn btn-secondary" style="border: 1px solid var(--border-color);">
                            <i class="fas fa-pencil-alt"></i> Save as Draft
                        </button>
                        <button type="button" id="publishBtn" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> <?php echo $editId ? 'Update Story' : 'Publish Story'; ?>
                        </button>
                    </div>
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
