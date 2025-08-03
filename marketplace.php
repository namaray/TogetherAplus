<?php
session_start();
include 'dbconnect.php'; // Your database connection file

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all available products from the database
$sql = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY created_at DESC";
$result = $conn->query($sql);
$products = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Get all unique categories for filtering
$categories = array_unique(array_column($products, 'category'));
sort($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - TogetherA+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px -10px rgba(0,0,0,0.1);
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .filter-btn.active {
            background-color: #4f46e5; /* indigo-600 */
            color: white;
            border-color: #4f46e5;
        }
    </style>
</head>
<body class="bg-slate-50">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <!-- Back button -->
        <div class="mb-6">
            <a href="userdashboard.php" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-slate-900 font-semibold text-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Marketplace Header -->
        <header class="fade-in mb-10 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Assistive Technology Marketplace</h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-slate-600">Discover tools and technology designed to support independence and daily living.</p>
        </header>

        <!-- Filters -->
        <div class="fade-in mb-8 flex flex-wrap justify-center gap-3" style="animation-delay: 100ms;">
            <button class="filter-btn active px-4 py-2 text-sm font-semibold bg-white text-slate-700 rounded-full shadow-sm border border-slate-200 hover:bg-slate-100" data-filter="all">All Products</button>
            <?php foreach($categories as $category): ?>
                <button class="filter-btn px-4 py-2 text-sm font-semibold bg-white text-slate-700 rounded-full shadow-sm border border-slate-200 hover:bg-slate-100" data-filter="<?php echo htmlspecialchars($category); ?>">
                    <?php echo htmlspecialchars(ucfirst($category)); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Product Grid -->
        <div class="product-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $index => $product): ?>
                    <div class="product-card fade-in bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col overflow-hidden" data-category="<?php echo htmlspecialchars($product['category']); ?>" style="animation-delay: <?php echo ($index * 50) + 200; ?>ms;">
                        <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-full w-full object-cover object-center">
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <div class="flex-grow">
                                <p class="text-xs font-semibold text-indigo-600 uppercase"><?php echo htmlspecialchars($product['vendor']); ?></p>
                                <h3 class="mt-1 font-bold text-lg text-slate-900"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="mt-2 text-3xl font-extrabold text-slate-800">$<?php echo number_format($product['price'], 2); ?></p>
                                <?php if ($product['stock_quantity'] <= 5): ?>
                                    <p class="text-xs font-bold text-red-600 mt-1">Only <?php echo $product['stock_quantity']; ?> left in stock!</p>
                                <?php endif; ?>
                            </div>
                            <div class="mt-6 flex flex-col gap-3">
                                <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="w-full text-center font-semibold text-sm bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-full text-center text-slate-500 py-10">No products are currently available in the marketplace.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Animation Observer
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
        
        // Filter Logic
        const filterButtons = document.querySelectorAll('.filter-btn');
        const productCards = document.querySelectorAll('.product-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Update active state
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                const filter = button.dataset.filter;
                
                // Show/hide cards
                productCards.forEach(card => {
                    if (filter === 'all' || card.dataset.category === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });
    </script>
</body>
</html>
