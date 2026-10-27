<?php
/**
 * api_services.php — IT Services API endpoint for Zoloverse.
 * Handles public browsing and administrator CRUD.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? '';
if (($method === 'POST' || $method === 'GET') && $action === 'delete') {
    $method = 'DELETE';
}
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE') {
    $method = 'DELETE';
}
if ($method === 'POST') {
    $dataCheck = read_json_body();
    if ($dataCheck && !empty($dataCheck->action) && $dataCheck->action === 'delete') {
        $method = 'DELETE';
    }
}

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        json_error('Method Not Allowed', 405);
}

function handleGet(): void
{
    $admin = verifySessionToken(true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : null;
    $featured = isset($_GET['featured']) && $_GET['featured'] === '1';

    $db = db();

    if ($id) {
        $sql = 'SELECT * FROM services WHERE id = ?';
        if (!$admin) {
            $sql .= " AND publication_status = 'Published'";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        if (!$service) {
            json_error('Service not found', 404);
        }
        json_success('Service loaded', ['service' => $service]);
    }

    if ($slug) {
        $sql = 'SELECT * FROM services WHERE slug = ?';
        if (!$admin) {
            $sql .= " AND publication_status = 'Published'";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$slug]);
        $service = $stmt->fetch();
        if (!$service) {
            json_error('Service not found', 404);
        }
        json_success('Service loaded', ['service' => $service]);
    }

    $sql = 'SELECT * FROM services';
    $params = [];
    $where = [];

    if (!$admin) {
        $where[] = "publication_status = 'Published'";
    }

    if ($featured) {
        $where[] = 'featured = 1';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY display_order ASC, id ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll();

    json_success('Services retrieved', ['services' => $services]);
}

function handlePost(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || !require_fields($data, ['title', 'short_description', 'full_description'])) {
        json_error('Title, short description, and full description are required.');
    }

    $title = trim($data->title);
    $slug = !empty($data->slug) ? slugify($data->slug) : slugify($title);
    $shortDesc = trim($data->short_description);
    $fullDesc = trim($data->full_description);
    $imagePath = !empty($data->image_path) ? trim($data->image_path) : null;
    $technologies = !empty($data->technologies) ? trim($data->technologies) : null;
    $features = !empty($data->features) ? trim($data->features) : null;
    $benefits = !empty($data->benefits) ? trim($data->benefits) : null;
    $publicationStatus = in_array($data->publication_status ?? '', ['Draft', 'Published', 'Unpublished'], true)
        ? $data->publication_status : 'Published';
    $featured = !empty($data->featured) ? 1 : 0;
    $displayOrder = isset($data->display_order) ? (int)$data->display_order : 0;

    $db = db();

    // Ensure unique slug
    $slug = ensureUniqueSlug('services', $slug);

    $stmt = $db->prepare(
        'INSERT INTO services (title, slug, short_description, full_description, image_path, technologies, features, benefits, publication_status, featured, display_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title, $slug, $shortDesc, $fullDesc, $imagePath,
        $technologies, $features, $benefits, $publicationStatus,
        $featured, $displayOrder
    ]);

    $newId = (int)$db->lastInsertId();
    logAudit('CREATE_SERVICE', "Created service #{$newId}: {$title}", (int)$admin->id);

    json_success('Service created successfully.', ['id' => $newId, 'slug' => $slug]);
}

function handlePut(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || empty($data->id) || !require_fields($data, ['title', 'short_description', 'full_description'])) {
        json_error('Service ID, title, short description, and full description are required.');
    }

    $id = (int)$data->id;
    $title = trim($data->title);
    $shortDesc = trim($data->short_description);
    $fullDesc = trim($data->full_description);
    $imagePath = !empty($data->image_path) ? trim($data->image_path) : null;
    $technologies = !empty($data->technologies) ? trim($data->technologies) : null;
    $features = !empty($data->features) ? trim($data->features) : null;
    $benefits = !empty($data->benefits) ? trim($data->benefits) : null;
    $publicationStatus = in_array($data->publication_status ?? '', ['Draft', 'Published', 'Unpublished'], true)
        ? $data->publication_status : 'Published';
    $featured = !empty($data->featured) ? 1 : 0;
    $displayOrder = isset($data->display_order) ? (int)$data->display_order : 0;

    $db = db();
    $stmt = $db->prepare(
        'UPDATE services
         SET title = ?, short_description = ?, full_description = ?, image_path = ?,
             technologies = ?, features = ?, benefits = ?, publication_status = ?,
             featured = ?, display_order = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $title, $shortDesc, $fullDesc, $imagePath,
        $technologies, $features, $benefits, $publicationStatus,
        $featured, $displayOrder, $id
    ]);

    logAudit('UPDATE_SERVICE', "Updated service #{$id}: {$title}", (int)$admin->id);
    json_success('Service updated successfully.');
}

function handleDelete(): void
{
    $admin = requireAuth(true);
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? $_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        $data = read_json_body();
        if ($data && !empty($data->id)) {
            $id = (int)$data->id;
        }
    }

    if ($id <= 0) {
        json_error('Valid service ID is required.');
    }

    $db = db();
    $db->beginTransaction();
    try {
        // Automatically delete any linked service registrations to prevent foreign key errors
        $delReg = $db->prepare('DELETE FROM service_registrations WHERE service_id = ?');
        $delReg->execute([$id]);

        $stmt = $db->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([$id]);

        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        json_error('Failed to delete service: ' . $e->getMessage(), 500);
    }

    logAudit('DELETE_SERVICE', "Deleted service #{$id}", (int)$admin->id);
    json_success('Service deleted successfully.');
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'service';
}

function ensureUniqueSlug(string $table, string $slug, int $excludeId = 0): string
{
    $db = db();
    $baseSlug = $slug;
    $counter = 1;

    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . (++$counter);
    }
}
