-- Zoloverse: IT Services, Development Courses & Project Showcase Platform
-- Database Schema for MySQL / MariaDB (Local XAMPP)

CREATE DATABASE IF NOT EXISTS zoloverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zoloverse;

-- 1. Users & Administrators
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Professional IT Services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    short_description VARCHAR(500) NOT NULL,
    full_description TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    technologies VARCHAR(255) DEFAULT NULL,
    features TEXT DEFAULT NULL,
    benefits TEXT DEFAULT NULL,
    publication_status ENUM('Draft', 'Published', 'Unpublished') DEFAULT 'Published',
    featured TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Development and Design Courses
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Full-Stack Development',
    level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
    duration VARCHAR(100) NOT NULL,
    learning_mode ENUM('Online', 'Offline', 'Hybrid') DEFAULT 'Online',
    prerequisites TEXT DEFAULT NULL,
    learning_outcomes TEXT DEFAULT NULL,
    curriculum TEXT DEFAULT NULL,
    instructor_info VARCHAR(255) DEFAULT NULL,
    registration_status ENUM('Open', 'Closed') DEFAULT 'Open',
    publication_status ENUM('Draft', 'Published', 'Unpublished') DEFAULT 'Published',
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Projects Portfolio
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    main_image VARCHAR(255) NOT NULL,
    project_url VARCHAR(500) DEFAULT NULL,
    technologies VARCHAR(255) DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'Web Application',
    project_status ENUM('In Development', 'Completed', 'Maintenance') DEFAULT 'Completed',
    publication_status ENUM('Draft', 'Published', 'Unpublished') DEFAULT 'Published',
    featured TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Additional Project Screenshot Galleries
