<?php
include 'dbconnect.php';

$input = json_decode(file_get_contents('php://input'), true);
$hiringId = $input['hiring_id'];
$status = $input['status'];

$stmt = $conn->prepare("UPDATE hiring_records SET status = ? WHERE hiring_id = ?");
$stmt->bind_param('si', $status, $hiringId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
