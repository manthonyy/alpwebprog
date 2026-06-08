<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once 'db.php';

// ─── helper ───────────────────────────────────────────────
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$action = trim($_GET['action'] ?? '');

// ─── GET drivers ──────────────────────────────────────────
if ($action === 'get') {

    $stmt = $conn->prepare("
        SELECT driver_id, driver_name, phone_number, driver_status
        FROM drivers
        ORDER BY driver_id ASC
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->execute();
    $result = $stmt->get_result();

    $drivers = [];
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }

    $stmt->close();
    $conn->close();

    respond(['success' => true, 'drivers' => $drivers]);
}

// ─── ADD driver ───────────────────────────────────────────
if ($action === 'add') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $driver_name   = trim($_POST['driver_name']   ?? '');
    $phone_number  = trim($_POST['phone_number']  ?? '');
    $driver_status = trim($_POST['driver_status'] ?? 'available');

    if (!$driver_name || !$phone_number) {
        respond(['success' => false, 'message' => 'Driver name and phone number are required.'], 422);
    }

    $allowed = ['available', 'assigned', 'off'];
    if (!in_array($driver_status, $allowed)) $driver_status = 'available';

    $stmt = $conn->prepare("
        INSERT INTO drivers (driver_name, phone_number, driver_status)
        VALUES (?, ?, ?)
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->bind_param('sss', $driver_name, $phone_number, $driver_status);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        respond(['success' => true, 'message' => 'Driver added successfully.', 'driver_id' => $new_id]);
    } else {
        respond(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
    }
}

// ─── UPDATE driver ────────────────────────────────────────
if ($action === 'update') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $driver_id     = intval($_POST['driver_id']     ?? 0);
    $driver_name   = trim($_POST['driver_name']     ?? '');
    $phone_number  = trim($_POST['phone_number']    ?? '');
    $driver_status = trim($_POST['driver_status']   ?? 'available');

    if ($driver_id <= 0) respond(['success' => false, 'message' => 'Invalid driver ID.'], 422);

    if (!$driver_name || !$phone_number) {
        respond(['success' => false, 'message' => 'Driver name and phone number are required.'], 422);
    }

    $allowed = ['available', 'assigned', 'off'];
    if (!in_array($driver_status, $allowed)) $driver_status = 'available';

    $stmt = $conn->prepare("
        UPDATE drivers
        SET
            driver_name   = ?,
            phone_number  = ?,
            driver_status = ?
        WHERE driver_id = ?
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->bind_param('sssi', $driver_name, $phone_number, $driver_status, $driver_id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        if ($affected === 0) {
            respond(['success' => false, 'message' => 'Driver not found or no changes made.'], 404);
        }

        respond(['success' => true, 'message' => 'Driver updated successfully.']);
    } else {
        respond(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
    }
}

// ─── DELETE driver ────────────────────────────────────────
if ($action === 'delete') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $driver_id = intval($_POST['driver_id'] ?? 0);

    if ($driver_id <= 0) respond(['success' => false, 'message' => 'Invalid driver ID.'], 422);

    $stmt = $conn->prepare("DELETE FROM drivers WHERE driver_id = ?");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->bind_param('i', $driver_id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        if ($affected === 0) {
            respond(['success' => false, 'message' => 'Driver not found.'], 404);
        }

        respond(['success' => true, 'message' => 'Driver deleted successfully.']);
    } else {
        respond(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
    }
}

// ─── fallback ─────────────────────────────────────────────
respond(['success' => false, 'message' => 'Unknown action. Use ?action=get|add|update|delete'], 400);