CREATE TABLE IF NOT EXISTS project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. IT Service Registrations & Enquiries
CREATE TABLE IF NOT EXISTS service_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_reference VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    service_id INT NOT NULL,
    requirements TEXT NOT NULL,
    preferred_contact_method VARCHAR(50) DEFAULT 'Email',
    status ENUM('Pending', 'In Progress', 'Contacted', 'Completed', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    admin_notes TEXT DEFAULT NULL,
    reviewed_by VARCHAR(100) DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Course Registrations
CREATE TABLE IF NOT EXISTS course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_reference VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    course_id INT NOT NULL,
    qualification VARCHAR(150) NOT NULL,
    experience VARCHAR(255) DEFAULT NULL,
    reason_for_joining TEXT DEFAULT NULL,
    preferred_batch VARCHAR(100) DEFAULT 'Immediate',
    consent TINYINT(1) DEFAULT 1,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    rejection_reason TEXT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    reviewed_by VARCHAR(100) DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Contact Enquiries
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New', 'In Progress', 'Responded', 'Closed') DEFAULT 'New',
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Dynamic Website Content (Home, About, Settings)
CREATE TABLE IF NOT EXISTS website_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE,
    content_json LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Password Resets
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reset_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin Account (password: Zoloverse26!)
INSERT INTO users (name, email, password, role) VALUES
('Chiranjeevi', 'chiranjeevi@zoloverse.com', '$2y$10$vwwgw5zrjc2Xq4IWjm.1Ue5BXbTcwHBGSQhUuROh/8K8pQ0Ybx.qK', 'admin')
ON DUPLICATE KEY UPDATE name = 'Chiranjeevi', password = '$2y$10$vwwgw5zrjc2Xq4IWjm.1Ue5BXbTcwHBGSQhUuROh/8K8pQ0Ybx.qK', role = 'admin';

-- Seed Services
INSERT INTO services (id, title, slug, short_description, full_description, image_path, technologies, features, benefits, publication_status, featured, display_order) VALUES
(1, 'Full-Stack Application and Web Development', 'full-stack-web-development',
'End-to-end full-stack web and cloud application development tailored for high performance and seamless scale.',
'Our Full-Stack Application and Web Development service covers the complete software lifecycle from architecture design to production release. We engineer modern, accessible, and resilient systems utilizing both traditional stacks and cutting-edge MERN/JAMstack implementations. We specialize in robust backend architecture, secure token authentication, high-performance database schema design, and responsive frontend interfaces.',
'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
'MERN Stack, React, Node.js, Express, PHP, PostgreSQL, MySQL, REST APIs, Docker',
'Full-stack application development\nResponsive website development\nFrontend & Backend engineering\nREST API & Microservices development\nDatabase schema architecture\nAuthentication & authorization systems\nAdmin dashboard creation\nCloud application deployment\nOngoing maintenance & performance tuning',
'Tailored architecture ensuring peak load resilience\nScalable database design preventing future bottlenecks\nEnterprise security protocols and safe data storage\nResponsive across all desktop, tablet, and mobile screens',
'Published', 1, 1),

(2, 'UI/UX Interface Design', 'ui-ux-interface-design',
'Intuitive user interfaces, ergonomic design systems, and delightful interaction experiences for web and mobile.',
'Zoloverse delivers premium UI/UX design that merges aesthetic brilliance with measurable ergonomic clarity. We craft comprehensive wireframes, clickable high-fidelity prototypes, scalable design systems, and modern visual identities. Every interface is tested for usability, accessibility compliance, and pixel-perfect responsiveness across screen sizes.',
'https://images.unsplash.com/photo-1581291518655-9523c932edcf?auto=format&fit=crop&w=800&q=80',
'Figma, Design Systems, Wireframing, High-Fidelity Prototyping, Accessibility (WCAG)',
'User Interface (UI) design\nUser Experience (UX) research & mapping\nWeb & mobile responsive interfaces\nInteractive prototyping & flow testing\nScalable design systems & tokens\nDashboard & administrative UI design\nUsability audits & heuristic evaluation\nMicro-animations & transitions',
'Enhanced user retention and engagement\nConsistent brand aesthetics across platforms\nSeamless developer handoff with structured design tokens\nReduced friction and higher conversion rates',
'Published', 1, 2),

(3, 'API & Cloud Database Architecture', 'api-cloud-database-architecture',
'Scalable RESTful API development, PostgreSQL/MySQL database modeling, and containerized deployment.',
'We architect enterprise-grade APIs and robust relational database structures tailored to handle concurrent requests with minimal latency. Our implementations adhere to strict REST principles, robust JWT authentication, optimized indexing, and containerized deployment pipelines.',
'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=800&q=80',
'PostgreSQL 16, MySQL, Apache, Docker, REST, JWT, Redis, Render',
'REST API design & documentation\nRelational database normalization\nPerformance query optimization\nDockerized container environments\nToken security and CORS configuration\nData backup & migration pipelines',
'Blazing fast query execution times\nSecure and isolated API endpoints\nEffortless horizontal scaling and migrations\nProduction-ready reliability',
'Published', 1, 3)
ON DUPLICATE KEY UPDATE title = VALUES(title), short_description = VALUES(short_description), full_description = VALUES(full_description);

-- Seed Courses
INSERT INTO courses (id, title, slug, description, image_path, category, level, duration, learning_mode, prerequisites, learning_outcomes, curriculum, instructor_info, registration_status, publication_status, featured) VALUES
(1, 'Full-Stack MERN & Web Architecture', 'full-stack-mern-web-architecture',
'Master modern web development from fundamentals through full-stack MERN engineering, RESTful APIs, and deployment.',
'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80',
'Full-Stack Development', 'Intermediate', '12 Weeks', 'Hybrid',
'Basic understanding of HTML, CSS, and basic programming concepts.',
'Build complete full-stack web applications from scratch\nMaster React component architecture, hooks, and state\nDevelop secure REST APIs with Node.js and Express\nModel relational and document databases with high performance\nImplement JWT authentication and role-based access control\nDeploy applications to live cloud servers using Docker',
'Module 1: Advanced JavaScript & Modern ES6+\nModule 2: React Core & Component Design Patterns\nModule 3: Server Architecture, Express & Middleware\nModule 4: Database Design, Queries & Indexing\nModule 5: Security, Authentication & Session Tokens\nModule 6: Capstone Project & Cloud Deployment',
'Lead Full-Stack Architect at Zoloverse', 'Open', 'Published', 1),

(2, 'UI/UX Design Systems & Prototyping', 'ui-ux-design-systems-prototyping',
'Craft modern user interfaces, design systems, and responsive prototypes in Figma for web and mobile platforms.',
'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?auto=format&fit=crop&w=800&q=80',
'UI/UX Design', 'Beginner', '8 Weeks', 'Online',
'No prior design experience required. Curiosity and creative problem-solving mindset.',
'Understand user psychology and design heuristics\nBuild clickable interactive prototypes in Figma\nConstruct scalable design systems with variables and components\nDesign responsive mobile and web layouts\nPerform usability audits and hand off assets to developers',
'Module 1: UI/UX Principles & Visual Hierarchy\nModule 2: Figma Mastery & Vector Tools\nModule 3: Typography, Color Theory & Dark Mode\nModule 4: Wireframing & Information Architecture\nModule 5: Building Reusable Design Systems\nModule 6: High-Fidelity Client Showcase Project',
'Senior UI/UX Specialist at Zoloverse', 'Open', 'Published', 1),

(3, 'Backend Architecture & Database Engineering', 'backend-architecture-database-engineering',
'Deep dive into secure backend systems, database normalization, query optimization, and production deployment.',
'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',
'Backend Development', 'Advanced', '10 Weeks', 'Offline',
'Proficiency in at least one backend language (PHP, JavaScript/Node, or Python).',
'Architect scalable, maintainable server backends\nMaster relational SQL modeling (PostgreSQL & MySQL)\nImplement cryptographic authentication and rate-limiting\nDiagnose and eliminate database query bottlenecks\nConfigure Apache, Docker, and reverse proxies',
'Module 1: REST API Standards & Clean Architecture\nModule 2: Relational Schema Modeling & Foreign Keys\nModule 3: Indexing, Transactions & ACID Guarantees\nModule 4: Authentication, Tokens & CSRF/XSS Mitigation\nModule 5: Containerization with Docker & Apache\nModule 6: Production Monitoring & Resilient Deployments',
'Systems Engineer & Security Lead at Zoloverse', 'Open', 'Published', 1)
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description);

