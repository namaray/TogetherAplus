<?php
session_start();
include 'dbconnect.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to give a review.'); window.location.href = 'login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['hiring_id'])) {
    // More user-friendly redirect
    $_SESSION['error_message'] = "Invalid request: No hiring record specified for review.";
    header('Location: userdashboard.php');
    exit;
}

$hiring_id = (int)$_GET['hiring_id'];

// Fetch task details to display on the review page for context
$task_query = "SELECT t.title, h.name AS helper_name
               FROM hiring_records hr
               JOIN tasks t ON hr.task_id = t.task_id
               JOIN helpers h ON hr.helper_id = h.helper_id
               WHERE hr.hiring_id = ? AND hr.user_id = ?";
$task_stmt = $conn->prepare($task_query);
$task_stmt->bind_param("ii", $hiring_id, $user_id);
$task_stmt->execute();
$task_details = $task_stmt->get_result()->fetch_assoc();
$task_stmt->close();

if (!$task_details) {
    $_SESSION['error_message'] = "Could not find the task you are trying to review.";
    header('Location: userdashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = $conn->real_escape_string($_POST['review']);

    if ($rating < 1 || $rating > 5) {
        $_SESSION['error_message'] = "Invalid rating. Please select a star rating between 1 and 5.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $check_query = "SELECT review_id FROM reviews WHERE hiring_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $hiring_id, $user_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "You have already submitted a review for this task.";
        header('Location: userdashboard.php');
        exit;
    }
    $check_stmt->close();

    $conn->begin_transaction();
    try {
        $insert_query = "INSERT INTO reviews (user_id, helper_id, hiring_id, rating, comment, created_at)
                         SELECT hr.user_id, hr.helper_id, hr.hiring_id, ?, ?, NOW()
                         FROM hiring_records hr
                         WHERE hr.hiring_id = ?";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isi", $rating, $review, $hiring_id);
        $insert_stmt->execute();
        $insert_stmt->close();

        $update_rating_query = "
            UPDATE helpers h
            JOIN (SELECT helper_id FROM hiring_records WHERE hiring_id = ?) AS hr ON h.helper_id = hr.helper_id
            SET h.rating = (SELECT AVG(r.rating) FROM reviews r WHERE r.helper_id = h.helper_id)";
        $update_stmt = $conn->prepare($update_rating_query);
        $update_stmt->bind_param("i", $hiring_id);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();
        $_SESSION['success_message'] = "Thank you for your review!";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "A database error occurred. Could not submit review.";
        // Optional: For debugging, you can log the error: error_log($e->getMessage());
    }
    
    header('Location: userdashboard.php');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Review - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.8s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Custom styles for the star rating */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.25rem;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 2.5rem; /* Increased size */
            color: #d1d5db; /* gray-300 */
            transition: color 0.2s ease-in-out;
        }
        .star-rating input[type="radio"]:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f59e0b; /* amber-500 */
        }
    </style>
</head>
<body class="bg-slate-100">

    <main class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-lg mx-auto">
            <div class="fade-in bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-8">
                    <div class="text-center">
                        <a href="userdashboard.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Back to Dashboard
                        </a>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Leave a Review</h1>
                        <p class="mt-2 text-slate-600">Share your experience for the task <strong class="font-medium text-slate-800">"<?php echo htmlspecialchars($task_details['title']); ?>"</strong> completed by <strong class="font-medium text-slate-800"><?php echo htmlspecialchars($task_details['helper_name']); ?></strong>.</p>
                    </div>

                    <form method="POST" action="give_review.php?hiring_id=<?php echo $hiring_id; ?>" class="mt-8 space-y-6">
                        <div class="form-group">
                            <label class="block text-center text-lg font-bold text-slate-800 mb-2">Your Rating</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars">&#9733;</label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars">&#9733;</label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars">&#9733;</label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars">&#9733;</label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star">&#9733;</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="review" class="block text-lg font-bold text-slate-800 mb-2">Your Review</label>
                            <textarea id="review" name="review" rows="5" placeholder="Tell us about your experience..." required class="block w-full px-4 py-2 text-slate-900 bg-white border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-lg text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:scale-105">
                                Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>