<?php
session_start();

// Check if user is logged in and is an aggregator
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'aggregator') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';

// Initialize
$farmer = null;
$products = [];
$message = "";
$success_message = "";

$aggregator_name = $_SESSION['aggregator_name'];
$aggregator_id = $_SESSION['aggregator_id'];
$aggregator_cid = $_SESSION['aggregator_cid'];

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

// Get statistics from database (moved here so it runs on every page load)
// Count total products collected by this aggregator
$products_collected_stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM aggregator_collections WHERE aggregator_id = ?");
$products_collected_stmt->bind_param("i", $aggregator_id);
$products_collected_stmt->execute();
$products_collected_result = $products_collected_stmt->get_result();
$products_collected = $products_collected_result->fetch_assoc()['total_products'];
$products_collected_stmt->close();

// Count unique farmers connected to this aggregator
$connected_farmers_stmt = $conn->prepare("SELECT COUNT(DISTINCT farmer_id) as unique_farmers FROM aggregator_collections WHERE aggregator_id = ?");
$connected_farmers_stmt->bind_param("i", $aggregator_id);
$connected_farmers_stmt->execute();
$connected_farmers_result = $connected_farmers_stmt->get_result();
$connected_farmers = $connected_farmers_result->fetch_assoc()['unique_farmers'];
$connected_farmers_stmt->close();

// Handle farmer search
if (isset($_POST['find_farmer'])) {
    $cid = trim($_POST['cid']);

    if (strlen($cid) == 11) {
        // Fetch farmer
        $stmt = $conn->prepare("SELECT farmer_id, cid, name, dzo_name, gewog_name, village FROM farmer_tbl WHERE cid = ?");
        $stmt->bind_param("s", $cid);
        $stmt->execute();
        $farmerResult = $stmt->get_result();

        if ($farmerResult->num_rows > 0) {
            $farmer = $farmerResult->fetch_assoc();

            // Fetch farmer's products with separate quantity and unit fields
            $stmtProd = $conn->prepare("SELECT product_id, product_name, quantity, unit, harvest_date, farm_location, product_image 
                                        FROM products WHERE farmer_id = ? AND quantity IS NOT NULL AND quantity != ''");
            $stmtProd->bind_param("i", $farmer['farmer_id']);
            $stmtProd->execute();
            $products = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);

            // Process products to get available quantity
            foreach ($products as &$product) {
                // If the database still has combined quantity (old format), parse it
                if (empty($product['unit']) && strpos($product['quantity'], ' ') !== false) {
                    $quantity_parts = explode(' ', $product['quantity'], 2);
                    $product['available_quantity'] = isset($quantity_parts[0]) ? floatval($quantity_parts[0]) : 0;
                    $product['unit'] = isset($quantity_parts[1]) ? $quantity_parts[1] : '';
                } else {
                    // New format with separate fields
                    $product['available_quantity'] = floatval($product['quantity']);
                    // Unit is already separate
                }
            }

        } else {
            $message = "No farmer found with this CID.";
        }
    } else {
        $message = "Please enter a valid 11-digit CID.";
    }
}

