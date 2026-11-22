-- Blog App Core Features Migration (v2)
USE blog_app;

-- 1. Update user table with avatar and bio
ALTER TABLE user 
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL;

-- 2. Create categories table
CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default categories
INSERT IGNORE INTO category (name, slug, description) VALUES
('Web Development', 'web-development', 'Frontend, backend, APIs, and modern web architectures'),
('Technology', 'technology', 'Tech trends, AI, cloud computing, and hardware insights'),
('Design & UI/UX', 'design-ui-ux', 'Visual design, usability, typography, and frontend aesthetics'),
('Tutorials & Guides', 'tutorials-guides', 'Step-by-step practical guides, code snippets, and how-tos'),
('Career & Lifestyle', 'career-lifestyle', 'Developer productivity, career advice, and lifestyle tips');

-- 3. Create tags and post_tag tables
CREATE TABLE IF NOT EXISTS tag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_tag (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tag(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Update blogPost table
ALTER TABLE blogPost
    ADD COLUMN IF NOT EXISTS slug VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('draft', 'published') DEFAULT 'published',
    ADD COLUMN IF NOT EXISTS views INT DEFAULT 0;

-- Backfill slugs for existing posts if any
UPDATE blogPost 
SET slug = CONCAT(LOWER(REPLACE(REPLACE(REPLACE(TRIM(title), ' ', '-'), '/', '-'), '?', '')), '-', id)
WHERE slug IS NULL OR slug = '';

-- 5. Create comments table
CREATE TABLE IF NOT EXISTS comment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_comment_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create post_like table
CREATE TABLE IF NOT EXISTS post_like (
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create post_bookmark table
CREATE TABLE IF NOT EXISTS post_bookmark (
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
