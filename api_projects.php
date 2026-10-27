<?php
/**
 * api_projects.php — Projects Portfolio API endpoint for Zoloverse.
 * Handles public portfolio showcase and administrator project management.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? '';
if (($method === 'POST' || $method === 'GET') && ($action === 'delete' || $action === 'delete_gallery_image')) {
    $method = 'DELETE';
}
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE') {
    $method = 'DELETE';
}
if ($method === 'POST') {
    $dataCheck = read_json_body();
    if ($dataCheck && !empty($dataCheck->action) && ($dataCheck->action === 'delete' || $dataCheck->action === 'delete_gallery_image')) {
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
        $sql = 'SELECT * FROM projects WHERE id = ?';
        if (!$admin) {
            $sql .= " AND publication_status = 'Published'";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) {
            json_error('Project not found', 404);
        }

        // Fetch gallery screenshots
        $imgStmt = $db->prepare('SELECT * FROM project_images WHERE project_id = ? ORDER BY display_order ASC, id ASC');
        $imgStmt->execute([$id]);
        $project['gallery'] = $imgStmt->fetchAll();

        json_success('Project loaded', ['project' => $project]);
    }

    if ($slug) {
        $sql = 'SELECT * FROM projects WHERE slug = ?';
        if (!$admin) {
            $sql .= " AND publication_status = 'Published'";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$slug]);
        $project = $stmt->fetch();
        if (!$project) {
            json_error('Project not found', 404);
        }

        $imgStmt = $db->prepare('SELECT * FROM project_images WHERE project_id = ? ORDER BY display_order ASC, id ASC');
        $imgStmt->execute([$project['id']]);
        $project['gallery'] = $imgStmt->fetchAll();

        json_success('Project loaded', ['project' => $project]);
    }

    $sql = 'SELECT * FROM projects';
    $where = [];
    $params = [];

    if (!$admin) {
        $where[] = "publication_status = 'Published'";
    }

    if ($featured) {
        $where[] = 'featured = 1';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY display_order ASC, id DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    json_success('Projects retrieved', ['projects' => $projects]);
}

function handlePost(): void
{
    $admin = requireAuth(true);

    // Sub-action: Add gallery image
    if (isset($_GET['action']) && $_GET['action'] === 'gallery_image') {
        handleAddGalleryImage((int)$admin->id);
        return;
    }

    $data = read_json_body();
    if (!$data || !require_fields($data, ['title', 'description', 'main_image'])) {
        json_error('Project title, description, and main image are required.');
    }

    $title = trim($data->title);
    $slug = !empty($data->slug) ? slugify($data->slug) : slugify($title);
    $description = trim($data->description);
    $mainImage = trim($data->main_image);
    $projectUrl = !empty($data->project_url) ? trim($data->project_url) : null;

    if ($projectUrl && !filter_var($projectUrl, FILTER_VALIDATE_URL)) {
        json_error('Invalid Project Link / Live Application URL format.');
    }

    $technologies = !empty($data->technologies) ? trim($data->technologies) : null;
    $category = !empty($data->category) ? trim($data->category) : 'Web Application';
    $projectStatus = in_array($data->project_status ?? '', ['In Development', 'Completed', 'Maintenance'], true)
        ? $data->project_status : 'Completed';
    $pubStatus = in_array($data->publication_status ?? '', ['Draft', 'Published', 'Unpublished'], true)
        ? $data->publication_status : 'Published';
    $featured = !empty($data->featured) ? 1 : 0;
    $displayOrder = isset($data->display_order) ? (int)$data->display_order : 0;

    $db = db();
    $slug = ensureUniqueProjectSlug($slug);

    $stmt = $db->prepare(
        'INSERT INTO projects (title, slug, description, main_image, project_url, technologies, category, project_status, publication_status, featured, display_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title, $slug, $description, $mainImage, $projectUrl,
        $technologies, $category, $projectStatus, $pubStatus,
        $featured, $displayOrder
    ]);

    $newId = (int)$db->lastInsertId();

    // Optionally add multiple screenshot images
    if (!empty($data->gallery) && is_array($data->gallery)) {
        $imgStmt = $db->prepare('INSERT INTO project_images (project_id, image_path, alt_text, display_order) VALUES (?, ?, ?, ?)');
        $order = 0;
        foreach ($data->gallery as $imgPath) {
            $pathStr = is_string($imgPath) ? trim($imgPath) : (is_object($imgPath) ? trim($imgPath->image_path ?? '') : '');
            if ($pathStr !== '') {
                $imgStmt->execute([$newId, $pathStr, $title . ' screenshot', $order++]);
            }
        }
    }

    logAudit('CREATE_PROJECT', "Created project #{$newId}: {$title}", (int)$admin->id);
    json_success('Project created successfully.', ['id' => $newId, 'slug' => $slug]);
}

function handlePut(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || empty($data->id) || !require_fields($data, ['title', 'description', 'main_image'])) {
        json_error('Project ID, title, description, and main image are required.');
    }

    $id = (int)$data->id;
    $title = trim($data->title);
    $description = trim($data->description);
    $mainImage = trim($data->main_image);
    $projectUrl = !empty($data->project_url) ? trim($data->project_url) : null;

    if ($projectUrl && !filter_var($projectUrl, FILTER_VALIDATE_URL)) {
        json_error('Invalid Project Link / Live Application URL format.');
    }

    $technologies = !empty($data->technologies) ? trim($data->technologies) : null;
    $category = !empty($data->category) ? trim($data->category) : 'Web Application';
    $projectStatus = in_array($data->project_status ?? '', ['In Development', 'Completed', 'Maintenance'], true)
        ? $data->project_status : 'Completed';
    $pubStatus = in_array($data->publication_status ?? '', ['Draft', 'Published', 'Unpublished'], true)
        ? $data->publication_status : 'Published';
    $featured = !empty($data->featured) ? 1 : 0;
    $displayOrder = isset($data->display_order) ? (int)$data->display_order : 0;

    $db = db();
    $stmt = $db->prepare(
        'UPDATE projects
         SET title = ?, description = ?, main_image = ?, project_url = ?,
             technologies = ?, category = ?, project_status = ?,
             publication_status = ?, featured = ?, display_order = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $title, $description, $mainImage, $projectUrl,
        $technologies, $category, $projectStatus,
        $pubStatus, $featured, $displayOrder, $id
    ]);

    logAudit('UPDATE_PROJECT', "Updated project #{$id}: {$title}", (int)$admin->id);
    json_success('Project updated successfully.');
}

function handleDelete(): void
{
    $admin = requireAuth(true);

    if (isset($_GET['action']) && $_GET['action'] === 'delete_gallery_image') {
        $imageId = isset($_GET['image_id']) ? (int)$_GET['image_id'] : 0;
        if ($imageId <= 0) {
            json_error('Valid image ID is required.');
        }
        $db = db();
        $stmt = $db->prepare('DELETE FROM project_images WHERE id = ?');
        $stmt->execute([$imageId]);
        json_success('Gallery image removed.');
    }

    $id = (int)($_GET['id'] ?? $_POST['id'] ?? $_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        $data = read_json_body();
        if ($data && !empty($data->id)) {
            $id = (int)$data->id;
        }
    }
    if ($id <= 0) {
        json_error('Valid project ID is required.');
    }

    $db = db();
    $db->beginTransaction();
    try {
        $stmtImg = $db->prepare('DELETE FROM project_images WHERE project_id = ?');
        $stmtImg->execute([$id]);

        $stmt = $db->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$id]);

        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        json_error('Failed to delete project: ' . $e->getMessage(), 500);
    }

    logAudit('DELETE_PROJECT', "Deleted project #{$id}", (int)$admin->id);
    json_success('Project deleted successfully.');
}

function handleAddGalleryImage(int $adminId): void
{
    $data = read_json_body();
    if (!$data || empty($data->project_id) || empty($data->image_path)) {
        json_error('Project ID and image path are required.');
    }
    $projectId = (int)$data->project_id;
    $imagePath = trim((string)$data->image_path);
    $altText = !empty($data->alt_text) ? trim((string)$data->alt_text) : 'Project screenshot';
    $displayOrder = isset($data->display_order) ? (int)$data->display_order : 0;

    $db = db();
    $stmt = $db->prepare('INSERT INTO project_images (project_id, image_path, alt_text, display_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$projectId, $imagePath, $altText, $displayOrder]);
    $newImgId = (int)$db->lastInsertId();

    logAudit('ADD_PROJECT_IMAGE', "Added screenshot #{$newImgId} for project #{$projectId}", $adminId);
    json_success('Gallery screenshot added.', ['id' => $newImgId]);
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'project';
}

function ensureUniqueProjectSlug(string $slug, int $excludeId = 0): string
{
    $db = db();
    $baseSlug = $slug;
    $counter = 1;

    while (true) {
        $sql = "SELECT id FROM projects WHERE slug = ?";
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
