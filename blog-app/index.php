<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <!-- Hero & Category Discovery Navigation -->
        <div class="discovery-header" style="margin-bottom: 2rem;">
            <!-- Category Pills Bar -->
            <div id="categoryPills" class="category-pills" style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.75rem; scrollbar-width: none; margin-bottom: 1.25rem;">
                <!-- Dynamically loaded via main.js -->
            </div>

            <!-- Page Header with Search & Filter status -->
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem 0;">
                <h2 id="feedTitle" style="margin: 0;">Latest Stories</h2>
                
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search stories, topics, authors..." class="search-input">
                    <button id="searchBtn" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button id="clearSearchBtn" class="btn btn-secondary" style="display: none;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <!-- Active Filter Badge Row -->
            <div id="activeFilterContainer" style="display: none; align-items: center; gap: 0.75rem; margin-bottom: 1rem; background: rgba(59, 130, 246, 0.08); padding: 0.6rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <span id="activeFilterText" style="font-size: 0.9rem; font-weight: 500; color: var(--text-color);">Filtering by:</span>
                <button id="clearFilterBtn" class="btn btn-secondary" style="padding: 0.2rem 0.6rem; font-size: 0.8rem;">
                    <i class="fas fa-times"></i> Reset Filter
                </button>
            </div>
        </div>
        
        <div id="searchResults" class="search-results" style="display: none; margin-bottom: 1.5rem;"></div>
        
        <div id="blogsContainer" class="blogs-grid">
            <div class="loading" id="loadingIndicator">
                <i class="fas fa-spinner fa-spin"></i> Loading stories...
            </div>
        </div>
        
        <div id="pagination" class="pagination" style="margin-top: 2rem;"></div>
    </div>
</main>
<?php require_once 'includes/footer.php'; ?>
