<?php
/**
 * api_registrations.php — Registrations and Enquiries API endpoint for Zoloverse.
 * Handles public submissions and admin workflow management for both:
 *   A. Service Enquiries
 *   B. Course Registrations
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$type = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : 'service';

if (!in_array($type, ['service', 'course'], true)) {
    json_error('Invalid registration type. Must be "service" or "course".', 400);
}

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
        handleGet($type);
        break;
    case 'POST':
        handlePost($type);
        break;
    case 'PUT':
        handlePut($type);
        break;
    case 'DELETE':
        handleDelete($type);
        break;
    default:
        json_error('Method Not Allowed', 405);
}

function handlePost(string $type): void
{
    $data = read_json_body();
    if (!$data) {
        json_error('Invalid request body. JSON payload expected.');
    }

    if (!empty($data->registration_type)) {
        $regType = strtolower(trim((string)$data->registration_type));
        if (in_array($regType, ['service', 'course'], true)) {
            $type = $regType;
        }
    }

    if ($type === 'service') {
        handleServiceSubmission($data);
    } else {
        json_error('Course registrations are no longer accepted. Zoloverse specializes in bespoke IT engineering and project delivery. Please submit a service order request.', 400);
    }
}

function handleServiceSubmission(object $data): void
{
    if (!require_fields($data, ['full_name', 'email', 'phone', 'service_id', 'requirements'])) {
        json_error('Full name, email, phone number, service selection, and project requirements are required.');
    }

    $fullName = trim((string)$data->full_name);
    $email = strtolower(trim((string)$data->email));
    $phone = trim((string)$data->phone);
    $serviceId = (int)$data->service_id;
    $requirements = trim((string)$data->requirements);
    $preferredContact = !empty($data->preferred_contact_method) ? trim((string)$data->preferred_contact_method) : 'Email';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please enter a valid email address.');
    }

    if (strlen($phone) < 7 || strlen($phone) > 25) {
        json_error('Please enter a valid contact phone number.');
    }

    $db = db();

    // Verify service exists and is published
    $svcStmt = $db->prepare("SELECT id, title FROM services WHERE id = ? AND publication_status = 'Published'");
    $svcStmt->execute([$serviceId]);
    $service = $svcStmt->fetch();
    if (!$service) {
        json_error('The selected IT Service is currently unavailable or does not exist.', 404);
    }

    // Rate-limit duplicate submissions: same email & service within 5 minutes
    $dupStmt = $db->prepare(
        'SELECT id, registration_reference FROM service_registrations
         WHERE email = ? AND service_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)'
    );
    $dupStmt->execute([$email, $serviceId]);
    $existing = $dupStmt->fetch();
    if ($existing) {
        json_success('You have recently submitted an enquiry for this service. Our team is already reviewing it.', [
            'reference' => $existing['registration_reference'],
            'service_title' => $service['title']
        ]);
        return;
    }

    // Generate unique reference
    $reference = 'ZOLO-SRV-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    $stmt = $db->prepare(
        'INSERT INTO service_registrations (registration_reference, full_name, email, phone, service_id, requirements, preferred_contact_method, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $reference, $fullName, $email, $phone,
        $serviceId, $requirements, $preferredContact, 'Pending'
    ]);

    json_success('Service enquiry submitted successfully. Our team will contact you shortly.', [
        'reference' => $reference,
        'service_title' => $service['title']
    ]);
}

function handleCourseSubmission(object $data): void
{
    if (!require_fields($data, ['full_name', 'email', 'phone', 'course_id', 'qualification'])) {
        json_error('Full name, email, phone number, course selection, and qualification are required.');
    }

    if (empty($data->consent)) {
        json_error('You must consent to the registration terms to enroll.');
    }

    $fullName = trim((string)$data->full_name);
    $email = strtolower(trim((string)$data->email));
    $phone = trim((string)$data->phone);
    $courseId = (int)$data->course_id;
    $qualification = trim((string)$data->qualification);
    $experience = !empty($data->experience) ? trim((string)$data->experience) : null;
    $reason = !empty($data->reason_for_joining) ? trim((string)$data->reason_for_joining) : null;
    $preferredBatch = !empty($data->preferred_batch) ? trim((string)$data->preferred_batch) : 'Immediate';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please enter a valid email address.');
    }

    if (strlen($phone) < 7 || strlen($phone) > 25) {
        json_error('Please enter a valid contact phone number.');
    }

    $db = db();

    // Verify course exists, is published, and registration is open
    $crsStmt = $db->prepare("SELECT id, title, registration_status FROM courses WHERE id = ? AND publication_status = 'Published'");
    $crsStmt->execute([$courseId]);
    $course = $crsStmt->fetch();
    if (!$course) {
        json_error('The selected course does not exist or is not currently offered.', 404);
    }

    if ($course['registration_status'] !== 'Open') {
        json_error('Registration for this course is currently closed. Please check back for upcoming cohorts.', 400);
    }

    // Check duplicate active registration for same email & course
    $dupStmt = $db->prepare("SELECT id, registration_reference, status FROM course_registrations WHERE email = ? AND course_id = ? AND status IN ('Pending', 'Approved')");
    $dupStmt->execute([$email, $courseId]);
    $existing = $dupStmt->fetch();
    if ($existing) {
        json_success('An enrollment application is already active for this email address.', [
            'reference' => $existing['registration_reference'],
            'status' => $existing['status'],
            'course_title' => $course['title']
        ]);
        return;
    }

    $reference = 'ZOLO-CRS-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    $stmt = $db->prepare(
        'INSERT INTO course_registrations (registration_reference, full_name, email, phone, course_id, qualification, experience, reason_for_joining, preferred_batch, consent, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
    );
    $stmt->execute([
        $reference, $fullName, $email, $phone,
        $courseId, $qualification, $experience, $reason,
        $preferredBatch, 'Pending'
    ]);

    json_success('Course enrollment application submitted successfully!', [
        'reference' => $reference,
        'course_title' => $course['title']
    ]);
}

function handleGet(string $type): void
{
    $admin = requireAuth(true);
    $db = db();

    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $export = isset($_GET['export']) && $_GET['export'] === 'csv';

    if ($type === 'service') {
        $sql = 'SELECT r.*, s.title AS service_title
                FROM service_registrations r
                LEFT JOIN services s ON r.service_id = s.id';
        $where = [];
        $params = [];

        if ($status !== '' && $status !== 'All') {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(r.full_name LIKE ? OR r.email LIKE ? OR r.phone LIKE ? OR r.registration_reference LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.id DESC';

        if ($export) {
            exportServiceCsv($sql, $params);
            return;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $registrations = $stmt->fetchAll();

        json_success('Service enquiries retrieved', ['registrations' => $registrations]);
    } else {
        $sql = 'SELECT r.*, c.title AS course_title, c.category AS course_category
                FROM course_registrations r
                LEFT JOIN courses c ON r.course_id = c.id';
        $where = [];
        $params = [];

        if ($status !== '' && $status !== 'All') {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        if (!empty($_GET['course_id'])) {
            $where[] = 'r.course_id = ?';
            $params[] = (int)$_GET['course_id'];
        }

        if ($search !== '') {
            $where[] = '(r.full_name LIKE ? OR r.email LIKE ? OR r.phone LIKE ? OR r.registration_reference LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.id DESC';

        if ($export) {
            exportCourseCsv($sql, $params);
            return;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $registrations = $stmt->fetchAll();

        json_success('Course registrations retrieved', ['registrations' => $registrations]);
    }
}

function handlePut(string $type): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || empty($data->id) || empty($data->status)) {
        json_error('Record ID and updated status are required.');
    }

    if (!empty($data->type)) {
        $regType = strtolower(trim((string)$data->type));
        if (in_array($regType, ['service', 'course'], true)) {
            $type = $regType;
        }
    }

    $id = (int)$data->id;
    $db = db();
    $now = date('Y-m-d H:i:s');
    $adminName = $admin->name ?? 'Admin';

    if ($type === 'service') {
        $validStatuses = ['Pending', 'In Progress', 'Contacted', 'Completed', 'Rejected', 'Cancelled'];
        if (!in_array($data->status, $validStatuses, true)) {
            json_error('Invalid service enquiry status.');
        }

        $status = $data->status;
        $adminNotes = isset($data->admin_notes) ? trim((string)$data->admin_notes) : null;

        $stmt = $db->prepare(
            'UPDATE service_registrations
             SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = ?
             WHERE id = ?'
        );
        $stmt->execute([$status, $adminNotes, $adminName, $now, $id]);

        logAudit('UPDATE_SERVICE_ENQUIRY', "Updated service enquiry #{$id} status to {$status}", (int)$admin->id);
        json_success('Service enquiry updated successfully.');
    } else {
        $validStatuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
        if (!in_array($data->status, $validStatuses, true)) {
            json_error('Invalid course registration status.');
        }

        $status = $data->status;
        $rejectionReason = !empty($data->rejection_reason) ? trim((string)$data->rejection_reason) : null;
        $adminNotes = isset($data->admin_notes) ? trim((string)$data->admin_notes) : null;

        $stmt = $db->prepare(
            'UPDATE course_registrations
             SET status = ?, rejection_reason = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = ?
             WHERE id = ?'
        );
        $stmt->execute([$status, $rejectionReason, $adminNotes, $adminName, $now, $id]);

        logAudit('UPDATE_COURSE_REGISTRATION', "Updated course registration #{$id} status to {$status}", (int)$admin->id);
        json_success('Course registration updated successfully.');
    }
}

function exportServiceCsv(string $sql, array $params): void
{
    $db = db();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="zoloverse_service_enquiries_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Reference', 'Name', 'Email', 'Phone', 'Service', 'Requirements', 'Preferred Contact', 'Status', 'Submitted At']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['registration_reference'],
            $r['full_name'],
            $r['email'],
            $r['phone'],
            $r['service_title'] ?? 'N/A',
            $r['requirements'],
            $r['preferred_contact_method'],
            $r['status'],
            $r['created_at']
        ]);
    }
    fclose($out);
    exit;
}

function exportCourseCsv(string $sql, array $params): void
{
    $db = db();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="zoloverse_course_registrations_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Reference', 'Student Name', 'Email', 'Phone', 'Course', 'Qualification', 'Experience', 'Batch', 'Status', 'Submitted At']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['registration_reference'],
            $r['full_name'],
            $r['email'],
            $r['phone'],
            $r['course_title'] ?? 'N/A',
            $r['qualification'],
            $r['experience'] ?? '',
            $r['preferred_batch'] ?? '',
            $r['status'],
            $r['created_at']
        ]);
    }
    fclose($out);
    exit;
}

function handleDelete(string $type): void
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
        json_error('Valid registration ID is required.');
    }

    $db = db();
    $table = ($type === 'service') ? 'service_registrations' : 'course_registrations';

    $stmt = $db->prepare("SELECT registration_reference, full_name FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        json_error('Registration record not found.', 404);
    }

    $delStmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
    $delStmt->execute([$id]);

    logAudit(
        'DELETE_REGISTRATION',
        "Deleted {$type} registration #{$id} ({$record['registration_reference']} - {$record['full_name']})",
        (int)$admin->id
    );

    json_success('Registration deleted successfully.');
}