-- Seed Projects
INSERT INTO projects (id, title, slug, description, main_image, project_url, technologies, category, project_status, publication_status, featured, display_order) VALUES
(1, 'Zoloverse IT Platform', 'zoloverse-it-platform',
'High-performance IT services, technology course portal, and showcase platform powered by PHP PDO and clean Vanilla UI.',
'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80',
'http://localhost/Zoloverse/',
'HTML5, Vanilla CSS3, JavaScript ES6+, PHP 8.3, PDO, MySQL, PostgreSQL, Docker',
'Web Application', 'Completed', 'Published', 1, 1),

(2, 'KOMS Karate Student & Admin Panel', 'koms-karate-student-admin-panel',
'Comprehensive martial arts academy management suite with student portal, attendance tracking, and administrative control.',
'https://images.unsplash.com/photo-1555597673-b21d5c935865?auto=format&fit=crop&w=800&q=80',
'http://localhost:8443',
'React 19, TypeScript, Vite, Tailwind CSS, Localhost Dev Server',
'Management System', 'Completed', 'Published', 1, 2),

(3, 'Cinematic Portfolio & Showcase', 'cinematic-portfolio-showcase',
'Immersive, typography-driven developer portfolio showcasing projects, interactive animations, and responsive design systems.',
'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
'http://localhost:5173',
'React 19, Vite, Tailwind CSS v4, Lucide Icons, GSAP Animations',
'Portfolio Application', 'Completed', 'Published', 1, 3)
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), project_url = VALUES(project_url);

