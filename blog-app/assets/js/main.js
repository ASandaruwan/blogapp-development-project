// Upgraded Main JavaScript: Categories, Tags, SEO Slugs, Cards & Dark Mode

(function() {
    if (typeof fetch === 'undefined') {
        console.error('Fetch API is not supported in this browser.');
        return;
    }
    
    if (typeof window.BASE_URL === 'undefined') {
        const baseUrlMeta = document.querySelector('meta[name="base-url"]');
        if (baseUrlMeta && baseUrlMeta.getAttribute('content')) {
            window.BASE_URL = baseUrlMeta.getAttribute('content').trim();
        } else {
            const origin = window.location.origin;
            const path = window.location.pathname;
            const pathParts = path.split('/').filter(p => p);
            if (pathParts.length > 0 && pathParts[0] === 'blog-app') {
                window.BASE_URL = origin + '/blog-app';
            } else {
                window.BASE_URL = origin;
            }
        }
        if (window.BASE_URL.endsWith('/')) {
            window.BASE_URL = window.BASE_URL.slice(0, -1);
        }
    }
    const BASE_URL = window.BASE_URL;

    document.addEventListener('DOMContentLoaded', async function() {
        // 1. Theme Toggle Controller
        const themeToggleBtn = document.getElementById('themeToggle');
        if (themeToggleBtn) {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            updateThemeIcon(currentTheme);

            themeToggleBtn.addEventListener('click', function() {
                const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
                const nextTheme = activeTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', nextTheme);
                try {
                    localStorage.setItem('theme', nextTheme);
                } catch(e) {}
                updateThemeIcon(nextTheme);
                if (window.showToast) {
                    showToast(`Switched to ${nextTheme} mode`, 'info', 'Theme Changed', 2000);
                }
            });

            function updateThemeIcon(theme) {
                const icon = themeToggleBtn.querySelector('i');
                if (icon) {
                    if (theme === 'dark') {
                        icon.className = 'fas fa-sun';
                        themeToggleBtn.setAttribute('title', 'Switch to Light Mode');
                    } else {
                        icon.className = 'fas fa-moon';
                        themeToggleBtn.setAttribute('title', 'Switch to Dark Mode');
                    }
                }
            }
        }

        // 2. Mobile Navigation Toggle
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (navToggle) {
            navToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
            });
        }
        
        document.addEventListener('click', function(e) {
            if (navMenu && navToggle && 
                !navMenu.contains(e.target) && 
                !navToggle.contains(e.target) && 
                navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });

        // 3. Home Page - Feed & Categories
        const blogsContainer = document.getElementById('blogsContainer');
        if (!blogsContainer) {
            return; // Not on the home page
        }
        
        let currentPage = 1;
        let currentSearch = '';
        let currentCategory = '';
        let currentTag = '';

        // Read URL query parameters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('search')) currentSearch = urlParams.get('search');
        if (urlParams.get('category')) currentCategory = urlParams.get('category');
        if (urlParams.get('tag')) currentTag = urlParams.get('tag');

        // Load Category Pills
        await loadCategoryPills();

        // Load Blogs
        async function loadBlogs(page = 1, search = currentSearch, category = currentCategory, tag = currentTag) {
            currentPage = page;
            currentSearch = search;
            currentCategory = category;
            currentTag = tag;

            const container = document.getElementById('blogsContainer');
            const pagination = document.getElementById('pagination');
            const searchResults = document.getElementById('searchResults');
            const filterContainer = document.getElementById('activeFilterContainer');
            const filterText = document.getElementById('activeFilterText');
            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearchBtn');
            
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading stories...</div>';
            
            // Update Active Filter UI
            if (category || tag || search) {
                if (filterContainer && filterText) {
                    let desc = [];
                    if (category) desc.push(`Category: <strong>${escapeHtml(category)}</strong>`);
                    if (tag) desc.push(`Tag: <strong>#${escapeHtml(tag)}</strong>`);
                    if (search) desc.push(`Keyword: "<strong>${escapeHtml(search)}</strong>"`);
                    filterText.innerHTML = `Filtering stories by ${desc.join(' &middot; ')}`;
                    filterContainer.style.display = 'flex';
                }
            } else {
                if (filterContainer) filterContainer.style.display = 'none';
            }

            // Highlight Active Category Pill
            document.querySelectorAll('.cat-pill').forEach(pill => {
                if ((pill.dataset.category || '') === currentCategory) {
                    pill.style.background = 'var(--primary-color)';
                    pill.style.color = '#ffffff';
                    pill.style.borderColor = 'transparent';
                } else {
                    pill.style.background = 'var(--surface-bg)';
                    pill.style.color = 'var(--text-color)';
                    pill.style.borderColor = 'var(--border-color)';
                }
            });

            try {
                let url = `${BASE_URL}/api/blogs/get.php?page=${page}&limit=9`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (category) url += `&category=${encodeURIComponent(category)}`;
                if (tag) url += `&tag=${encodeURIComponent(tag)}`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    // Update search results indicator
                    if (search && searchResults) {
                        searchResults.innerHTML = `
                            <p style="color: var(--text-light); margin: 0;"><strong>${data.total}</strong> story(ies) found for "<strong>${escapeHtml(search)}</strong>"</p>
                        `;
                        searchResults.style.display = 'block';
                        if (clearSearchBtn) clearSearchBtn.style.display = 'inline-flex';
                    } else if (searchResults) {
                        searchResults.style.display = 'none';
                        if (clearSearchBtn) clearSearchBtn.style.display = 'none';
                    }
                    
                    if (searchInput && search) {
                        searchInput.value = search;
                    }

                    // Render Blog Cards
                    if (data.blogs.length === 0) {
                        container.innerHTML = `
                            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 1rem; background: var(--surface-bg); border: 1px dashed var(--border-color); border-radius: 12px;">
                                <i class="fas fa-newspaper" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 0.75rem; display: block;"></i>
                                <h3 style="margin: 0 0 0.5rem 0; color: var(--dark-color);">No stories found</h3>
                                <p style="color: var(--text-light); margin: 0 0 1rem 0;">Try adjusting your filters or search keywords.</p>
                                <button onclick="resetAllFilters()" class="btn btn-primary">
                                    <i class="fas fa-undo"></i> Show All Stories
                                </button>
                            </div>
                        `;
                    } else {
                        container.innerHTML = data.blogs.map(blog => {
                            const postUrl = `${BASE_URL}/blog.php?${blog.slug ? 'slug=' + encodeURIComponent(blog.slug) : 'id=' + blog.id}`;

                            let coverHtml = '';
                            if (blog.cover_image) {
                                coverHtml = `
                                    <div class="blog-card-cover" style="height: 190px; overflow: hidden; position: relative;">
                                        <a href="${postUrl}">
                                            <img src="${blog.cover_url || (BASE_URL + blog.cover_image)}" 
                                                 alt="${escapeHtml(blog.title)}" 
                                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                                        </a>
                                        ${blog.category_name ? `
                                            <a href="javascript:void(0)" onclick="filterByCategory('${escapeHtml(blog.category_slug)}')" 
                                               style="position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); color: #ffffff; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 20px; text-decoration: none;">
                                                ${escapeHtml(blog.category_name)}
                                            </a>
                                        ` : ''}
                                    </div>
                                `;
                            } else if (blog.category_name) {
                                coverHtml = `
                                    <div style="padding: 1.25rem 1.5rem 0 1.5rem;">
                                        <a href="javascript:void(0)" onclick="filterByCategory('${escapeHtml(blog.category_slug)}')" 
                                           style="display: inline-block; background: rgba(59, 130, 246, 0.1); color: var(--primary-color); font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.65rem; border-radius: 20px; text-decoration: none;">
                                            ${escapeHtml(blog.category_name)}
                                        </a>
                                    </div>
                                `;
                            }

                            // Tags chips
                            let tagsHtml = '';
                            if (blog.tags && blog.tags.length > 0) {
                                tagsHtml = `
                                    <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.75rem;">
                                        ${blog.tags.slice(0, 3).map(t => `
                                            <a href="javascript:void(0)" onclick="filterByTag('${escapeHtml(t.slug)}')" 
                                               style="font-size: 0.75rem; color: var(--text-light); background: var(--light-color); border: 1px solid var(--border-color); padding: 0.15rem 0.45rem; border-radius: 4px; text-decoration: none;">
                                                #${escapeHtml(t.name)}
                                            </a>
                                        `).join('')}
                                    </div>
                                `;
                            }

                            return `
                                <div class="blog-card" style="display: flex; flex-direction: column;">
                                    ${coverHtml}
                                    <div class="blog-card-header" style="flex: 1;">
                                        <h2 class="blog-card-title">
                                            <a href="${postUrl}">${escapeHtml(blog.title)}</a>
                                        </h2>
                                        <p class="blog-card-excerpt">${truncateText(blog.content, 140)}</p>
                                        ${tagsHtml}
                                    </div>
                                    <div class="blog-card-meta">
                                        <a href="${BASE_URL}/profile.php?username=${encodeURIComponent(blog.username)}" 
                                           class="blog-author" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                                            <img src="${escapeHtml(blog.avatar_url)}" alt="${escapeHtml(blog.username)}" 
                                                 style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                            <span>${escapeHtml(blog.username)}</span>
                                        </a>
                                        <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.8rem;">
                                            <span title="Reading time"><i class="far fa-clock"></i> ${calculateReadingTime(blog.content)}</span>
                                            <span title="Views"><i class="far fa-eye"></i> ${blog.views || 0}</span>
                                            <span title="Likes"><i class="far fa-heart"></i> ${blog.like_count || 0}</span>
                                            <span title="Comments"><i class="far fa-comment"></i> ${blog.comment_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                    
                    // Pagination
                    const totalPages = Math.ceil(data.total / data.limit);
                    if (totalPages > 1) {
                        let paginationHTML = '';
                        if (currentPage > 1) {
                            paginationHTML += `<button onclick="loadBlogs(${currentPage - 1})">Previous</button>`;
                        }
                        for (let i = 1; i <= totalPages; i++) {
                            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                                paginationHTML += `<button class="${i === currentPage ? 'active' : ''}" onclick="loadBlogs(${i})">${i}</button>`;
                            } else if (i === currentPage - 3 || i === currentPage + 3) {
                                paginationHTML += `<button disabled>...</button>`;
                            }
                        }
                        if (currentPage < totalPages) {
                            paginationHTML += `<button onclick="loadBlogs(${currentPage + 1})">Next</button>`;
                        }
                        pagination.innerHTML = paginationHTML;
                    } else {
                        pagination.innerHTML = '';
                    }
                }
            } catch (error) {
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; padding: 2rem; background: var(--surface-bg); border: 1px solid var(--danger-color); border-radius: 8px; color: var(--danger-color); text-align: center;">
                        <p>Error loading stories: ${escapeHtml(error.message)}</p>
                        <button onclick="loadBlogs(1)" class="btn btn-secondary">Retry</button>
                    </div>
                `;
            }
        }

        // Category Pills Loader
        async function loadCategoryPills() {
            const pillsContainer = document.getElementById('categoryPills');
            if (!pillsContainer) return;

            try {
                const response = await fetch(`${BASE_URL}/api/categories/get.php`);
                const data = await response.json();

                if (data.success && data.categories) {
                    let html = `
                        <button class="cat-pill" data-category="" onclick="filterByCategory('')" 
                                style="white-space: nowrap; border-radius: 20px; padding: 0.45rem 1rem; border: 1px solid var(--border-color); background: var(--surface-bg); color: var(--text-color); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            All Topics
                        </button>
                    `;

                    html += data.categories.map(c => `
                        <button class="cat-pill" data-category="${escapeHtml(c.slug)}" onclick="filterByCategory('${escapeHtml(c.slug)}')" 
                                style="white-space: nowrap; border-radius: 20px; padding: 0.45rem 1rem; border: 1px solid var(--border-color); background: var(--surface-bg); color: var(--text-color); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            ${escapeHtml(c.name)} <span style="opacity: 0.6; font-size: 0.75rem;">(${c.post_count})</span>
                        </button>
                    `).join('');

                    pillsContainer.innerHTML = html;
                }
            } catch (e) {
                console.warn('Failed to load category pills:', e);
            }
        }

        // Search Handlers
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const clearFilterBtn = document.getElementById('clearFilterBtn');

        if (searchBtn && searchInput) {
            searchBtn.addEventListener('click', () => loadBlogs(1, searchInput.value.trim()));
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') loadBlogs(1, searchInput.value.trim());
            });
        }

        if (clearSearchBtn && searchInput) {
            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                loadBlogs(1, '');
            });
        }

        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', () => resetAllFilters());
        }

        // Global functions for inline HTML calls
        window.loadBlogs = loadBlogs;
        window.filterByCategory = function(categorySlug) {
            currentCategory = categorySlug;
            loadBlogs(1, currentSearch, currentCategory, currentTag);
        };
        window.filterByTag = function(tagSlug) {
            currentTag = tagSlug;
            loadBlogs(1, currentSearch, currentCategory, currentTag);
        };
        window.resetAllFilters = function() {
            currentCategory = '';
            currentTag = '';
            currentSearch = '';
            if (searchInput) searchInput.value = '';
            loadBlogs(1, '', '', '');
        };

        // Initial Load
        loadBlogs(1, currentSearch, currentCategory, currentTag);
    });

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function truncateText(text, maxLength) {
        if (!text) return '';
        const plainText = text.replace(/<[^>]*>/g, '').replace(/[#*`_~\[\]]/g, '').trim();
        if (plainText.length <= maxLength) return escapeHtml(plainText);
        return escapeHtml(plainText.substring(0, maxLength)) + '...';
    }

    function calculateReadingTime(text) {
        if (!text) return '1 min read';
        const plainText = text.replace(/<[^>]*>/g, '').replace(/[#*`_~\[\]]/g, '').trim();
        const wordCount = plainText.split(/\s+/).filter(Boolean).length;
        const minutes = Math.max(1, Math.ceil(wordCount / 200));
        return `${minutes} min read`;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
})();
