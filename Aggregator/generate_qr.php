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

// Get product ID from URL
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id) {
    header("Location: view_collection.php");
    exit;
}

// Get product details with collection info
$stmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.harvest_date,
        p.farm_location,
        p.product_image,
        f.name as farmer_name,
        f.cid as farmer_cid,
        ac.collection_id,
        ac.quantity as collected_quantity,
        ac.collection_date,
        ac.target_market,
        ac.status
    FROM products p
    JOIN farmer_tbl f ON p.farmer_id = f.farmer_id
    LEFT JOIN aggregator_collections ac ON p.product_id = ac.product_id AND ac.aggregator_id = ?
    WHERE p.product_id = ?
");
$stmt->bind_param("ii", $aggregator_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view_collection.php");
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Generate QR code data - you can customize this URL/data as needed
$qr_data = "https://organictrace.com/product/" . $product_id . "?verify=" . md5($product_id . $product['farmer_cid']);

// QR Code API (using qr-server.com - free service)
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Code - <?= htmlspecialchars($product['product_name']) ?> - OrganicTrace</title>
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

    .back-btn { 
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); 
    }
    
    .back-btn:hover { 
        background: linear-gradient(135deg, #5a6268 0%, #495057 100%); 
    }

    .download-btn {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        margin-left: 10px;
    }
    
    .download-btn:hover {
        background: linear-gradient(135deg, #f57c00 0%, #ef6c00 100%);
    }

    main { 
        max-width: 1000px; 
        margin: 20px auto; 
        padding: 0 15px; 
    }

    .collections {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
        padding: 20px 0;
    }

    .qr-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 40px;
        text-align: center;
        max-width: 600px;
        width: 100%;
    }

    .product-image {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 12px;
        margin: 0 auto 20px;
        display: block;
        border: 3px solid #e0e0e0;
    }

    .product-title {
        font-size: 28px;
        font-weight: bold;
        color: #2d5016;
        margin: 0 0 10px 0;
    }

    .product-subtitle {
        font-size: 16px;
        color: #666;
        margin-bottom: 30px;
    }

    .qr-code {
        width: 300px;
        height: 300px;
        margin: 20px auto;
        border: 3px solid #4CAF50;
        border-radius: 12px;
        padding: 15px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .qr-code img {
        width: 100%;
        height: 100%;
        display: block;
    }

    .product-details {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 30px 0;
        text-align: left;
    }

    .product-details h4 {
        color: #2d5016;
        margin: 0 0 15px 0;
        font-size: 18px;
        text-align: center;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #555;
    }

    .detail-value {
        color: #333;
        text-align: right;
    }

    .qr-info {
        background: #e8f5e8;
        border: 1px solid #4CAF50;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        font-size: 14px;
        color: #2e7d32;
    }

    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .qr-container {
            padding: 20px;
            margin: 10px;
        }

        .product-image {
            width: 150px;
            height: 150px;
        }

        .product-title {
            font-size: 24px;
        }

        .qr-code {
            width: 250px;
            height: 250px;
            padding: 10px;
        }

        .detail-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .detail-value {
            text-align: left;
        }

        .action-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn {
            width: 200px;
            justify-content: center;
        }

        header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        header h2 {
            font-size: 18px;
        }
    }

    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #4CAF50;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>
<body>
<header>
    <h2>
        <i class="fas fa-qrcode"></i>
        QR Code Generator - OrganicTrace
    </h2>
    
    <div>
        <a href="view_collection.php" class="btn back-btn">
            <i class="fas fa-arrow-left"></i> Back to Collection
        </a>
        <a href="#" onclick="downloadQR()" class="btn download-btn">
            <i class="fas fa-download"></i> Download QR
        </a>
    </div>
</header>

<main>
    <div class="collections">
        <div class="qr-container">
            <!-- Product Image -->
            <?php if (!empty($product['product_image'])): ?>
                <img src="../Farmer/uploads/products/<?= htmlspecialchars($product['product_image']) ?>" 
                     alt="<?= htmlspecialchars($product['product_name']) ?>"
                     class="product-image"
                     onerror="this.src='../assets/images/no-image.png'">
            <?php else: ?>
                <img src="../assets/images/no-image.png" alt="No Image" class="product-image">
            <?php endif; ?>

            <!-- Product Title -->
            <h1 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h1>
            <p class="product-subtitle">Farmer: <?= htmlspecialchars($product['farmer_name']) ?></p>

            <!-- QR Code -->
            <div class="qr-code">
                <img src="<?= $qr_api_url ?>" alt="QR Code" id="qrImage" onload="hideLoading()">
                <div class="loading" id="loadingSpinner"></div>
            </div>

            <!-- QR Info -->
            <div class="qr-info">
                <i class="fas fa-info-circle"></i>
                <strong>Scan this QR code</strong> to verify product authenticity and view traceability information.
            </div>

            <!-- Product Details -->
            <div class="product-details">
                <h4><i class="fas fa-info-circle"></i> Product Information</h4>
                
                <div class="detail-row">
                    <span class="detail-label">Product ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($product['product_id']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Harvest Date:</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($product['harvest_date'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Farm Location:</span>
                    <span class="detail-value"><?= htmlspecialchars($product['farm_location']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Farmer CID:</span>
                    <span class="detail-value"><?= htmlspecialchars($product['farmer_cid']) ?></span>
                </div>

                <?php if ($product['collection_id']): ?>
                <div class="detail-row">
                    <span class="detail-label">Collection Date:</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($product['collection_date'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Collected Quantity:</span>
                    <span class="detail-value"><?= htmlspecialchars($product['collected_quantity']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Target Market:</span>
                    <span class="detail-value"><?= htmlspecialchars($product['target_market']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span style="background: #e8f5e8; color: #2e7d32; padding: 2px 8px; border-radius: 12px; font-size: 12px; text-transform: uppercase;">
                            <?= htmlspecialchars($product['status']) ?>
                        </span>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="view_collection.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Collection
                </a>
                <button onclick="printQR()" class="btn" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                    <i class="fas fa-print"></i> Print QR Code
                </button>
            </div>
        </div>
    </div>
</main>

<script>
function hideLoading() {
    document.getElementById('loadingSpinner').style.display = 'none';
    document.getElementById('qrImage').style.display = 'block';
}

function downloadQR() {
    const qrImage = document.getElementById('qrImage');
    const link = document.createElement('a');
    link.download = 'qr-code-<?= htmlspecialchars($product['product_name']) ?>-<?= $product['product_id'] ?>.png';
    link.href = qrImage.src;
    link.click();
}

function printQR() {
    const printWindow = window.open('', '_blank');
    const qrImage = document.getElementById('qrImage');
    const productName = '<?= htmlspecialchars($product['product_name']) ?>';
    const farmerName = '<?= htmlspecialchars($product['farmer_name']) ?>';
    const productId = '<?= $product['product_id'] ?>';
    
    printWindow.document.write(`
        <html>
        <head>
            <title>QR Code - ${productName}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    text-align: center; 
                    padding: 20px; 
                }
                .qr-print {
                    border: 2px solid #4CAF50;
                    padding: 20px;
                    display: inline-block;
                    border-radius: 8px;
                }
                h1 { 
                    color: #2d5016; 
                    margin-bottom: 10px; 
                }
                .farmer { 
                    color: #666; 
                    margin-bottom: 20px; 
                }
                .qr-image { 
                    width: 300px; 
                    height: 300px; 
                    margin: 20px 0; 
                }
                .product-id { 
                    margin-top: 20px; 
                    color: #333; 
                    font-size: 14px; 
                }
            </style>
        </head>
        <body>
            <div class="qr-print">
                <h1>${productName}</h1>
                <div class="farmer">Farmer: ${farmerName}</div>
                <img src="${qrImage.src}" alt="QR Code" class="qr-image">
                <div class="product-id">Product ID: ${productId}</div>
                <div class="product-id">Scan to verify authenticity</div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Hide loading spinner initially and show when QR loads
document.addEventListener('DOMContentLoaded', function() {
    const qrImage = document.getElementById('qrImage');
    qrImage.style.display = 'none';
    
    // If image fails to load, hide loading spinner
    qrImage.onerror = function() {
        document.getElementById('loadingSpinner').style.display = 'none';
        this.style.display = 'block';
        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIzMDAiIGZpbGw9IiNmOGY5ZmEiLz4KPHR0ZXh0IHg9IjE1MCIgeT0iMTUwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5RUiBDb2RlIFVuYXZhaWxhYmxlPC90ZXh0Pgo8L3N2Zz4K';
        this.alt = 'QR Code Unavailable';
    };
});
</script>

</body>
</html>