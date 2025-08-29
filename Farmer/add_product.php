<?php
// add_product.php
session_start();

// Check if user is logged in and is a farmer
if (!isset($_SESSION['farmer_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: ../login.php");
    exit;
}

require_once '../database/db.php';

$farmer_id = $_SESSION['farmer_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // First, check if farmer's profile picture and farm image are uploaded
        $stmt = $conn->prepare("SELECT profile_picture, farm_images FROM farmer_tbl WHERE farmer_id = ?");
        $stmt->bind_param("i", $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $farmer_data = $result->fetch_assoc();
        $stmt->close();
        
        // Validate required farmer data
        if (empty($farmer_data['profile_picture'])) {
            $error = "Please upload your profile picture before adding a product. Go to your profile settings to upload it.";
        } elseif (empty($farmer_data['farm_images'])) {
            $error = "Please upload your farm image before adding a product. Go to your profile settings to upload it.";
        } else {
            // Proceed with form processing only if validation passes
            $product_name = trim($_POST['product_name']);
            $quantity = trim($_POST['quantity']);
            $unit = trim($_POST['unit']);
            $harvest_date = $_POST['harvest_date'];
            $farm_location = trim($_POST['farm_location']);
            
            // Handle file upload
        $product_image = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $upload_dir = __DIR__ . '/uploads/products/';

            // Create uploads directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp','jfif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $filename = 'product_' . $farmer_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    $product_image = $filename;
                } else {
                    $error = "Failed to upload product image.";
                }
            } else {
                $error = "Invalid file type. Please upload JPG, JPEG, PNG, GIF, or WEBP files only.";
            }
        }

        if (empty($error)) {
            // Insert product into database with separate quantity and unit fields
            $stmt = $conn->prepare("INSERT INTO products (farmer_id, product_name, quantity, unit, harvest_date, farm_location, product_image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("issssss", $farmer_id, $product_name, $quantity, $unit, $harvest_date, $farm_location, $product_image);

            if ($stmt->execute()) {
                $message = "Product added successfully!";
                // Optionally redirect after a short delay
                // header("Location: farmer_dashboard.php");
                // exit;
            } else {
                $error = "Error adding product. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - AgriConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f8f9fa; }
        header { 
            background: #4CAF50; 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
        }
        header h2 { margin: 0; }
        main { max-width: 600px; margin: 20px auto; padding: 0 15px; }
        .form-card { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
        }
        .form-group { margin-bottom: 20px; }
        .form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        .form-row .form-group.quantity-input {
            flex: 2;
        }
        .form-row .form-group.unit-select {
            flex: 1;
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333; 
        }
        input, textarea, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px; 
            box-sizing: border-box; 
        }
        input:focus, textarea:focus, select:focus { 
            outline: none; 
            border-color: #4CAF50; 
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3); 
        }
        select {
            background-color: white;
            background-image: url("data:image/svg+xml;utf8,<svg fill='%23666' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        .btn { 
            background: #4CAF50; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px; 
            text-decoration: none; 
            display: inline-block; 
            margin-right: 10px; 
            transition: background 0.2s;
        }
        .btn:hover { background: #388E3C; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-camera { background: #28a745; }
        .btn-camera:hover { background: #1e7e34; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .alert-error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .file-input-wrapper { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
            margin-top: 5px;
        }
        .file-input-wrapper input[type=file] { 
            position: absolute; 
            left: -9999px; 
        }
        .file-input-label { 
            display: inline-block; 
            padding: 8px 12px; 
            background: #17a2b8; 
            color: white; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px; 
            transition: background 0.2s;
        }
        .file-input-label:hover { background: #138496; }
        .camera-btn { 
            display: inline-block; 
            padding: 8px 12px; 
            background: #28a745; 
            color: white; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px; 
            border: none; 
        }
        .camera-btn:hover { background: #1e7e34; }
        .selected-file { 
            margin-top: 8px; 
            font-size: 12px; 
            color: #6c757d; 
        }
        .image-preview { 
            margin-top: 10px; 
            text-align: center; 
        }
        .preview-image { 
            max-width: 200px; 
            max-height: 200px; 
            border-radius: 8px; 
            border: 2px solid #ddd; 
            object-fit: cover;
        }
        .camera-modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.9); 
        }
        .camera-modal-content { 
            position: relative; 
            margin: 5% auto; 
            width: 90%; 
            max-width: 600px; 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
        }
        .camera-video { 
            width: 100%; 
            height: auto; 
            border-radius: 8px; 
        }
        .camera-controls { 
            text-align: center; 
            margin-top: 15px; 
        }
        .close-camera { 
            position: absolute; 
            top: 10px; 
            right: 15px; 
            color: #aaa; 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        .close-camera:hover { color: #000; }
        .captured-images { margin-top: 15px; }
        .captured-image { 
            width: 80px; 
            height: 80px; 
            object-fit: cover; 
            border-radius: 5px; 
            margin: 5px; 
            border: 2px solid #4CAF50; 
        }
        .form-actions { 
            margin-top: 30px; 
            text-align: center; 
            padding-top: 20px; 
            border-top: 1px solid #e9ecef; 
        }
        @media (max-width: 768px) { 
            header { flex-direction: column; gap: 10px; } 
            main { padding: 0 10px; }
            .form-card { padding: 20px; }
            .file-input-wrapper { flex-direction: column; }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-row .form-group {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>
        <i class="fas fa-plus-circle"></i>
        Add New Product
    </h2>
    <div>
        <a href="farmer_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</header>

<main>
    <div class="form-card">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                <script>
                    // Auto-redirect after success
                    setTimeout(function() {
                        window.location.href = 'farmer_dashboard.php';
                    }, 2000);
                </script>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="product_name">
                    <i class="fas fa-seedling"></i> Product Name *
                </label>
                <input type="text" id="product_name" name="product_name" required 
                       placeholder="e.g., Organic Tomatoes, Red Chilies, etc.">
            </div>
            
            <div class="form-row">
                <div class="form-group quantity-input">
                    <label for="quantity">
                        <i class="fas fa-weight-hanging"></i> Quantity *
                    </label>
                    <input type="number" id="quantity" name="quantity" required 
                           placeholder="Enter quantity" min="0" step="0.1">
                </div>
                <div class="form-group unit-select">
                    <label for="unit">
                        <i class="fas fa-ruler"></i> Unit *
                    </label>
                    <select id="unit" name="unit" required>
                        <option value="">Select unit</option>
                        <option value="kgs">Kilograms (kgs)</option>
                        <option value="grams">Grams (g)</option>
                        <option value="tons">Tons (t)</option>
                        <option value="pieces">Pieces (pcs)</option>
                        <option value="bundles">Bundles</option>
                        <option value="boxes">Boxes</option>
                        <option value="bags">Bags</option>
                        <option value="liters">Liters (L)</option>
                        <option value="pounds">Pounds (lbs)</option>
                        <option value="dozen">Dozen</option>
                        <option value="units">Units</option>
                    </select>
                </div>
            </div><br>
            
            <div class="form-group">
                <label for="harvest_date">
                    <i class="fas fa-calendar"></i> Harvest Date *
                </label>
                <input type="date" id="harvest_date" name="harvest_date" required>
            </div>
            
            <div class="form-group">
                <label for="farm_location">
                    <i class="fas fa-map-marker-alt"></i> Farm Location *
                </label>
                <input type="text" id="farm_location" name="farm_location" required 
                       placeholder="e.g., Chang Valley Farm, Thimphu">
            </div>
            
            <div class="form-group">
                <label for="product_image">
                    <i class="fas fa-camera"></i> Product Image
                </label>
                <div class="file-input-wrapper">
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <label for="product_image" class="file-input-label">
                        <i class="fas fa-image"></i> Choose Photo
                    </label>
                    <button type="button" class="camera-btn" onclick="openCamera()">
                        <i class="fas fa-camera"></i> Take Photo
                    </button>
                </div>
                <div class="selected-file" id="selected-file"></div>
                <div class="image-preview" id="image-preview"></div>
                <small style="color: #666; margin-top: 5px; display: block;">
                    Optional - Upload or take a photo of your product
                </small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Add Product
                </button>
                <a href="farmer_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<!-- Camera Modal -->
<div id="cameraModal" class="camera-modal">
    <div class="camera-modal-content">
        <span class="close-camera" onclick="closeCamera()">&times;</span>
        <video id="cameraVideo" class="camera-video" autoplay playsinline></video>
        <div class="camera-controls">
            <button type="button" class="btn" onclick="capturePhoto()">
                <i class="fas fa-camera"></i> Capture Photo
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeCamera()">
                <i class="fas fa-times"></i> Close Camera
            </button>
        </div>
        <div id="capturedImages" class="captured-images"></div>
        <canvas id="captureCanvas" style="display: none;"></canvas>
    </div>
</div>

<script>
let stream = null;
let capturedFile = null;

// Set minimum date to today for harvest date
document.getElementById('harvest_date').max = new Date().toISOString().split('T')[0];

// Show selected file and preview
document.getElementById('product_image').addEventListener('change', function(e) {
    const selectedFile = document.getElementById('selected-file');
    const imagePreview = document.getElementById('image-preview');
    
    if (e.target.files.length > 0) {
        const file = e.target.files[0];
        selectedFile.textContent = 'Selected: ' + file.name;
        
        // Show image preview
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="preview-image">';
        };
        reader.readAsDataURL(file);
    } else {
        selectedFile.textContent = '';
        imagePreview.innerHTML = '';
    }
});

// Camera functions
async function openCamera() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    
    try {
        // Request camera access with mobile-optimized constraints
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment', // Use back camera on mobile
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        });
        
        video.srcObject = stream;
        modal.style.display = 'block';
        
        // Clear previous captures
        capturedFile = null;
        document.getElementById('capturedImages').innerHTML = '';
        
    } catch (err) {
        alert('Camera access denied or not available: ' + err.message);
    }
}

function closeCamera() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    video.srcObject = null;
    modal.style.display = 'none';
    
    // Apply captured image to form
    if (capturedFile) {
        applyCapturedImage();
    }
}

function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('captureCanvas');
    const context = canvas.getContext('2d');
    
    // Set canvas dimensions to video dimensions
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw video frame to canvas
    context.drawImage(video, 0, 0);
    
    // Convert to blob and create file
    canvas.toBlob(function(blob) {
        const fileName = 'product_' + Date.now() + '.jpg';
        capturedFile = new File([blob], fileName, { type: 'image/jpeg' });
        
        // Show captured image preview
        const img = document.createElement('img');
        img.src = URL.createObjectURL(blob);
        img.className = 'captured-image';
        img.title = fileName;
        
        const capturedImagesDiv = document.getElementById('capturedImages');
        capturedImagesDiv.innerHTML = ''; // Clear previous captures
        capturedImagesDiv.appendChild(img);
        
    }, 'image/jpeg', 0.8);
}

function applyCapturedImage() {
    if (capturedFile) {
        // Apply to product image input
        const dt = new DataTransfer();
        dt.items.add(capturedFile);
        document.getElementById('product_image').files = dt.files;
        
        // Update UI
        document.getElementById('selected-file').textContent = 'Camera captured: ' + capturedFile.name;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const imagePreview = document.getElementById('image-preview');
            imagePreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="preview-image">';
        };
        reader.readAsDataURL(capturedFile);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('cameraModal');
    if (event.target === modal) {
        closeCamera();
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const productName = document.getElementById('product_name').value.trim();
    const quantity = document.getElementById('quantity').value.trim();
    const unit = document.getElementById('unit').value;
    const harvestDate = document.getElementById('harvest_date').value;
    const farmLocation = document.getElementById('farm_location').value.trim();
    
    if (!productName || !quantity || !unit || !harvestDate || !farmLocation) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (parseFloat(quantity) <= 0) {
        e.preventDefault();
        alert('Quantity must be greater than 0.');
        return false;
    }
    
    // Check if harvest date is not in the future
    const today = new Date();
    const selectedDate = new Date(harvestDate);
    
    if (selectedDate > today) {
        e.preventDefault();
        alert('Harvest date cannot be in the future.');
        return false;
    }
});
</script>

</body>
</html>