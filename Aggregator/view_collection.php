<?php 
session_start();

// Check if user is logged in and is an aggregator
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'aggregator') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';

$aggregator_name = $_SESSION['aggregator_name'];
$aggregator_id = $_SESSION['aggregator_id'];
$aggregator_cid = $_SESSION['aggregator_cid'];

// Get aggregator's profile image
$profile_stmt = $conn->prepare("SELECT profile_picture FROM aggre_tbl WHERE aggre_id = ?");
$profile_stmt->bind_param("i", $aggregator_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$aggregator_profile = $profile_result->fetch_assoc();
$profile_stmt->close();

$profile_picture = $aggregator_profile['profile_picture'] ?? null;
$profile_image_path = null;

// Check if profile picture exists
if ($profile_picture) {
    $profile_image_path = "/Test/Aggregator/uploads/profiles/" . $profile_picture;
    // Check if file actually exists on server
    $server_path = __DIR__ . "/uploads/profiles/" . $profile_picture;
    if (!file_exists($server_path)) {
        $profile_image_path = null;
    }
}

// Get aggregator's collected products with product details
$stmt = $conn->prepare("
    SELECT 
        ac.collection_id,
        ac.quantity as collected_quantity,
        ac.collection_date,
        ac.target_market,
        ac.status,
        p.product_id,
        p.product_name,
        p.harvest_date,
        p.farm_location,
        p.product_image,
        f.name as farmer_name,
        f.cid as farmer_cid
    FROM aggregator_collections ac
    JOIN products p ON ac.product_id = p.product_id
    JOIN farmer_tbl f ON ac.farmer_id = f.farmer_id
    WHERE ac.aggregator_id = ?
    ORDER BY ac.collection_date DESC
");
$stmt->bind_param("i", $aggregator_id);
$stmt->execute();
$collections = $stmt->get_result();
$stmt->close();

// Get basic stats
$stats_stmt = $conn->prepare("SELECT COUNT(*) as collection_count FROM aggregator_collections WHERE aggregator_id = ?");
$stats_stmt->bind_param("i", $aggregator_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Collection - OrganicTrace</title>
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
        background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%); 
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
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); 
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
        background: linear-gradient(135deg, #45a049 0%, #388e3c 100%); 
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* QR Button Specific Style */
    .qr-btn {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%) !important;
    }
    .qr-btn:hover {
        background: linear-gradient(135deg, #f57c00 0%, #ef6c00 100%) !important;
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
        background: #4CAF50;
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
    main { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
    .stats { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .stats h3 { margin: 0 0 10px 0; color: #4CAF50; }
    .collections { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
    .collection-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .collection-card img { max-width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin-bottom: 15px; }
    .product-name { font-size: 18px; font-weight: bold; color: #333; margin: 0 0 10px 0; }
    .product-info p { margin: 5px 0; color: #666; font-size: 14px; }
    .collection-info { background: #e8f5e8; padding: 12px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4CAF50; }
    .collection-info h5 { margin: 0 0 5px 0; color: #2e7d32; font-size: 14px; }
    .collection-info p { margin: 2px 0; font-size: 13px; }
    
    /* Product Actions Styling */
    .product-actions {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
        text-align: center;
    }

    .status-badge { 
        display: inline-block; 
        padding: 4px 8px; 
        border-radius: 12px; 
        font-size: 12px; 
        font-weight: bold; 
        text-transform: uppercase;
        margin-top: 5px;
    }
    .status-collected { background: #e8f5e8; color: #2e7d32; }
    .status-shipped { background: #e3f2fd; color: #1565c0; }
    .status-delivered { background: #f3e5f5; color: #7b1fa2; }
    .empty-state { text-align: center; padding: 60px 20px; color: #999; grid-column: 1 / -1; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin: 20px 0 10px; }
    .section-header h3 { margin: 0; color: #333; }
    .back-btn { background: #6c757d; }
    .back-btn:hover { background: #5a6268; }

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
        
        main { 
            padding: 15px; 
            margin: 15px auto;
        } 
        
        .stats {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .collections { 
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .collection-card {
            padding: 15px;
        }
        
        .collection-card img {
            height: 200px;
            margin-bottom: 12px;
        }
        
        .section-header { 
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 15px;
        }
        
        .dropdown-menu {
            right: 0;
            min-width: 220px;
            margin-top: 8px;
        }
    }
</style>
</head>
<body>
<header>
    <h2>
        <i class="fas fa-warehouse"></i>
        My Collection - OrganicTrace
    </h2>
    
    <div class="profile-dropdown" id="profileDropdown">
        <button class="profile-trigger" onclick="toggleDropdown()">
            <div class="profile-avatar">
                <?php if ($profile_image_path): ?>
                    <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($aggregator_name) ?>">
                <?php else: ?>
                    <span class="profile-avatar-text"><?= strtoupper(substr($aggregator_name, 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($aggregator_name) ?></div>
                <div class="profile-role">Aggregator</div>
            </div>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
        </button>
        
        <div class="dropdown-menu">
            <div class="dropdown-header">
                <div class="dropdown-avatar">
                    <?php if ($profile_image_path): ?>
                        <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($aggregator_name) ?>">
                    <?php else: ?>
                        <span class="dropdown-avatar-text"><?= strtoupper(substr($aggregator_name, 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="dropdown-name"><?= htmlspecialchars($aggregator_name) ?></div>
                <div class="dropdown-role">Aggregator</div>
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
    <h3>Collection Dashboard</h3>
    <p>Total Collections: <strong><?= $stats['collection_count'] ?></strong></p>
</div>

<div class="section-header">
    <h3>Your Collections</h3>
</div>

<div class="collections">
<?php if ($collections->num_rows > 0): ?>
    <?php while ($collection = $collections->fetch_assoc()): ?>
    <div class="collection-card">
        <?php if (!empty($collection['product_image'])): ?>
            <img src="../Farmer/uploads/products/<?= htmlspecialchars($collection['product_image']) ?>" 
                 alt="<?= htmlspecialchars($collection['product_name']) ?>"
                 onerror="this.src='../assets/images/no-image.png'">
        <?php else: ?>
            <img src="../assets/images/no-image.png" alt="No Image">
        <?php endif; ?>
        
        <h4 class="product-name"><?= htmlspecialchars($collection['product_name']) ?></h4>
        
        <div class="product-info">
            <p><strong>Harvest Date:</strong> <?= htmlspecialchars($collection['harvest_date']) ?></p>
            <p><strong>Farm Location:</strong> <?= htmlspecialchars($collection['farm_location']) ?></p>
            <p><strong>Farmer:</strong> <?= htmlspecialchars($collection['farmer_name']) ?></p>
        </div>
        
        <div class="collection-info">
            <h5><i class="fas fa-info-circle"></i> Collection Details</h5>
            <p><strong>Collected Quantity:</strong> <?= htmlspecialchars($collection['collected_quantity']) ?></p>
            <p><strong>Collection Date:</strong> <?= date('M d, Y - h:i A', strtotime($collection['collection_date'])) ?></p>
            <p><strong>Target Market:</strong> <?= htmlspecialchars($collection['target_market']) ?></p>
            <span class="status-badge status-<?= $collection['status'] ?>">
                <?= htmlspecialchars($collection['status']) ?>
            </span>
        </div>

        <div class="product-actions">
            <a href="generate_qr.php?product_id=<?= $collection['product_id'] ?>" class="btn qr-btn">
                <i class="fas fa-qrcode"></i> Generate QR
            </a>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-state">
        <h4><i class="fas fa-box-open" style="color: #ccc; font-size: 48px; margin-bottom: 20px;"></i></h4>
        <h4>No Collections Yet</h4>
        <p>You haven't collected any products yet. Start by adding products from farmers.</p>
        <a href="aggregator_dashboard.php" class="btn">
            <i class="fas fa-plus-circle"></i> Start Collecting
        </a>
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