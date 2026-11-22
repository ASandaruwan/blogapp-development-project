// Single Blog Post Page JavaScript

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

document.addEventListener('DOMContentLoaded', async function() {
    // Reading Progress Bar tracker
    const progressBar = document.getElementById('readingProgressBar');
    if (progressBar) {
        window.addEventListener('scroll', function() {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const percent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            progressBar.style.width = `${Math.min(100, Math.max(0, percent))}%`;
        });
    }

    const container = document.getElementById('blogContainer');
    
    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/get.php?id=${blogId}`);
        const data = await response.json();
        
        if (data.success && data.blog) {
            const blog = data.blog;
            
            let actionsHTML = '';
            if (blog.is_owner) {
                actionsHTML = `
                    <div class="blog-actions">
                        <a href="${appBaseUrl}/editor.php?id=${blog.id}" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button onclick="deleteBlog(${blog.id})" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
            }
            
            container.innerHTML = `
                <div class="blog-header">
                    <h1 class="blog-title">${escapeHtml(blog.title)}</h1>
                    <div class="blog-meta">
                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <div class="blog-author">
                                <i class="fas fa-user"></i>
                                <span>${escapeHtml(blog.username)}</span>
                            </div>
                            <div class="blog-date">
                                <i class="fas fa-calendar"></i>
                                <span>${formatDate(blog.created_at)}</span>
                            </div>
                            <div class="blog-reading-time">
                                <i class="far fa-clock"></i>
                                <span>${calculateReadingTime(blog.content)}</span>
                            </div>
                        </div>
                        ${actionsHTML}
                    </div>
                </div>
                <div class="blog-content" id="blogPostContent">
                    ${renderMarkdown(blog.content)}
                </div>
            `;

            // Syntax highlighting for code blocks
            if (window.Prism) {
                Prism.highlightAllUnder(container);
            }
        } else {
            container.innerHTML = '<div class="error-messages active"><p>Blog not found.</p></div>';
            if (window.showToast) {
                showToast('Blog post not found', 'error', 'Error');
            }
        }
    } catch (error) {
        container.innerHTML = '<div class="error-messages active"><p>Error loading blog: ' + escapeHtml(error.message) + '</p></div>';
        if (window.showToast) {
            showToast('Failed to load blog: ' + error.message, 'error', 'Error');
        }
    }
});

async function deleteBlog(id) {
    if (!confirm('Are you sure you want to delete this blog? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (window.showToast) {
                showToast('Blog post deleted successfully', 'success', 'Deleted');
            }
            setTimeout(() => {
                window.location.href = appBaseUrl + '/index.php';
            }, 600);
        } else {
            if (window.showToast) {
                showToast(data.error || 'Failed to delete blog', 'error', 'Error');
            } else {
                alert('Error: ' + (data.error || 'Failed to delete blog'));
            }
        }
    } catch (error) {
        if (window.showToast) {
            showToast(error.message, 'error', 'Error');
        } else {
            alert('Error: ' + error.message);
        }
    }
}

function calculateReadingTime(text) {
    if (!text) return '1 min read';
    const clean = text.replace(/<[^>]*>/g, '').replace(/[#*`_~\[\]]/g, '').trim();
    const words = clean.split(/\s+/).filter(Boolean).length;
    const minutes = Math.max(1, Math.ceil(words / 200));
    return `${minutes} min read`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// Markdown renderer with Marked.js + DOMPurify (and regex fallback)
function renderMarkdown(text) {
    if (!text) return '';

    // If Marked and DOMPurify are loaded via CDN
    if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
        try {
            marked.setOptions({
                breaks: true,
                gfm: true
            });
            const rawHtml = marked.parse(text);
            return DOMPurify.sanitize(rawHtml);
        } catch (e) {
            console.warn('Marked.js parse error, falling back to basic parser:', e);
        }
    }

    // Fallback safe regex parser
    let html = escapeHtml(text);
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
    html = html.replace(/^> (.*$)/gim, '<blockquote>$1</blockquote>');
    html = html.replace(/\n\n/g, '<br><br>');
    return html;
}
