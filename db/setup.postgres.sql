-- Zoloverse: IT Services, Development Courses & Project Showcase Platform
-- Database Schema for PostgreSQL 16 (Render Cloud Environment)
-- Idempotent: safe to run repeatedly.

-- 1. Users
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (role IN ('customer', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Services
CREATE TABLE IF NOT EXISTS services (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    short_description VARCHAR(500) NOT NULL,
    full_description TEXT NOT NULL,
    image_path VARCHAR(255),
    technologies VARCHAR(255),
    features TEXT,
    benefits TEXT,
    publication_status VARCHAR(20) NOT NULL DEFAULT 'Published' CHECK (publication_status IN ('Draft', 'Published', 'Unpublished')),
    featured SMALLINT DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Courses
CREATE TABLE IF NOT EXISTS courses (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    image_path VARCHAR(255),
    category VARCHAR(100) NOT NULL DEFAULT 'Full-Stack Development',
    level VARCHAR(20) NOT NULL DEFAULT 'Beginner' CHECK (level IN ('Beginner', 'Intermediate', 'Advanced')),
    duration VARCHAR(100) NOT NULL,
    learning_mode VARCHAR(20) NOT NULL DEFAULT 'Online' CHECK (learning_mode IN ('Online', 'Offline', 'Hybrid')),
    prerequisites TEXT,
    learning_outcomes TEXT,
    curriculum TEXT,
    instructor_info VARCHAR(255),
    registration_status VARCHAR(20) NOT NULL DEFAULT 'Open' CHECK (registration_status IN ('Open', 'Closed')),
    publication_status VARCHAR(20) NOT NULL DEFAULT 'Published' CHECK (publication_status IN ('Draft', 'Published', 'Unpublished')),
    featured SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Projects
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    main_image VARCHAR(255) NOT NULL,
    project_url VARCHAR(500),
    technologies VARCHAR(255),
    category VARCHAR(100) DEFAULT 'Web Application',
    project_status VARCHAR(30) NOT NULL DEFAULT 'Completed' CHECK (project_status IN ('In Development', 'Completed', 'Maintenance')),
    publication_status VARCHAR(20) NOT NULL DEFAULT 'Published' CHECK (publication_status IN ('Draft', 'Published', 'Unpublished')),
    featured SMALLINT DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Project Images
CREATE TABLE IF NOT EXISTS project_images (
    id SERIAL PRIMARY KEY,
    project_id INT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Service Registrations
CREATE TABLE IF NOT EXISTS service_registrations (
    id SERIAL PRIMARY KEY,
    registration_reference VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    service_id INT NOT NULL REFERENCES services(id) ON DELETE RESTRICT,
    requirements TEXT NOT NULL,
    preferred_contact_method VARCHAR(50) DEFAULT 'Email',
    status VARCHAR(30) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending', 'In Progress', 'Contacted', 'Completed', 'Rejected', 'Cancelled')),
    admin_notes TEXT,
    reviewed_by VARCHAR(100),
    reviewed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Course Registrations
CREATE TABLE IF NOT EXISTS course_registrations (
    id SERIAL PRIMARY KEY,
    registration_reference VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    course_id INT NOT NULL REFERENCES courses(id) ON DELETE RESTRICT,
    qualification VARCHAR(150) NOT NULL,
    experience VARCHAR(255),
    reason_for_joining TEXT,
    preferred_batch VARCHAR(100) DEFAULT 'Immediate',
    consent SMALLINT DEFAULT 1,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending', 'Approved', 'Rejected', 'Cancelled')),
    rejection_reason TEXT,
    admin_notes TEXT,
    reviewed_by VARCHAR(100),
    reviewed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Contact Messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'New' CHECK (status IN ('New', 'In Progress', 'Responded', 'Closed')),
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Dynamic Website Content
CREATE TABLE IF NOT EXISTS website_content (
    id SERIAL PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE,
    content_json TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 11. Password Resets
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Default Admin
INSERT INTO users (name, email, password, role) VALUES
('Chiranjeevi', 'chiranjeevi@zoloverse.com', '$2y$10$vwwgw5zrjc2Xq4IWjm.1Ue5BXbTcwHBGSQhUuROh/8K8pQ0Ybx.qK', 'admin')
ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name, password = EXCLUDED.password, role = 'admin';

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
ON CONFLICT (slug) DO UPDATE SET title = EXCLUDED.title, short_description = EXCLUDED.short_description, full_description = EXCLUDED.full_description;

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
ON CONFLICT (slug) DO UPDATE SET title = EXCLUDED.title, description = EXCLUDED.description;

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
ON CONFLICT (slug) DO UPDATE SET title = EXCLUDED.title, description = EXCLUDED.description, project_url = EXCLUDED.project_url;

-- Seed Dynamic Website Content (Home & About)
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
ON CONFLICT (section_key) DO UPDATE SET content_json = EXCLUDED.content_json;

