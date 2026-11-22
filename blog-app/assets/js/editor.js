// Blog Editor JavaScript with EasyMDE & Modern Toast Notifications

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

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('blogForm');
    const contentTextarea = document.getElementById('content');
    const cancelBtn = document.getElementById('cancelBtn');

    // Initialize EasyMDE Editor
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

    // Load blog data if in edit mode
    if (editId > 0) {
        loadBlog(editId);
    }

    // Form submission
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveBlog();
        });
    }

    // Cancel button
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

async function loadBlog(id) {
    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/get.php?id=${id}`);
        const data = await response.json();
        
        if (data.success && data.blog) {
            if (data.blog.is_owner) {
                document.getElementById('title').value = data.blog.title;
                if (easyMDEInstance) {
                    easyMDEInstance.value(data.blog.content);
                } else {
                    document.getElementById('content').value = data.blog.content;
                }
            } else {
                if (window.showToast) {
                    showToast('You do not have permission to edit this blog post.', 'error', 'Permission Denied');
                } else {
                    alert('You do not have permission to edit this blog.');
                }
                setTimeout(() => {
                    window.location.href = appBaseUrl + '/index.php';
                }, 1000);
            }
        } else {
            if (window.showToast) {
                showToast('Blog post not found.', 'error', 'Error');
            } else {
                alert('Blog not found.');
            }
            setTimeout(() => {
                window.location.href = appBaseUrl + '/index.php';
            }, 1000);
        }
    } catch (error) {
        if (window.showToast) {
            showToast('Error loading blog: ' + error.message, 'error', 'Error');
        } else {
            alert('Error loading blog: ' + error.message);
        }
    }
}

async function saveBlog() {
    const title = document.getElementById('title').value.trim();
    const content = easyMDEInstance ? easyMDEInstance.value().trim() : document.getElementById('content').value.trim();
    const blogId = document.getElementById('blogId').value;
    
    if (!title) {
        if (window.showToast) {
            showToast('Please enter a blog title.', 'warning', 'Missing Title');
        } else {
            alert('Please enter a title.');
        }
        document.getElementById('title').focus();
        return;
    }

    if (!content) {
        if (window.showToast) {
            showToast('Please enter some content for your story.', 'warning', 'Missing Content');
        } else {
            alert('Please write some content.');
        }
        return;
    }
    
    const submitBtn = document.getElementById('saveBtn') || document.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    try {
        const url = blogId > 0 ? `${appBaseUrl}/api/blogs/update.php` : `${appBaseUrl}/api/blogs/create.php`;
        let body = `title=${encodeURIComponent(title)}&content=${encodeURIComponent(content)}`;
        if (blogId > 0) {
            body += `&id=${encodeURIComponent(blogId)}`;
        }
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (easyMDEInstance) {
                easyMDEInstance.clearAutosavedValue();
            }
            const targetId = blogId > 0 ? blogId : data.id;
            const message = blogId > 0 ? 'Blog updated successfully!' : 'Story published successfully!';
            
            if (window.showToast) {
                showToast(message, 'success', 'Saved');
            }
            
            setTimeout(() => {
                if (targetId) {
                    window.location.href = `${appBaseUrl}/blog.php?id=${targetId}`;
                } else {
                    window.location.href = `${appBaseUrl}/index.php`;
                }
            }, 700);
        } else {
            const errorMsg = data.errors ? data.errors.join(', ') : (data.error || 'Failed to save blog');
            if (window.showToast) {
                showToast(errorMsg, 'error', 'Error Saving');
            } else {
                alert('Error: ' + errorMsg);
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        if (window.showToast) {
            showToast(error.message, 'error', 'Request Failed');
        } else {
            alert('Error: ' + error.message);
        }
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}
