<?php 
// generate_qr.php
session_start();

// Check if user is logged in and is a farmer
if (!isset($_SESSION['farmer_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';

// Get product ID from URL
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    header("Location: farmer_dashboard.php");
    exit;
}

$product_id = (int)$_GET['product_id'];
$farmer_id = $_SESSION['farmer_id'];

// Verify product belongs to this farmer
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $product_id, $farmer_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: farmer_dashboard.php");
    exit;
}

// Generate the URL that the QR code will contain
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$product_url = $base_url . '/view_product.php?id=' . $product_id;

// Simple QR Code generation using a different free API
function generateQRCodeAlternative($data, $size = 300) {
    $encoded_data = urlencode($data);
    // Using qr-server.com - another reliable free QR API
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}";
}

// Try multiple QR code services as backup
$qr_apis = [
    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($product_url),
    "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($product_url) . "&choe=UTF-8",
    "https://quickchart.io/qr?text=" . urlencode($product_url) . "&size=300"
];

// If 'generate' parameter is set, create QR using PHP (fallback method)
if (isset($_GET['generate']) && $_GET['generate'] === 'local') {
    // Simple QR Code generation using HTML/CSS method (fallback)
    $qr_text = $product_url;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Code - <?= htmlspecialchars($product['product_name']) ?></title>
<style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 0; 
        background: #f8f9fa; 
        padding: 20px;
    }

    .container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        text-align: center;
    }

    .product-info {
        background: #e8f5e8;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .product-name {
        font-size: 24px;
        font-weight: bold;
        color: #4CAF50;
        margin: 0 0 10px 0;
    }

    .qr-section {
        margin: 30px 0;
    }

    .qr-code {
        max-width: 300px;
        height: auto;
        border: 2px solid #4CAF50;
        border-radius: 8px;
        padding: 10px;
        background: white;
        margin: 10px;
    }

    .url-info {
        background: #f0f8ff;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        border-left: 4px solid #2196F3;
    }

    .url-info strong {
        color: #1976D2;
    }

    .url-text {
        word-break: break-all;
        font-family: monospace;
        background: #f5f5f5;
        padding: 8px;
        border-radius: 4px;
        margin: 8px 0;
        font-size: 12px;
    }

    .btn {
        display: inline-block;
        padding: 12px 20px;
        background: #4CAF50;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin: 10px 5px;
        transition: background 0.2s;
        cursor: pointer;
        border: none;
    }

    .btn:hover {
        background: #45a049;
    }

    .btn-secondary {
        background: #6c757d;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-warning {
        background: #ff9800;
    }

    .btn-warning:hover {
        background: #f57c00;
    }

    .instructions {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .instructions h4 {
        margin-top: 0;
        color: #856404;
    }

    .instructions ol {
        color: #856404;
        line-height: 1.6;
    }

    .error-msg {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
    }

    .success-msg {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
    }

    .qr-fallback {
        background: #e9ecef;
        border: 2px dashed #6c757d;
        padding: 40px 20px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #4CAF50;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 10px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .container {
            margin: 10px;
            padding: 20px;
        }
        
        .qr-code {
            max-width: 250px;
        }
    }
</style>
</head>
<body>

<div class="container">
    <div class="product-info">
        <h1 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h1>
        <p><strong>Harvest Date:</strong> <?= htmlspecialchars($product['harvest_date']) ?></p>
        <p><strong>Farm Location:</strong> <?= htmlspecialchars($product['farm_location']) ?></p>
    </div>

    <div class="qr-section">
        <h3>QR Code Generator</h3>
        
        <div id="qrContainer">
            <div class="success-msg" id="loadingMsg">
                <span class="loading"></span>Generating QR Code...
            </div>
        </div>
        
        <!-- <div class="url-info">
            <strong>QR Code Will Link To:</strong>
            <div class="url-text"><?= htmlspecialchars($product_url) ?></div>
        </div> -->

        <div style="margin: 20px 0;">
            <!-- <button onclick="generateQR(0)" class="btn">🔄 Generate QR Code</button> -->
            <button onclick="tryDifferentService()" class="btn btn-warning">🔄 Refreash</button>
        </div>
    </div>

    <!-- <div class="instructions">
        <h4>📱 How to Use This QR Code:</h4>
        <ol>
            <li>Click "Generate QR Code" to create your QR code</li>
            <li>Right-click on the QR code and save it as an image</li>
            <li>Print and attach it to your product packaging</li>
            <li>Customers can scan it to view your product details</li>
        </ol>
    </div> -->

    <div style="margin-top: 30px;">
        <a href="<?= $product_url ?>" target="_blank" class="btn">🔗 Test Product Page</a>
        <a href="farmer_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<script>
