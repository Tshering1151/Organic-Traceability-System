<?php
include '../database/db.php';

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cid        = $_POST['cid'];
    $name       = $_POST['name'];
    $phone_no   = $_POST['phone_no'];
    $dzo_name   = $_POST['dzo_name'];
    $gewog_name = $_POST['gewog_name'];
    $village    = $_POST['village'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle certificate upload
    $certificate = "";
    if (!empty($_FILES['certificate']['name'])) {
        $targetDir = "../uploads/certificates/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['certificate']['name']);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetFilePath)) {
            $certificate = $fileName;
        } else {
            $message = "Error uploading certificate.";
        }
    }

    if ($certificate != "") {
        $sql = "INSERT INTO user_tbl (cid, name, phone_no, dzo_name, gewog_name, village, certificate, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $cid, $name, $phone_no, $dzo_name, $gewog_name, $village, $certificate, $password);

        if ($stmt->execute()) {
            $message = "✅ Registration successful!";
        } else {
            $message = "❌ Error: " . $conn->error;
        }
    }
}

// Fetch Dzongkhags
$dzoQuery = $conn->query("SELECT dzo_id, dzo_name FROM dzo_tbl ORDER BY dzo_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Registration - Organic Farming Community</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            --error-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e1e8ed;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.6;
        }

        .registration-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            animation: slideUp 0.8s ease-out;
            position: relative;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            background: var(--success-gradient);
            color: var(--white);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.05) 10px,
                rgba(255,255,255,0.05) 20px
            );
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .form-header h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .form-header p {
            font-size: 1.2rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        .form-content {
            padding: 50px 40px;
            background: var(--white);
        }

        .message {
            padding: 18px 24px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid var(--success-color);
            color: #155724;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 2px solid var(--error-color);
            color: #721c24;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--text-light);
            width: 16px;
        }

        .required {
            color: var(--error-color);
            font-size: 1.2em;
        }

        .form-group input,
        .form-group select {
            padding: 16px 20px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1.05rem;
            transition: var(--transition);
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background-color: var(--white);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-group input.error,
        .form-group select.error {
            border-color: var(--error-color);
            background-color: #fff5f5;
        }

        .form-group input.success,
        .form-group select.success {
            border-color: var(--success-color);
            background-color: #f0fff4;
        }

        .validation-message {
            margin-top: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            opacity: 0;
            transform: translateY(-5px);
            transition: var(--transition);
        }

        .validation-message.show {
            opacity: 1;
            transform: translateY(0);
        }

        .validation-message.error {
            color: var(--error-color);
        }

        .validation-message.success {
            color: var(--success-color);
        }

        .file-upload-container {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: var(--white);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            padding: 20px;
            border: 3px dashed transparent;
        }

        .file-upload-wrapper:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .file-upload-wrapper.dragover {
            border-color: var(--white);
            background: linear-gradient(135deg, #0984e3 0%, #74b9ff 100%);
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        .file-upload-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
        }

        .selected-file {
            margin-top: 15px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            border: 2px solid var(--success-color);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            color: #155724;
            display: none;
            font-weight: 600;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .password-strength {
            margin-top: 8px;
            display: none;
        }

        .strength-bar {
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: var(--transition);
            border-radius: 2px;
        }

        .strength-weak { background-color: var(--error-color); }
        .strength-medium { background-color: var(--warning-color); }
        .strength-strong { background-color: var(--success-color); }

        .strength-text {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .submit-btn {
            background: var(--primary-gradient);
            color: var(--white);
            padding: 18px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .login-link {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: var(--bg-light);
            border-radius: var(--border-radius);
            color: var(--text-light);
            font-weight: 500;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .loading {
            display: none;
            text-align: center;
            color: #667eea;
            font-style: italic;
            margin: 20px 0;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #667eea;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .form-header {
                padding: 30px 20px;
            }

            .form-header h1 {
                font-size: 2.2rem;
            }

            .form-content {
                padding: 30px 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .form-header h1 {
                font-size: 1.9rem;
            }

            .form-content {
                padding: 25px 20px;
            }

            .form-group input,
            .form-group select {
                padding: 14px 16px;
            }
        }
    </style>
</head>
<body>

<div class="registration-container">
    <div class="form-header">
        <h1><i class="fas fa-seedling"></i> Farmer Registration</h1>
        <p>Join our organic farming community and grow sustainably</p>
    </div>
    
    <div class="form-content">
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <i class="fas <?php echo strpos($message, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="registrationForm">
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> CID Number <span class="required">*</span></label>
                    <input type="text" id="cid" name="cid" 
                        value="<?php echo isset($cid) ? htmlspecialchars($cid) : ''; ?>" 
                        placeholder="Enter 11-digit CID" 
                        maxlength="11" required>
                    <div class="validation-message" id="cid-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" 
                        value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                        placeholder="Enter your full name" required>
                    <div class="validation-message" id="name-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone_no" name="phone_no" 
                        value="<?php echo isset($phone_no) ? htmlspecialchars($phone_no) : ''; ?>" 
                        placeholder="17XXXXXX or 77XXXXXX" 
                        maxlength="8" required>
                    <div class="validation-message" id="phone-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Dzongkhag <span class="required">*</span></label>
                    <select name="dzo_name" id="dzongkhag" required>
                        <option value="">--Select Dzongkhag--</option>
                        <?php while($row = $dzoQuery->fetch_assoc()): ?>
                            <option value="<?php echo $row['dzo_name']; ?>" data-id="<?php echo $row['dzo_id']; ?>">
                                <?php echo $row['dzo_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="validation-message" id="dzongkhag-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map"></i> Gewog <span class="required">*</span></label>
                    <select name="gewog_name" id="gewog" required>
                        <option value="">--Select Gewog--</option>
                    </select>
                    <div class="validation-message" id="gewog-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Village <span class="required">*</span></label>
                    <input type="text" name="village" id="village" 
                        placeholder="Enter your village name" required>
                    <div class="validation-message" id="village-message"></div>
                </div>
                
                <div class="form-group full-width">
                    <label><i class="fas fa-certificate"></i> Certificate (PDF/JPG/PNG) <span class="required">*</span></label>
                    <div class="file-upload-container">
                        <div class="file-upload-wrapper" id="fileUpload">
                            <input type="file" name="certificate" id="certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="file-upload-text">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload or drag and drop</span>
                            </div>
                            <div class="file-info">Maximum file size: 5MB • Supported formats: PDF, JPG, PNG</div>
                        </div>
                        <div class="selected-file" id="selectedFile"></div>
                    </div>
                    <div class="validation-message" id="certificate-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter password (min 6 characters)" 
                           minlength="6" required>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                    <div class="validation-message" id="password-message"></div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter password" 
                           minlength="6" required>
                    <div class="validation-message" id="confirm-password-message"></div>
                </div>
            </div>
            
            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-seedling"></i> Register as Farmer
            </button>
            
            <div class="login-link">
                Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login here</a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){
    // Phone validation
    $('#phone_no').on('input', function(){
        const phone = $(this).val();
        const phoneRegex = /^(17|77)\d{6}$/;
        const messageEl = $('#phone-message');
        
        if (phone.length === 0) {
            $(this).removeClass('error success');
            messageEl.removeClass('show');
        } else if (phoneRegex.test(phone)) {
            $(this).removeClass('error').addClass('success');
            showMessage('phone-message', 'Valid phone number', 'success');
        } else {
            $(this).removeClass('success').addClass('error');
            showMessage('phone-message', 'Phone number must start with 17 or 77 and be 8 digits long', 'error');
        }
    });
    
    // CID validation
    $('#cid').on('input', function(){
        const cid = $(this).val();
        const cidRegex = /^\d{11}$/;
        
        if (cid.length === 0) {
            $(this).removeClass('error success');
            $('#cid-message').removeClass('show');
        } else if (cidRegex.test(cid)) {
            $(this).removeClass('error').addClass('success');
            showMessage('cid-message', 'Valid CID format', 'success');
        } else {
            $(this).removeClass('success').addClass('error');
            showMessage('cid-message', 'CID must be exactly 11 digits', 'error');
        }
    });
    
    // Name validation
    $('#name').on('input', function(){
        const name = $(this).val().trim();
        
        if (name.length === 0) {
            $(this).removeClass('error success');
            $('#name-message').removeClass('show');
        } else if (name.length >= 2) {
            $(this).removeClass('error').addClass('success');
            showMessage('name-message', 'Name looks good', 'success');
        } else {
            $(this).removeClass('success').addClass('error');
            showMessage('name-message', 'Name must be at least 2 characters long', 'error');
        }
    });
    
    // Password strength checker
    $('#password').on('input', function(){
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updatePasswordStrength(strength);
        
        if (password.length === 0) {
            $(this).removeClass('error success');
            $('#password-message').removeClass('show');
            $('#passwordStrength').hide();
        } else if (password.length >= 6) {
            $(this).removeClass('error').addClass('success');
            showMessage('password-message', 'Password meets minimum requirements', 'success');
            $('#passwordStrength').show();
        } else {
            $(this).removeClass('success').addClass('error');
            showMessage('password-message', 'Password must be at least 6 characters long', 'error');
            $('#passwordStrength').show();
        }
        
        // Check confirm password match
        checkPasswordMatch();
    });
    
    // Confirm password validation
    $('#confirm_password').on('input', function(){
        checkPasswordMatch();
    });
    
    function checkPasswordMatch() {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        const confirmEl = $('#confirm_password');
        
        if (confirmPassword.length === 0) {
            confirmEl.removeClass('error success');
            $('#confirm-password-message').removeClass('show');
        } else if (password === confirmPassword) {
            confirmEl.removeClass('error').addClass('success');
            showMessage('confirm-password-message', 'Passwords match', 'success');
        } else {
            confirmEl.removeClass('success').addClass('error');
            showMessage('confirm-password-message', 'Passwords do not match', 'error');
        }
    }
    
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/\d/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        return strength;
    }
    
    function updatePasswordStrength(strength) {
        const fill = $('#strengthFill');
        const text = $('#strengthText');
        
        switch(strength) {
            case 0:
            case 1:
                fill.css('width', '25%').removeClass().addClass('strength-fill strength-weak');
                text.text('Weak').css('color', '#e74c3c');
                break;
            case 2:
            case 3:
                fill.css('width', '60%').removeClass().addClass('strength-fill strength-medium');
                text.text('Medium').css('color', '#f39c12');
                break;
            case 4:
            case 5:
                fill.css('width', '100%').removeClass().addClass('strength-fill strength-strong');
                text.text('Strong').css('color', '#27ae60');
                break;
        }
    }
    
    function showMessage(id, message, type) {
        const messageEl = $('#' + id);
        const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        messageEl.html(`<i class="${icon}"></i> ${message}`)
               .removeClass('error success')
               .addClass(type + ' show');
    }
    
    // File upload handling
    const fileInput = $('#certificate');
    const fileUpload = $('#fileUpload');
    const selectedFile = $('#selectedFile');
    
    fileInput.on('change', function() {
        const file = this.files[0];
        if (file) {
            const fileSize = file.size / 1024 / 1024; // Convert to MB
            if (fileSize > 5) {
                showMessage('certificate-message', 'File size must be less than 5MB', 'error');
                $(this).removeClass('success').addClass('error');
                selectedFile.hide();
            } else {
                selectedFile.html(`<i class="fas fa-file"></i> ${file.name} (${fileSize.toFixed(2)} MB)`).show();
                showMessage('certificate-message', 'File selected successfully', 'success');
                $(this).removeClass('error').addClass('success');
            }
        }
    });
    
    // Drag and drop functionality
    fileUpload.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    fileUpload.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    fileUpload.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            fileInput.trigger('change');
        }
    });
    
    fileUpload.on('click', function() {
        fileInput.click();
    });
    
    // Dzongkhag change handler
    $('#dzongkhag').change(function(){
        var dzo_id = $(this).find(':selected').data('id');
        if(dzo_id){
            $.ajax({
                type:'POST',
                url:'get_gewogs.php',
                data:{dzo_id:dzo_id},
                success:function(html){
                    $('#gewog').html(html);
                    showMessage('dzongkhag-message', 'Dzongkhag selected', 'success');
                    $(this).removeClass('error').addClass('success');
                }
            });
        } else {
            $('#gewog').html('<option value="">--Select Gewog--</option>');
            $('#dzongkhag-message').removeClass('show');
            $(this).removeClass('error success');
        }
    });
    
    // Gewog change handler
    $('#gewog').change(function(){
        if($(this).val()) {
            showMessage('gewog-message', 'Gewog selected', 'success');
            $(this).removeClass('error').addClass('success');
        } else {
            $('#gewog-message').removeClass('show');
            $(this).removeClass('error success');
        }
    });
    
    // Village validation
    $('#village').on('input', function(){
        const village = $(this).val().trim();
        
        if (village.length === 0) {
            $(this).removeClass('error success');
            $('#village-message').removeClass('show');
        } else if (village.length >= 2) {
            $(this).removeClass('error').addClass('success');
            showMessage('village-message', 'Village name looks good', 'success');
        } else {
            $(this).removeClass('success').addClass('error');
            showMessage('village-message', 'Village name must be at least 2 characters', 'error');
        }
    });
    
    // Form submission validation
    $('#registrationForm').on('submit', function(e) {
        let isValid = true;
        const submitBtn = $('#submitBtn');
        
        // Phone validation
        const phone = $('#phone_no').val();
        const phoneRegex = /^(17|77)\d{6}$/;
        if (!phoneRegex.test(phone)) {
            showMessage('phone-message', 'Please enter a valid phone number starting with 17 or 77', 'error');
            $('#phone_no').addClass('error');
            isValid = false;
        }
        
        // CID validation
        const cid = $('#cid').val();
        const cidRegex = /^\d{11}$/;
        if (!cidRegex.test(cid)) {
            showMessage('cid-message', 'Please enter a valid 11-digit CID', 'error');
            $('#cid').addClass('error');
            isValid = false;
        }
        
        // Password match validation
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        if (password !== confirmPassword) {
            showMessage('confirm-password-message', 'Passwords do not match', 'error');
            $('#confirm_password').addClass('error');
            isValid = false;
        }
        
        // File validation
        const fileInput = document.getElementById('certificate');
        if (!fileInput.files[0]) {
            showMessage('certificate-message', 'Please select a certificate file', 'error');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.error').first().offset().top - 100
            }, 500);
        } else {
            // Show loading state
            submitBtn.prop('disabled', true)
                     .html('<i class="fas fa-spinner fa-spin"></i> Registering...');
        }
    });
    
    // Auto-format CID input (numbers only)
    $('#cid').on('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    
    // Auto-format phone input (numbers only)
    $('#phone_no').on('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    
    // Real-time validation feedback
    $('input[required], select[required]').on('blur', function() {
        if (!$(this).val()) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    // Remove error class on focus
    $('input, select').on('focus', function() {
        $(this).removeClass('error');
    });
});

// Additional utility functions
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}