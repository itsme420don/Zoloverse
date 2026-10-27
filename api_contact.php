<?php
/**
 * api_contact.php — Contact Enquiries API endpoint for Zoloverse.
 * Handles public contact form submissions and administrator enquiry workflow.
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

function handlePost(): void
{
    $data = read_json_body();
    if (!$data || !require_fields($data, ['name', 'email', 'subject', 'message'])) {
        json_error('Your name, email address, subject, and message are required.');
    }

    // Anti-spam honeypot check
    if (!empty($data->website_hp)) {
        // Silently accept without saving spam bot submissions
        json_success('Thank you for contacting Zoloverse. Your message has been received.');
        return;
    }

    $name = trim((string)$data->name);
    $email = strtolower(trim((string)$data->email));
    $subject = trim((string)$data->subject);
    $message = trim((string)$data->message);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please provide a valid email address.');
    }

    if (strlen($message) < 10) {
        json_error('Please provide a meaningful message (at least 10 characters).');
    }

    $db = db();

    // Rate-limiting check: maximum 5 messages per email in 1 hour
    $rateStmt = $db->prepare('SELECT COUNT(*) FROM contact_messages WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $rateStmt->execute([$email]);
    if ((int)$rateStmt->fetchColumn() >= 5) {
        json_error('Too many requests. Please wait a bit before submitting another message.', 429);
    }

    $stmt = $db->prepare('INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $subject, $message, 'New']);

    json_success('Thank you for contacting Zoloverse. We have received your enquiry and will respond promptly.');
}

function handleGet(): void
{
    $admin = requireAuth(true);
    $db = db();

    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

    $sql = 'SELECT * FROM contact_messages';
    $where = [];
    $params = [];

    if ($status !== '' && $status !== 'All') {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like]);
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY id DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    json_success('Messages retrieved', ['messages' => $messages]);
}

function handlePut(): void
{
    $admin = requireAuth(true);
    $data = read_json_body();

    if (!$data || empty($data->id) || empty($data->status)) {
        json_error('Message ID and status are required.');
    }

    $id = (int)$data->id;
    $validStatuses = ['New', 'In Progress', 'Responded', 'Closed'];
    if (!in_array($data->status, $validStatuses, true)) {
        json_error('Invalid message status.');
    }

    $status = $data->status;
    $adminNotes = isset($data->admin_notes) ? trim((string)$data->admin_notes) : null;

    $db = db();
    $stmt = $db->prepare('UPDATE contact_messages SET status = ?, admin_notes = ? WHERE id = ?');
    $stmt->execute([$status, $adminNotes, $id]);

    logAudit('UPDATE_CONTACT_MESSAGE', "Updated contact message #{$id} status to {$status}", (int)$admin->id);
    json_success('Message updated successfully.');
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
        json_error('Valid message ID is required.');
    }

    $db = db();
    $stmt = $db->prepare('DELETE FROM contact_messages WHERE id = ?');
    $stmt->execute([$id]);

    logAudit('DELETE_CONTACT_MESSAGE', "Deleted contact message #{$id}", (int)$admin->id);
    json_success('Message deleted successfully.');
}
