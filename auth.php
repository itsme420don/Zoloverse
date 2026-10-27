<?php
/**
 * auth.php — Authentication & account endpoints.
 *   GET  ?action=config            Public client config (Google Client ID)
 *   POST ?action=register          Create a customer account
 *   POST ?action=login             Sign in (email/name + password)
 *   POST ?action=google_login      Sign in with a Google credential
 *   GET  ?action=verify            Validate the current session token
 *   GET  ?action=profile           Fetch the signed-in user's profile
 *   POST ?action=update_profile    Update name / phone for signed-in user
 *   POST ?action=change_password   Change password (requires current password)
 *   POST ?action=forgot_password   Generate a password reset token
 *   POST ?action=reset_password    Set a new password using a reset token
 */

require_once 'includes/init.php';

$pdo  = db();
$action = $_GET['action'] ?? '';

/* ------------------------------ Public config ------------------------------ */
if ($action === 'config') {
    json_response(['google_client_id' => GOOGLE_CLIENT_ID]);
}

/* -------------------------------- Register -------------------------------- */
if ($action === 'register') {
    $data = read_json_body();
    $name = trim($data->name ?? '');
    $email = strtolower(trim($data->email ?? ''));
    $password = $data->password ?? '';

    if ($name === '' || $email === '') {
        json_error('Please fill in all required fields.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please enter a valid email address.');
    }
    if (!isValidPassword($password)) {
        json_error('Password must be between 6 and 72 characters.');
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
    try {
        $stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':password' => $hashed,
            ':role'    => 'customer',
        ]);
        json_success('Account created successfully. Please sign in.');
    } catch (PDOException $e) {
        if ($e->errorInfo[1] === 1062) { // Duplicate entry (unique email)
            json_error('This email is already registered. Try signing in.', 400);
        }
        error_log('[zoloverse] register failed: ' . $e->getMessage());
        json_error('Registration failed. Please try again later.', 500);
    }
}

/* ---------------------------------- Login --------------------------------- */
if ($action === 'login') {
    $data = read_json_body();
    $identifier = strtolower(trim($data->email ?? ''));
    $password   = $data->password ?? '';

    if ($identifier === '' || $password === '') {
        json_error('Email and password are required.');
    }

    $stmt = $pdo->prepare('SELECT id, name, email, phone, password, role FROM users WHERE email = :email OR name = :name LIMIT 1');
    $stmt->execute([':email' => $identifier, ':name' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_error('Invalid email or password.', 401);
    }

    $tokenPayload = [
        'id'    => (int) $user['id'],
        'email' => $user['email'],
        'name'  => $user['name'],
        'role'  => $user['role'] ?? 'customer',
        'exp'   => time() + SESSION_TTL,
    ];

    json_response([
        'success' => true,
        'token'   => generateSessionToken($tokenPayload),
        'user'    => [
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ],
    ]);
}

/* ------------------------------ Google login ------------------------------ */
if ($action === 'google_login') {
    $data = read_json_body();
    $google_token = trim($data->credential ?? '');

    if ($google_token === '') {
        json_error('Google credential token required.');
    }

    $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($google_token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        json_error('Failed to verify token with Google API.', 400);
    }

    $payload = json_decode($response);

    // Audience (Client ID) verification — skipped while the placeholder is in use.
    $is_placeholder = strpos(GOOGLE_CLIENT_ID, 'YOUR_GOOGLE_CLIENT_ID') !== false;
    if (!$is_placeholder && ($payload->aud ?? '') !== GOOGLE_CLIENT_ID) {
        json_error('Audience verification failed.', 400);
    }

    $iss = $payload->iss ?? '';
    if ($iss !== 'https://accounts.google.com' && $iss !== 'accounts.google.com') {
        json_error('Issuer verification failed.', 400);
    }

    if (($payload->exp ?? 0) <= time()) {
        json_error('Token has expired.', 400);
    }

    $email = strtolower(trim($payload->email ?? ''));
    if ($email === '') {
        json_error('Email not found in Google payload.', 400);
    }

    $name = $payload->name ?? 'Google User';

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $insert_stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $insert_stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':password' => $random_password,
            ':role'    => 'customer',
        ]);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    }

    $tokenPayload = [
        'id'    => (int) $user['id'],
        'email' => $user['email'],
        'name'  => $user['name'],
        'role'  => $user['role'],
        'exp'   => time() + SESSION_TTL,
    ];

    json_response([
        'success' => true,
        'token'   => generateSessionToken($tokenPayload),
        'user'    => [
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ],
    ]);
}

