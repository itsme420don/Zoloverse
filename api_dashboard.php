<?php
/**
 * api_dashboard.php — Administrative Dashboard Statistics & Summaries for Zoloverse.
 * Computes live metrics from database tables without hardcoded numbers.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$admin = requireAuth(true);
$db = db();

// 1. KPI Counts
$stats = [
    'services_total'      => (int)$db->query('SELECT COUNT(*) FROM services')->fetchColumn(),
    'services_published'  => (int)$db->query("SELECT COUNT(*) FROM services WHERE publication_status = 'Published'")->fetchColumn(),
    'courses_total'       => (int)$db->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'courses_published'   => (int)$db->query("SELECT COUNT(*) FROM courses WHERE publication_status = 'Published'")->fetchColumn(),
    'projects_total'      => (int)$db->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'projects_published'  => (int)$db->query("SELECT COUNT(*) FROM projects WHERE publication_status = 'Published'")->fetchColumn(),
    'service_enquiries_total'   => (int)$db->query('SELECT COUNT(*) FROM service_registrations')->fetchColumn(),
    'service_enquiries_pending' => (int)$db->query("SELECT COUNT(*) FROM service_registrations WHERE status = 'Pending'")->fetchColumn(),
    'course_registrations_total'   => (int)$db->query('SELECT COUNT(*) FROM course_registrations')->fetchColumn(),
    'course_registrations_pending' => (int)$db->query("SELECT COUNT(*) FROM course_registrations WHERE status = 'Pending'")->fetchColumn(),
    'contact_messages_new' => (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'New'")->fetchColumn(),
];

// 2. Recent Service Enquiries
$stmt = $db->query(
    'SELECT r.id, r.registration_reference, r.full_name, r.email, r.status, r.created_at, s.title AS service_title
     FROM service_registrations r
     LEFT JOIN services s ON r.service_id = s.id
     ORDER BY r.id DESC LIMIT 5'
);
$recentServiceEnquiries = $stmt->fetchAll();

// 3. Recent Course Registrations
$stmt = $db->query(
    'SELECT r.id, r.registration_reference, r.full_name, r.email, r.status, r.created_at, c.title AS course_title
     FROM course_registrations r
     LEFT JOIN courses c ON r.course_id = c.id
     ORDER BY r.id DESC LIMIT 5'
);
$recentCourseRegistrations = $stmt->fetchAll();

// 4. Recent Contact Messages
$stmt = $db->query(
    'SELECT id, name, email, subject, status, created_at
     FROM contact_messages
     ORDER BY id DESC LIMIT 5'
);
$recentContactMessages = $stmt->fetchAll();

// 5. Recently Updated Projects
$stmt = $db->query(
    'SELECT id, title, main_image, project_url, publication_status, project_status, updated_at
     FROM projects
     ORDER BY updated_at DESC LIMIT 5'
);
$recentProjects = $stmt->fetchAll();

json_success('Dashboard statistics retrieved', [
    'stats' => $stats,
    'recent_service_enquiries' => $recentServiceEnquiries,
    'recent_course_registrations' => $recentCourseRegistrations,
    'recent_contact_messages' => $recentContactMessages,
    'recent_projects' => $recentProjects,
]);
