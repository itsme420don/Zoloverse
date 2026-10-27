<?php
/**
 * api_content.php — Dynamic Website Content API for Zoloverse.
 * Manages Home Page and About Us content editable from the Admin Panel.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && (($_GET['action'] ?? '') === 'save' || ($_POST['_method'] ?? '') === 'PUT')) {
    $method = 'PUT';
}

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
    case 'PUT':
        handlePut();
        break;
    default:
        json_error('Method Not Allowed', 405);
}

function handleGet(): void
{
    $section = isset($_GET['section']) ? trim((string)$_GET['section']) : 'all';
    $db = db();

    $defaultSettings = (object)[
        'site_name' => 'Zoloverse',
        'logo_url' => 'Logo.jpeg',
        'tagline' => 'Crafting Digital Excellence & Software Architecture',
        'contact_email' => 'contact@zoloverse.com',
        'contact_phone' => '+91 98765 43210',
        'contact_location' => 'Chennai, Tamil Nadu, India',
        'contact_hours' => 'Monday - Saturday: 9:00 AM - 7:00 PM',
        'notify_email' => 'zoloverse26@gmail.com',
        'whatsapp' => '+91 98765 43210',
        'instagram' => 'https://instagram.com/zoloverse',
        'linkedin' => 'https://linkedin.com/company/zoloverse',
        'github' => 'https://github.com/zoloverse'
    ];

    if ($section !== 'all') {
        $stmt = $db->prepare('SELECT section_key, content_json, updated_at FROM website_content WHERE section_key = ?');
        $stmt->execute([$section]);
        $row = $stmt->fetch();
        if (!$row) {
            if ($section === 'settings' || $section === 'contact') {
                json_success('Default settings loaded', [
                    'section' => $section,
                    'content' => $defaultSettings,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            json_error('Section not found', 404);
        }
        $data = json_decode($row['content_json']);
        // If settings, merge with defaults to ensure all contact keys exist
        if ($section === 'settings' || $section === 'contact') {
            $data = (object)array_merge((array)$defaultSettings, (array)$data);
        }
        json_success('Content loaded', [
            'section' => $section,
            'content' => $data,
            'updated_at' => $row['updated_at']
        ]);
    } else {
        $stmt = $db->query('SELECT section_key, content_json, updated_at FROM website_content');
        $rows = $stmt->fetchAll();
        $sections = [];
        foreach ($rows as $r) {
            $sections[$r['section_key']] = json_decode($r['content_json']);
        }
        if (!isset($sections['settings'])) {
            $sections['settings'] = $defaultSettings;
        } else {
            $sections['settings'] = (object)array_merge((array)$defaultSettings, (array)$sections['settings']);
        }
        json_success('All sections loaded', ['sections' => $sections]);
    }
}

function handlePut(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || empty($data->section_key) || !isset($data->content)) {
        json_error('Section key and content are required.');
    }

    $sectionKey = trim((string)$data->section_key);
    if (!in_array($sectionKey, ['home', 'about', 'settings', 'contact'], true)) {
        json_error('Invalid content section key.');
    }

    $contentJson = json_encode($data->content);
    if (!$contentJson) {
        json_error('Failed to encode content as valid JSON.');
    }

    $db = db();
    $stmt = $db->prepare(
        'INSERT INTO website_content (section_key, content_json)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE content_json = VALUES(content_json)'
    );
    $stmt->execute([$sectionKey, $contentJson]);

    logAudit('UPDATE_CONTENT', "Updated website content for section: {$sectionKey}", (int)$admin->id);
    json_success("Website {$sectionKey} content updated successfully.");
}
