// Author Profile JavaScript

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
    await loadProfileData();
    await loadAuthorStories();
    initProfileEditing();
});

async function loadProfileData() {
    try {
        const response = await fetch(`${appBaseUrl}/api/user/profile.php?username=${encodeURIComponent(profileUsername)}`);
        const data = await response.json();

        if (data.success && data.user) {
            const u = data.user;
            const avatarImg = document.getElementById('authorAvatar');
            const usernameEl = document.getElementById('authorUsername');
            const bioEl = document.getElementById('authorBio');
            const bioInput = document.getElementById('bioInput');

            if (avatarImg) avatarImg.src = u.avatar_url;
            if (usernameEl) usernameEl.textContent = u.username;
            if (bioEl) {
                bioEl.textContent = u.bio ? u.bio : 'This author hasn\'t written a bio yet.';
                bioEl.style.fontStyle = u.bio ? 'normal' : 'italic';
            }
            if (bioInput && u.bio) {
                bioInput.value = u.bio;
            }

            document.getElementById('profileTotalPosts').textContent = u.stats.total_posts;
            document.getElementById('profileTotalViews').textContent = u.stats.total_views;
            document.getElementById('profileTotalLikes').textContent = u.stats.total_likes;
        }
    } catch (e) {
        console.warn('Failed to load profile:', e);
    }
}

async function loadAuthorStories() {
    const container = document.getElementById('authorStoriesContainer');
    if (!container) return;

    try {
        const response = await fetch(`${appBaseUrl}/api/blogs/get.php?author=${encodeURIComponent(profileUsername)}`);
        const data = await response.json();

        if (data.success && data.blogs) {
            if (data.blogs.length === 0) {
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: var(--text-light);">
                        <p style="font-size: 1.1rem;">No published stories yet by this author.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = data.blogs.map(blog => {
                const postUrl = blog.slug 
                    ? `${appBaseUrl}/posts/${blog.slug}` 
                    : `${appBaseUrl}/blog.php?id=${blog.id}`;

                let coverHtml = '';
                if (blog.cover_image) {
                    coverHtml = `
                        <div style="height: 180px; overflow: hidden;">
                            <img src="${blog.cover_url || (appBaseUrl + blog.cover_image)}" alt="${escapeHtml(blog.title)}" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    `;
                }

                return `
                    <div class="blog-card" style="display: flex; flex-direction: column;">
                        ${coverHtml}
                        <div class="blog-card-header" style="flex: 1;">
                            ${blog.category_name ? `
                                <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary-color); background: rgba(59, 130, 246, 0.1); padding: 0.2rem 0.6rem; border-radius: 12px; display: inline-block; margin-bottom: 0.5rem;">
                                    ${escapeHtml(blog.category_name)}
                                </span>
                            ` : ''}
                            <h3 class="blog-card-title">
                                <a href="${postUrl}">${escapeHtml(blog.title)}</a>
                            </h3>
                            <p class="blog-card-excerpt">${truncateText(blog.content, 140)}</p>
                        </div>
                        <div class="blog-card-meta">
                            <div class="blog-date">
                                <i class="fas fa-calendar"></i>
                                <span>${formatDate(blog.created_at)}</span>
                            </div>
                            <div style="display: flex; gap: 0.75rem; font-size: 0.85rem; color: var(--text-light);">
                                <span><i class="far fa-eye"></i> ${blog.views || 0}</span>
                                <span><i class="far fa-heart"></i> ${blog.like_count || 0}</span>
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

function initProfileEditing() {
    const toggleBtn = document.getElementById('toggleEditBioBtn');
    const editSection = document.getElementById('editBioSection');
    const cancelBtn = document.getElementById('cancelBioBtn');
    const saveBtn = document.getElementById('saveBioBtn');
    const bioInput = document.getElementById('bioInput');
    const authorBio = document.getElementById('authorBio');

    if (toggleBtn && editSection) {
        toggleBtn.addEventListener('click', () => {
            editSection.style.display = 'block';
            toggleBtn.style.display = 'none';
            if (bioInput) bioInput.focus();
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                editSection.style.display = 'none';
                toggleBtn.style.display = 'inline-block';
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                const newBio = bioInput.value.trim();
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                try {
                    const response = await fetch(`${appBaseUrl}/api/user/profile.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `bio=${encodeURIComponent(newBio)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        authorBio.textContent = newBio || 'This author hasn\'t written a bio yet.';
                        authorBio.style.fontStyle = newBio ? 'normal' : 'italic';
                        editSection.style.display = 'none';
                        toggleBtn.style.display = 'inline-block';
                        if (window.showToast) showToast('Profile bio updated!', 'success');
                    }
                } catch (e) {
                    if (window.showToast) showToast(e.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save Bio';
                }
            });
        }
    }

    // Avatar upload
    const avatarBtn = document.getElementById('avatarUploadBtn');
    const avatarInput = document.getElementById('avatarFileInput');
    if (avatarBtn && avatarInput) {
        avatarBtn.addEventListener('click', () => avatarInput.click());

        avatarInput.addEventListener('change', async () => {
            if (!avatarInput.files || !avatarInput.files[0]) return;
            const file = avatarInput.files[0];
            const formData = new FormData();
            formData.append('avatar', file);

            if (window.showToast) showToast('Uploading new avatar...', 'info');

            try {
                const response = await fetch(`${appBaseUrl}/api/upload/avatar.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success && data.url) {
                    const avatarImg = document.getElementById('authorAvatar');
                    if (avatarImg) avatarImg.src = data.full_url || (appBaseUrl + data.url);
                    if (window.showToast) showToast('Profile avatar updated!', 'success');
                } else {
                    if (window.showToast) showToast(data.error || 'Failed to update avatar', 'error');
                }
            } catch (err) {
                if (window.showToast) showToast(err.message, 'error');
            }
        });
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
