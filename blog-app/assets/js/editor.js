// Upgraded Blog Editor with Categories, Tags, Cover Upload & Draft Support

if (typeof window.BASE_URL === 'undefined') {
    const baseUrlMeta = document.querySelector('meta[name="base-url"]');
    if (baseUrlMeta) {
        window.BASE_URL = baseUrlMeta.getAttribute('content');
    } else {
        const path = window.location.pathname;
        const pathParts = path.split('/').filter(p => p);
        if (pathParts.length > 0 && pathParts[0] === 'blog-app') {
            window.BASE_URL = window.location.origin + '/blog-app';
        } else {
            window.BASE_URL = window.location.origin;
        }
    }
}
const appBaseUrl = window.BASE_URL;

let easyMDEInstance = null;

document.addEventListener('DOMContentLoaded', async function() {
    const contentTextarea = document.getElementById('content');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const publishBtn = document.getElementById('publishBtn');

    // 1. Initialize EasyMDE Editor
    if (contentTextarea && typeof EasyMDE !== 'undefined') {
        easyMDEInstance = new EasyMDE({
            element: contentTextarea,
            autoDownloadFontAwesome: false,
            spellChecker: false,
            autosave: {
                enabled: true,
                uniqueId: editId > 0 ? `blog_edit_${editId}` : 'blog_new_draft',
                delay: 1000,
            },
            placeholder: 'Write your story with Markdown... (Headings, code blocks, lists, links, quotes)',
            renderingConfig: {
                singleLineBreaks: false,
                codeSyntaxHighlighting: true,
            },
            previewRender: function(plainText) {
                if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
                    marked.setOptions({ breaks: true, gfm: true });
                    return DOMPurify.sanitize(marked.parse(plainText));
                }
                return plainText;
            },
            status: ['lines', 'words', 'cursor'],
            toolbar: [
                'bold', 'italic', 'strikethrough', 'heading', '|',
                'quote', 'code', 'unordered-list', 'ordered-list', '|',
                'link', 'image', 'table', 'horizontal-rule', '|',
                'preview', 'side-by-side', 'fullscreen', '|',
                'guide'
            ]
        });
    }

    // 2. Load Categories
    await loadCategories();

    // 3. Initialize Cover Image Uploader
    initCoverUploader();

    // 4. Load Blog Data (if editing)
    if (editId > 0) {
        await loadBlog(editId);
    }

    // 5. Action Handlers
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            saveBlog('draft');
        });
    }

    if (publishBtn) {
        publishBtn.addEventListener('click', function(e) {
            e.preventDefault();
            saveBlog('published');
        });
    }

    // Cancel Button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel? Any unsaved edits will be discarded.')) {
                if (easyMDEInstance) {
                    easyMDEInstance.clearAutosavedValue();
                }
                window.location.href = appBaseUrl + '/index.php';
            }
        });
    }
});

// Load Categories for dropdown
async function loadCategories() {
    const categorySelect = document.getElementById('categoryId');
    if (!categorySelect) return;

    try {
        const response = await fetch(`${appBaseUrl}/api/categories/get.php`);
        const data = await response.json();
        if (data.success && data.categories) {
            data.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                categorySelect.appendChild(opt);
            });
        }
    } catch (e) {
        console.warn('Failed to load categories:', e);
    }
}

