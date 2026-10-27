<?php
/**
 * health.php — System status and endpoint health checks for Zoloverse.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$status = 'operational';
$services = [];

try {
    $db = db();
    $db->query('SELECT 1');
    $services['database'] = ['status' => 'up', 'label' => (DB_DRIVER === 'pgsql' ? 'PostgreSQL 16' : 'MySQL / MariaDB')];
} catch (Exception $e) {
    $status = 'degraded';
    $services['database'] = ['status' => 'down', 'label' => 'Database Server'];
}

$endpoints = [
    'api_services.php'      => 'IT Services API',
    'api_courses.php'       => 'Courses API',
    'api_projects.php'      => 'Projects Showcase API',
    'api_registrations.php' => 'Registrations API',
    'api_contact.php'       => 'Contact Enquiries API',
    'api_content.php'       => 'Website Content API',
    'api_media.php'         => 'Media Library API',
    'api_dashboard.php'     => 'Dashboard Metrics API',
    'auth.php'              => 'Authentication API',
];

foreach ($endpoints as $file => $label) {
    $exists = file_exists(__DIR__ . '/' . $file);
    if (!$exists) {
        $status = 'degraded';
    }
    $services[pathinfo($file, PATHINFO_FILENAME)] = [
        'status' => $exists ? 'up' : 'down',
        'label'  => $label,
    ];
}

json_response([
    'status'    => $status,
    'timestamp' => date('c'),
    'platform'  => 'Zoloverse IT Services & Academy',
    'services'  => $services,
]);