// Single Blog Post with Likes, Bookmarks, Comments, Author Bio, and Tags

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

let currentPostData = null;

document.addEventListener('DOMContentLoaded', async function() {
    // 1. Reading Progress Bar tracker
    const progressBar = document.getElementById('readingProgressBar');
    if (progressBar) {
        window.addEventListener('scroll', function() {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const percent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            progressBar.style.width = `${Math.min(100, Math.max(0, percent))}%`;
        });
    }

    // 2. Fetch Blog Post Data
    await loadBlogPost();

    // 3. Initialize Comment Submission Form
    initCommentForm();
});

async function loadBlogPost() {
    const container = document.getElementById('blogContainer');
    const commentsSection = document.getElementById('commentsSection');
    
    try {
        let endpoint = `${appBaseUrl}/api/blogs/get.php?`;
        if (blogId > 0) {
            endpoint += `id=${blogId}`;
        } else if (blogSlug) {
            endpoint += `slug=${encodeURIComponent(blogSlug)}`;
        }
        
        const response = await fetch(endpoint);
        const data = await response.json();
        
        if (data.success && data.blog) {
            const blog = data.blog;
            currentPostData = blog;

            // Render Cover Banner
            let coverHTML = '';
            if (blog.cover_image) {
                coverHTML = `
                    <div class="blog-cover-wrapper" style="margin: -2rem -2rem 2rem -2rem; overflow: hidden; border-radius: 0.75rem 0.75rem 0 0; max-height: 420px;">
                        <img src="${escapeHtml(blog.cover_url || (appBaseUrl + blog.cover_image))}" 
                             alt="${escapeHtml(blog.title)}" 
                             style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    </div>
                `;
            }

            // Category Badge
            let categoryHTML = '';
            if (blog.category_name) {
                categoryHTML = `
                    <a href="${appBaseUrl}/index.php?category=${encodeURIComponent(blog.category_slug)}" 
                       class="blog-category-badge" 
                       style="display: inline-block; background: rgba(59, 130, 246, 0.12); color: var(--primary-color); font-weight: 600; font-size: 0.85rem; padding: 0.25rem 0.75rem; border-radius: 20px; text-decoration: none; margin-bottom: 0.75rem;">
                        <i class="fas fa-folder"></i> ${escapeHtml(blog.category_name)}
                    </a>
                `;
            }

            // Status Badge (if draft)
            let statusBadge = '';
            if (blog.status === 'draft') {
                statusBadge = `
                    <span style="display: inline-block; background: rgba(245, 158, 11, 0.15); color: var(--warning-color); font-weight: 700; font-size: 0.8rem; padding: 0.25rem 0.65rem; border-radius: 20px; margin-left: 0.5rem;">
                        <i class="fas fa-pencil-alt"></i> DRAFT
                    </span>
                `;
            }

            // Tags Pills
            let tagsHTML = '';
            if (blog.tags && blog.tags.length > 0) {
                tagsHTML = `
                    <div class="blog-tags-wrapper" style="margin: 2rem 0; padding-top: 1.5rem; border-top: 1px solid var(--border-color); display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-right: 0.25rem;"><i class="fas fa-tags"></i> Tags:</span>
                        ${blog.tags.map(t => `
                            <a href="${appBaseUrl}/index.php?tag=${encodeURIComponent(t.slug)}" 
                               style="background: var(--light-color); color: var(--text-color); border: 1px solid var(--border-color); font-size: 0.85rem; padding: 0.25rem 0.65rem; border-radius: 6px; text-decoration: none; transition: all 0.2s;">
                                #${escapeHtml(t.name)}
                            </a>
                        `).join('')}
                    </div>
                `;
            }

            // Action Bar (Likes, Bookmark, Share, Edit/Delete)
            const isLiked = blog.is_liked;
            const isBookmarked = blog.is_bookmarked;
            const likeCount = blog.like_count || 0;

            let ownerActions = '';
            if (blog.is_owner) {
                ownerActions = `
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="${appBaseUrl}/editor/${blog.id}" class="btn btn-secondary" style="padding: 0.4rem 0.85rem; font-size: 0.9rem;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button onclick="deleteBlog(${blog.id})" class="btn btn-danger" style="padding: 0.4rem 0.85rem; font-size: 0.9rem;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
            }

            const actionsBarHTML = `
                <div class="blog-action-bar" style="margin-top: 1.5rem; padding: 1rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <!-- Like Button -->
                        <button id="likeBtn" onclick="toggleLike(${blog.id})" 
                                class="btn-action ${isLiked ? 'active' : ''}" 
                                style="background: ${isLiked ? 'rgba(239, 68, 68, 0.15)' : 'var(--light-color)'}; color: ${isLiked ? '#ef4444' : 'var(--text-color)'}; border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: 20px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;">
                            <i class="${isLiked ? 'fas' : 'far'} fa-heart" style="${isLiked ? 'color: #ef4444;' : ''}"></i> 
                            <span id="likeCountText">${likeCount}</span>
                        </button>

                        <!-- Bookmark Button -->
                        <button id="bookmarkBtn" onclick="toggleBookmark(${blog.id})" 
                                class="btn-action ${isBookmarked ? 'active' : ''}" 
                                style="background: ${isBookmarked ? 'rgba(59, 130, 246, 0.15)' : 'var(--light-color)'}; color: ${isBookmarked ? 'var(--primary-color)' : 'var(--text-color)'}; border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: 20px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;"
                                title="${isBookmarked ? 'Saved to bookmarks' : 'Save for later'}">
                            <i class="${isBookmarked ? 'fas' : 'far'} fa-bookmark"></i>
                            <span id="bookmarkText">${isBookmarked ? 'Saved' : 'Save'}</span>
                        </button>

                        <!-- Share Button -->
                        <button onclick="sharePost()" 
                                style="background: var(--light-color); color: var(--text-color); border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: 20px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>

                    ${ownerActions}
                </div>
            `;

            // Author Bio Card
            const authorBioHTML = `
                <div class="author-bio-card" style="margin-top: 2.5rem; background: var(--light-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap;">
                    <img src="${escapeHtml(blog.avatar_url)}" alt="${escapeHtml(blog.username)}" 
                         style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-light);">
                    <div style="flex: 1; min-width: 200px;">
                        <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-light); font-weight: 700;">Written by</span>
                        <h4 style="margin: 0.2rem 0; font-size: 1.2rem;">
                            <a href="${appBaseUrl}/profile/${encodeURIComponent(blog.username)}" style="color: inherit; text-decoration: none;">
                                ${escapeHtml(blog.username)}
                            </a>
                        </h4>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: var(--text-light);">
                            ${blog.bio ? escapeHtml(blog.bio) : 'Contributor and writer on Blog App.'}
                        </p>
                    </div>
                    <div>
                        <a href="${appBaseUrl}/profile/${encodeURIComponent(blog.username)}" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">
                            View Profile
                        </a>
                    </div>
                </div>
            `;

            container.innerHTML = `
                ${coverHTML}
                <div class="blog-header">
                    ${categoryHTML}
                    <h1 class="blog-title">${escapeHtml(blog.title)} ${statusBadge}</h1>
                    <div class="blog-meta" style="margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <a href="${appBaseUrl}/profile/${encodeURIComponent(blog.username)}" 
                               class="blog-author" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                                <img src="${escapeHtml(blog.avatar_url)}" alt="${escapeHtml(blog.username)}" 
                                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                <span style="font-weight: 600;">${escapeHtml(blog.username)}</span>
                            </a>
                            <div class="blog-date">
                                <i class="fas fa-calendar"></i>
                                <span>${formatDate(blog.created_at)}</span>
                            </div>
                            <div class="blog-reading-time">
                                <i class="far fa-clock"></i>
                                <span>${calculateReadingTime(blog.content)}</span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-light);">
                                <i class="far fa-eye"></i> ${blog.views || 0} views
                            </div>
                        </div>
                    </div>
                    ${actionsBarHTML}
                </div>
                <div class="blog-content" id="blogPostContent">
                    ${renderMarkdown(blog.content)}
                </div>
                ${tagsHTML}
                ${authorBioHTML}
            `;

            // Trigger Prism syntax highlighting
            if (window.Prism) {
                Prism.highlightAllUnder(container);
            }

            // Show Comments Section & Load Comments
            if (commentsSection) {
                commentsSection.style.display = 'block';
                loadComments(blog.id);
            }
        } else {
            container.innerHTML = '<div class="error-messages active"><p>Story not found.</p></div>';
            if (window.showToast) {
                showToast('Blog post not found', 'error', 'Error');
            }
        }
    } catch (error) {
        container.innerHTML = '<div class="error-messages active"><p>Error loading story: ' + escapeHtml(error.message) + '</p></div>';
        if (window.showToast) {
            showToast('Failed to load story: ' + error.message, 'error', 'Error');
        }
    }
}

// ------------------------------------------------------------------
// Comments System
// ------------------------------------------------------------------
async function loadComments(postId) {
    const listContainer = document.getElementById('commentsList');
    const countSpan = document.getElementById('commentsCount');
    if (!listContainer) return;

    try {
        const response = await fetch(`${appBaseUrl}/api/comments/get.php?post_id=${postId}`);
        const data = await response.json();

        if (data.success && data.comments) {
            if (countSpan) countSpan.textContent = data.total;

            if (data.comments.length === 0) {
                listContainer.innerHTML = '<div style="text-align: center; color: var(--text-light); padding: 1.5rem;">No comments yet. Be the first to start the discussion!</div>';
                return;
            }

            listContainer.innerHTML = data.comments.map(c => `
                <div class="comment-item" id="comment-${c.id}" 
                     style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <a href="${appBaseUrl}/profile/${encodeURIComponent(c.username)}" 
                           style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none; color: inherit;">
                            <img src="${escapeHtml(c.avatar_url)}" alt="${escapeHtml(c.username)}" 
                                 style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">${escapeHtml(c.username)}</div>
                                <div style="font-size: 0.75rem; color: var(--text-light);">${formatDate(c.created_at)}</div>
                            </div>
                        </a>
                        ${c.can_delete ? `
                            <button onclick="deleteComment(${c.id})" 
                                    style="background: none; border: none; color: var(--text-light); cursor: pointer; padding: 0.25rem;" 
                                    title="Delete comment">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div style="font-size: 0.95rem; line-height: 1.5; color: var(--text-color); margin-top: 0.5rem; word-break: break-word;">
                        ${escapeHtml(c.content).replace(/\n/g, '<br>')}
                    </div>
                </div>
            `).join('');
        }
    } catch (err) {
        listContainer.innerHTML = '<div style="color: var(--danger-color); padding: 1rem;">Failed to load comments.</div>';
    }
}

