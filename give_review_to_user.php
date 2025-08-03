<?php
session_start();
include 'dbconnect.php'; // Include database connection

// Ensure the helper is logged in
if (!isset($_SESSION['helper_id'])) {
    echo "<script>alert('Please log in to leave a review.'); window.location.href = 'login.php';</script>";
    exit;
}

$helper_id = $_SESSION['helper_id'];

// Validate hiring_id from the query string
if (!isset($_GET['hiring_id'])) {
    echo "<script>alert('Invalid request.'); window.location.href = 'current_jobs.php';</script>";
    exit;
}

$hiring_id = (int)$_GET['hiring_id'];

// Fetch the user's details for the given hiring_id
$query = "SELECT hr.user_id, u.name AS user_name
          FROM hiring_records hr
          JOIN users u ON hr.user_id = u.user_id
          WHERE hr.hiring_id = $hiring_id AND hr.helper_id = $helper_id";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "<script>alert('Invalid hiring record or you are not authorized to review this user.'); window.location.href = 'current_jobs.php';</script>";
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['user_id'];
$user_name = $user['user_name'];

// Check if the review already exists
$check_query = "SELECT rating, comment FROM reviews WHERE hiring_id = $hiring_id AND helper_id = $helper_id";
$check_result = $conn->query($check_query);

$review_exists = false;
$review_data = null;

if ($check_result->num_rows > 0) {
    $review_exists = true;
    $review_data = $check_result->fetch_assoc(); // Fetch the existing review details
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$review_exists) {
    $rating = (float)$_POST['rating']; // Accept decimal values
    $comment = $conn->real_escape_string($_POST['comment']);

    // Validate rating (allow decimals between 1 and 5)
    if ($rating < 1 || $rating > 5) {
        echo "<script>alert('Invalid rating. Please provide a rating between 1 and 5, including decimal values.');</script>";
        exit;
    }

    // Insert the review into the reviews table
    $insert_query = "INSERT INTO reviews (user_id, helper_id, hiring_id, rating, comment, created_at) 
                     VALUES ($user_id, $helper_id, $hiring_id, $rating, '$comment', NOW())";

    if ($conn->query($insert_query) === TRUE) {
        echo "<script>
            alert('Thank you for your review!');
            window.location.href = 'current_jobs.php';
        </script>";
    } else {
        echo "<script>alert('Error: Unable to submit review. " . $conn->error . "');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review</title>
    <style>
        .review-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .review-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: #43a047;
        }

        .already-reviewed {
            text-align: center;
            padding: 15px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body><?php include "header_helper.php"?>
    <div class="review-container">
        <h1>Leave a Review for <?php echo htmlspecialchars($user_name); ?></h1>

        <?php if ($review_exists): ?>
            <!-- If the review already exists, show the existing review -->
            <div class="already-reviewed">
                <p><strong>Rating:</strong> <?php echo htmlspecialchars($review_data['rating']); ?></p>
                <p><strong>Comment:</strong> <?php echo htmlspecialchars($review_data['comment']); ?></p>
                <p>You have already submitted a review for this user.</p>
            </div>
        <?php else: ?>
            <!-- If no review exists, show the review form -->
            <form method="POST">
                <div class="form-group">
                    <label for="rating">Rating (1.0 to 5.0):</label>
                    <input type="number" id="rating" name="rating" step="0.1" min="1" max="5" required>
                </div>
                <div class="form-group">
                    <label for="comment">Comment:</label>
                    <textarea id="comment" name="comment" rows="5" placeholder="Write your review here..." required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit">Submit Review</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
