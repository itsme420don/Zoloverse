<?php
// Scratch test script to run end-to-end tests against local Zoloverse APIs
$baseUrl = 'http://localhost/Zoloverse';

function postJson($url, $data, $token = null) {
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" . ($token ? "Authorization: Bearer $token\r\n" : ""),
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);
    return json_decode($res, true);
}

function putJson($url, $data, $token = null) {
    $opts = [
        'http' => [
            'method'  => 'PUT',
            'header'  => "Content-Type: application/json\r\n" . ($token ? "Authorization: Bearer $token\r\n" : ""),
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);
    return json_decode($res, true);
}

function getJson($url, $token = null) {
    $opts = [
        'http' => [
            'method'  => 'GET',
            'header'  => ($token ? "Authorization: Bearer $token\r\n" : ""),
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);
    return json_decode($res, true);
}

echo "=== 1. TEST IT SERVICE ENQUIRY ===\n";
$srvPayload = [
    'registration_type' => 'service',
    'full_name' => 'Vikram Sharma',
    'email' => 'vikram@example.com',
    'phone' => '+91 98765 12345',
    'service_id' => 1,
    'requirements' => 'Custom full-stack CRM application with admin dashboard.',
    'preferred_contact_method' => 'Email',
    'consent' => 1
];
$srvRes = postJson("$baseUrl/api_registrations.php", $srvPayload);
echo "Service Enquiry Result: " . json_encode($srvRes) . "\n\n";

echo "=== 2. TEST COURSE REGISTRATION ===\n";
$crsPayload = [
    'registration_type' => 'course',
    'full_name' => 'Ananya Roy',
    'email' => 'ananya@example.com',
    'phone' => '+91 98765 67890',
    'course_id' => 1,
    'qualification' => 'B.Tech IT',
    'experience' => '1 year JavaScript',
    'reason_for_joining' => 'Master MERN stack & system architecture',
    'preferred_batch' => 'Weekend Cohort',
    'consent' => 1
];
$crsRes = postJson("$baseUrl/api_registrations.php", $crsPayload);
echo "Course Registration Result: " . json_encode($crsRes) . "\n\n";

echo "=== 3. TEST CONTACT MESSAGE ===\n";
$contactPayload = [
    'name' => 'Rohan Gupta',
    'email' => 'rohan@example.com',
    'subject' => 'Enterprise UI/UX Audit',
    'message' => 'We would like to request an ergonomic UI/UX evaluation for our mobile portal.'
];
$contactRes = postJson("$baseUrl/api_contact.php", $contactPayload);
echo "Contact Result: " . json_encode($contactRes) . "\n\n";

echo "=== 4. TEST ADMIN LOGIN ===\n";
$loginPayload = [
    'email' => 'chiranjeevi@zoloverse.com',
    'password' => 'Zoloverse26!'
];
$loginRes = postJson("$baseUrl/auth.php?action=login", $loginPayload);
echo "Admin Login Success: " . ($loginRes['success'] ? 'YES' : 'NO') . "\n";
$adminToken = $loginRes['token'] ?? null;

if ($adminToken) {
    echo "\n=== 5. TEST DASHBOARD STATS ===\n";
    $dashRes = getJson("$baseUrl/api_dashboard.php", $adminToken);
    echo "Dashboard KPIs: " . json_encode($dashRes['stats'] ?? []) . "\n";

    echo "\n=== 6. TEST LIST REGISTRATIONS ===\n";
    $regList = getJson("$baseUrl/api_registrations.php?type=service", $adminToken);
    echo "Service Registrations Count: " . count($regList['registrations'] ?? []) . "\n";
    $crsList = getJson("$baseUrl/api_registrations.php?type=course", $adminToken);
    echo "Course Registrations Count: " . count($crsList['registrations'] ?? []) . "\n";

    echo "\n=== 7. TEST LIST CONTACT MESSAGES ===\n";
    $msgList = getJson("$baseUrl/api_contact.php", $adminToken);
    echo "Contact Messages Count: " . count($msgList['messages'] ?? []) . "\n";

    echo "\n=== 8. TEST UPDATE COURSE REGISTRATION STATUS ===\n";
    if (!empty($crsList['registrations'][0]['id'])) {
        $firstId = $crsList['registrations'][0]['id'];
        $updatePayload = [
            'type' => 'course',
            'id' => $firstId,
            'status' => 'Approved',
            'admin_notes' => 'Approved following portfolio check.'
        ];
        $updateRes = putJson("$baseUrl/api_registrations.php", $updatePayload, $adminToken);
        echo "Registration Status Update: " . json_encode($updateRes) . "\n";
    }

    echo "\n=== 9. TEST UPDATE CONTACT MESSAGE STATUS ===\n";
    if (!empty($msgList['messages'][0]['id'])) {
        $firstMsgId = $msgList['messages'][0]['id'];
        $msgUpdatePayload = [
            'id' => $firstMsgId,
            'status' => 'In Progress',
            'admin_notes' => 'Assigned to senior consultant.'
        ];
        $msgUpdateRes = putJson("$baseUrl/api_contact.php", $msgUpdatePayload, $adminToken);
        echo "Contact Message Update: " . json_encode($msgUpdateRes) . "\n";
    }

    echo "\n=== 10. TEST MEDIA UPLOAD ===\n";
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpFile = tempnam(sys_get_temp_dir(), 'test_img') . '.png';
    file_put_contents($tmpFile, $png);

    $ch = curl_init("$baseUrl/api_media.php");
    $cfile = new CURLFile($tmpFile, 'image/png', 'sample_test.png');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $adminToken"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
    $mediaRes = curl_exec($ch);
    curl_close($ch);
    unlink($tmpFile);
    echo "Media Upload Result: " . $mediaRes . "\n";

    echo "\n=== 11. TEST MEDIA LIST ===\n";
    $mediaList = getJson("$baseUrl/api_media.php", $adminToken);
    echo "Media List Count: " . count($mediaList['media'] ?? []) . "\n";
}
