// Dashboard JavaScript

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

let currentTab = 'published';

document.addEventListener('DOMContentLoaded', async function() {
    // 1. Load User Stats
    await loadUserStats();

    // 2. Load Drafts count
    await loadDraftsCount();

    // 3. Tab switching listeners
    const tabs = document.querySelectorAll('.dash-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => {
                t.classList.remove('active');
                t.style.color = 'var(--text-light)';
                t.style.borderBottomColor = 'transparent';
            });
            this.classList.add('active');
            this.style.color = 'var(--primary-color)';
            this.style.borderBottomColor = 'var(--primary-color)';
            currentTab = this.dataset.tab;
            loadTabContent(currentTab);
        });
    });

    // 4. Initial load of published stories
    loadTabContent('published');
});

async function loadUserStats() {
    try {
        const response = await fetch(`${appBaseUrl}/api/user/profile.php`);
        const data = await response.json();
        if (data.success && data.user) {
            const u = data.user;
            const avatarImg = document.getElementById('dashAvatar');
            if (avatarImg) avatarImg.src = u.avatar_url;

            const publishedEl = document.getElementById('statPublished');
            const viewsEl = document.getElementById('statViews');
            const likesEl = document.getElementById('statLikes');
            const tabPublishedEl = document.getElementById('tabCountPublished');

            if (publishedEl) publishedEl.textContent = u.stats.total_posts;
            if (viewsEl) viewsEl.textContent = u.stats.total_views;
            if (likesEl) likesEl.textContent = u.stats.total_likes;
            if (tabPublishedEl) tabPublishedEl.textContent = u.stats.total_posts;
        }
    } catch (e) {
        console.warn('Failed to load user stats:', e);
    }
}

async function loadDraftsCount() {
    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/get.php?drafts=1&limit=1`);
        const data = await response.json();
        if (data.success) {
            const draftsEl = document.getElementById('statDrafts');
            const tabDraftsEl = document.getElementById('tabCountDrafts');
            if (draftsEl) draftsEl.textContent = data.total;
            if (tabDraftsEl) tabDraftsEl.textContent = data.total;
        }
    } catch (e) {
        console.warn('Failed to load drafts count:', e);
    }
}

async function loadTabContent(tabName) {
    const container = document.getElementById('tabContentArea');
    if (!container) return;

    container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    let endpoint = '';
    if (tabName === 'published') {
        endpoint = `${appBaseUrl}/api/blogs/get.php?author=${encodeURIComponent(currentUsername)}`;
    } else if (tabName === 'drafts') {
        endpoint = `${appBaseUrl}/api/blogs/get.php?drafts=1`;
    } else if (tabName === 'bookmarks') {
        endpoint = `${appBaseUrl}/api/blogs/get.php?bookmarked=1`;
    }

    try {
        const response = await fetch(endpoint);
        const data = await response.json();

        if (data.success && data.blogs) {
            if (data.blogs.length === 0) {
                let emptyMsg = 'No published stories yet.';
                let btnHtml = `<a href="${appBaseUrl}/editor" class="btn btn-primary" style="margin-top: 1rem;"><i class="fas fa-plus"></i> Write Your First Story</a>`;
                
                if (tabName === 'drafts') {
                    emptyMsg = 'You have no saved drafts.';
                    btnHtml = `<a href="${appBaseUrl}/editor" class="btn btn-primary" style="margin-top: 1rem;"><i class="fas fa-edit"></i> Start a New Draft</a>`;
                } else if (tabName === 'bookmarks') {
                    emptyMsg = 'You haven\'t bookmarked any stories yet.';
                    btnHtml = `<a href="${appBaseUrl}/" class="btn btn-primary" style="margin-top: 1rem;"><i class="fas fa-compass"></i> Explore Stories</a>`;
                }

                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; background: var(--surface-bg); border: 1px dashed var(--border-color); border-radius: 12px;">
                        <i class="fas fa-folder-open" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 0.75rem; display: block;"></i>
                        <p style="color: var(--text-light); font-size: 1.1rem; margin: 0;">${emptyMsg}</p>
                        ${btnHtml}
                    </div>
                `;
                return;
            }

            container.innerHTML = data.blogs.map(blog => {
                const isDraft = blog.status === 'draft';
                const postUrl = isDraft 
                    ? `${appBaseUrl}/editor/${blog.id}` 
                    : (blog.slug ? `${appBaseUrl}/posts/${blog.slug}` : `${appBaseUrl}/blog.php?id=${blog.id}`);

                let coverHtml = '';
                if (blog.cover_image) {
                    coverHtml = `
                        <div style="height: 160px; overflow: hidden;">
                            <img src="${blog.cover_url || (appBaseUrl + blog.cover_image)}" alt="${escapeHtml(blog.title)}" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    `;
                }

                return `
                    <div class="blog-card" style="display: flex; flex-direction: column;">
                        ${coverHtml}
                        <div class="blog-card-header" style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                ${blog.category_name ? `
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary-color); background: rgba(59, 130, 246, 0.1); padding: 0.2rem 0.6rem; border-radius: 12px;">
                                        ${escapeHtml(blog.category_name)}
                                    </span>
                                ` : '<span></span>'}
                                ${isDraft ? `
                                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--warning-color); background: rgba(245, 158, 11, 0.15); padding: 0.2rem 0.55rem; border-radius: 12px;">
                                        DRAFT
                                    </span>
                                ` : ''}
                            </div>
                            <h3 class="blog-card-title" style="font-size: 1.25rem;">
                                <a href="${postUrl}">${escapeHtml(blog.title)}</a>
                            </h3>
                            <p class="blog-card-excerpt">${truncateText(blog.content, 120)}</p>
                        </div>

                        <div class="blog-card-meta" style="flex-wrap: wrap; gap: 0.5rem; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.8rem;">
                                <span><i class="fas fa-calendar"></i> ${formatDate(blog.created_at)}</span>
                                ${!isDraft ? `<span><i class="far fa-eye"></i> ${blog.views || 0}</span>` : ''}
                                ${!isDraft ? `<span><i class="far fa-heart"></i> ${blog.like_count || 0}</span>` : ''}
                            </div>
                            <div style="display: flex; gap: 0.35rem;">
                                <a href="${appBaseUrl}/editor/${blog.id}" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button onclick="deleteStory(${blog.id})" class="btn btn-danger" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch (err) {
        container.innerHTML = '<div style="color: var(--danger-color); padding: 1.5rem; text-align: center;">Error loading stories.</div>';
    }
}

async function deleteStory(id) {
    if (!confirm('Are you sure you want to delete this story? This cannot be undone.')) return;

    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        });
        const data = await response.json();
        if (data.success) {
            if (window.showToast) showToast('Story deleted', 'success');
            await loadUserStats();
            await loadDraftsCount();
            loadTabContent(currentTab);
        } else {
            if (window.showToast) showToast(data.error || 'Failed to delete', 'error');
        }
    } catch (e) {
        if (window.showToast) showToast(e.message, 'error');
    }
}

function truncateText(text, maxLen) {
    if (!text) return '';
    const clean = text.replace(/<[^>]*>/g, '').replace(/[#*`_~\[\]]/g, '').trim();
    if (clean.length <= maxLen) return escapeHtml(clean);
    return escapeHtml(clean.substring(0, maxLen)) + '...';
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
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
