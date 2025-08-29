<?php
// view_product.php - Display product and farmer information when QR is scanned
require_once __DIR__ . '/../database/db.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    die("Invalid product ID");
}

// Get product and farmer information with JOIN
$stmt = $conn->prepare("
    SELECT p.*, f.name as farmer_name, f.dzo_name, f.gewog_name,
           f.certificate_type, f.certificate_no,
           f.profile_picture, f.farm_images
    FROM products p
    JOIN farmer_tbl f ON p.farmer_id = f.farmer_id
    WHERE p.product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found");
}

$data = $result->fetch_assoc();
$stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($data['product_name']) ?> - Product Information</title>
<!-- Font Awesome 6 Free (CDN) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<!-- Font Awesome 5 Free (CDN) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
    min-height: 100vh;
    padding: 20px 10px;
}
.container {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}
.header {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    padding: 30px 25px;
    text-align: center;
}
.header h1 { margin: 0; font-size: 28px; font-weight: bold; }
.header p { margin: 10px 0 0 0; opacity: 0.9; font-size: 16px; }
.content { padding: 30px 25px; }
.product-image {
    width: 100%;
    max-width: 300px;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
    display: block;
    margin: 0 auto 25px auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.info-section {
    background: #f8f9ff;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 5px solid #4CAF50;
}
.info-section h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e0e0e0;
    flex-wrap: wrap;
}
.info-row:last-child { border-bottom: none; }
.info-label { font-weight: bold; color: #555; min-width: 120px; }
.info-value { color: #333; text-align: right; flex: 1; }
.certification-badge {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    display: inline-block;
    margin-top: 10px;
}
.not-certified { background: #6c757d; }
.contact-section {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
}
.contact-section h3 { margin: 0 0 15px 0; font-size: 20px; }
.footer {
    text-align: center;
    padding: 20px;
    color: #666;
    font-size: 14px;
    background: #f8f9fa;
}
.fresh-indicator {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 5px;
}
.fresh { background: #d4edda; color: #155724; }
.moderate { background: #fff3cd; color: #856404; }
.old { background: #f8d7da; color: #721c24; }

/* Enhanced Farmer Profile Section */
.farmer-profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    border-radius: 15px;
    border: 2px solid #e8f5e8;
}

.profile-picture-container {
    flex-shrink: 0;
}

.profile-picture {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #4CAF50;
    box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
    transition: transform 0.3s ease;
}

.profile-picture:hover {
    transform: scale(1.05);
}

.default-profile {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
    font-weight: bold;
    border: 4px solid #4CAF50;
    box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
}

.farmer-basic-info {
    flex: 1;
}

.farmer-name {
    font-size: 24px;
    font-weight: bold;
    color: #2c5530;
    margin: 0 0 8px 0;
}

.farmer-location {
    color: #666;
    font-size: 16px;
    margin-bottom: 10px;
}

.inline-certification {
    display: inline-block;
}

/* Farm Images Gallery */
.farm-gallery {
    margin-top: 25px;
}

.farm-gallery h4 {
    color: #2c5530;
    font-size: 18px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.farm-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-top: 15px;
}

.farm-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.farm-image:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* Image Modal */
.image-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    cursor: pointer;
}

.modal-content {
    display: block;
    margin: auto;
    max-width: 90%;
    max-height: 90%;
    margin-top: 5%;
    border-radius: 10px;
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #ccc;
}

/* Debug Info */
.debug-info {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 5px;
    padding: 10px;
    margin: 10px 0;
    font-size: 12px;
    color: #856404;
}

/* Enhanced Responsive Design */
@media (max-width: 600px) {
    .container { margin: 0 10px; }
    .content { padding: 20px; }
    .header { padding: 20px; }
    .info-row { flex-direction: column; align-items: flex-start; gap: 5px; }
    .info-value { text-align: left; }
    
    .farmer-profile-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .farm-images-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .profile-picture, .default-profile {
        width: 80px;
        height: 80px;
    }
    
    .default-profile {
        font-size: 28px;
    }
    
    .farmer-name {
        font-size: 20px;
    }
}

@media (max-width: 400px) {
    .farm-images-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-leaf me-2 text-success"></i><?= htmlspecialchars($data['product_name']) ?></h1>
        <p><i class="fas fa-seedling text-success"></i> Organic Product Information</p>
    </div>

    <div class="content">
        <!-- Product Image -->
        <?php if (!empty($data['product_image']) && file_exists(__DIR__ . '/uploads/products/' . $data['product_image'])): ?>
            <img src="uploads/products/<?= htmlspecialchars($data['product_image']) ?>" 
                 alt="<?= htmlspecialchars($data['product_name']) ?>" class="product-image">
        <?php else: ?>
            <img src="uploads/products/default.png" alt="No Image" class="product-image">
        <?php endif; ?>

        <!-- Product Details -->
        <div class="info-section">
            <h3><i class="fas fa-box-open me-2 text-primary"></i> Product Details</h3>
            
            <div class="info-row">
                <span class="info-label"><i class="fas fa-tag me-2 text-muted"></i>Product Name:</span>
                <span class="info-value"><?= htmlspecialchars($data['product_name']) ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar-alt me-2 text-muted"></i>Harvest Date:</span>
                <span class="info-value">
                    <?= date('F d, Y', strtotime($data['harvest_date'])) ?>
                    <?php
                    $harvest_date = new DateTime($data['harvest_date']);
                    $today = new DateTime();
                    $days_old = $today->diff($harvest_date)->days;

                    if ($days_old <= 7) {
                        echo '<span class="fresh-indicator fresh"><i class="fas fa-leaf"></i> Fresh (' . $days_old . ' days)</span>';
                    } elseif ($days_old <= 30) {
                        echo '<span class="fresh-indicator moderate"><i class="fas fa-check-circle"></i> Good (' . $days_old . ' days)</span>';
                    } else {
                        echo '<span class="fresh-indicator old"><i class="fas fa-exclamation-circle"></i> (' . $days_old . ' days old)</span>';
                    }
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><i class="fas fa-map-marker-alt me-2 text-muted"></i>Farm Location:</span>
                <span class="info-value"><?= htmlspecialchars($data['farm_location']) ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><i class="fas fa-clock me-2 text-muted"></i>Added to System:</span>
                <span class="info-value"><?= date('F d, Y', strtotime($data['created_at'])) ?></span>
            </div>
        </div>

        <!-- Farmer Information -->
        <div class="info-section">
            <h3><i class="fas fa-user-farmer me-2 text-success"></i> Meet Your Farmer</h3> <!-- If FA6 not available, use fa-user -->

            <div class="farmer-profile-header">
                <div class="profile-picture-container">
                    <?php 
                    $profile_path = __DIR__ . '/uploads/profiles/' . $data['profile_picture'];
                    $profile_url = 'uploads/profiles/' . $data['profile_picture'];
                    
                    if(!empty($data['profile_picture']) && file_exists($profile_path)): 
                    ?>
                        <img src="<?= $profile_url ?>" 
                             alt="<?= htmlspecialchars($data['farmer_name']) ?>" class="profile-picture">
                    <?php else: ?>
                        <div class="default-profile">
                            <i class="fas fa-user text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="farmer-basic-info">
                    <h4 class="farmer-name"><?= htmlspecialchars($data['farmer_name']) ?></h4>
                    <p class="farmer-location"><i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($data['gewog_name']) ?>, <?= htmlspecialchars($data['dzo_name']) ?></p>
                    
                    <div class="inline-certification">
                        <?php if ($data['certificate_no']): ?>
                            <span class="certification-badge">
                                <i class="fas fa-certificate text-success"></i> <?= htmlspecialchars($data['certificate_type']) ?>
                            </span>
                            <br><small style="color: #666; margin-top: 5px; font-size: 12px;">
                                <i class="fas fa-id-card me-1"></i> Certificate No: <?= htmlspecialchars($data['certificate_no']) ?>
                            </small>
                        <?php else: ?>
                            <span class="certification-badge not-certified"><i class="fas fa-leaf"></i> Traditionally Grown</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Farm Images Gallery -->
            <?php if(!empty($data['farm_images'])): ?>
                <div class="farm-gallery">
                    <h4><i class="fas fa-tractor me-2 text-warning"></i> Farm Gallery</h4>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px; font-weight: bold;">
                            Get a glimpse of where your food is grown with love and care
                        </p>
                    
                    <div class="farm-images-grid">
                        <?php
                        $images = [];
                        $farm_images_data = trim($data['farm_images']);
                        if (strpos($farm_images_data, '[') === 0 && strpos($farm_images_data, ']') !== false) {
                            $decoded = json_decode($farm_images_data, true);
                            if (is_array($decoded)) $images = $decoded;
                        } else {
                            $images = explode(',', $farm_images_data);
                        }
                        
                        foreach($images as $index => $img):
                            $img = trim($img, ' "[]');
                            if($img != ''):
                                $farm_image_path = __DIR__ . '/uploads/farm_images/' . $img;
                                $farm_image_url = 'uploads/farm_images/' . $img;
                                if(file_exists($farm_image_path)):
                        ?>
                            <img src="<?= $farm_image_url ?>" 
                                 alt="Farm Image <?= $index + 1 ?>" 
                                 class="farm-image"
                                 onclick="openModal('<?= $farm_image_url ?>')">
                        <?php else: ?>
                            <div class="debug-info"><i class="fas fa-exclamation-triangle text-danger"></i> Missing: <?= htmlspecialchars($img) ?></div>
                        <?php endif; endif; endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: #666; font-style: italic; text-align: center; padding: 20px;">
                    <i class="fas fa-image text-muted"></i> Farm images will be available soon
                </p>
            <?php endif; ?>
        </div>

        <!-- Contact Section -->
        <div class="contact-section">
            <h3><i class="fas fa-phone-alt me-2 text-info"></i> Connect with the Farmer</h3>
            <p>Want to know more about farming practices, place bulk orders, or visit the farm?</p>
            <p><strong><i class="fas fa-map-marker-alt me-1"></i> Location:</strong> <?= htmlspecialchars($data['gewog_name']) ?>, <?= htmlspecialchars($data['dzo_name']) ?></p>
            <p style="font-size: 14px; opacity: 0.9;"><i class="fas fa-lock me-1"></i> Contact details available through AgriConnect platform</p>
        </div>
    </div>

    <div class="footer">
        <p><i class="fas fa-seedling text-success"></i> AgriConnect - Connecting Farmers & Consumers</p>
        <p><i class="fas fa-qrcode me-1"></i> Scanned on <?= date('F d, Y \a\t g:i A') ?></p>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal" onclick="closeModal()">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
function openModal(imageSrc) {
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImage').src = imageSrc;
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html>