function initCommentForm() {
    const form = document.getElementById('commentForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const textarea = document.getElementById('commentContent');
        const submitBtn = document.getElementById('submitCommentBtn');
        const content = textarea.value.trim();

        if (!content) {
            if (window.showToast) showToast('Please enter a comment', 'warning');
            return;
        }

        if (!currentPostData || !currentPostData.id) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

        try {
            const params = new URLSearchParams();
            params.append('post_id', currentPostData.id);
            params.append('content', content);

            const response = await fetch(`${appBaseUrl}/api/comments/create.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            });

            const data = await response.json();
            if (data.success) {
                textarea.value = '';
                if (window.showToast) showToast('Comment posted!', 'success');
                await loadComments(currentPostData.id);
            } else {
                if (window.showToast) showToast(data.error || 'Failed to post comment', 'error');
            }
        } catch (err) {
            if (window.showToast) showToast(err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Post Comment';
        }
    });
}

async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    try {
        const response = await fetch(`${appBaseUrl}/api/comments/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${commentId}`
        });
        const data = await response.json();
        if (data.success) {
            if (window.showToast) showToast('Comment deleted', 'info');
            const commentEl = document.getElementById(`comment-${commentId}`);
            if (commentEl) commentEl.remove();
            const countSpan = document.getElementById('commentsCount');
            if (countSpan) {
                const currentCount = parseInt(countSpan.textContent) || 1;
                countSpan.textContent = Math.max(0, currentCount - 1);
            }
        } else {
            if (window.showToast) showToast(data.error || 'Failed to delete', 'error');
        }
    } catch (err) {
        if (window.showToast) showToast(err.message, 'error');
    }
}

// ------------------------------------------------------------------
// Likes and Bookmarks
// ------------------------------------------------------------------
async function toggleLike(postId) {
    if (!isUserLoggedIn) {
        if (window.showToast) {
            showToast('Please log in to like this story', 'info', 'Login Required');
        }
        return;
    }

    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/like.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${postId}`
        });
        const data = await response.json();
        if (data.success) {
            const likeBtn = document.getElementById('likeBtn');
            const likeCountText = document.getElementById('likeCountText');
            if (likeCountText) likeCountText.textContent = data.count;

            if (likeBtn) {
                const icon = likeBtn.querySelector('i');
                if (data.liked) {
                    likeBtn.classList.add('active');
                    likeBtn.style.background = 'rgba(239, 68, 68, 0.15)';
                    likeBtn.style.color = '#ef4444';
                    if (icon) {
                        icon.className = 'fas fa-heart';
                        icon.style.color = '#ef4444';
                    }
                    if (window.showToast) showToast('Story liked!', 'success');
                } else {
                    likeBtn.classList.remove('active');
                    likeBtn.style.background = 'var(--light-color)';
                    likeBtn.style.color = 'var(--text-color)';
                    if (icon) {
                        icon.className = 'far fa-heart';
                        icon.style.color = '';
                    }
                }
            }
        }
    } catch (err) {
        if (window.showToast) showToast(err.message, 'error');
    }
}

