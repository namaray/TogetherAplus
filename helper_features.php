<?php
session_start();
include 'dbconnect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helper Features - TogetherA+</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        h1 {
            margin-bottom: 30px;
            font-size: 28px;
            color: #333;
        }
        .feature-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .feature-card {
            flex: 1 1 calc(33% - 40px);
            background-color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            max-width: 300px;
            overflow: hidden;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }
        .feature-card p {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }
        .feature-card a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }
        .feature-card a:hover {
            background-color: #0056b3;
        }
        .feature-icon {
            font-size: 40px;
            margin-bottom: 10px;
            color: #007bff;
        }
        .feature-icon:hover {
            color: #0056b3;
        }
        /* Background animation */
        .feature-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0));
            transform: rotate(45deg);
            z-index: -1;
            transition: opacity 0.3s;
            opacity: 0;
        }
        .feature-card:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include 'header_user.php'; ?>

    <main>
        <h1>Explore Helper Features</h1>
        <div class="feature-container">
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Find New Helpers</h3>
                <p>Discover helpers you haven’t worked with before.</p>
                <a href="find_new_helper.php">View Feature</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Personalized Recommendations</h3>
                <p>Get helper recommendations tailored to your specific needs and preferences.</p>
                <a href="personalized_helpers.php">View Feature</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h3>Available Helpers</h3>
                <p>Find helpers who are currently available to assist you with your tasks.</p>
                <a href="available_helpers.php">View Feature</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Top Helpers by Task Specialization</h3>
                <p>See top-rated helpers based on the type of tasks they specialize in.</p>
                <a href="specialized_helpers.php">View Feature</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Affordable Helpers</h3>
                <p>Explore helpers that fit your budget without compromising on quality.</p>
                <a href="affordable_helpers.php">View Feature</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Long-Term Hiring Potential</h3>
                <p>Identify helpers who are ideal for long-term support and collaboration.</p>
                <a href="long_term_helpers.php">View Feature</a>
            </div>
        </div>
    </main>

</body>
</html>
