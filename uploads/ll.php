<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organic Farmer Registration - DAMC</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary-color: #81c784;
            --accent-color: #a5d6a7;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --warning-color: #f39c12;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e8f5e8;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            --shadow-hover: 0 15px 40px rgba(46, 125, 50, 0.25);
            --border-radius: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
            background: linear-gradient(135deg, #a8e6cf 0%, #dcedc8 50%, #f1f8e9 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            animation: slideUp 0.8s ease-out;
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

        .registration-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 50%, #66bb6a 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            text-align: center;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .registration-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 15px,
                rgba(255,255,255,0.05) 15px,
                rgba(255,255,255,0.05) 30px
            );
            animation: float 25s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .registration-header h1 {
            margin-bottom: 0.8rem;
            font-weight: 800;
            font-size: 2.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .registration-header p {
            margin-bottom: 1rem;
            opacity: 0.95;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .organic-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
        }

        .registration-card {
            background: var(--white);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .form-container {
            padding: 3rem 2.5rem;
        }

        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInDown 0.5s ease-out;
            border: none;
        }

        @keyframes fadeInDown {
            from { 
                opacity: 0; 
                transform: translateY(-20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 5px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 5px solid var(--error-color);
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.4rem;
            font-weight: 700;
            margin: 2rem 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-light), transparent);
            margin-left: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .input-group {
            position: relative;
            display: flex;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            background: linear-gradient(135deg, var(--primary-light), #66bb6a);
            border: none;
            color: white;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px 0 0 12px;
            min-width: 50px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .form-floating {
            position: relative;
            flex: 1;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid var(--border-color);
            border-radius: 0 12px 12px 0;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            background-color: var(--bg-light);
            color: var(--text-dark);
            border-left: none;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-light);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
            transform: translateY(-1px);
        }

        .form-floating label {
            position: absolute;
            top: 1rem;
            left: 1.2rem;
            font-size: 1rem;
            color: var(--text-light);
            pointer-events: none;
            transition: var(--transition);
            background: transparent;
            font-weight: 500;
        }

        .form-control:focus ~ label,
        .form-control:not(:placeholder-shown) ~ label {
            top: -0.5rem;
            left: 1rem;
            font-size: 0.85rem;
            color: var(--primary-light);
            background: var(--white);
            padding: 0 0.5rem;
            font-weight: 600;
        }

        .form-control.error, .form-select.error {
            border-color: var(--error-color);
            background-color: #fff5f5;
        }

        .form-control.success, .form-select.success {
            border-color: var(--success-color);
            background-color: #f0fff4;
        }

        .validation-message {
            margin-top: 0.5rem;
            font-size: 0.875rem;
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

        .file-upload-wrapper {
            position: relative;
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 2.5rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 3px dashed transparent;
            overflow: hidden;
        }

        .file-upload-wrapper::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, rgba(255,255,255,0.3), transparent, rgba(255,255,255,0.3));
            border-radius: var(--border-radius);
            z-index: -1;
            opacity: 0;
            transition: var(--transition);
        }

        .file-upload-wrapper:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .file-upload-wrapper:hover::before {
            opacity: 1;
        }

        .file-upload-wrapper.dragover {
            border-color: white;
            background: linear-gradient(135deg, #0984e3 0%, #74b9ff 100%);
            transform: scale(1.02);
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .file-upload-icon {
            font-size: 3rem;
            opacity: 0.9;
        }

        .file-upload-text {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .file-upload-hint {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .selected-file {
            margin-top: 1rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            border: 2px solid var(--success-color);
            border-radius: 12px;
            color: #155724;
            font-weight: 600;
            display: none;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateX(-20px); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }

        .password-strength {
            margin-top: 0.8rem;
            display: none;
        }

        .strength-bar {
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: var(--transition);
            border-radius: 2px;
        }

        .strength-weak { background: linear-gradient(90deg, var(--error-color), #ff6b6b); }
        .strength-medium { background: linear-gradient(90deg, var(--warning-color), #ffd93d); }
        .strength-strong { background: linear-gradient(90deg, var(--success-color), #6bcf7f); }

        .strength-text {
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            min-width: 180px;
            justify-content: center;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-light) 0%, #66bb6a 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .btn-register:hover {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
        }

        .btn-reset {
            background: linear-gradient(135deg, #ff7043 0%, #ff8a65 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(255, 112, 67, 0.4);
        }

        .btn-reset:hover {
            background: linear-gradient(135deg, #f4511e 0%, #ff7043 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 112, 67, 0.5);
        }

        .btn:disabled {
            background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading-spinner {
            display: none;
            margin-left: 10px;
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: inherit;
            cursor: pointer;
            padding: 0.2rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .close-btn:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .registration-header {
                padding: 2rem 1.5rem;
            }

            .registration-header h1 {
                font-size: 2rem;
            }

            .form-container {
                padding: 2rem 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .btn-group {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .registration-header h1 {
                font-size: 1.8rem;
            }

            .form-container {
                padding: 1.5rem 1rem;
            }

            .input-group {
                flex-direction: column;
            }

            .input-icon {
                border-radius: 12px 12px 0 0;
                padding: 0.8rem;
            }

            .form-control, .form-select {
                border-radius: 0 0 12px 12px;
                border-left: 2px solid var(--border-color);
                border-top: none;
            }
        }

        /* Additional animations */
        .form-group {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="registration-header">
            <h1><i class="fas fa-seedling"></i> Organic Farmer Registration</h1>
            <p>Department of Agricultural Marketing & Cooperatives</p>
            <div class="organic-badge">
                <i class="fas fa-shield-check"></i> Certified Organic Tracing System
            </div>
        </div>

        <!-- Registration Card -->
        <div class="registration-card">
            <div class="form-container">
                <!-- Demo Messages (Replace with PHP) -->
                <div class="alert alert-success" style="display: none;" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    <span>Registration successful! Welcome to the Organic Tracing System.</span>
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="alert alert-danger" style="display: none;" id="errorMessage">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Please correct the errors below and try again.</span>
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Registration Form -->
                <form method="POST" enctype="multipart/form-data" id="registrationForm">
                    <!-- Personal Information Section -->
                    <h4 class="section-title">
                        <i class="fas fa-user-circle"></i> Personal Information
                    </h4>

                    <div class="form-grid">
                        <!-- CID -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-id-card"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="cid" id="cid" 
                                           placeholder=" " required maxlength="11">
                                    <label for="cid">Citizenship ID (CID)</label>
                                </div>
                            </div>
                            <div class="validation-message" id="cid-message"></div>
                        </div>

                        <!-- Full Name -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-user"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="name" id="name" 
                                           placeholder=" " required>
                                    <label for="name">Full Name</label>
                                </div>
                            </div>
                            <div class="validation-message" id="name-message"></div>
                        </div>

                        <!-- Phone Number -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="tel" class="form-control" name="phone_no" id="phone_no" 
                                           placeholder=" " required maxlength="8">
                                    <label for="phone_no">Phone Number</label>
                                </div>
                            </div>
                            <div class="validation-message" id="phone-message"></div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="password" class="form-control" name="password" id="password" 
                                           placeholder=" " required minlength="6">
                                    <label for="password">Password</label>
                                </div>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                            </div>
                            <div class="validation-message" id="password-message"></div>
                        </div>
                    </div>

                    <!-- Location Section -->
                    <h4 class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Location Details
                    </h4>

                    <div class="form-grid">
                        <!-- Dzongkhag -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-building"></i>
                                </span>
                                <select name="dzo_name" id="dzo" class="form-select" required>
                                    <option value="">Select Dzongkhag</option>
                                    <option value="Thimphu" data-id="1">Thimphu</option>
                                    <option value="Paro" data-id="2">Paro</option>
                                    <option value="Punakha" data-id="3">Punakha</option>
                                    <option value="Wangdue" data-id="4">Wangdué Phodrang</option>
                                    <option value="Chhukha" data-id="5">Chhukha</option>
                                </select>
                                <div class="loading-spinner">
                                    <div class="spinner-border" role="status"></div>
                                </div>
                            </div>
                            <div class="validation-message" id="dzo-message"></div>
                        </div>

                        <!-- Gewog -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-map"></i>
                                </span>
                                <select name="gewog_name" id="gewog" class="form-select" required>
                                    <option value="">Select Gewog</option>
                                </select>
                            </div>
                            <div class="validation-message" id="gewog-message"></div>
                        </div>

                        <!-- Village -->
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-home"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="village" id="village" 
                                           placeholder=" " required>
                                    <label for="village">Village</label>
                                </div>
                            </div>
                            <div class="validation-message" id="village-message"></div>
                        </div>
                    </div>

                    <!-- Certificate Upload -->
                    <h4 class="section-title">
                        <i class="fas fa-certificate"></i> Organic Certificate
                    </h4>

                    <div class="form-group full-width">
                        <div class="file-upload-wrapper" id="fileUpload">
                            <input type="file" name="certificate" id="certificate" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="file-upload-content">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">
                                    Click to upload or drag and drop your certificate
                                </div>
                                <div class="file-upload-hint">
                                    Supported formats: PDF, JPG, PNG (Max 5MB)
                                </div>
                            </div>
                        </div>
                        <div class="selected-file" id="selectedFile"></div>
                        <div class="validation-message" id="certificate-message"></div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="btn-group">
                        <button type="submit" class="btn btn-register" id="submitBtn">
                            <i class="fas fa-check-circle"></i> Register Now
                        </button>
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-undo-alt"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            // CID validation (11 digits only)
            $('#cid').on('input', function(){
                let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
                $(this).val(value);
                
                if (value.length === 0) {
                    $(this).removeClass('error success');
                    hideMessage('cid-message');
                } else if (value.length === 11) {
                    $(this).removeClass('error').addClass('success');
                    showMessage('cid-message', 'Valid CID format', 'success');
                } else {
                    $(this).removeClass('success').addClass('error');
                    showMessage('cid-message', 'CID must be exactly 11 digits', 'error');
                }
            });

            // Phone validation (8 digits, starting with 17 or 77)
            $('#phone_no').on('input', function(){
                let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
                $(this).val(value);
                
                const phoneRegex = /^(17|77)\d{6}$/;
                
                if (value.length === 0) {
                    $(this).removeClass('error success');
                    hideMessage('phone-message');
                } else if (phoneRegex.test(value)) {
                    $(this).removeClass('error').addClass('success');
                    showMessage('phone-message', 'Valid phone number', 'success');
                } else {
                    $(this).removeClass('success').addClass('error');
                    showMessage('phone-message', 'Phone must start with 17 or 77 and be 8 digits', 'error');
                }
            });

            // Name validation
            $('#name').on('input', function(){
                const name = $(this).val().trim();
                
                if (name.length === 0) {
                    $(this).removeClass('error success');
                    hideMessage('name-message');
                } else if (name.length >= 2) {
                    $(this).removeClass('error').addClass('success');
                    showMessage('name-message', 'Name looks good', 'success');
                } else {
                    $(this).removeClass('success').addClass('error');
                    showMessage('name-message', 'Name must be at least 2 characters', 'error');
                }
            });

            // Password strength validation
            $('#password').on('input', function(){
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                updatePasswordStrength(strength);
                
                if (password.length === 0) {
                    $(this).removeClass('error success');
                    hideMessage('password-message');
                    $('#passwordStrength').hide();
                } else if (password.length >= 6) {
                    $(this).removeClass('error').addClass('success');
                    showMessage('password-message', 'Password meets requirements', 'success');
                    $('#passwordStrength').show();
                } else {
                    $(this).removeClass('success').addClass('error');
                    showMessage('password-message', 'Password must be at least 6 characters', 'error');
                    $('#passwordStrength').show();
                }
            });

            // Village validation
            $('#village').on('input', function(){
                const village = $(this).val().trim();
                
                if (village.length === 0) {
                    $(this).removeClass('error success');
                    hideMessage('village-message');
                } else if (village.length >= 2) {
                    $(this).removeClass('error').addClass('success');
                    showMessage('village-message', 'Village name looks good', 'success');
                } else {
                    $(this).removeClass('success').addClass('error');
                    showMessage('village-message', 'Village name must be at least 2 characters', 'error');
                }
            });

            // Dzongkhag change handler
            $('#dzo').change(function(){
                const dzo_id = $(this).find(':selected').data('id');
                const gewogSelect = $('#gewog');
                const loadingSpinner = $('.loading-spinner');
                
                if (dzo_id) {
                    loadingSpinner.show();
                    gewogSelect.prop('disabled', true);
                    
                    // Simulate AJAX call with sample data
                    setTimeout(function() {
                        const gewogOptions = {
                            1: [
                                {id: 1, name: 'Chang Gewog'},
                                {id: 2, name: 'Dagana Gewog'},
                                {id: 3, name: 'Genye Gewog'}
                            ],
                            2: [
                                {id: 4, name: 'Shaba Gewog'},
                                {id: 5, name: 'Dogar Gewog'},
                                {id: 6, name: 'Lamgong Gewog'}
                            ],
                            3: [
                                {id: 7, name: 'Kabesa Gewog'},
                                {id: 8, name: 'Toeb Gewog'},
                                {id: 9, name: 'Dzomi Gewog'}
                            ],
                            4: [
                                {id: 10, name: 'Athang Gewog'},
                                {id: 11, name: 'Bjena Gewog'},
                                {id: 12, name: 'Dangchu Gewog'}
                            ],
                            5: [
                                {id: 13, name: 'Bongo Gewog'},
                                {id: 14, name: 'Chapcha Gewog'},
                                {id: 15, name: 'Darla Gewog'}
                            ]
                        };
                        
                        let options = '<option value="">Select Gewog</option>';
                        if (gewogOptions[dzo_id]) {
                            gewogOptions[dzo_id].forEach(function(gewog) {
                                options += `<option value="${gewog.name}">${gewog.name}</option>`;
                            });
                        }
                        
                        gewogSelect.html(options);
                        gewogSelect.prop('disabled', false);
                        loadingSpinner.hide();
                        
                        $(this).removeClass('error').addClass('success');
                        showMessage('dzo-message', 'Dzongkhag selected', 'success');
                    }, 1000);
                } else {
                    gewogSelect.html('<option value="">Select Gewog</option>');
                    $(this).removeClass('error success');
                    hideMessage('dzo-message');
                }
            });

            // Gewog change handler
            $('#gewog').change(function(){
                if ($(this).val()) {
                    $(this).removeClass('error').addClass('success');
                    showMessage('gewog-message', 'Gewog selected', 'success');
                } else {
                    $(this).removeClass('error success');
                    hideMessage('gewog-message');
                }
            });

            // File upload handling
            const fileInput = $('#certificate');
            const fileUpload = $('#fileUpload');
            const selectedFile = $('#selectedFile');

            fileInput.on('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileSize = file.size / 1024 / 1024; // Convert to MB
                    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                    
                    if (!allowedTypes.includes(file.type)) {
                        showMessage('certificate-message', 'Please select a valid file (PDF, JPG, PNG)', 'error');
                        $(this).removeClass('success').addClass('error');
                        selectedFile.hide();
                        return;
                    }
                    
                    if (fileSize > 5) {
                        showMessage('certificate-message', 'File size must be less than 5MB', 'error');
                        $(this).removeClass('success').addClass('error');
                        selectedFile.hide();
                        return;
                    }
                    
                    selectedFile.html(`
                        <i class="fas fa-file-${file.type.includes('pdf') ? 'pdf' : 'image'}"></i> 
                        ${file.name} (${fileSize.toFixed(2)} MB)
                    `).show();
                    showMessage('certificate-message', 'File selected successfully', 'success');
                    $(this).removeClass('error').addClass('success');
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

            fileUpload.on('click', function(e) {
                if (e.target === this || $(e.target).closest('.file-upload-content').length) {
                    fileInput.click();
                }
            });

            // Form submission validation
            $('#registrationForm').on('submit', function(e) {
                let isValid = true;
                const submitBtn = $('#submitBtn');

                // Validate all required fields
                const validations = [
                    {field: '#cid', regex: /^\d{11}$/, message: 'CID must be exactly 11 digits'},
                    {field: '#phone_no', regex: /^(17|77)\d{6}$/, message: 'Phone must start with 17 or 77 and be 8 digits'},
                    {field: '#name', test: val => val.trim().length >= 2, message: 'Name must be at least 2 characters'},
                    {field: '#password', test: val => val.length >= 6, message: 'Password must be at least 6 characters'},
                    {field: '#village', test: val => val.trim().length >= 2, message: 'Village must be at least 2 characters'},
                    {field: '#dzo', test: val => val !== '', message: 'Please select a Dzongkhag'},
                    {field: '#gewog', test: val => val !== '', message: 'Please select a Gewog'}
                ];

                validations.forEach(function(validation) {
                    const field = $(validation.field);
                    const value = field.val();
                    let isFieldValid = false;

                    if (validation.regex) {
                        isFieldValid = validation.regex.test(value);
                    } else if (validation.test) {
                        isFieldValid = validation.test(value);
                    }

                    if (!isFieldValid) {
                        const messageId = field.attr('id') + '-message';
                        showMessage(messageId, validation.message, 'error');
                        field.addClass('error').removeClass('success');
                        isValid = false;
                    }
                });

                // File validation
                const fileInput = document.getElementById('certificate');
                if (!fileInput.files[0]) {
                    showMessage('certificate-message', 'Please select a certificate file', 'error');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    $('#errorMessage').show();
                    // Scroll to first error
                    const firstError = $('.error').first();
                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 100
                        }, 500);
                    }
                } else {
                    // Show loading state
                    submitBtn.prop('disabled', true)
                             .html('<i class="fas fa-spinner fa-spin"></i> Registering...');
                    
                    // For demo purposes, show success message after 2 seconds
                    setTimeout(function() {
                        e.preventDefault();
                        $('#errorMessage').hide();
                        $('#successMessage').show();
                        submitBtn.prop('disabled', false)
                                 .html('<i class="fas fa-check-circle"></i> Register Now');
                        $('html, body').animate({scrollTop: 0}, 500);
                    }, 2000);
                }
            });

            // Reset form handler
            $('.btn-reset').on('click', function() {
                $('.form-control, .form-select').removeClass('error success');
                $('.validation-message').removeClass('show');
                $('.alert').hide();
                $('#selectedFile').hide();
                $('#passwordStrength').hide();
                $('#gewog').html('<option value="">Select Gewog</option>');
            });

            // Utility functions
            function showMessage(id, message, type) {
                const messageEl = $('#' + id);
                const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
                messageEl.html(`<i class="${icon}"></i> ${message}`)
                         .removeClass('error success')
                         .addClass(type + ' show');
            }

            function hideMessage(id) {
                $('#' + id).removeClass('show');
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

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 8000);

            // Add smooth focus transitions
            $('.form-control, .form-select').on('focus', function() {
                $(this).parent().addClass('focused');
            }).on('blur', function() {
                $(this).parent().removeClass('focused');
            });
        });
    </script>
</body>
</html>