async function toggleBookmark(postId) {
    if (!isUserLoggedIn) {
        if (window.showToast) {
            showToast('Please log in to bookmark stories', 'info', 'Login Required');
        }
        return;
    }

    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/bookmark.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${postId}`
        });
        const data = await response.json();
        if (data.success) {
            const bmBtn = document.getElementById('bookmarkBtn');
            const bmText = document.getElementById('bookmarkText');
            if (bmBtn) {
                const icon = bmBtn.querySelector('i');
                if (data.bookmarked) {
                    bmBtn.classList.add('active');
                    bmBtn.style.background = 'rgba(59, 130, 246, 0.15)';
                    bmBtn.style.color = 'var(--primary-color)';
                    if (icon) icon.className = 'fas fa-bookmark';
                    if (bmText) bmText.textContent = 'Saved';
                    if (window.showToast) showToast('Saved to your bookmarks!', 'success');
                } else {
                    bmBtn.classList.remove('active');
                    bmBtn.style.background = 'var(--light-color)';
                    bmBtn.style.color = 'var(--text-color)';
                    if (icon) icon.className = 'far fa-bookmark';
                    if (bmText) bmText.textContent = 'Save';
                    if (window.showToast) showToast('Removed from bookmarks', 'info');
                }
            }
        }
    } catch (err) {
        if (window.showToast) showToast(err.message, 'error');
    }
}

function sharePost() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            if (window.showToast) showToast('Story link copied to clipboard!', 'success', 'Link Copied');
        }).catch(() => {
            prompt('Copy story link:', window.location.href);
        });
    } else {
        prompt('Copy story link:', window.location.href);
    }
}

async function deleteBlog(id) {
    if (!confirm('Are you sure you want to delete this story? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        });
        
        const data = await response.json();
        if (data.success) {
            if (window.showToast) showToast('Story deleted successfully', 'success', 'Deleted');
            setTimeout(() => {
                window.location.href = appBaseUrl + '/index.php';
            }, 600);
        } else {
            if (window.showToast) showToast(data.error || 'Failed to delete story', 'error');
        }
    } catch (error) {
        if (window.showToast) showToast(error.message, 'error');
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
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function renderMarkdown(text) {
    if (!text) return '';
    if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
        try {
            marked.setOptions({ breaks: true, gfm: true });
            return DOMPurify.sanitize(marked.parse(text));
        } catch (e) {
            console.warn('Marked.js error:', e);
        }
    }
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
