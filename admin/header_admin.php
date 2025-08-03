
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color:rgb(47, 48, 49);
            color: white;
            position: fixed;
            padding: 20px 15px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color:rgb(34, 34, 34);
        }
        .navbar {
            margin-left: 250px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }
        .stat-card {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .chart-container {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a>
        <a href="helper.php"><i class="fa-solid fa-person"></i> Manage helper</a>
        <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="tasks.php"><i class="fas fa-tasks"></i> Manage Tasks</a>
        <a href="reviews.php"><i class="fas fa-comments"></i> Manage Reviews</a>
        <a href="../upload_resource.php"><i class="fa-solid fa-upload"></i> Upload Resource</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>