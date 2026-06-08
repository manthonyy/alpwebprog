<?php

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ─── helper ───────────────────────────────────────────────
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$action = trim($_GET['action'] ?? '');

// ─── LOGIN ────────────────────────────────────────────────
if ($action === 'login') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim(strtolower($body['email']    ?? ''));
    $pass  = $body['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['success' => false, 'message' => 'Please enter a valid email address.'], 422);
    }

    if (strlen($pass) < 6) {
        respond(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
    }

    $stmt = $conn->prepare("
        SELECT user_id, name, email, password, role
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Database query preparation failed.'], 500);

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($pass, $user['password'])) {
        respond(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    session_regenerate_id(true);

    $_SESSION['suride_user'] = [
        'user_id' => $user['user_id'],
        'name'    => $user['name'],
        'email'   => $user['email'],
        'role'    => $user['role'],
    ];

    respond([
        'success' => true,
        'message' => 'Login successful.',
        'user'    => [
            'user_id' => $user['user_id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ],
    ]);
}

// ─── LOGOUT ───────────────────────────────────────────────
if ($action === 'logout') {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();

    respond(['success' => true, 'message' => 'Logged out successfully.']);
}

// ─── fallback ─────────────────────────────────────────────
respond(['success' => false, 'message' => 'Unknown action. Use ?action=login|logout'], 400);
