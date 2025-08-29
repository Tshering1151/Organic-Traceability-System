<?php 
// farmer_dashboard.php
session_start();

// Check if user is logged in and is a farmer
if (!isset($_SESSION['farmer_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';

$farmer_id = $_SESSION['farmer_id'];
$farmer_name = $_SESSION['user_type'];

// Get farmer's profile image from farmers table
$profile_stmt = $conn->prepare("SELECT profile_picture FROM farmer_tbl WHERE farmer_id = ?");
$profile_stmt->bind_param("i", $farmer_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$farmer_profile = $profile_result->fetch_assoc();
$profile_stmt->close();

$profile_picture = $farmer_profile['profile_picture'] ?? null;
$profile_image_path = null;

// Check if profile picture exists
if ($profile_picture) {
    $profile_image_path = "/Test/Farmer/uploads/profiles/" . $profile_picture;
    // Check if file actually exists on server
    $server_path = __DIR__ . "/uploads/profiles/" . $profile_picture;
    if (!file_exists($server_path)) {
        $profile_image_path = null;
    }
}

// (Optional) Get aggregator details if stored in session or DB
$aggregator_details = isset($_SESSION['aggregator']) ? $_SESSION['aggregator'] : [
    'business_name' => 'Your Aggregator'
];

// Get farmer's products
$stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// Get basic stats
$stats_stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM products WHERE farmer_id = ?");
$stats_stmt->bind_param("i", $farmer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmer Dashboard - Organic Traceability</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
        margin: 0; 
        background: #f8f9fa; 
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
    }
    header { 
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); 
        color: white; 
        padding: 15px 20px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        flex-wrap: wrap; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    header h2 { 
        margin: 0; 
        font-weight: 600;
        font-size: 22px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn { 
        text-decoration: none; 
        padding: 10px 16px; 
        background: linear-gradient(135deg, #388E3C 0%, #2E7D32 100%); 
        color: white; 
        border-radius: 6px; 
        font-size: 14px; 
        font-weight: 500;
        margin-left: 8px; 
        transition: all 0.2s ease; 
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-align: center; 
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn:hover { 
        background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%); 
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* Profile Dropdown Styles */
    .profile-dropdown {
        position: relative;
        display: inline-block;
    }

    .profile-trigger {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 15px;
        border-radius: 25px;
        cursor: pointer;
        transition: background 0.3s ease;
        border: none;
        color: white;
        font-size: 14px;
    }

    .profile-trigger:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .profile-avatar {
        width: 32px;
        height: 32px;
        background: #2E7D32;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .profile-avatar-text {
        color: white;
    }

    .profile-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .profile-name {
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .profile-role {
        font-size: 11px;
        opacity: 0.8;
        margin: 0;
        line-height: 1.2;
    }

    .dropdown-arrow {
        margin-left: 5px;
        transition: transform 0.3s ease;
    }

    .profile-dropdown.active .dropdown-arrow {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        min-width: 200px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        border: 1px solid #e0e0e0;
    }

    .profile-dropdown.active .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-header {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        text-align: center;
    }

    .dropdown-avatar {
        width: 50px;
        height: 50px;
        background: #4CAF50;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        color: white;
        margin: 0 auto 10px;
        overflow: hidden;
        border: 3px solid #f0f0f0;
    }

    .dropdown-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .dropdown-avatar-text {
        color: white;
    }

    .dropdown-name {
        font-weight: bold;
        color: #333;
        margin: 0 0 5px 0;
    }

    .dropdown-role {
        color: #666;
        font-size: 12px;
        margin: 0;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        text-decoration: none;
        color: #333;
        transition: background 0.2s ease;
        border: none;
        width: 100%;
        background: none;
        cursor: pointer;
        font-size: 14px;
    }

    .dropdown-item:hover {
        background: #f5f5f5;
    }

    .dropdown-item i {
        width: 16px;
        text-align: center;
        color: #666;
    }

    .dropdown-divider {
        height: 1px;
        background: #f0f0f0;
        margin: 5px 0;
    }

    .logout-btn { 
        color: #f44336 !important; 
    }

    .logout-btn:hover {
        background: #ffebee !important;
    }

    .logout-btn i {
        color: #f44336 !important;
    }

    /* Other existing styles */
    .view-btn { background: #2196F3; margin: 10px 5px 0 5px; width: calc(50% - 10px); }
    .view-btn:hover { background: #1976D2; }
    .qr-btn { background: #ff9800; }
    .qr-btn:hover { background: #f57c00; }
    main { max-width: 1000px; margin: 20px auto; padding: 0 15px; }
    .stats { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .stats h3 { margin: 0 0 10px 0; color: #4CAF50; }
    .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .product-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
    .product-card img { max-width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin-bottom: 10px; }
    .product-name { font-size: 18px; font-weight: bold; color: #333; margin: 0 0 10px 0; }
    .product-info p { margin: 5px 0; color: #666; font-size: 14px; }
    .product-actions { display: flex; flex-wrap: wrap; justify-content: center; margin-top: 15px; }
    .qr-info { background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 12px; color: #2e7d32; }
    .empty-state { text-align: center; padding: 40px 20px; color: #999; grid-column: 1 / -1; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin: 20px 0 10px; }
    .section-header h3 { margin: 0; }

    @media (max-width: 768px) { 
        header { 
            flex-direction: row; 
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            gap: 10px;
        }
        
        header h2 {
            font-size: 18px;
            flex: 1;
        }
        
        header h2 i {
            margin-right: 8px;
        }
        
        main { 
            padding: 15px; 
            margin: 15px auto;
        } 
        
        .stats {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .stats h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .stats p {
            font-size: 16px;
            margin: 5px 0 0 0;
        }
        
        .products { 
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .product-card {
            padding: 15px;
        }
        
        .product-card img {
            height: 200px;
            margin-bottom: 12px;
        }
        
        .product-name {
            font-size: 18px;
            margin-bottom: 12px;
        }
        
        .product-info p {
            font-size: 14px;
            margin: 6px 0;
        }
        
        .view-btn { 
            width: 100%; 
            margin: 10px 0 0 0;
            padding: 12px;
            font-size: 16px;
        }
        
        .section-header { 
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 15px;
        }
        
        .section-header h3 {
            font-size: 20px;
            margin: 0;
        }
        
        .btn {
            padding: 10px 15px;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .profile-trigger {
            padding: 8px 12px;
            border-radius: 20px;
        }
        
        .profile-avatar {
            width: 32px;
            height: 32px;
            font-size: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .profile-name {
            font-size: 14px;
            font-weight: 600;
        }
        
        .profile-role {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .dropdown-menu {
            right: 0;
            min-width: 220px;
            margin-top: 8px;
        }
        
        .dropdown-header {
            padding: 20px 15px;
        }
        
        .dropdown-avatar {
            width: 60px;
            height: 60px;
            margin-bottom: 12px;
        }
        
        .dropdown-name {
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .dropdown-role {
            font-size: 13px;
        }
        
        .dropdown-item {
            padding: 15px 20px;
            font-size: 15px;
        }
        
        .empty-state {
            padding: 60px 20px;
        }
        
        .empty-state h4 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #666;
        }
    }
</style>
</head>
<body>
<header>
    <h2>
        <i class="fas fa-seedling"></i>
        AgriConnect - Producer Dashboard
    </h2>
    
    <div class="profile-dropdown" id="profileDropdown">
        <button class="profile-trigger" onclick="toggleDropdown()">
            <div class="profile-avatar">
                <?php if ($profile_image_path): ?>
                    <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($farmer_name) ?>">
                <?php else: ?>
                    <span class="profile-avatar-text"><?= strtoupper(substr($farmer_name, 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($farmer_name) ?></div>
                <div class="profile-role">Producer</div>
            </div>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
        </button>
        
        <div class="dropdown-menu">
            <div class="dropdown-header">
                <div class="dropdown-avatar">
                    <?php if ($profile_image_path): ?>
                        <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($farmer_name) ?>">
                    <?php else: ?>
                        <span class="dropdown-avatar-text"><?= strtoupper(substr($farmer_name, 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="dropdown-name"><?= htmlspecialchars($farmer_name) ?></div>
                <div class="dropdown-role">Producer</div>
            </div>
            
            <a href="profile.php" class="dropdown-item">
                <i class="fas fa-user"></i>
                Profile
            </a>
            
            <div class="dropdown-divider"></div>
            
            <a href="../logout.php" class="dropdown-item logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</header>

<main>
<div class="stats">
    <h3>Dashboard</h3>
    <p>Products Listed: <strong><?= $stats['product_count'] ?></strong></p>
</div>

<div class="section-header">
    <h3>Your Products</h3>
    <a href="add_product.php" class="btn">➕ Add Product</a>
</div>

<div class="products">
<?php if ($products->num_rows > 0): ?>
    <?php while ($product = $products->fetch_assoc()): ?>
    <div class="product-card">
        <?php if (!empty($product['product_image'])): ?>
            <img src="/Test/Farmer/uploads/products/<?= htmlspecialchars($product['product_image']) ?>" 
                 alt="<?= htmlspecialchars($product['product_name']) ?>">
        <?php else: ?>
            <img src="/Test/Farmer/uploads/default.png" alt="No Image">
        <?php endif; ?>
        <h4 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h4>
        <div class="product-info">
            <p><strong>Harvest Date:</strong> <?= htmlspecialchars($product['harvest_date']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($product['farm_location']) ?></p>
            <p><strong>Added:</strong> <?= date('M d, Y', strtotime($product['created_at'])) ?></p>
        </div>
        <div class="product-actions">
            <a href="generate_qr.php?product_id=<?= $product['product_id'] ?>" class="btn view-btn" style="background: #ff9800;">
                <i class="fas fa-qrcode me-2"></i> Generate QR
            </a>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-state">
        <h4>No Products Yet</h4>
        <p>Start by adding your first product.</p>
        <a href="add_product.php" class="btn">Add Your First Product</a>
    </div>
<?php endif; ?>
</div>
</main>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Close dropdown on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('profileDropdown');
        dropdown.classList.remove('active');
    }
});
</script>

</body>
</html>