<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organic Traceability System</title>
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
            /* Replace this with actual image: */
            /* background-image: url('path/to/your/logo.png'); */
            /* background-size: contain; */
            /* background-repeat: no-repeat; */
            /* background-position: center; */
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
        
        /* Main Content Styles */
        main {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .content-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .content-section h1 {
            color: #2d5016;
            margin-bottom: 20px;
            font-size: 2.8rem;
        }
        
        .content-section p {
            font-size: 1.2rem;
            color: #4a7c59;
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(45, 80, 22, 0.1);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            border-color: #8bc34a;
            box-shadow: 0 8px 30px rgba(45, 80, 22, 0.15);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8bc34a, #68a978);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
        }
        
        .feature-card h3 {
            color: #2d5016;
            margin-bottom: 15px;
            font-size: 1.4rem;
            text-align: center;
        }
        
        .feature-card p {
            color: #4a7c59;
            font-size: 1rem;
            text-align: center;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #68a978, #8bc34a);
            padding: 50px 40px;
            border-radius: 15px;
            margin-top: 50px;
            color: white;
            text-align: center;
        }
        
        .cta-section h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 2.2rem;
        }
        
        .cta-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .cta-btn {
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: white;
            color: #2d5016;
            border: 2px solid white;
        }
        
        .btn-primary:hover {
            background-color: transparent;
            color: white;
        }
        
        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background-color: white;
            color: #2d5016;
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
            
            .content-section h1 {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo-section">
                <!-- <img src="../images/logo_left.jpg" alt="Header Left Image" class="img-fluid header-img"> -->
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
        <div class="content-section">
            <h1>Organic Traceability System</h1>
            <p>Track your organic products from farm to table. Ensure authenticity, build trust, and maintain the integrity of your organic supply chain with our comprehensive traceability solution.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🌾</div>
                <h3>Farm-to-Fork Tracking</h3>
                <p>Complete visibility of your organic products through every stage of the supply chain. Track from seed to harvest, processing to packaging, and distribution to consumer.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Instant Verification</h3>
                <p>Quick QR code scanning and digital certificates ensure authentic organic products. Consumers can verify the organic status and journey of their purchases instantly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Compliance Management</h3>
                <p>Automated compliance tracking with organic certification standards. Generate reports, manage audits, and maintain certification requirements effortlessly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Blockchain Security</h3>
                <p>Immutable records powered by blockchain technology ensure data integrity. Every transaction and movement is securely recorded and cannot be tampered with.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Mobile-First Design</h3>
                <p>Access your traceability data anywhere, anytime. Our mobile-optimized platform works seamlessly across all devices for maximum convenience.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤝</div>
                <h3>Stakeholder Collaboration</h3>
                <p>Connect farmers, processors, distributors, and retailers in one platform. Streamline communication and data sharing across your entire network.</p>
            </div>
        </div>

        <div class="cta-section">
            <h2>Ready to Transform Your Organic Business?</h2>
            <p>Join leading organic producers who trust our platform to maintain the integrity of their products and build consumer confidence.</p>
            <div class="cta-buttons">
                <a href="register.php" class="cta-btn btn-secondary">Start Free Trial</a>
                <a href="login.php" class="cta-btn btn-primary">Access Portal</a>
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
</body>
</html>