-- Seed Website Content (Home & About)
INSERT INTO website_content (section_key, content_json) VALUES
('home', '{
  "hero_heading": "Build Your Digital Future with Zoloverse",
  "hero_description": "Explore application development, intuitive UI/UX design, and practical technology learning opportunities with Zoloverse.",
  "hero_image": "https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80",
  "about_intro": "Zoloverse is an elite digital technology organization delivering modern full-stack web solutions, ergonomic UI/UX interface designs, and practical industry courses. We empower businesses to excel online and equip developers with real-world technical skills.",
  "why_choose": [
    {"icon": "fa-layer-group", "title": "Full-Stack Excellence", "desc": "From sleek user interfaces to robust database architecture, our solutions are built to scale seamlessly."},
    {"icon": "fa-palette", "title": "Ergonomic UI/UX", "desc": "We craft experiences that delight users and elevate brands with modern design standards."},
    {"icon": "fa-chalkboard-teacher", "title": "Practical Learning", "desc": "Our development and design courses bridge the gap between academic theory and real-world engineering."},
    {"icon": "fa-shield-alt", "title": "Enterprise Security", "desc": "Built with strict data validation, prepared statements, and industry-standard authentication."}
  ],
  "faqs": [
    {"q": "What services does Zoloverse provide?", "a": "We provide full-stack web and application development (including MERN and PHP stacks), UI/UX interface design in Figma, database architecture, and technology courses."},
    {"q": "How can I enroll in a course or request a service?", "a": "Navigate to the Register Services page, select whether you are requesting an IT Service or enrolling in a Course, fill out the form, and our team will contact you with your unique reference code."},
    {"q": "Are the courses online or in-person?", "a": "We offer flexible formats including Online, Offline, and Hybrid learning modes based on the course you select."},
    {"q": "Can Zoloverse build custom applications for our business?", "a": "Yes! We specialize in custom full-stack applications, admin dashboards, APIs, and responsive design systems."}
  ],
  "cta_heading": "Ready to Transform Your Digital Presence?",
  "cta_subheading": "Partner with Zoloverse for industry-grade development or enroll in our next engineering cohort."
}'),
('about', '{
  "org_intro": "Zoloverse is a modern technology studio and engineering academy dedicated to building premier digital products and training next-generation software builders.",
  "mission": "To engineer reliable, beautiful, and secure software applications while providing hands-on technology education that enables developers and businesses to thrive in the digital era.",
  "vision": "To become a recognized benchmark in full-stack software craftsmanship, intuitive user interface design, and transformative technology mentorship.",
  "core_values": [
    {"title": "Engineering Integrity", "desc": "We build with strict standards, robust security, and clean code that stands the test of time."},
    {"title": "Human-Centered Design", "desc": "Technology must be effortless, intuitive, and accessible to every user."},
    {"title": "Practical Education", "desc": "We focus on real projects, industry patterns, and hands-on problem solving."},
    {"title": "Continuous Evolution", "desc": "We embrace modern tools, constant improvement, and forward-looking architecture."}
  ],
  "tech_capabilities": [
    "Full-Stack MERN (MongoDB, Express, React, Node.js)",
    "Modern PHP 8.x & PDO Relational Architecture",
    "PostgreSQL 16 & MySQL Database Design",
    "Figma UI/UX & Design Systems",
    "RESTful API Engineering & JWT Security",
    "Docker & Cloud Deployment Pipelines"
  ],
  "contact_info": {
    "email": "contact@zoloverse.com",
    "phone": "+91 98765 43210",
    "location": "Chennai, Tamil Nadu, India",
    "hours": "Monday - Saturday: 9:00 AM - 7:00 PM"
  }
}')
ON DUPLICATE KEY UPDATE content_json = VALUES(content_json);
