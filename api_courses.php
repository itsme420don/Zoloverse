<?php
/**
 * api_courses.php — Courses API endpoint for Zoloverse.
 * Handles public browsing and administrator course management.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];

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
    $category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;
    $featured = isset($_GET['featured']) && $_GET['featured'] === '1';

    $db = db();

    if ($id) {
        $sql = 'SELECT * FROM courses WHERE id = ?';
        if (!$admin) {
            $sql .= " AND publication_status = 'Published'";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $course = $stmt->fetch();
        if (!$course) {
            json_error('Course not found', 404);
        }
        json_success('Course loaded', ['course' => $course]);
    }

    if ($slug) {
        $sql = 'SELECT * FROM courses WHERE slug = ?';
        if (!$admin) {
            $sql .= " AND publication_status = 'Published'";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$slug]);
        $course = $stmt->fetch();
        if (!$course) {
            json_error('Course not found', 404);
        }
        json_success('Course loaded', ['course' => $course]);
    }

    $sql = 'SELECT * FROM courses';
    $where = [];
    $params = [];

    if (!$admin) {
        $where[] = "publication_status = 'Published'";
    }

    if ($category && $category !== 'All') {
        $where[] = 'category = ?';
        $params[] = $category;
    }

    if ($featured) {
        $where[] = 'featured = 1';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY id ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();

    json_success('Courses retrieved', ['courses' => $courses]);
}

function handlePost(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || !require_fields($data, ['title', 'description', 'duration'])) {
        json_error('Course title, description, and duration are required.');
    }

    $title = trim($data->title);
    $slug = !empty($data->slug) ? slugify($data->slug) : slugify($title);
    $desc = trim($data->description);
    $imagePath = !empty($data->image_path) ? trim($data->image_path) : null;
    $category = !empty($data->category) ? trim($data->category) : 'Full-Stack Development';
    $level = in_array($data->level ?? '', ['Beginner', 'Intermediate', 'Advanced'], true) ? $data->level : 'Beginner';
    $duration = trim($data->duration);
    $learningMode = in_array($data->learning_mode ?? '', ['Online', 'Offline', 'Hybrid'], true) ? $data->learning_mode : 'Online';
    $prerequisites = !empty($data->prerequisites) ? trim($data->prerequisites) : null;
    $learningOutcomes = !empty($data->learning_outcomes) ? trim($data->learning_outcomes) : null;
    $curriculum = !empty($data->curriculum) ? trim($data->curriculum) : null;
    $instructorInfo = !empty($data->instructor_info) ? trim($data->instructor_info) : null;
    $regStatus = in_array($data->registration_status ?? '', ['Open', 'Closed'], true) ? $data->registration_status : 'Open';
    $pubStatus = in_array($data->publication_status ?? '', ['Draft', 'Published', 'Unpublished'], true) ? $data->publication_status : 'Published';
    $featured = !empty($data->featured) ? 1 : 0;

    $db = db();
    $slug = ensureUniqueCourseSlug($slug);

    $stmt = $db->prepare(
        'INSERT INTO courses (title, slug, description, image_path, category, level, duration, learning_mode, prerequisites, learning_outcomes, curriculum, instructor_info, registration_status, publication_status, featured)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title, $slug, $desc, $imagePath, $category, $level, $duration,
        $learningMode, $prerequisites, $learningOutcomes, $curriculum,
        $instructorInfo, $regStatus, $pubStatus, $featured
    ]);

    $newId = (int)$db->lastInsertId();
    logAudit('CREATE_COURSE', "Created course #{$newId}: {$title}", (int)$admin->id);

    json_success('Course created successfully.', ['id' => $newId, 'slug' => $slug]);
}

function handlePut(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || empty($data->id) || !require_fields($data, ['title', 'description', 'duration'])) {
        json_error('Course ID, title, description, and duration are required.');
    }

    $id = (int)$data->id;
    $title = trim($data->title);
    $desc = trim($data->description);
    $imagePath = !empty($data->image_path) ? trim($data->image_path) : null;
    $category = !empty($data->category) ? trim($data->category) : 'Full-Stack Development';
    $level = in_array($data->level ?? '', ['Beginner', 'Intermediate', 'Advanced'], true) ? $data->level : 'Beginner';
    $duration = trim($data->duration);
    $learningMode = in_array($data->learning_mode ?? '', ['Online', 'Offline', 'Hybrid'], true) ? $data->learning_mode : 'Online';
    $prerequisites = !empty($data->prerequisites) ? trim($data->prerequisites) : null;
    $learningOutcomes = !empty($data->learning_outcomes) ? trim($data->learning_outcomes) : null;
    $curriculum = !empty($data->curriculum) ? trim($data->curriculum) : null;
    $instructorInfo = !empty($data->instructor_info) ? trim($data->instructor_info) : null;
    $regStatus = in_array($data->registration_status ?? '', ['Open', 'Closed'], true) ? $data->registration_status : 'Open';
    $pubStatus = in_array($data->publication_status ?? '', ['Draft', 'Published', 'Unpublished'], true) ? $data->publication_status : 'Published';
    $featured = !empty($data->featured) ? 1 : 0;

    $db = db();
    $stmt = $db->prepare(
        'UPDATE courses
         SET title = ?, description = ?, image_path = ?, category = ?, level = ?,
             duration = ?, learning_mode = ?, prerequisites = ?, learning_outcomes = ?,
             curriculum = ?, instructor_info = ?, registration_status = ?,
             publication_status = ?, featured = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $title, $desc, $imagePath, $category, $level, $duration,
        $learningMode, $prerequisites, $learningOutcomes, $curriculum,
        $instructorInfo, $regStatus, $pubStatus, $featured, $id
    ]);

    logAudit('UPDATE_COURSE', "Updated course #{$id}: {$title}", (int)$admin->id);
    json_success('Course updated successfully.');
}

function handleDelete(): void
{
    $admin = requireAuth(true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        json_error('Valid course ID is required.');
    }

    $db = db();

    // Check if registrations exist for this course
    $checkStmt = $db->prepare('SELECT COUNT(*) FROM course_registrations WHERE course_id = ?');
    $checkStmt->execute([$id]);
    $regCount = (int)$checkStmt->fetchColumn();

    $cascade = isset($_GET['cascade']) && $_GET['cascade'] === '1';

    if ($regCount > 0 && !$cascade) {
        json_response([
            'success'            => false,
            'message'            => "Cannot delete course because it has {$regCount} linked student registration(s).",
            'has_linked_records' => true,
            'reg_count'          => $regCount,
            'can_cascade'        => true
        ], 409);
    }

    $db->beginTransaction();
    try {
        if ($regCount > 0 && $cascade) {
            $delReg = $db->prepare('DELETE FROM course_registrations WHERE course_id = ?');
            $delReg->execute([$id]);
        }

        $stmt = $db->prepare('DELETE FROM courses WHERE id = ?');
        $stmt->execute([$id]);

        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        json_error('Failed to delete course: ' . $e->getMessage(), 500);
    }

    logAudit('DELETE_COURSE', "Deleted course #{$id}" . ($cascade ? " (and {$regCount} linked student registration(s))" : ''), (int)$admin->id);
    json_success('Course deleted successfully.');
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'course';
}

function ensureUniqueCourseSlug(string $slug, int $excludeId = 0): string
{
    $db = db();
    $baseSlug = $slug;
    $counter = 1;

    while (true) {
        $sql = "SELECT id FROM courses WHERE slug = ?";
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
