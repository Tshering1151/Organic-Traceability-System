<?php
// use your connection file
require_once __DIR__ . '/database/db.php';
$message = $error = "";

// Fetch Dzongkhags for the dropdown
$dzo_result = $conn->query("SELECT dzo_id, dzo_name FROM dzo_tbl ORDER BY dzo_name ASC");

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $role = $_POST['role'] ?? '';
    $cid = trim($_POST['cid'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dzo_name = trim($_POST['dzo_name'] ?? '');
    $gewog = trim($_POST['gewog_name'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];

    if (empty($role)) {
        $errors[] = "Please select a role";
    }

    if (empty($name)) {
        $errors[] = "Please enter your full name";
    }

    if (empty($cid)) {
        $errors[] = "Please enter your CID number";
    } elseif (strlen($cid) !== 11 || !ctype_digit($cid)) {
        $errors[] = "CID number must be exactly 11 digits";
    }

    if (empty($phone)) {
        $errors[] = "Please enter your phone number";
    } elseif (strlen($phone) !== 8 || !ctype_digit($phone)) {
        $errors[] = "Phone number must be exactly 8 digits";
    } elseif (!in_array(substr($phone, 0, 2), ['17', '77'])) {
        $errors[] = "Phone number must start with 17 or 77";
    }

    if (empty($dzo_name)) {
        $errors[] = "Please select a Dzongkhag";
    }

    if (empty($gewog)) {
        $errors[] = "Please select a Gewog";
    }

    if (empty($village)) {
        $errors[] = "Please enter your village name";
    }

    if (empty($password)) {
        $errors[] = "Please enter a password";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Role-specific validation
    if ($role === 'Farmer' && !empty($_POST['has_certificate'])) {
        if (empty($_POST['certificate_type'])) {
            $errors[] = "Please select a certificate type";
        }
        if (empty($_POST['certificate_no'])) {
            $errors[] = "Please enter your certificate number";
        }
    }

    if ($role === 'Aggregator' && !empty($_POST['has_License'])) {
        if (empty($_POST['business_name'])) {
            $errors[] = "Please enter your business name";
        }
        if (empty($_POST['license_no'])) {
            $errors[] = "Please enter your license number";
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        // Hash password
        $password = password_hash($password, PASSWORD_DEFAULT);

        // Farmer specific
        $certificate_type = null;
        $certificate_no = null;

        // Aggregator specific
        $business_name = null;
        $license_no = null;

        if ($role === 'Farmer') {
            if (!empty($_POST['has_certificate'])) {
                $certificate_type = trim($_POST['certificate_type'] ?? '');
                $certificate_no = trim($_POST['certificate_no'] ?? '');
            }

            $sql = "INSERT INTO farmer_tbl (cid, name, phone_no, dzo_name, gewog_name, village, certificate_type, certificate_no, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("sssssssss", $cid, $name, $phone, $dzo_name, $gewog, $village, $certificate_type, $certificate_no, $password);
                if ($stmt->execute()) {
                    echo "<script>alert('Farmer registration successful!'); window.location='login.php';</script>";
                    exit;
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($role === 'Aggregator') {
            if (!empty($_POST['has_License'])) {
                $business_name = trim($_POST['business_name'] ?? '');
                $license_no = trim($_POST['license_no'] ?? '');
            }
            
            $sql = "INSERT INTO aggre_tbl (cid, name, phone_no, dzo_name, gewog_name, village, business_name, license_no, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("sssssssss", $cid, $name, $phone, $dzo_name, $gewog, $village, $business_name, $license_no, $password);
                if ($stmt->execute()) {
                    echo "<script>alert('Aggregator registration successful!'); window.location='login.php';</script>";
                    exit;
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Organic Traceability System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2d5016;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f8fbf6 0%, #e8f5e8 100%);
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(45, 80, 22, 0.3);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-image {
            width: 50px;
            height: 50px;
            background: #68a978;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: bold;
            color: #e8f5e8;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #68a978;
        }
        
        .login-btn {
            background-color: #68a978;
        }
        
        .login-btn:hover {
            background-color: #5a9268;
        }
        
        .register-btn {
            background-color: #8bc34a;
        }
        
        .register-btn:hover {
            background-color: #7cb342;
        }

        /* Main Content */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Header Section */
        .form-header {
            background: #4CAF50;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }

        .form-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-header p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Form Content */
        .form-content {
            padding: 30px;
        }

        /* Role Selection Section */
        .role-section {
            background: #f0f9f0;
            border: 2px solid #4CAF50;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .role-section h3 {
            color: #4CAF50;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .role-option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e0e0e0;
        }

        .role-option:hover {
            background: #f8f9fa;
            border-color: #4CAF50;
        }

        .role-option.selected {
            background: #f0f9f0;
            border-color: #4CAF50;
        }

        .role-option input[type="radio"] {
            margin-right: 12px;
            accent-color: #4CAF50;
        }

        .role-option .role-icon {
            color: #4CAF50;
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .role-option .role-text {
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }

        /* Basic Information Section */
        .info-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-section h3 {
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            transition: border-color 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #dc3545;
        }

        /* Toggle Sections */
        .toggle-section {
            display: none;
            background: #f0f9f0;
            border: 1px solid #d4edda;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .toggle-section h5 {
            color: #4CAF50;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check {
            margin-bottom: 15px;
        }

        .form-check-input:checked {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #333;
            margin-left: 8px;
        }

        /* Register Button */
        .register-btn-container {
            margin-top: 25px;
        }

        .register-btn-main {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .register-btn-main:hover {
            background: #45a049;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        /* Footer Styles */
        footer {
            background: #2d5016;
            color: #e8f5e8;
            text-align: center;
            padding: 30px 0;
            margin-top: auto;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: #8bc34a;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #e8f5e8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .register-container {
                margin: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .form-content {
                padding: 20px;
            }

            .form-header {
                padding: 25px 15px;
            }

            .form-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo-section">
                <div class="logo-image">🌱</div>
                <div class="logo-text">OrganicTrace</div>
            </div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="trace.php">Trace Product</a>
                <a href="verify.php">Verify</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="contact.php">Contact</a>
                <a href="login.php" class="login-btn">Login</a>
                <a href="register.php" class="register-btn">Register</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="register-container">
            <!-- Form Header -->
            <div class="form-header">
                <h1><i class="fas fa-seedling"></i> OrganicTrace</h1>
                <p>Join our agricultural community</p>
            </div>

            <!-- Form Content -->
            <div class="form-content">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <?php if(!empty($message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <!-- Role Selection -->
                    <div class="role-section">
                        <h3>Select Your Role</h3>
                        
                        <div class="role-option" data-role="Farmer">
                            <input type="radio" name="role" value="Farmer" id="role-farmer" <?= (isset($_POST['role']) && $_POST['role'] === 'Farmer') ? 'checked' : '' ?>>
                            <i class="fas fa-user-tie role-icon"></i>
                            <span class="role-text">Farmer - I grow and produce agricultural products</span>
                        </div>
                        
                        <div class="role-option" data-role="Aggregator">
                            <input type="radio" name="role" value="Aggregator" id="role-aggregator" <?= (isset($_POST['role']) && $_POST['role'] === 'Aggregator') ? 'checked' : '' ?>>
                            <i class="fas fa-warehouse role-icon"></i>
                            <span class="role-text">Aggregator - I collect, store, and distribute agricultural products</span>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-user"></i> Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>CID Number</label>
                                <input type="text" name="cid" class="form-control" required placeholder="11 digits only" value="<?= htmlspecialchars($_POST['cid'] ?? '') ?>" maxlength="11">
                                <div class="invalid-feedback" id="cid-error"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Dzongkhag</label>
                                <select name="dzo_name" id="dzo" class="form-select" required>
                                    <option value="">Select Dzongkhag</option>
                                    <?php if($dzo_result): while($row = $dzo_result->fetch_assoc()): ?>
                                        <option
                                            value="<?= htmlspecialchars($row['dzo_name']) ?>"
                                            data-id="<?= (int)$row['dzo_id'] ?>"
                                            <?= (isset($_POST['dzo_name']) && $_POST['dzo_name'] === $row['dzo_name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['dzo_name']) ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Gewog</label>
                                <select name="gewog_name" id="gewog" class="form-select" required>
                                    <option value="">Select Gewog</option>
                                    <?php if (isset($_POST['gewog_name'])): ?>
                                        <option value="<?= htmlspecialchars($_POST['gewog_name']) ?>" selected><?= htmlspecialchars($_POST['gewog_name']) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Village</label>
                                <input type="text" name="village" class="form-control" required value="<?= htmlspecialchars($_POST['village'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label>Phone No</label>
                                <input type="text" name="phone" class="form-control" required placeholder="17XXXXXX or 77XXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" maxlength="8">
                                <div class="invalid-feedback" id="phone-error"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                <div class="invalid-feedback" id="password-error"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Farmer section -->
                    <div id="farmer-section" class="toggle-section">
                        <h5><i class="fas fa-certificate"></i> Farmer Certification</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hasCertificate" name="has_certificate" value="1" <?= (!empty($_POST['has_certificate'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="hasCertificate">I am a certified farmer</label>
                        </div>

                        <div id="farmer-certificate-fields" style="display:<?= (!empty($_POST['has_certificate'])) ? 'block' : 'none' ?>;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Certificate Type</label>
                                    <select name="certificate_type" class="form-select">
                                        <option value="">--Select Certificate Type--</option>
                                        <option value="BAFTA Third Party" <?= (isset($_POST['certificate_type']) && $_POST['certificate_type'] === 'BAFTA Third Party') ? 'selected' : '' ?>>BAFTA Third Party</option>
                                        <option value="BOS- Bhutan Organic Standards" <?= (isset($_POST['certificate_type']) && $_POST['certificate_type'] === 'BOS- Bhutan Organic Standards') ? 'selected' : '' ?>>BOS- Bhutan Organic Standards</option>
                                        <option value="GAP-Good Agriculture Practices" <?= (isset($_POST['certificate_type']) && $_POST['certificate_type'] === 'GAP-Good Agriculture Practices') ? 'selected' : '' ?>>GAP-Good Agriculture Practices</option>
                                        <option value="LOAS" <?= (isset($_POST['certificate_type']) && $_POST['certificate_type'] === 'LOAS') ? 'selected' : '' ?>>LOAS</option>
                                        <option value="Other" <?= (isset($_POST['certificate_type']) && $_POST['certificate_type'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Certificate Number</label>
                                    <input type="text" name="certificate_no" class="form-control" value="<?= htmlspecialchars($_POST['certificate_no'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aggregator section -->
                    <div id="aggregator-section" class="toggle-section">
                        <h5><i class="fas fa-building"></i> Business Information</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hasLicense" name="has_License" value="1" <?= (!empty($_POST['has_License'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="hasLicense">I have Business License</label>
                        </div>

                        <div id="aggregator-license-fields" style="display:<?= (!empty($_POST['has_License'])) ? 'block' : 'none' ?>;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Business Name</label>
                                    <input type="text" name="business_name" class="form-control" value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>License No</label>
                                    <input type="text" name="license_no" class="form-control" value="<?= htmlspecialchars($_POST['license_no'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <div class="register-btn-container">
                        <button type="submit" name="register" class="register-btn-main">
                            <i class="fas fa-user-plus"></i> Register Account
                        </button>
                    </div>
                </form>

                <!-- Login Link -->
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="certifications.php">Certifications</a>
                <a href="api-docs.php">API Documentation</a>
                <a href="support.php">Support Center</a>
                <a href="contact.php">Contact Us</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> OrganicTrace. Ensuring organic integrity worldwide.</p>
        </div>
    </footer>

    <script>
    $(function(){
        // Initialize form based on existing POST data
        initializeForm();

        // Role selection handling
        $('.role-option').on('click', function(){
            const role = $(this).data('role');
            const radioInput = $(this).find('input[type="radio"]');
            
            // Update radio selection
            $('input[name="role"]').prop('checked', false);
            radioInput.prop('checked', true);
            
            // Update visual selection
            $('.role-option').removeClass('selected');
            $(this).addClass('selected');
            
            // Show/hide role-specific sections
            if(role === 'Farmer'){
                $('#farmer-section').show();
                $('#aggregator-section').hide();
            } else if(role === 'Aggregator'){
                $('#farmer-section').hide();
                $('#aggregator-section').show();
            }
        });

        // Certificate fields toggle for farmers
        $('#hasCertificate').on('change', function(){
            $('#farmer-certificate-fields').toggle(this.checked);
        });

        // License fields toggle for aggregators
        $('#hasLicense').on('change', function(){
            $('#aggregator-license-fields').toggle(this.checked);
        });

        // Dzongkhag -> Gewog
        $('#dzo').on('change', function(){
            const dzo_id = $('#dzo option:selected').data('id');
            const $gewog = $('#gewog');
            
            if(dzo_id){
                $gewog.html('<option value="">Loading...</option>').prop('disabled', true);
                
                $.post('get_gewogs.php', { dzo_id: dzo_id }, function(html){
                    $gewog.html(html).prop('disabled', false);
                }).fail(function(){
                    $gewog.html('<option value="">Error loading Gewogs</option>').prop('disabled', false);
                    console.error('Failed to load gewogs');
                });
            } else {
                $gewog.html('<option value="">Select Gewog</option>');
            }
        });

        // Real-time validation for CID
        $('input[name="cid"]').on('input', function(){
            const value = this.value.replace(/\D/g,'');
            this.value = value.slice(0,11);
            
            const $input = $(this);
            const $error = $('#cid-error');
            
            if (value.length === 0) {
                $input.removeClass('is-invalid');
                $error.text('');
            } else if (value.length !== 11) {
                $input.addClass('is-invalid');
                $error.text('CID number must be exactly 11 digits');
            } else {
                $input.removeClass('is-invalid');
                $error.text('');
            }
        });

        // Real-time validation for Phone
        $('input[name="phone"]').on('input', function(){
            const value = this.value.replace(/\D/g,'');
            this.value = value.slice(0,8);
            
            const $input = $(this);
            const $error = $('#phone-error');
            
            if (value.length === 0) {
                $input.removeClass('is-invalid');
                $error.text('');
            } else if (value.length !== 8) {
                $input.addClass('is-invalid');
                $error.text('Phone number must be exactly 8 digits');
            } else if (!value.startsWith('17') && !value.startsWith('77')) {
                $input.addClass('is-invalid');
                $error.text('Phone number must start with 17 or 77');
            } else {
                $input.removeClass('is-invalid');
                $error.text('');
            }
        });

        // Real-time validation for password confirmation
        $('input[name="confirm_password"]').on('input', function(){
            const password = $('input[name="password"]').val();
            const confirmPassword = this.value;
            const $input = $(this);
            const $error = $('#password-error');
            
            if (confirmPassword.length === 0) {
                $input.removeClass('is-invalid');
                $error.text('');
            } else if (password !== confirmPassword) {
                $input.addClass('is-invalid');
                $error.text('Passwords do not match');
            } else {
                $input.removeClass('is-invalid');
                $error.text('');
            }
        });

        // Also check password confirmation when password changes
        $('input[name="password"]').on('input', function(){
            const confirmPassword = $('input[name="confirm_password"]').val();
            if (confirmPassword.length > 0) {
                $('input[name="confirm_password"]').trigger('input');
            }
        });

        // Form validation on submit
        $('#registerForm').on('submit', function(e){
            let isValid = true;
            let errors = [];
            
            // Clear previous validation states
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            // Role validation
            const role = $('input[name="role"]:checked').val();
            if(!role){
                errors.push('Please select a role');
                isValid = false;
            }
            
            // Name validation
            const name = $('input[name="name"]').val().trim();
            if (!name) {
                $('input[name="name"]').addClass('is-invalid');
                errors.push('Please enter your full name');
                isValid = false;
            }
            
            // CID validation
            const cid = $('input[name="cid"]').val();
            if (!cid) {
                $('input[name="cid"]').addClass('is-invalid');
                $('#cid-error').text('Please enter your CID number');
                errors.push('Please enter your CID number');
                isValid = false;
            } else if (cid.length !== 11 || !/^\d{11}$/.test(cid)) {
                $('input[name="cid"]').addClass('is-invalid');
                $('#cid-error').text('CID number must be exactly 11 digits');
                errors.push('CID number must be exactly 11 digits');
                isValid = false;
            }
            
            // Phone validation
            const phone = $('input[name="phone"]').val();
            if (!phone) {
                $('input[name="phone"]').addClass('is-invalid');
                $('#phone-error').text('Please enter your phone number');
                errors.push('Please enter your phone number');
                isValid = false;
            } else if (phone.length !== 8 || !/^\d{8}$/.test(phone)) {
                $('input[name="phone"]').addClass('is-invalid');
                $('#phone-error').text('Phone number must be exactly 8 digits');
                errors.push('Phone number must be exactly 8 digits');
                isValid = false;
            } else if (!phone.startsWith('17') && !phone.startsWith('77')) {
                $('input[name="phone"]').addClass('is-invalid');
                $('#phone-error').text('Phone number must start with 17 or 77');
                errors.push('Phone number must start with 17 or 77');
                isValid = false;
            }
            
            // Dzongkhag validation
            if (!$('#dzo').val()) {
                $('#dzo').addClass('is-invalid');
                errors.push('Please select a Dzongkhag');
                isValid = false;
            }
            
            // Gewog validation
            if (!$('#gewog').val()) {
                $('#gewog').addClass('is-invalid');
                errors.push('Please select a Gewog');
                isValid = false;
            }
            
            // Village validation
            const village = $('input[name="village"]').val().trim();
            if (!village) {
                $('input[name="village"]').addClass('is-invalid');
                errors.push('Please enter your village name');
                isValid = false;
            }
            
            // Password validation
            const password = $('input[name="password"]').val();
            const confirmPassword = $('input[name="confirm_password"]').val();
            
            if (!password) {
                $('input[name="password"]').addClass('is-invalid');
                errors.push('Please enter a password');
                isValid = false;
            } else if (password.length < 6) {
                $('input[name="password"]').addClass('is-invalid');
                errors.push('Password must be at least 6 characters long');
                isValid = false;
            }
            
            if (!confirmPassword) {
                $('input[name="confirm_password"]').addClass('is-invalid');
                $('#password-error').text('Please confirm your password');
                errors.push('Please confirm your password');
                isValid = false;
            } else if (password !== confirmPassword) {
                $('input[name="confirm_password"]').addClass('is-invalid');
                $('#password-error').text('Passwords do not match');
                errors.push('Passwords do not match');
                isValid = false;
            }
            
            // Role-specific validation
            if (role === 'Farmer' && $('#hasCertificate').is(':checked')) {
                if (!$('select[name="certificate_type"]').val()) {
                    $('select[name="certificate_type"]').addClass('is-invalid');
                    errors.push('Please select a certificate type');
                    isValid = false;
                }
                if (!$('input[name="certificate_no"]').val().trim()) {
                    $('input[name="certificate_no"]').addClass('is-invalid');
                    errors.push('Please enter your certificate number');
                    isValid = false;
                }
            }
            
            if (role === 'Aggregator' && $('#hasLicense').is(':checked')) {
                if (!$('input[name="business_name"]').val().trim()) {
                    $('input[name="business_name"]').addClass('is-invalid');
                    errors.push('Please enter your business name');
                    isValid = false;
                }
                if (!$('input[name="license_no"]').val().trim()) {
                    $('input[name="license_no"]').addClass('is-invalid');
                    errors.push('Please enter your license number');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Show error alert
                let errorHtml = '<div class="alert alert-danger">';
                if (errors.length === 1) {
                    errorHtml += errors[0];
                } else {
                    errorHtml += 'Please fix the following errors:<ul>';
                    errors.forEach(function(error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                }
                errorHtml += '</div>';
                
                // Remove existing alerts and add new one
                $('.alert').remove();
                $('.form-content').prepend(errorHtml);
                
                // Scroll to top of form
                $('.register-container')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                return false;
            }
        });

        // Initialize form sections based on existing POST data
        function initializeForm(){
            const selectedRole = $('input[name="role"]:checked').val();
            if(selectedRole){
                $(`.role-option[data-role="${selectedRole}"]`).addClass('selected');
                
                if(selectedRole === 'Farmer'){
                    $('#farmer-section').show();
                } else if(selectedRole === 'Aggregator'){
                    $('#aggregator-section').show();
                }
            }
        }
    });
    </script>
</body>
</html>