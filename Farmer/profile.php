<?php 
// profile.php
session_start();

// Check if user is logged in and is a farmer
if (!isset($_SESSION['farmer_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';

$farmer_id = $_SESSION['farmer_id'];
$message = '';
$error = '';

// Handle AJAX delete request for farm images
if (isset($_POST['action']) && $_POST['action'] === 'delete_farm_image') {
    $image_to_delete = $_POST['image_name'];
    
    // Get current farm images
    $stmt = $conn->prepare("SELECT farm_images FROM farmer_tbl WHERE farmer_id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result && !empty($result['farm_images'])) {
        $current_images = json_decode($result['farm_images'], true) ?: [];
        
        // Remove the image from array
        $updated_images = array_filter($current_images, function($img) use ($image_to_delete) {
            return $img !== $image_to_delete;
        });
        
        // Update database
        $updated_images_json = json_encode(array_values($updated_images));
        $update_stmt = $conn->prepare("UPDATE farmer_tbl SET farm_images = ? WHERE farmer_id = ?");
        $update_stmt->bind_param("si", $updated_images_json, $farmer_id);
        
        if ($update_stmt->execute()) {
            // Delete physical file
            $file_path = __DIR__ . '/uploads/farm_images/' . $image_to_delete;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed']);
        }
        $update_stmt->close();
    }
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $name = trim($_POST['name']);
    $cid = trim($_POST['cid']);
    $phone_no = trim($_POST['phone_no']);
    $dzo_name = trim($_POST['dzo_name']);
    $gewog_name = trim($_POST['gewog_name']);
    $village = trim($_POST['village']);
    $certificate_no = trim($_POST['certificate_no']);
    $certificate_type = trim($_POST['certificate_type']);
    
    // Handle profile picture upload
    $profile_picture = '';
    $update_profile_picture = false;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp','jfif'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $new_filename = 'farmer_' . $farmer_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
                $update_profile_picture = true;
                
                // Delete old profile picture if exists
                $old_pic_stmt = $conn->prepare("SELECT profile_picture FROM farmer_tbl WHERE farmer_id = ?");
                $old_pic_stmt->bind_param("i", $farmer_id);
                $old_pic_stmt->execute();
                $old_pic_result = $old_pic_stmt->get_result()->fetch_assoc();
                $old_pic_stmt->close();
                
                if ($old_pic_result && $old_pic_result['profile_picture']) {
                    $old_pic_path = $upload_dir . $old_pic_result['profile_picture'];
                    if (file_exists($old_pic_path)) {
                        unlink($old_pic_path);
                    }
                }
            } else {
                $error = "Failed to upload profile picture.";
            }
        } else {
            $error = "Invalid file type. Please upload JPG, JPEG, PNG, GIF, or WEBP files only.";
        }
    }
    
    // Handle farm images upload (multiple images)
    $farm_images = [];
    $update_farm_images = false;
    if (isset($_FILES['farm_images']) && !empty($_FILES['farm_images']['name'][0])) {
        $farm_upload_dir = __DIR__ . '/uploads/farm_images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($farm_upload_dir)) {
            mkdir($farm_upload_dir, 0755, true);
        }
        
        // Get existing farm images first
        $existing_stmt = $conn->prepare("SELECT farm_images FROM farmer_tbl WHERE farmer_id = ?");
        $existing_stmt->bind_param("i", $farmer_id);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result()->fetch_assoc();
        $existing_stmt->close();
        
        if ($existing_result && !empty($existing_result['farm_images'])) {
            $farm_images = json_decode($existing_result['farm_images'], true) ?: [];
        }
        
        $upload_count = count($_FILES['farm_images']['name']);
        
        for ($i = 0; $i < $upload_count; $i++) {
            if ($_FILES['farm_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($_FILES['farm_images']['name'][$i], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $new_filename = 'farm_' . $farmer_id . '_' . time() . '_' . $i . '.' . $file_extension;
                    $upload_path = $farm_upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['farm_images']['tmp_name'][$i], $upload_path)) {
                        $farm_images[] = $new_filename;
                        $update_farm_images = true;
                    }
                } else {
                    $error = "Invalid file type for farm image " . ($i + 1) . ". Please upload JPG, JPEG, PNG, GIF, or WEBP files only.";
                    break;
                }
            }
        }
    }
    
    if (empty($error)) {
       // Build update query based on what needs to be updated
        if ($update_profile_picture && $update_farm_images) {
            $farm_images_json = json_encode($farm_images);
            $update_stmt = $conn->prepare("UPDATE farmer_tbl 
                SET name = ?, cid = ?, phone_no = ?, dzo_name = ?, gewog_name = ?, village = ?, 
                    certificate_no = ?, certificate_type = ?, profile_picture = ?, farm_images = ?, updated_at = NOW() 
                WHERE farmer_id = ?");
            $update_stmt->bind_param("ssssssssssi", 
                $name, $cid, $phone_no, $dzo_name, $gewog_name, $village, 
                $certificate_no, $certificate_type, $profile_picture, $farm_images_json, $farmer_id);

        } elseif ($update_profile_picture) {
            $update_stmt = $conn->prepare("UPDATE farmer_tbl 
                SET name = ?, cid = ?, phone_no = ?, dzo_name = ?, gewog_name = ?, village = ?, 
                    certificate_no = ?, certificate_type = ?, profile_picture = ?, updated_at = NOW() 
                WHERE farmer_id = ?");
            $update_stmt->bind_param("sssssssssi", 
                $name, $cid, $phone_no, $dzo_name, $gewog_name, $village, 
                $certificate_no, $certificate_type, $profile_picture, $farmer_id);

        } elseif ($update_farm_images) {
            $farm_images_json = json_encode($farm_images);
            $update_stmt = $conn->prepare("UPDATE farmer_tbl 
                SET name = ?, cid = ?, phone_no = ?, dzo_name = ?, gewog_name = ?, village = ?, 
                    certificate_no = ?, certificate_type = ?, farm_images = ?, updated_at = NOW() 
                WHERE farmer_id = ?");
            $update_stmt->bind_param("sssssssssi", 
                $name, $cid, $phone_no, $dzo_name, $gewog_name, $village, 
                $certificate_no, $certificate_type, $farm_images_json, $farmer_id);

        } else {
            $update_stmt = $conn->prepare("UPDATE farmer_tbl 
                SET name = ?, cid = ?, phone_no = ?, dzo_name = ?, gewog_name = ?, village = ?, 
                    certificate_no = ?, certificate_type = ?, updated_at = NOW() 
                WHERE farmer_id = ?");
            $update_stmt->bind_param("ssssssssi", 
                $name, $cid, $phone_no, $dzo_name, $gewog_name, $village, 
                $certificate_no, $certificate_type, $farmer_id);
        }

        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            $_SESSION['farmer_name'] = $name; // Update session name if you store it
        } else {
            $error = "Failed to update profile. Please try again.";
        }
        $update_stmt->close();
    }
}

// Get current farmer information
$stmt = $conn->prepare("SELECT * FROM farmer_tbl WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Parse existing farm images
$existing_farm_images = [];
if (!empty($farmer['farm_images'])) {
    $existing_farm_images = json_decode($farmer['farm_images'], true) ?: [];
}

// Check if farmer is certified (you can determine this based on certificate_no being present)
$is_certified = !empty($farmer['certificate_no']);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Organic Traceability</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; margin: 0; background: #f8f9fa; }
header { background: #4CAF50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
header h2 { margin: 0; }
.btn { text-decoration: none; padding: 10px 16px; background: #4CAF50; color: white; border-radius: 5px; font-size: 14px; transition: background 0.2s; display: inline-block; text-align: center; border: none; cursor: pointer; }
.btn:hover { background: #388E3C; }
.btn-secondary { background: #6c757d; }
.btn-secondary:hover { background: #5a6268; }
.btn-camera { background: #17a2b8; }
.btn-camera:hover { background: #138496; }
main { max-width: 800px; margin: 20px auto; padding: 0 15px; }
.profile-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.profile-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
.profile-picture { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #4CAF50; margin-bottom: 15px; }
.default-avatar { width: 150px; height: 150px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #6c757d; margin: 0 auto 15px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
.form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
.form-group input:focus, .form-group select:focus { outline: none; border-color: #4CAF50; box-shadow: 0 0 5px rgba(76, 175, 80, 0.3); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.form-row-triple { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
.alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.certification-badge { background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; display: inline-block; margin-left: 10px; }
.file-input-wrapper { position: relative; display: flex; gap: 10px; flex-wrap: wrap; }
.file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
.file-input-label { display: inline-block; padding: 8px 12px; background: #17a2b8; color: white; border-radius: 5px; cursor: pointer; font-size: 14px; }
.file-input-label:hover { background: #138496; }
.camera-btn { display: inline-block; padding: 8px 12px; background: #28a745; color: white; border-radius: 5px; cursor: pointer; font-size: 14px; border: none; }
.camera-btn:hover { background: #1e7e34; }
.selected-file { margin-top: 8px; font-size: 12px; color: #6c757d; }
.farm-images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 10px; }
.farm-image-container { position: relative; }
.farm-image { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; }
.farm-image:hover { border-color: #4CAF50; }
.delete-image-btn { position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3); }
.delete-image-btn:hover { background: #c82333; }
.camera-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); }
.camera-modal-content { position: relative; margin: 5% auto; width: 90%; max-width: 600px; background: white; padding: 20px; border-radius: 10px; }
.camera-video { width: 100%; height: auto; border-radius: 8px; }
.camera-controls { text-align: center; margin-top: 15px; }
.close-camera { position: absolute; top: 10px; right: 15px; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
.close-camera:hover { color: #000; }
.captured-images { margin-top: 15px; }
.captured-image { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; margin: 5px; border: 2px solid #4CAF50; }
.section-title { font-size: 18px; font-weight: bold; color: #333; margin: 25px 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid #4CAF50; }
@media (max-width: 768px) { 
    header { flex-direction: column; gap: 10px; } 
    main { padding: 0 10px; }
    .form-row, .form-row-triple { grid-template-columns: 1fr; }
    .profile-container { padding: 20px; }
    .file-input-wrapper { flex-direction: column; }
}
</style>
</head>
<body>
<header>
    <h2>
        <i class="fas fa-seedling"></i>
        AgriConnect - My Profile
    </h2>
    <div>
        <a href="farmer_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</header>

<main>
<div class="profile-container">
    <div class="profile-header">
        <div id="current-profile-picture">
            <?php if (!empty($farmer['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $farmer['profile_picture'])): ?>
                <img src="uploads/profiles/<?= htmlspecialchars($farmer['profile_picture']) ?>" alt="Profile Picture" class="profile-picture">
            <?php else: ?>
                <div class="default-avatar">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        <h3><?= htmlspecialchars($farmer['name']) ?>
            <?php if ($is_certified): ?>
                <span class="certification-badge">
                    <i class="fas fa-certificate"></i> Certified
                </span>
            <?php endif; ?>
        </h3>
        <p style="color: #666; margin: 5px 0;">CID: <?= htmlspecialchars($farmer['cid']) ?></p>
        <p style="color: #666; margin: 0; font-size: 14px;">
            Member since: <?= date('M d, Y', strtotime($farmer['created_at'])) ?>
        </p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="section-title">
            <i class="fas fa-user"></i> Personal Information
        </div>

        <div class="form-group">
            <label for="profile_picture">Profile Picture</label>
            <div class="file-input-wrapper">
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <label for="profile_picture" class="file-input-label">
                    <i class="fas fa-image"></i> Choose Photo
                </label>
                <button type="button" class="camera-btn" onclick="openCamera('profile')">
                    <i class="fas fa-camera"></i> Take Photo
                </button>
            </div>
            <div class="selected-file" id="selected-file-profile"></div>
        </div>

        <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($farmer['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="cid">Citizenship ID Number *</label>
            <input type="text" id="cid" name="cid" value="<?= htmlspecialchars($farmer['cid']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone_no">Phone Number *</label>
            <input type="text" id="phone_no" name="phone_no" value="<?= htmlspecialchars($farmer['phone_no']) ?>" required>
        </div>

        <div class="form-row-triple">
            <div class="form-group">
                <label for="dzo_name">Dzongkhag *</label>
                <input type="text" id="dzo_name" name="dzo_name" value="<?= htmlspecialchars($farmer['dzo_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="gewog_name">Gewog *</label>
                <input type="text" id="gewog_name" name="gewog_name" value="<?= htmlspecialchars($farmer['gewog_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="village">Village *</label>
                <input type="text" id="village" name="village" value="<?= htmlspecialchars($farmer['village']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="certificate_no">Certificate Number</label>
                <input type="text" id="certificate_no" name="certificate_no" value="<?= htmlspecialchars($farmer['certificate_no'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="certificate_type">Certificate Type</label>
                <select id="certificate_type" name="certificate_type">
                    <option value="">Select Type</option>
                    <option value="BAFTA Third Party" <?= ($farmer['certificate_type'] ?? '') === 'BAFTA Third Party' ? 'selected' : '' ?>>BAFTA Third Party</option>
                    <option value="BOS- Bhutan Organic Standards" <?= ($farmer['certificate_type'] ?? '') === 'BOS- Bhutan Organic Standards' ? 'selected' : '' ?>>BOS- Bhutan Organic Standards</option>
                    <option value="GAP-Good Agriculture Practices" <?= ($farmer['certificate_type'] ?? '') === 'GAP-Good Agriculture Practices' ? 'selected' : '' ?>>GAP-Good Agriculture Practices</option>
                    <option value="LOAS" <?= ($farmer['certificate_type'] ?? '') === 'LOAS' ? 'selected' : '' ?>>LOAS</option>
                </select>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-seedling"></i> Farm Information
        </div>

        <div class="form-group">
            <label for="farm_images">Farm Images (You can upload multiple images)</label>
            <div class="file-input-wrapper">
                <input type="file" id="farm_images" name="farm_images[]" accept="image/*" multiple>
                <label for="farm_images" class="file-input-label">
                    <i class="fas fa-images"></i> Choose Images
                </label>
                <button type="button" class="camera-btn" onclick="openCamera('farm')">
                    <i class="fas fa-camera"></i> Take Photos
                </button>
            </div>
            <div class="selected-file" id="selected-file-farm"></div>
            
            <div id="current-farm-images">
                <?php if (!empty($existing_farm_images)): ?>
                    <p style="margin-top: 15px; font-weight: bold; color: #333;">Current Farm Images:</p>
                    <div class="farm-images-grid">
                        <?php foreach ($existing_farm_images as $image): ?>
                            <?php if (file_exists(__DIR__ . '/uploads/farm_images/' . $image)): ?>
                                <div class="farm-image-container">
                                    <img src="uploads/farm_images/<?= htmlspecialchars($image) ?>" alt="Farm Image" class="farm-image">
                                    <button type="button" class="delete-image-btn" onclick="deleteFarmImage('<?= htmlspecialchars($image) ?>')" title="Delete Image">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Update Profile
            </button>
            <a href="farmer_dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">
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
let currentCameraType = '';
let stream = null;
let capturedFiles = [];

// Show selected file names
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const selectedFile = document.getElementById('selected-file-profile');
    if (e.target.files.length > 0) {
        selectedFile.textContent = 'Selected: ' + e.target.files[0].name;
        
        // Show preview of selected profile image
        const file = e.target.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileContainer = document.getElementById('current-profile-picture');
            profileContainer.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture" class="profile-picture">';
        };
        reader.readAsDataURL(file);
    } else {
        selectedFile.textContent = '';
    }
});

document.getElementById('farm_images').addEventListener('change', function(e) {
    const selectedFile = document.getElementById('selected-file-farm');
    if (e.target.files.length > 0) {
        const fileNames = Array.from(e.target.files).map(f => f.name);
        selectedFile.textContent = 'Selected: ' + fileNames.join(', ');
    } else {
        selectedFile.textContent = '';
    }
});

// Camera functions
async function openCamera(type) {
    currentCameraType = type;
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
        capturedFiles = [];
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
    
    // Apply captured images to form
    if (capturedFiles.length > 0) {
        applyCapturedImages();
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
        const fileName = currentCameraType + '_' + Date.now() + '.jpg';
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        
        capturedFiles.push(file);
        
        // Show captured image preview
        const img = document.createElement('img');
        img.src = URL.createObjectURL(blob);
        img.className = 'captured-image';
        img.title = fileName;
        
        document.getElementById('capturedImages').appendChild(img);
        
    }, 'image/jpeg', 0.8);
}

function applyCapturedImages() {
    if (currentCameraType === 'profile' && capturedFiles.length > 0) {
        // Apply to profile picture input
        const dt = new DataTransfer();
        dt.items.add(capturedFiles[0]);
        document.getElementById('profile_picture').files = dt.files;
        
        document.getElementById('selected-file-profile').textContent = 'Camera captured: ' + capturedFiles[0].name;
        
        // Show preview of captured profile image
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileContainer = document.getElementById('current-profile-picture');
            profileContainer.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture" class="profile-picture">';
        };
        reader.readAsDataURL(capturedFiles[0]);
        
    } else if (currentCameraType === 'farm' && capturedFiles.length > 0) {
        // Apply to farm images input
        const dt = new DataTransfer();
        capturedFiles.forEach(file => dt.items.add(file));
        document.getElementById('farm_images').files = dt.files;
        
        const fileNames = capturedFiles.map(f => f.name);
        document.getElementById('selected-file-farm').textContent = 'Camera captured: ' + fileNames.join(', ');
    }
}

// Delete farm image function
async function deleteFarmImage(imageName) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_farm_image');
        formData.append('image_name', imageName);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove the image container from the display
            const imageContainers = document.querySelectorAll('.farm-image-container');
            imageContainers.forEach(container => {
                const img = container.querySelector('img');
                if (img && img.src.includes(imageName)) {
                    container.remove();
                }
            });
            
            // Check if there are no more images and hide the section
            const remainingImages = document.querySelectorAll('.farm-image-container');
            if (remainingImages.length === 0) {
                const currentFarmImages = document.getElementById('current-farm-images');
                currentFarmImages.innerHTML = '';
            }
            
            // Show success message
            showAlert('Image deleted successfully!', 'success');
        } else {
            showAlert('Failed to delete image: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        showAlert('Error deleting image: ' + error.message, 'error');
    }
}

// Function to show alert messages
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
    alert.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    
    // Insert at the top of the profile container
    const profileContainer = document.querySelector('.profile-container');
    const profileHeader = document.querySelector('.profile-header');
    profileContainer.insertBefore(alert, profileHeader.nextSibling);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('cameraModal');
    if (event.target === modal) {
        closeCamera();
    }
}
</script>

</body>
</html>