// Handle product collection
if (isset($_POST['collect_products'])) {
    if (isset($_POST['selected_products']) && !empty($_POST['selected_products'])) {
        $target_market = $_POST['target_market'];
        $selected_products = $_POST['selected_products'];
        $quantities = $_POST['quantities'];
        $farmer_id = $_POST['farmer_id']; // Get farmer_id from form
        
        $errors = [];
        $valid_collections = [];
        
        // Validate quantities against available stock
        foreach ($selected_products as $product_id) {
            $requested_quantity = isset($quantities[$product_id]) ? floatval($quantities[$product_id]) : 0;
            
            if ($requested_quantity <= 0) {
                $errors[] = "Invalid quantity for product ID: $product_id";
                continue;
            }
            
            // Get current available quantity from database
            $stmt = $conn->prepare("SELECT product_name, quantity, unit FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product_data = $result->fetch_assoc();
                
                // Handle both old and new format
                if (empty($product_data['unit']) && strpos($product_data['quantity'], ' ') !== false) {
                    // Old format - quantity contains both number and unit
                    $quantity_parts = explode(' ', $product_data['quantity'], 2);
                    $available_quantity = isset($quantity_parts[0]) ? floatval($quantity_parts[0]) : 0;
                    $unit = isset($quantity_parts[1]) ? $quantity_parts[1] : '';
                } else {
                    // New format - separate fields
                    $available_quantity = floatval($product_data['quantity']);
                    $unit = $product_data['unit'];
                }
                
                if ($requested_quantity > $available_quantity) {
                    $errors[] = "Requested quantity ($requested_quantity) exceeds available stock ($available_quantity $unit) for {$product_data['product_name']}";
                } else {
                    $valid_collections[] = [
                        'product_id' => $product_id,
                        'product_name' => $product_data['product_name'],
                        'requested_quantity' => $requested_quantity,
                        'available_quantity' => $available_quantity,
                        'unit' => $unit
                    ];
                }
            }
        }
        
        if (!empty($errors)) {
            $message = implode('<br>', $errors);
        } else {
            $collection_date = date('Y-m-d H:i:s');
            $collected_count = 0;
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                foreach ($valid_collections as $collection) {
                    $product_id = $collection['product_id'];
                    $requested_quantity = $collection['requested_quantity'];
                    $available_quantity = $collection['available_quantity'];
                    $unit = $collection['unit'];
                    
                    // Insert into collections table with farmer_id
                    $insert_stmt = $conn->prepare("INSERT INTO aggregator_collections 
                        (aggregator_id, farmer_id, product_id, target_market, quantity, collection_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'collected')");
                    $insert_stmt->bind_param("iiisds", $aggregator_id, $farmer_id, $product_id, $target_market, $requested_quantity, $collection_date);
                    
                    if ($insert_stmt->execute()) {
                        // Update product quantity in products table
                        $new_quantity = $available_quantity - $requested_quantity;
                        
                        // Update using separate quantity and unit fields
                        $update_stmt = $conn->prepare("UPDATE products SET quantity = ?, updated_at = NOW() WHERE product_id = ?");
                        $update_stmt->bind_param("di", $new_quantity, $product_id);
                        
                        if ($update_stmt->execute()) {
                            $collected_count++;
                        } else {
                            throw new Exception("Failed to update product quantity for product ID: $product_id");
                        }
                    } else {
                        throw new Exception("Failed to insert collection record for product ID: $product_id");
                    }
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                if ($collected_count > 0) {
                    $success_message = "Successfully collected $collected_count product(s) to your collection! Product quantities have been updated.";
                    
                    // Update statistics after successful collection
                    $products_collected_stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM aggregator_collections WHERE aggregator_id = ?");
                    $products_collected_stmt->bind_param("i", $aggregator_id);
                    $products_collected_stmt->execute();
                    $products_collected_result = $products_collected_stmt->get_result();
                    $products_collected = $products_collected_result->fetch_assoc()['total_products'];
                    $products_collected_stmt->close();

                    $connected_farmers_stmt = $conn->prepare("SELECT COUNT(DISTINCT farmer_id) as unique_farmers FROM aggregator_collections WHERE aggregator_id = ?");
                    $connected_farmers_stmt->bind_param("i", $aggregator_id);
                    $connected_farmers_stmt->execute();
                    $connected_farmers_result = $connected_farmers_stmt->get_result();
                    $connected_farmers = $connected_farmers_result->fetch_assoc()['unique_farmers'];
                    $connected_farmers_stmt->close();
                    
                    // Refresh products data to show updated quantities
                    if ($farmer) {
                        $stmtProd = $conn->prepare("SELECT product_id, product_name, quantity, unit, harvest_date, farm_location, product_image 
                                                    FROM products WHERE farmer_id = ? AND quantity IS NOT NULL AND quantity != ''");
                        $stmtProd->bind_param("i", $farmer['farmer_id']);
                        $stmtProd->execute();
                        $products = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);

                        // Process products to get available quantity
                        foreach ($products as &$product) {
                            // If the database still has combined quantity (old format), parse it
                            if (empty($product['unit']) && strpos($product['quantity'], ' ') !== false) {
                                $quantity_parts = explode(' ', $product['quantity'], 2);
                                $product['available_quantity'] = isset($quantity_parts[0]) ? floatval($quantity_parts[0]) : 0;
                                $product['unit'] = isset($quantity_parts[1]) ? $quantity_parts[1] : '';
                            } else {
                                // New format with separate fields
                                $product['available_quantity'] = floatval($product['quantity']);
                                // Unit is already separate
                            }
                        }
                    }
                }
                
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $message = "Error during collection: " . $e->getMessage();
            }
        }
    } else {
        $message = "Please select at least one product to collect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggregator Dashboard - OrganicTrace</title>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fbf6 0%, #e8f5e8 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        
        .navbar-nav .nav-link {
            color: white !important;
        }
        
        .dashboard-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            padding: 25px;
        }

        /* Profile Dropdown Styles */
        .profile-dropdown {
            position: relative;
        
        }
        .profile-trigger {
            background: none;
            border: none;
            display: flex;
            align-items: center;
            cursor: pointer;
        }


        /* .profile-trigger:hover {
            background: rgba(255, 255, 255, 0.2);
        } */


        .profile-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            margin-right: 8px;
        }


        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .profile-avatar-text {
            font-weight: bold;
            color: #555;
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
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 10px 0;
            min-width: 180px;
            z-index: 1000;
        }


        .profile-dropdown.active .dropdown-menu {
            display: block;
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
            font-size: 14px;
        }


        .dropdown-role {
            color: #666;
            font-size: 12px;
            margin: 0;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
        }


        .dropdown-item:hover {
            background: #f5f5f5;
           
        }


        .dropdown-item i {
            width: 16px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }


        .dropdown-item span {
            color: #333;
            font-weight: 500;
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
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .search-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #4CAF50;
            display: none;
        }
        
        .farmer-info-card {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .product-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }
        
        .product-card.selected {
            border-color: #4CAF50;
            background: #f0f9f0;
        }
        
        .product-card.out-of-stock {
            background: #f8f8f8;
            opacity: 0.6;
            pointer-events: none;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .btn-custom {
            background: #4CAF50;
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            background: #45a049;
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-outline-custom {
            border: 2px solid #4CAF50;
            color: #4CAF50;
            background: transparent;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-custom:hover {
            background: #4CAF50;
            color: white;
        }
        
        .collection-form {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
            display: none;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        
        .stat-card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stat-card .stat-icon {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 15px;
        }
        
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d5016;
        }
        
        .stat-card .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .quantity-input {
            width: 120px;
            display: inline-block;
        }
        
        .quantity-info {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .quantity-warning {
            color: #dc3545;
            font-weight: bold;
        }
        
        .out-of-stock-badge {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        .available-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        /* Add this to your existing CSS styles */
        .clickable-stat {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .clickable-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .clickable-stat:hover .stat-icon {
            color: #45a049;
            transform: scale(1.1);
        }

        .clickable-stat:hover .stat-number {
            color: #2d5016;
        }

        .clickable-stat::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .clickable-stat:hover::after {
            border-color: #4CAF50;
        }

        .stat-icon {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-leaf me-2"></i>OrganicTrace - Aggregator
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
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
                                <div class="dropdown-header px-3 pb-2 border-bottom">
                                    <div class="dropdown-avatar mb-2">
                                        <?php if ($profile_image_path): ?>
                                            <img src="<?= $profile_image_path ?>" alt="<?= htmlspecialchars($aggregator_name) ?>">
                                        <?php else: ?>
                                            <span class="dropdown-avatar-text"><?= strtoupper(substr($aggregator_name, 0, 1)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dropdown-name fw-bold"><?= htmlspecialchars($aggregator_name) ?></div>
                                    <div class="dropdown-role text-muted">Aggregator</div>
                                </div>
                               
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i>
                                    <span>Profile</span>
                                </a>
                               
                                <div class="dropdown-divider"></div>
                               
                                <a href="../logout.php" class="dropdown-item logout-btn text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <h1 class="mb-3">
                <i class="fas fa-warehouse text-success me-3"></i>
                Welcome, <?= htmlspecialchars($aggregator_name) ?>
            </h1>
            <p class="lead mb-2">Aggregator Dashboard - Manage your collection and distribution operations</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-6">
                <a href="view_collection.php" class="text-decoration-none">
                    <div class="stat-card clickable-stat">
                        <i class="fas fa-boxes stat-icon"></i>
                        <div class="stat-number"><?= $products_collected ?></div>
                        <div class="stat-label">Products Collected</div>
                        <small class="text-muted mt-2">Click to view details & generate Qr code</small>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-users stat-icon"></i>
                    <div class="stat-number"><?= $connected_farmers ?></div>
                    <div class="stat-label">Connected Farmers</div>
                </div>
            </div>
        </div>

        <!-- Main Actions -->
        <div class="main-card">
            <!-- Add Product Button -->
            <div class="text-center mb-4">
                <button type="button" class="btn btn-custom btn-lg" id="add_product_btn">
                    <i class="fas fa-plus-circle me-2"></i>Add Product from Farmer
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Farmer Search Form (Initially Hidden) -->
            <div class="search-section" id="search_section">
                <h4 class="text-success mb-3">
                    <i class="fas fa-search me-2"></i>Find Farmer
                </h4>
                <form method="POST" id="farmer_search_form">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label for="cid" class="form-label">Farmer CID</label>
                            <input type="text" name="cid" id="cid" class="form-control" 
                                   placeholder="Enter 11-digit Farmer CID" maxlength="11" 
                                   value="<?= htmlspecialchars($_POST['cid'] ?? '') ?>" required>
                            <div class="form-text">Enter the farmer's 11-digit CID number</div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="find_farmer" class="btn btn-custom w-100">
                                <i class="fas fa-search me-2"></i>Find Farmer
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($farmer): ?>
                <!-- Farmer Information -->
                <div class="farmer-info-card">
                    <h4><i class="fas fa-user-check me-2"></i>Farmer Found</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Name:</strong> <?= htmlspecialchars($farmer['name']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>CID:</strong> <?= htmlspecialchars($farmer['cid']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Location:</strong> <?= htmlspecialchars($farmer['dzo_name'] . ', ' . $farmer['gewog_name'] . ', ' . $farmer['village']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Available Products -->
                <div class="products-section">
                    <h4 class="text-success mb-3">
                        <i class="fas fa-boxes me-2"></i>Available Products
                    </h4>
                    <?php if (!empty($products)): ?>
                        <form method="POST" id="collect_products_form">
                            <input type="hidden" name="farmer_id" value="<?= $farmer['farmer_id']; ?>">
                            
                            <!-- Product Selection -->
                            <div class="row">
                                <?php foreach ($products as $prod): ?>
                                    <?php 
                                    $is_out_of_stock = $prod['available_quantity'] <= 0;
                                    $card_class = $is_out_of_stock ? 'product-card out-of-stock' : 'product-card';
                                    ?>
                                    <div class="col-md-6">
                                        <div class="<?= $card_class ?>" data-product-id="<?= $prod['product_id'] ?>" data-available="<?= $prod['available_quantity'] ?>" data-unit="<?= htmlspecialchars($prod['unit']) ?>">
                                            <div class="row align-items-center">
                                                <div class="col-3">
                                                    <?php if (!empty($prod['product_image'])): ?>
                                                        <img src="../Farmer/uploads/products/<?= htmlspecialchars($prod['product_image']); ?>" 
                                                             alt="Product Image" class="product-image"
                                                             onerror="this.src='../assets/images/no-image.png'">
                                                    <?php else: ?>
                                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-9">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input product-checkbox" type="checkbox" 
                                                                   name="selected_products[]" value="<?= $prod['product_id'] ?>" 
                                                                   id="product_<?= $prod['product_id'] ?>"
                                                                   <?= $is_out_of_stock ? 'disabled' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="product_<?= $prod['product_id'] ?>">
                                                                <?= htmlspecialchars($prod['product_name']); ?>
                                                            </label>
                                                        </div>
                                                        <?php if ($is_out_of_stock): ?>
                                                            <span class="out-of-stock-badge">Out of Stock</span>
                                                        <?php else: ?>
                                                            <span class="available-badge"><?= $prod['available_quantity'] ?> <?= htmlspecialchars($prod['unit']) ?> available</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <small class="text-muted d-block">
                                                        <strong>Harvest Date:</strong> <?= htmlspecialchars($prod['harvest_date']); ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <strong>Location:</strong> <?= htmlspecialchars($prod['farm_location']); ?>
                                                    </small>
                                                    
                                                    <?php if (!$is_out_of_stock): ?>
                                                        <div class="mt-2">
                                                            <label class="form-label small">Collect Quantity (<?= htmlspecialchars($prod['unit']) ?>):</label>
                                                            <input type="number" class="form-control quantity-input" 
                                                                   name="quantities[<?= $prod['product_id'] ?>]" 
                                                                   step="0.01" min="0.01" max="<?= $prod['available_quantity'] ?>"
                                                                   value="1.00" disabled
                                                                   data-max="<?= $prod['available_quantity'] ?>"
                                                                   data-product-name="<?= htmlspecialchars($prod['product_name']) ?>">
                                                            <div class="quantity-info">
                                                                Max available: <?= $prod['available_quantity'] ?> <?= htmlspecialchars($prod['unit']) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Collection Form -->
                            <div class="collection-form" id="collection_form">
                                <h5 class="text-success mb-3">
                                    <i class="fas fa-plus-circle me-2"></i>Collection Details
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="target_market" class="form-label">Target Market</label>
                                        <select name="target_market" id="target_market" class="form-select" required>
                                            <option value="">-- Select Market --</option>
                                            <option value="Kaja Throm">Kaja Throm</option>
                                            <option value="Debsi Wholesale Market">Debsi Wholesale Market</option>
                                            <option value="Other Market">Other Market</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" name="collect_products" class="btn btn-custom w-100">
                                            <i class="fas fa-plus me-2"></i>Collect Selected Products
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No products with available stock found for this farmer.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Profile dropdown functionality
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

        $(document).ready(function() {
            // Show/Hide Add Product Form
            $('#add_product_btn').on('click', function() {
                const searchSection = $('#search_section');
                if (searchSection.is(':visible')) {
                    searchSection.hide();
                    $(this).html('<i class="fas fa-plus-circle me-2"></i>Add Product from Farmer');
                } else {
                    searchSection.show();
                    $(this).html('<i class="fas fa-times me-2"></i>Cancel');
                }
            });

            // Format CID input to only allow digits
            $('#cid').on('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }
                this.value = value;
            });

            // Product selection handling
            $('.product-checkbox').on('change', function() {
                const productCard = $(this).closest('.product-card');
                const quantityInput = productCard.find('input[type="number"]');
                
                if ($(this).is(':checked')) {
                    productCard.addClass('selected');
                    quantityInput.prop('disabled', false).focus();
                } else {
                    productCard.removeClass('selected');
                    quantityInput.prop('disabled', true);
                }
                
                // Show/hide collection form based on selections
                updateCollectionForm();
            });

            // Update collection form visibility
            function updateCollectionForm() {
                const selectedCount = $('.product-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('#collection_form').show();
                } else {
                    $('#collection_form').hide();
                }
            }

            // Product card click to toggle selection
            $('.product-card').on('click', function(e) {
                if (!$(e.target).is('input, label')) {
                    const checkbox = $(this).find('.product-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Form validation
            $('#collect_products_form').on('submit', function(e) {
                const selectedProducts = $('.product-checkbox:checked').length;
                const targetMarket = $('#target_market').val();
                
                if (selectedProducts === 0) {
                    e.preventDefault();
                    alert('Please select at least one product to collect.');
                    return false;
                }
                
                if (!targetMarket) {
                    e.preventDefault();
                    alert('Please select a target market.');
                    return false;
                }
                
                // Validate quantities
                let validQuantities = true;
                $('.product-checkbox:checked').each(function() {
                    const productId = $(this).val();
                    const quantity = parseFloat($(`input[name="quantities[${productId}]"]`).val());
                    if (!quantity || quantity <= 0) {
                        validQuantities = false;
                        return false;
                    }
                });
                
                if (!validQuantities) {
                    e.preventDefault();
                    alert('Please enter valid quantities for all selected products.');
                    return false;
                }
            });

            // Auto-hide alerts after 5 seconds
            $('.alert-success').delay(5000).fadeOut();
        });
    </script>
</body>
</html>