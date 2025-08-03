<?php
session_start();
include 'dbconnect.php';

// We will always respond with JSON data.
header('Content-Type: application/json');

// Determine who is currently logged in (user or helper).
$current_user_id = null;
$current_user_role = null;

if (isset($_SESSION['user_id'])) {
    $current_user_id = (int)$_SESSION['user_id'];
    $current_user_role = 'user';
} elseif (isset($_SESSION['helper_id'])) {
    $current_user_id = (int)$_SESSION['helper_id'];
    $current_user_role = 'helper';
}

// Security: If no one is logged in, exit.
if (!$current_user_id) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // Action to get the list of all unique conversations.
    case 'get_conversations':
        // This query finds the last message for each unique conversation partner.
        // It's complex but efficient. It groups all messages by conversation pair.
        $sql = "
            SELECT 
                IF(m.sender_role = ?, m.receiver_id, m.sender_id) AS contact_id,
                IF(m.sender_role = ?, m.receiver_role, m.sender_role) AS contact_role,
                (CASE
                    WHEN IF(m.sender_role = ?, m.receiver_role, m.sender_role) = 'user' THEN (SELECT name FROM users WHERE user_id = IF(m.sender_role = ?, m.receiver_id, m.sender_id))
                    WHEN IF(m.sender_role = ?, m.receiver_role, m.sender_role) = 'helper' THEN (SELECT name FROM helpers WHERE helper_id = IF(m.sender_role = ?, m.receiver_id, m.sender_id))
                END) AS contact_name,
                m.message_content,
                m.timestamp,
                (SELECT COUNT(*) FROM chat_messages cm WHERE cm.receiver_id = ? AND cm.receiver_role = ? AND cm.sender_id = IF(m.sender_role = ?, m.receiver_id, m.sender_id) AND cm.sender_role = IF(m.sender_role = ?, m.receiver_role, m.sender_role) AND cm.is_read = 0) as unread_count
            FROM chat_messages m
            INNER JOIN (
                SELECT 
                    LEAST(CONCAT(sender_role, sender_id), CONCAT(receiver_role, receiver_id)) as conv_id_part1,
                    GREATEST(CONCAT(sender_role, sender_id), CONCAT(receiver_role, receiver_id)) as conv_id_part2,
                    MAX(message_id) as max_message_id
                FROM chat_messages
                WHERE (sender_id = ? AND sender_role = ?) OR (receiver_id = ? AND receiver_role = ?)
                GROUP BY conv_id_part1, conv_id_part2
            ) AS last_messages ON m.message_id = last_messages.max_message_id
            ORDER BY m.timestamp DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssissssisii", $current_user_role, $current_user_role, $current_user_role, $current_user_role, $current_user_role, $current_user_role, $current_user_id, $current_user_role, $current_user_role, $current_user_role, $current_user_id, $current_user_role, $current_user_id, $current_user_role);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversations = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($conversations);
        break;

    // Action to get the message history with a specific person.
    case 'get_messages':
        $contact_id = (int)($_GET['contact_id'] ?? 0);
        $contact_role = $_GET['contact_role'] ?? '';

        if ($contact_id > 0 && !empty($contact_role)) {
            // Mark messages from this contact as read.
            $update_sql = "UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("isis", $contact_id, $contact_role, $current_user_id, $current_user_role);
            $update_stmt->execute();

            // Fetch the entire conversation history.
            $sql = "SELECT * FROM chat_messages WHERE 
                    (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?) OR 
                    (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?) 
                    ORDER BY timestamp ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isisisis", $current_user_id, $current_user_role, $contact_id, $contact_role, $contact_id, $contact_role, $current_user_id, $current_user_role);
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($messages);
        }
        break;

    // Action to send a new message.
    case 'send_message':
        $input = json_decode(file_get_contents('php://input'), true);
        $receiver_id = (int)($input['receiver_id'] ?? 0);
        $receiver_role = $input['receiver_role'] ?? '';
        $message = trim($input['message'] ?? '');

        if ($receiver_id > 0 && !empty($receiver_role) && !empty($message)) {
            $sql = "INSERT INTO chat_messages (sender_id, sender_role, receiver_id, receiver_role, message_content) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiss", $current_user_id, $current_user_role, $receiver_id, $receiver_role, $message);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Failed to send message.']);
            }
        } else {
             echo json_encode(['error' => 'Invalid input.']);
        }
        break;
}
?>
