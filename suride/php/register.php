<?php

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================
   JSON RESPONSE HELPER
========================================= */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/* =========================================
   ONLY ALLOW POST
========================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}

/* =========================================
   GET JSON BODY
========================================= */
$body = json_decode(file_get_contents('php://input'), true);

$firstName = trim($body['first_name'] ?? '');
$lastName  = trim($body['last_name'] ?? '');
$email     = trim(strtolower($body['email'] ?? ''));
$phone     = trim($body['phone'] ?? '');
$password  = $body['password'] ?? '';

/* =========================================
   VALIDATION
========================================= */
$errors = [];

if (strlen($firstName) < 2) {
    $errors['first_name'] = 'First name is required.';
}

if (strlen($lastName) < 2) {
    $errors['last_name'] = 'Last name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email address.';
}

if (strlen($password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters.';
}

if (!empty($errors)) {
    jsonResponse([
        'success' => false,
        'message' => 'Validation failed.',
        'errors' => $errors
    ], 422);
}

/* =========================================
   CHECK DUPLICATE EMAIL
========================================= */
// Disesuaikan: memakai user_id sesuai skema suride_setup.sql
$checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";

$checkStmt = $conn->prepare($checkSql);

if (!$checkStmt) {
    jsonResponse([
        'success' => false,
        'message' => 'Database prepare error.'
    ], 500);
}

$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Email already exists.'
    ], 409);
}

/* =========================================
   INSERT USER
========================================= */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'customer';
$fullName = $firstName . ' ' . $lastName;

// PERBAIKAN: Kolom diubah menjadi `name` dan ditambah `phone` agar data nomor hp tersimpan
$insertSql = "
    INSERT INTO users
    (name, email, phone, password, role)
    VALUES (?, ?, ?, ?, ?)
";

$insertStmt = $conn->prepare($insertSql);

if (!$insertStmt) {
    jsonResponse([
        'success' => false,
        'message' => 'Insert prepare failed.'
    ], 500);
}

// Mengikat 5 parameter (name, email, phone, password, role)
$insertStmt->bind_param(
    "sssss",
    $fullName,
    $email,
    $phone,
    $hashedPassword,
    $role
);

$success = $insertStmt->execute();

if (!$success) {
    jsonResponse([
        'success' => false,
        'message' => 'Failed to register user.'
    ], 500);
}

/* =========================================
   GET NEW USER ID
========================================= */
$newUserId = $conn->insert_id;

/* =========================================
   CREATE SESSION
========================================= */
$_SESSION['suride_user'] = [
    'user_id' => $newUserId,
    'name'    => $fullName,
    'email'   => $email,
    'role'    => 'customer'
];

/* =========================================
   SUCCESS RESPONSE
========================================= */
jsonResponse([
    'success' => true,
    'message' => 'Registration successful.',
    'user' => [
        'user_id' => $newUserId,
        'name'    => $fullName,
        'email'   => $email,
        'role'    => 'customer'
    ]
], 201);

?>