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

// ─── GET cars ─────────────────────────────────────────────
if ($action === 'get') {

    $stmt = $conn->prepare("
        SELECT
            c.car_id,
            c.brand,
            c.model,
            c.year,
            c.license_plate,
            c.price_per_day,
            c.status,
            c.image_url,
            c.description,
            c.category_id,
            cat.category_name
        FROM cars c
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        ORDER BY c.car_id ASC
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error], 500);

    $stmt->execute();
    $result = $stmt->get_result();

    $cars = [];
    while ($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }

    $stmt->close();
    $conn->close();

    respond(['success' => true, 'cars' => $cars]);
}

// ─── ADD car ──────────────────────────────────────────────
if ($action === 'add') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $brand         = trim($_POST['brand']           ?? '');
    $model         = trim($_POST['model']           ?? '');
    $year          = intval($_POST['year']           ?? 0);
    $license_plate = trim($_POST['license_plate']   ?? '');
    $category_id   = intval($_POST['category_id']   ?? 0);
    $status        = trim($_POST['status']          ?? 'available');
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $image_url     = trim($_POST['image_url']       ?? '');
    $description   = trim($_POST['description']     ?? '');

    if (!$brand || !$model || !$year || !$license_plate || !$category_id || !$price_per_day) {
        respond(['success' => false, 'message' => 'Missing required fields.'], 422);
    }

    $allowed_statuses = ['available', 'rented', 'maintenance'];
    if (!in_array($status, $allowed_statuses)) $status = 'available';

    $stmt = $conn->prepare("
        INSERT INTO cars (brand, model, year, license_plate, category_id, status, price_per_day, image_url, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->bind_param('ssisisdss', $brand, $model, $year, $license_plate, $category_id, $status, $price_per_day, $image_url, $description);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        respond(['success' => true, 'message' => 'Car added successfully.', 'car_id' => $new_id]);
    } else {
        respond(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
    }
}

// ─── UPDATE car ───────────────────────────────────────────
if ($action === 'update') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $car_id        = intval($_POST['car_id']          ?? 0);
    $brand         = trim($_POST['brand']             ?? '');
    $model         = trim($_POST['model']             ?? '');
    $year          = intval($_POST['year']             ?? 0);
    $license_plate = trim($_POST['license_plate']     ?? '');
    $category_id   = intval($_POST['category_id']     ?? 0);
    $status        = trim($_POST['status']            ?? 'available');
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $image_url     = trim($_POST['image_url']         ?? '');
    $description   = trim($_POST['description']       ?? '');

    if ($car_id <= 0) respond(['success' => false, 'message' => 'Invalid car ID.'], 422);

    if (!$brand || !$model || !$year || !$license_plate || !$category_id || !$price_per_day) {
        respond(['success' => false, 'message' => 'Missing required fields.'], 422);
    }

    $allowed_statuses = ['available', 'rented', 'maintenance'];
    if (!in_array($status, $allowed_statuses)) $status = 'available';

    $stmt = $conn->prepare("
        UPDATE cars
        SET
            brand         = ?,
            model         = ?,
            year          = ?,
            license_plate = ?,
            category_id   = ?,
            status        = ?,
            price_per_day = ?,
            image_url     = ?,
            description   = ?
        WHERE car_id = ?
    ");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->bind_param('ssisisdssi', $brand, $model, $year, $license_plate, $category_id, $status, $price_per_day, $image_url, $description, $car_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(['success' => true, 'message' => 'Car updated successfully.']);
    } else {
        respond(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
    }
}

// ─── DELETE car ───────────────────────────────────────────
if ($action === 'delete') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'POST required.'], 405);
    }

    $car_id = intval($_POST['car_id'] ?? 0);

    if ($car_id <= 0) respond(['success' => false, 'message' => 'Invalid car ID.'], 422);

    $stmt = $conn->prepare("DELETE FROM cars WHERE car_id = ?");

    if (!$stmt) respond(['success' => false, 'message' => 'Prepare failed: ' . $conn->error], 500);

    $stmt->bind_param('i', $car_id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        if ($affected === 0) {
            respond(['success' => false, 'message' => 'Car not found.'], 404);
        }

        respond(['success' => true, 'message' => 'Car deleted successfully.']);
    } else {
        respond(['success' => false, 'message' => 'Execute failed: ' . $stmt->error], 500);
    }
}

// ─── fallback ─────────────────────────────────────────────
respond(['success' => false, 'message' => 'Unknown action. Use ?action=get|add|update|delete'], 400);
