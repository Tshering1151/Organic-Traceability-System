<?php
require_once __DIR__ . '/database/db.php';

$message = $error = "";
$errors = [];
$debug_info = [];

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = trim($_POST['cid']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $debug_info[] = "Login attempt for CID: " . $cid . " with role: " . $role;

    // Validation
    if (empty($role)) {
        $errors[] = "Please select a role";
    }
    
    if (empty($cid)) {
        $errors[] = "Please enter your CID or username";
    }
    
    if (empty($password)) {
        $errors[] = "Please enter your password";
    }

    if (empty($errors)) {
        if ($role === 'Admin') {
            // Example: fixed admin login (or you can query an "admins" table)
            $admin_user = "admin";
            $admin_pass = password_hash("admin123", PASSWORD_BCRYPT); // set a real password

            if ($cid === $admin_user && password_verify($password, $admin_pass)) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['admin_username'] = $admin_user;
                $_SESSION['user_type'] = 'admin';

                // Temporary redirect - create Admin folder and dashboard later
                $errors[] = "Admin login successful but dashboard not created yet";
                header("Location: Admin/admin_dashborad.php");
                exit;
            } else {
                $errors[] = "Invalid Admin credentials";
            }
        } elseif ($role === 'Farmer') {
            // Fixed: Using farmer_id instead of id to match your table structure
            $stmt = mysqli_prepare($conn, "SELECT farmer_id, cid, name, password FROM farmer_tbl WHERE cid = ?");
            
            if (!$stmt) {
                $errors[] = "Database error: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, "s", $cid);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $farmer = mysqli_fetch_assoc($result);

                if ($farmer && password_verify($password, $farmer['password'])) {
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['farmer_id'] = $farmer['farmer_id']; // Using farmer_id from your table
                    $_SESSION['farmer_name'] = $farmer['name'];
                    $_SESSION['farmer_cid'] = $cid;
                    $_SESSION['user_type'] = 'farmer';

                    header("Location: Farmer/farmer_dashboard.php");
                    exit;
                } else {
                    $errors[] = "Invalid Farmer credentials";
                    $debug_info[] = "Farmer found: " . ($farmer ? "Yes" : "No");
                    if ($farmer) {
                        $debug_info[] = "Password check: " . (password_verify($password, $farmer['password']) ? "Pass" : "Fail");
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($role === 'Aggregator') {
            // Fixed: Added 'id' field to the SELECT query
            $stmt = mysqli_prepare($conn, "SELECT aggre_id, cid, name, password FROM aggre_tbl WHERE cid = ?");
            
            if (!$stmt) {
                $errors[] = "Database error: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, "s", $cid);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $aggregator = mysqli_fetch_assoc($result);

                if ($aggregator && password_verify($password, $aggregator['password'])) {
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['aggregator_id'] = $aggregator['aggre_id'];
                    $_SESSION['aggregator_name'] = $aggregator['name'];
                    $_SESSION['aggregator_cid'] = $cid;
                    $_SESSION['user_type'] = 'aggregator';

                    // Enable redirect to Aggregator dashboard
                    header("Location: Aggregator/aggregator_dashboard.php");
                    exit;
                } else {
                    $errors[] = "Invalid Aggregator credentials";
                    $debug_info[] = "Aggregator found: " . ($aggregator ? "Yes" : "No");
                    if ($aggregator) {
                        $debug_info[] = "Password check: " . (password_verify($password, $aggregator['password']) ? "Pass" : "Fail");
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $errors[] = "Invalid role selected";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Organic Traceability System</title>
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

        .login-container {
            max-width: 500px;
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

        /* Login Information Section */
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            background: white;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #dc3545;
        }

        /* Login Button */
        .login-btn-container {
            margin-top: 25px;
        }

        .login-btn-main {
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

        .login-btn-main:hover {
            background: #45a049;
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.9rem;
        }

        .register-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
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

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            font-size: 0.85rem;
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
            
            .login-container {
                margin: 20px;
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
        <div class="login-container">
            <!-- Form Header -->
            <div class="form-header">
                <h1><i class="fas fa-sign-in-alt"></i> Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <!-- Form Content -->
            <div class="form-content">
                <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php if(count($errors) === 1): ?>
                            <?= htmlspecialchars($errors[0]) ?>
                        <?php else: ?>
                            Please fix the following errors:
                            <ul style="margin: 8px 0 0 20px;">
                                <?php foreach($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if(!empty($debug_info)): ?>
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        <?php foreach($debug_info as $info): ?>
                            <?= htmlspecialchars($info) ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <!-- Role Selection -->
                    <div class="role-section">
                        <h3>Select Your Role</h3>
                        
                        <div class="role-option" data-role="Farmer">
                            <input type="radio" name="role" value="Farmer" id="role-farmer" <?= (isset($_POST['role']) && $_POST['role'] === 'Farmer') ? 'checked' : '' ?>>
                            <i class="fas fa-user-tie role-icon"></i>
                            <span class="role-text">Farmer - Agricultural Producer</span>
                        </div>
                        
                        <div class="role-option" data-role="Aggregator">
                            <input type="radio" name="role" value="Aggregator" id="role-aggregator" <?= (isset($_POST['role']) && $_POST['role'] === 'Aggregator') ? 'checked' : '' ?>>
                            <i class="fas fa-warehouse role-icon"></i>
                            <span class="role-text">Aggregator - Product Collector & Distributor</span>
                        </div>
                        
                        <div class="role-option" data-role="Admin">
                            <input type="radio" name="role" value="Admin" id="role-admin" <?= (isset($_POST['role']) && $_POST['role'] === 'Admin') ? 'checked' : '' ?>>
                            <i class="fas fa-user-shield role-icon"></i>
                            <span class="role-text">Admin - System Administrator</span>
                        </div>
                    </div>

                    <!-- Login Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-key"></i> Login Information</h3>
                        
                        <div class="form-group">
                            <label for="cid">CID / Username</label>
                            <input type="text" name="cid" id="cid" class="form-control" required 
                                   placeholder="Enter your CID or Admin username"
                                   value="<?= htmlspecialchars($_POST['cid'] ?? '') ?>">
                            <div class="invalid-feedback" id="cid-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required 
                                   placeholder="Enter your password">
                            <div class="invalid-feedback" id="password-error"></div>
                        </div>
                    </div>

                    <!-- Login Button -->
                    <div class="login-btn-container">
                        <button type="submit" name="login" class="login-btn-main">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>
                    </div>
                </form>

                <!-- Register Link -->
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
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
            
            // Update placeholder text based on role
            updatePlaceholderText(role);
        });

        // Update placeholder text based on selected role
        function updatePlaceholderText(role) {
            const cidInput = $('#cid');
            if (role === 'Admin') {
                cidInput.attr('placeholder', 'Enter admin username');
            } else if (role === 'Farmer') {
                cidInput.attr('placeholder', 'Enter your CID number');
            } else if (role === 'Aggregator') {
                cidInput.attr('placeholder', 'Enter your CID number');
            } else {
                cidInput.attr('placeholder', 'Enter your CID or username');
            }
        }

        // Form validation on submit
        $('#loginForm').on('submit', function(e){
            let isValid = true;
            let errors = [];
            
            // Clear previous validation states
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            // Role validation
            const role = $('input[name="role"]:checked').val();
            if(!role){
                errors.push('Please select a role');
                isValid = false;
            }
            
            // CID/Username validation
            const cid = $('#cid').val().trim();
            if (!cid) {
                $('#cid').addClass('is-invalid');
                $('#cid-error').text('Please enter your CID or username');
                errors.push('Please enter your CID or username');
                isValid = false;
            } else if (role === 'Farmer' || role === 'Aggregator') {
                // Validate CID format for Farmer and Aggregator
                if (cid.length !== 11 || !/^\d{11}$/.test(cid)) {
                    $('#cid').addClass('is-invalid');
                    $('#cid-error').text('CID must be exactly 11 digits');
                    errors.push('CID must be exactly 11 digits');
                    isValid = false;
                }
            }
            
            // Password validation
            const password = $('#password').val();
            if (!password) {
                $('#password').addClass('is-invalid');
                $('#password-error').text('Please enter your password');
                errors.push('Please enter your password');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Show error alert
                let errorHtml = '<div class="alert alert-danger">';
                if (errors.length === 1) {
                    errorHtml += errors[0];
                } else {
                    errorHtml += 'Please fix the following errors:<ul style="margin: 8px 0 0 20px;">';
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
                $('.login-container')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                return false;
            }
        });

        // Initialize form sections based on existing POST data
        function initializeForm(){
            const selectedRole = $('input[name="role"]:checked').val();
            if(selectedRole){
                $(`.role-option[data-role="${selectedRole}"]`).addClass('selected');
                updatePlaceholderText(selectedRole);
            }
        }

        // Input formatting for CID (only allow digits for Farmer/Aggregator)
        $('#cid').on('input', function(){
            const role = $('input[name="role"]:checked').val();
            if (role === 'Farmer' || role === 'Aggregator') {
                // Only allow digits and limit to 11 characters
                const value = this.value.replace(/\D/g,'');
                this.value = value.slice(0,11);
                
                // Real-time validation feedback
                const $input = $(this);
                const $error = $('#cid-error');
                
                if (value.length === 0) {
                    $input.removeClass('is-invalid');
                    $error.text('');
                } else if (value.length !== 11) {
                    $input.addClass('is-invalid');
                    $error.text('CID must be exactly 11 digits');
                } else {
                    $input.removeClass('is-invalid');
                    $error.text('');
                }
            }
        });

        // Clear validation when role changes
        $('input[name="role"]').on('change', function(){
            $('#cid').removeClass('is-invalid').val('');
            $('#cid-error').text('');
        });
    });
    </script>
</body>
</html>