// Cover Image Upload Handler
function initCoverUploader() {
    const prompt = document.getElementById('coverUploadPrompt');
    const fileInput = document.getElementById('coverFileInput');
    const previewContainer = document.getElementById('coverPreviewContainer');
    const previewImg = document.getElementById('coverPreviewImg');
    const removeBtn = document.getElementById('removeCoverBtn');
    const coverUrlInput = document.getElementById('coverImageUrl');

    if (!prompt || !fileInput) return;

    prompt.addEventListener('click', () => fileInput.click());

    // Drag and Drop
    prompt.addEventListener('dragover', (e) => {
        e.preventDefault();
        prompt.style.borderColor = 'var(--primary-color)';
        prompt.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
    });

    prompt.addEventListener('dragleave', () => {
        prompt.style.borderColor = 'var(--border-color)';
        prompt.style.backgroundColor = 'transparent';
    });

    prompt.addEventListener('drop', (e) => {
        e.preventDefault();
        prompt.style.borderColor = 'var(--border-color)';
        prompt.style.backgroundColor = 'transparent';
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            uploadCoverFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            uploadCoverFile(fileInput.files[0]);
        }
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            coverUrlInput.value = 'remove';
            previewContainer.style.display = 'none';
            previewImg.src = '';
            prompt.style.display = 'block';
            fileInput.value = '';
            if (window.showToast) {
                showToast('Cover image removed', 'info');
            }
        });
    }

    async function uploadCoverFile(file) {
        const formData = new FormData();
        formData.append('cover', file);

        prompt.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i><span style="display: block; margin-top: 0.5rem;">Uploading image...</span>';

        try {
            const response = await fetch(`${appBaseUrl}/api/upload/cover.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success && data.url) {
                coverUrlInput.value = data.url;
                previewImg.src = data.full_url || (appBaseUrl + data.url);
                previewContainer.style.display = 'block';
                prompt.style.display = 'none';
                if (window.showToast) {
                    showToast('Cover image uploaded successfully', 'success', 'Uploaded');
                }
            } else {
                throw new Error(data.error || 'Upload failed');
            }
        } catch (err) {
            if (window.showToast) {
                showToast(err.message, 'error', 'Upload Error');
            } else {
                alert('Upload error: ' + err.message);
            }
            prompt.innerHTML = `
                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem; display: block;"></i>
                <span style="font-weight: 600; color: var(--text-color);">Click or drop an image here</span>
                <span style="display: block; font-size: 0.8rem; color: var(--text-light); margin-top: 0.25rem;">Supported: JPG, PNG, WebP (Max 5MB)</span>
            `;
        }
    }
}

// Load Blog for Editing
async function loadBlog(id) {
    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/get.php?id=${id}`);
        const data = await response.json();
        
        if (data.success && data.blog) {
            const blog = data.blog;
            if (blog.is_owner) {
                document.getElementById('title').value = blog.title;
                if (easyMDEInstance) {
                    easyMDEInstance.value(blog.content);
                } else {
                    document.getElementById('content').value = blog.content;
                }

                if (blog.category_id) {
                    document.getElementById('categoryId').value = blog.category_id;
                }

                if (blog.tags && blog.tags.length > 0) {
                    document.getElementById('tagsInput').value = blog.tags.map(t => t.name).join(', ');
                }

                if (blog.cover_image) {
                    document.getElementById('coverImageUrl').value = blog.cover_image;
                    const previewImg = document.getElementById('coverPreviewImg');
                    const previewContainer = document.getElementById('coverPreviewContainer');
                    const prompt = document.getElementById('coverUploadPrompt');
                    previewImg.src = blog.cover_url || (appBaseUrl + blog.cover_image);
                    previewContainer.style.display = 'block';
                    prompt.style.display = 'none';
                }

                if (blog.status === 'draft') {
                    const publishBtn = document.getElementById('publishBtn');
                    if (publishBtn) publishBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publish Draft';
                }
            } else {
                if (window.showToast) {
                    showToast('You do not have permission to edit this story.', 'error', 'Permission Denied');
                }
                setTimeout(() => window.location.href = appBaseUrl + '/index.php', 1000);
            }
        } else {
            if (window.showToast) {
                showToast('Blog post not found.', 'error', 'Error');
            }
            setTimeout(() => window.location.href = appBaseUrl + '/index.php', 1000);
        }
    } catch (error) {
        if (window.showToast) {
            showToast('Error loading blog: ' + error.message, 'error', 'Error');
        }
    }
}

// Save Story (Draft or Published)
async function saveBlog(targetStatus = 'published') {
    const title = document.getElementById('title').value.trim();
    const content = easyMDEInstance ? easyMDEInstance.value().trim() : document.getElementById('content').value.trim();
    const blogId = document.getElementById('blogId').value;
    const categoryId = document.getElementById('categoryId').value;
    const tags = document.getElementById('tagsInput').value.trim();
    const coverImage = document.getElementById('coverImageUrl').value;
    
    if (!title) {
        if (window.showToast) {
            showToast('Please enter a story title.', 'warning', 'Missing Title');
        }
        document.getElementById('title').focus();
        return;
    }

    if (!content) {
        if (window.showToast) {
            showToast('Please write some content for your story.', 'warning', 'Missing Content');
        }
        return;
    }
    
    const activeBtn = targetStatus === 'draft' ? document.getElementById('saveDraftBtn') : document.getElementById('publishBtn');
    const originalText = activeBtn ? activeBtn.innerHTML : '';
    if (activeBtn) {
        activeBtn.disabled = true;
        activeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    try {
        const url = blogId > 0 ? `${appBaseUrl}/api/blogs/update.php` : `${appBaseUrl}/api/blogs/create.php`;
        
        const params = new URLSearchParams();
        if (blogId > 0) params.append('id', blogId);
        params.append('title', title);
        params.append('content', content);
        params.append('status', targetStatus);
        if (categoryId) params.append('category_id', categoryId);
        if (tags) params.append('tags', tags);
        if (coverImage) params.append('cover_image', coverImage);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (easyMDEInstance) {
                easyMDEInstance.clearAutosavedValue();
            }
            
            const message = data.message || (targetStatus === 'draft' ? 'Draft saved!' : 'Story published!');
            if (window.showToast) {
                showToast(message, 'success', 'Success');
            }
            
            setTimeout(() => {
                if (targetStatus === 'draft') {
                    window.location.href = `${appBaseUrl}/dashboard`;
                } else if (data.slug) {
                    window.location.href = `${appBaseUrl}/posts/${data.slug}`;
                } else if (data.id) {
                    window.location.href = `${appBaseUrl}/blog.php?id=${data.id}`;
                } else {
                    window.location.href = `${appBaseUrl}/`;
                }
            }, 700);
        } else {
            const errorMsg = data.errors ? data.errors.join(', ') : (data.error || 'Failed to save');
            if (window.showToast) {
                showToast(errorMsg, 'error', 'Error');
            }
            if (activeBtn) {
                activeBtn.disabled = false;
                activeBtn.innerHTML = originalText;
            }
        }
    } catch (error) {
        if (window.showToast) {
            showToast(error.message, 'error', 'Request Failed');
        }
        if (activeBtn) {
            activeBtn.disabled = false;
            activeBtn.innerHTML = originalText;
        }
    }
}
