<?php
// Note: session_start() and dbconnect.php are already included in admin_header.php
include 'admin_header.php';

// --- SERVICE REVENUE CALCULATION ---
// Calculate revenue from helper tasks (10% commission)
$service_revenue_query = "SELECT SUM((hr.logged_hours * t.hourly_rate) * 0.1) AS total
                          FROM hiring_records hr
                          JOIN tasks t ON hr.task_id = t.task_id
                          WHERE hr.status = 'completed'";
$service_revenue_result = $conn->query($service_revenue_query);
$service_revenue = $service_revenue_result->fetch_assoc()['total'] ?? 0;

// --- MARKETPLACE REVENUE CALCULATION ---
// Assuming a 15% platform fee for the marketplace
$marketplace_revenue_query = "SELECT SUM(total_amount * 0.15) AS total
                              FROM orders
                              WHERE order_status IN ('paid', 'shipped', 'completed')";
$marketplace_revenue_result = $conn->query($marketplace_revenue_query);
$marketplace_revenue = $marketplace_revenue_result->fetch_assoc()['total'] ?? 0;

// --- OTHER CORE STATISTICS ---
$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$total_helpers = $conn->query("SELECT COUNT(*) AS count FROM helpers")->fetch_assoc()['count'];
$total_products_sold = $conn->query("SELECT SUM(quantity) as count FROM order_items")->fetch_assoc()['count'] ?? 0;
$pending_verifications = $conn->query("SELECT COUNT(*) as count FROM (
    SELECT user_id FROM users WHERE verification_status = 'pending'
    UNION ALL
    SELECT helper_id FROM helpers WHERE verification_status = 'pending'
) as pending")->fetch_assoc()['count'];
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
    
    <!-- Stat Cards Row -->
    <div class="row">
        <!-- Marketplace Revenue Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Marketplace Revenue (15%)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($marketplace_revenue, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Revenue Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Helper Service Revenue (10%)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($service_revenue, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Users Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Users & Helpers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users + $total_helpers; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Requests Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Verifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_verifications; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Chart and Recent Activity sections here later -->

</div>

<?php include 'admin_footer.php'; ?>