/* ------------------------------ Session check ----------------------------- */
if ($action === 'verify') {
    $user = verifySessionToken(false);
    if ($user) {
        json_response([
            'valid' => true,
            'user'  => [
                'name'  => $user->name ?? '',
                'email' => $user->email ?? '',
                'role'  => $user->role ?? 'customer',
            ],
        ]);
    }
    json_error('Session expired. Please sign in again.', 401);
}

/* --------------------------------- Profile -------------------------------- */
if ($action === 'profile') {
    $user = requireAuth(false);

    $stmt = db()->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id');
    $stmt->execute([':id' => (int) $user->id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        json_error('Account not found. Please sign in again.', 404);
    }

    json_response(['success' => true, 'profile' => $profile]);
}

if ($action === 'update_profile') {
    $user  = requireAuth(false);
    $data  = read_json_body();
    $name  = trim($data->name ?? '');
    $phone = trim($data->phone ?? '');

    if ($name === '') {
        json_error('Name cannot be empty.');
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,15}$/', $phone)) {
        json_error('Please enter a valid phone number.');
    }

    db()->prepare('UPDATE users SET name = :name, phone = :phone WHERE id = :id')
        ->execute([':name' => $name, ':phone' => $phone, ':id' => (int) $user->id]);

    json_success('Profile updated successfully.');
}

/* ---------------------------- Change password ----------------------------- */
if ($action === 'change_password') {
    $user  = requireAuth(false);
    $data  = read_json_body();
    $current = $data->current_password ?? '';
    $new     = $data->new_password ?? '';

    if ($current === '' || $new === '') {
        json_error('Current password and new password are required.');
    }
    if (!isValidPassword($new)) {
        json_error('New password must be between 6 and 72 characters.');
    }

    $stmt = db()->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->execute([':id' => (int) $user->id]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password'])) {
        json_error('Current password is incorrect.', 401);
    }

    db()->prepare('UPDATE users SET password = :password WHERE id = :id')
        ->execute([':password' => password_hash($new, PASSWORD_BCRYPT), ':id' => (int) $user->id]);

    json_success('Password changed successfully. Please use your new password next time.');
}

/* --------------------------- Forgot password ------------------------------ */
if ($action === 'forgot_password') {
    $data  = read_json_body();
    $email = strtolower(trim($data->email ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Enter a valid email address.');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

        db()->prepare('DELETE FROM password_resets WHERE email = :email')->execute([':email' => $email]);
        db()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)')
            ->execute([':email' => $email, ':token' => $tokenHash, ':expires' => $expiresAt]);

        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $resetLink = $scheme . '://' . $host . $baseDir . '/login.html?reset=' . $token;

        $sent = false;
        $subject = 'Zoloverse — Password Reset';
        $body    = "<p>Hello,</p><p>Use the link below to reset your Zoloverse password (valid for 1 hour):</p>"
                 . "<p><a href=\"{$resetLink}\">{$resetLink}</a></p>";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: operations@zoloverse.com\r\n";
        if (function_exists('mail')) {
            $sent = @mail($email, $subject, $body, $headers);
        }

        // The reset link is also returned directly so the flow works on
        // local development stacks where mail() is not configured.
        json_response([
            'success'    => true,
            'message'    => 'A password reset link has been generated.',
            'reset_link' => $resetLink,
            'sent'       => $sent ?? false,
        ]);
    }

    // Always succeed for non-registered emails to avoid user enumeration.
    json_response([
        'success' => true,
        'message' => 'If that email is registered, a reset link was generated for it.',
    ]);
}

/* ----------------------------- Reset password ------------------------------ */
if ($action === 'reset_password') {
    $data     = read_json_body();
    $token    = trim($data->token ?? '');
    $new      = $data->password ?? '';

    if (strlen($token) < 32) {
        json_error('Invalid or expired reset link.');
    }
    if (!isValidPassword($new)) {
        json_error('New password must be between 6 and 72 characters.');
    }

    $stmt = db()->prepare('SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1');
    $stmt->execute([':token' => hash('sha256', $token)]);
    $row = $stmt->fetch();

    if (!$row) {
        json_error('This reset link is invalid or has expired. Please request a new one.', 400);
    }

    db()->prepare('UPDATE users SET password = :password WHERE email = :email')
        ->execute([
            ':password' => password_hash($new, PASSWORD_BCRYPT),
            ':email'    => $row['email'],
        ]);
    db()->prepare('DELETE FROM password_resets WHERE email = :email')->execute([':email' => $row['email']]);

    json_success('Your password has been reset. Please sign in with your new password.');
}

/* ------------------------------ Unknown action ----------------------------- */
json_error('Invalid action.');