const qrApis = <?= json_encode($qr_apis) ?>;
const productUrl = <?= json_encode($product_url) ?>;
let currentApiIndex = 0;

function generateQR(apiIndex = 0) {
    const container = document.getElementById('qrContainer');
    const loadingMsg = document.getElementById('loadingMsg');
    
    // Show loading
    loadingMsg.style.display = 'block';
    loadingMsg.innerHTML = '<span class="loading"></span>Generating QR Code...';
    
    // Clear previous QR codes
    const existingQR = container.querySelector('.qr-code');
    if (existingQR) {
        existingQR.remove();
    }
    
    const existingError = container.querySelector('.error-msg');
    if (existingError) {
        existingError.remove();
    }
    
    // Create img element
    const img = document.createElement('img');
    img.className = 'qr-code';
    img.style.display = 'none';
    
    img.onload = function() {
        loadingMsg.style.display = 'none';
        img.style.display = 'inline-block';
        
        // Add success message
        const successMsg = document.createElement('div');
        successMsg.className = 'success-msg';
        successMsg.innerHTML = '✅ QR Code generated successfully!';
        container.appendChild(successMsg);
    };
    
    img.onerror = function() {
        img.remove();
        loadingMsg.style.display = 'none';
        
        if (apiIndex < qrApis.length - 1) {
            // Try next API
            setTimeout(() => generateQR(apiIndex + 1), 1000);
            loadingMsg.style.display = 'block';
            loadingMsg.innerHTML = '<span class="loading"></span>Trying alternative service...';
        } else {
            // All APIs failed, show manual option
            showManualQR();
        }
    };
    
    img.src = qrApis[apiIndex];
    container.appendChild(img);
    currentApiIndex = apiIndex;
}

function tryDifferentService() {
    const nextIndex = (currentApiIndex + 1) % qrApis.length;
    generateQR(nextIndex);
}

function showManualQR() {
    const container = document.getElementById('qrContainer');
    const loadingMsg = document.getElementById('loadingMsg');
    loadingMsg.style.display = 'none';
    
    const fallbackDiv = document.createElement('div');
    fallbackDiv.className = 'qr-fallback';
    fallbackDiv.innerHTML = `
        <h4>⚠ QR Code Service Unavailable</h4>
        <p><strong>Manual QR Code Generation:</strong></p>
        <ol style="text-align: left; max-width: 400px; margin: 0 auto;">
            <li>Copy this URL: <br><code style="background: #f8f9fa; padding: 5px; border-radius: 3px; word-break: break-all;">${productUrl}</code></li>
            <li>Go to any QR code generator website like:
                <ul>
                    <li><a href="https://qr-code-generator.com" target="_blank">qr-code-generator.com</a></li>
                    <li><a href="https://qrcodemonkey.com" target="_blank">qrcodemonkey.com</a></li>
                    <li><a href="https://www.qr-code-generator.org" target="_blank">qr-code-generator.org</a></li>
                </ul>
            </li>
            <li>Paste the URL and generate your QR code</li>
            <li>Download and use it for your product</li>
        </ol>
        <br>
        <button onclick="generateQR(0)" class="btn">🔄 Try Again</button>
    `;
    
    container.appendChild(fallbackDiv);
}

// Auto-generate on page load
window.onload = function() {
    setTimeout(() => generateQR(0), 500);
};
</script>

</body>
</html>