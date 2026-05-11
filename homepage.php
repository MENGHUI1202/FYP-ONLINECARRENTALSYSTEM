<?php 
session_start();
include("config.php");

$recommended_cars = [
    ['id' => 1, 'name' => 'Toyota Vios', 'price' => 250, 'transmission' => 'Auto', 'seats' => 5, 'type' => 'Sedan', 'img' => 'image/vios.jpg'],
    ['id' => 2, 'name' => 'Toyota Camry', 'price' => 450, 'transmission' => 'Auto', 'seats' => 5, 'type' => 'Sedan', 'img' => 'image/camry.jpg'],
    ['id' => 3, 'name' => 'Toyota Corolla', 'price' => 350, 'transmission' => 'Auto', 'seats' => 5, 'type' => 'Sedan', 'img' => 'image/corolla.jpg'],
    ['id' => 4, 'name' => 'Toyota Hilux', 'price' => 380, 'transmission' => 'Auto', 'seats' => 4, 'type' => 'Pickup', 'img' => 'image/hilux.jpg'],
    ['id' => 5, 'name' => 'Toyota Fortuner', 'price' => 500, 'transmission' => 'Auto', 'seats' => 7, 'type' => 'SUV', 'img' => 'image/fortuner.jpg'],
    ['id' => 6, 'name' => 'Toyota Yaris', 'price' => 220, 'transmission' => 'Auto', 'seats' => 5, 'type' => 'Hatchback', 'img' => 'image/yaris.jpg'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toyota Car Selling - Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
            background: var(--bg-primary);
            overflow-x: hidden;
            transition: background 0.3s, color 0.3s;
            color: var(--text-primary);
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fc;
            --bg-card: #ffffff;
            --text-primary: #1a1d21;
            --text-secondary: #666;
            --text-muted: #888;
            --border-color: #e8e8e8;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
            --shadow-hover: 0 20px 40px rgba(0,0,0,0.12);
            --navbar-bg: #1a1d21;
            --footer-bg: #1a1d21;
            --hero-overlay: linear-gradient(135deg, rgba(0,0,0,0.65), rgba(0,0,0,0.4));
        }

        body.dark {
            --bg-primary: #1a1d21;
            --bg-secondary: #25292e;
            --bg-card: #2c3035;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #888;
            --border-color: #3a3f45;
            --shadow: 0 10px 30px rgba(0,0,0,0.3);
            --shadow-hover: 0 20px 40px rgba(0,0,0,0.4);
            --navbar-bg: #0f1115;
            --footer-bg: #0f1115;
            --hero-overlay: linear-gradient(135deg, rgba(0,0,0,0.8), rgba(0,0,0,0.6));
        }

        .navbar {
            background: var(--navbar-bg);
            padding: 18px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
            transition: background 0.3s;
        }

        .logo {
            color: white;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .logo span {
            color: #e31927;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .nav-links {
            display: flex;
            gap: 35px;
        }

        .nav-links a {
            color: #e0e0e0;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #e31927;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-right a {
            padding: 8px 22px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.3s;
        }

        .login-btn {
            border: 1.5px solid #e31927;
            color: white;
            background: transparent;
        }

        .login-btn:hover {
            background: #e31927;
            color: white;
        }

        .register-btn, .logout-btn {
            background: #e31927;
            color: white;
        }

        .register-btn:hover, .logout-btn:hover {
            background: #b81520;
            transform: translateY(-2px);
        }

        .theme-toggle {
            background: rgba(255,255,255,0.15);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .theme-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .hero {
            position: relative;
            width: 100%;
            height: 650px;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 0.8s ease;
        }

        .slide.active {
            opacity: 1;
        }

        .overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: var(--hero-overlay);
        }

        .content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            z-index: 2;
            width: 100%;
            padding: 0 20px;
        }

        .content h1 {
            font-size: 62px;
            margin-bottom: 20px;
            text-shadow: 3px 3px 12px rgba(0,0,0,0.6);
        }

        .content p {
            font-size: 22px;
            margin-bottom: 35px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
        }

        .btn-browse {
            background: #e31927;
            color: white;
            padding: 16px 42px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-browse:hover {
            background: #b81520;
            transform: translateY(-3px);
        }

        .arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            color: white;
            background: rgba(0,0,0,0.5);
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 50%;
            transition: 0.3s;
        }

        .arrow:hover {
            background: rgba(227,25,39,0.8);
        }

        .arrow.left { left: 30px; }
        .arrow.right { right: 30px; }

        .dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 10;
        }

        .dot {
            width: 12px;
            height: 12px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
        }

        .dot.active {
            background: #e31927;
            width: 35px;
            border-radius: 10px;
        }

        .recommended {
            max-width: 1400px;
            margin: 80px auto;
            padding: 0 50px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 55px;
        }

        .section-header h2 {
            font-size: 42px;
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .section-header p {
            color: var(--text-secondary);
            font-size: 18px;
        }

        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 40px;
        }

        .car-card {
            background: var(--bg-card);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.35s;
            cursor: pointer;
            border: 1px solid var(--border-color);
        }

        .car-card:hover {
            transform: translateY(-12px);
            box-shadow: var(--shadow-hover);
            border-color: #e31927;
        }

        .car-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        .car-info {
            padding: 25px;
        }

        .car-info h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .car-details {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .car-badge {
            background: var(--bg-secondary);
            padding: 6px 14px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .car-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px solid var(--border-color);
        }

        .price {
            font-size: 28px;
            font-weight: 800;
            color: #e31927;
        }

        .price small {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .btn-view {
            background: var(--navbar-bg);
            color: white;
            border: none;
            padding: 10px 28px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-view:hover {
            background: #e31927;
        }

        .why-section {
            background: var(--bg-secondary);
            padding: 80px 0;
            transition: background 0.3s;
        }

        .why-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 50px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 45px;
            margin-top: 40px;
        }

        .feature-card {
            background: var(--bg-card);
            padding: 45px 35px;
            border-radius: 28px;
            text-align: center;
            transition: 0.35s;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: #e31927;
        }

        .feature-icon {
            font-size: 55px;
            margin-bottom: 25px;
        }

        .feature-card h4 {
            font-size: 24px;
            margin-bottom: 18px;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .faq-section {
            max-width: 1400px;
            margin: 80px auto;
            padding: 0 50px;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 35px;
            margin-top: 45px;
        }

        .faq-item {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 22px 30px;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid var(--border-color);
        }

        .faq-item:hover {
            border-color: #e31927;
        }

        .faq-question {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question .icon {
            font-size: 24px;
            color: #e31927;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-top: 5px;
        }

        .faq-item.active .faq-answer {
            max-height: 180px;
            margin-top: 18px;
        }

        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #e31927;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            opacity: 0;
            visibility: hidden;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(227,25,39,0.3);
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: #b81520;
            transform: translateY(-3px);
        }

        .footer {
            background: var(--footer-bg);
            color: #ccc;
            padding: 60px 0 30px;
            margin-top: 50px;
            transition: background 0.3s;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 50px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr;
            gap: 55px;
            margin-bottom: 55px;
        }

        .footer-col h4 {
            color: white;
            font-size: 20px;
            margin-bottom: 25px;
        }

        .footer-col p {
            line-height: 1.8;
            color: #aaa;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 14px;
        }

        .footer-col ul li a {
            color: #aaa;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-col ul li a:hover {
            color: #e31927;
        }

        .contact-info li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #aaa;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 35px;
            border-top: 1px solid #333;
            color: #888;
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .faq-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .navbar { padding: 18px 20px; flex-direction: column; gap: 15px; }
            .nav-left { flex-direction: column; gap: 15px; }
            .nav-links { gap: 20px; flex-wrap: wrap; justify-content: center; }
            .content h1 { font-size: 32px; }
            .content p { font-size: 16px; }
            .hero { height: 480px; }
            .recommended, .why-container, .faq-section, .footer-container { padding: 0 20px; }
            .features-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; gap: 35px; }
            .cars-grid { grid-template-columns: 1fr; }
            .arrow { display: none; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <div class="logo">TOYOTA <span>Car Selling</span></div>
        <div class="nav-links">
            <a href="homepage.php">Home</a>
            <a href="catalogue.php">Cars</a>
            <a href="aboutus.php">About Us</a>
            <a href="#contact">Contact</a>
        </div>
    </div>

    <div class="nav-right">
        <?php if (isset($_SESSION['user_name'])): ?>
            <a href="dashboard.php" class="login-btn" style="background:#e31927; color:white;">
                👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
            <a href="register.php" class="register-btn">Register</a>
        <?php endif; ?>
        
        <button class="theme-toggle" id="themeToggle">🌙</button>
    </div>
</div>

<!-- ===== HERO ===== -->
<div class="hero">
    <div class="slide active" style="background-image:url('image/hero1.jpg')">
        <div class="overlay"></div>
        <div class="content">
            <h1>Find Your Perfect Toyota</h1>
            <p>Best price. Best quality. Trusted since 2020.</p>
            <a href="catalogue.php" class="btn-browse">Browse Cars →</a>
        </div>
    </div>
    <div class="slide" style="background-image:url('image/hero2.jpg')">
        <div class="overlay"></div>
        <div class="content">
            <h1>Drive With Confidence</h1>
            <p>Reliable & well-maintained vehicles</p>
            <a href="catalogue.php" class="btn-browse">Browse Cars →</a>
        </div>
    </div>
    <div class="slide" style="background-image:url('image/hero3.jpg')">
        <div class="overlay"></div>
        <div class="content">
            <h1>Your Journey Starts Here</h1>
            <p>Start your dream drive today</p>
            <a href="catalogue.php" class="btn-browse">Browse Cars →</a>
        </div>
    </div>

    <div class="arrow left" onclick="prevSlide()">❮</div>
    <div class="arrow right" onclick="nextSlide()">❯</div>

    <div class="dots">
        <span class="dot active" onclick="goToSlide(0)"></span>
        <span class="dot" onclick="goToSlide(1)"></span>
        <span class="dot" onclick="goToSlide(2)"></span>
    </div>
</div>

<!-- ===== RECOMMENDED CARS ===== -->
<div class="recommended">
    <div class="section-header">
        <h2>🔥 Recommended Toyota Cars</h2>
        <p>Most popular choices among our customers</p>
    </div>
    <div class="cars-grid">
        <?php foreach ($recommended_cars as $car): ?>
        <div class="car-card" onclick="window.location.href='car_detail.php?id=<?php echo $car['id']; ?>'">
            <img src="<?php echo $car['img']; ?>" alt="<?php echo $car['name']; ?>" onerror="this.src='https://via.placeholder.com/400x240?text=Toyota'">
            <div class="car-info">
                <h3><?php echo $car['name']; ?></h3>
                <div class="car-details">
                    <span class="car-badge">⚙️ <?php echo $car['transmission']; ?></span>
                    <span class="car-badge">👥 <?php echo $car['seats']; ?> Seats</span>
                    <span class="car-badge">🚗 <?php echo $car['type']; ?></span>
                </div>
                <div class="car-price">
                    <div class="price">RM <?php echo number_format($car['price'], 0); ?> <small>/ day</small></div>
                    <button class="btn-view" onclick="event.stopPropagation(); window.location.href='car_detail.php?id=<?php echo $car['id']; ?>'">View →</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== WHY CHOOSE US ===== -->
<div class="why-section">
    <div class="why-container">
        <div class="section-header">
            <h2>Why Choose Our Toyota Car?</h2>
            <p>We make car buying simple, transparent, and reliable</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h4>Clear Pricing</h4>
                <p>No hidden charges. What you see is what you pay.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h4>Easy Purchase</h4>
                <p>Buy quickly with our streamlined process.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h4>Safe Vehicles</h4>
                <p>All cars are well-maintained and fully inspected.</p>
            </div>
        </div>
    </div>
</div>

<!-- ===== FAQ SECTION ===== -->
<div class="faq-section">
    <div class="section-header">
        <h2>❓ Frequently Asked Questions</h2>
        <p>Got questions? We've got answers.</p>
    </div>
    <div class="faq-grid">
        <div class="faq-item">
            <div class="faq-question">
                How do I buy a car?
                <span class="icon">+</span>
            </div>
            <div class="faq-answer">
                Simply browse our catalogue, select your preferred Toyota model, and click "Buy Now". Our team will contact you within 24 hours.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                Are all cars inspected?
                <span class="icon">+</span>
            </div>
            <div class="faq-answer">
                Yes! Every Toyota goes through a 150-point inspection and comes with a warranty.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                Do you provide delivery?
                <span class="icon">+</span>
            </div>
            <div class="faq-answer">
                Absolutely. We offer nationwide delivery across Malaysia with free delivery within 50km.
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                Can I test drive before buying?
                <span class="icon">+</span>
            </div>
            <div class="faq-answer">
                Yes, test drives are available at our showroom by appointment.
            </div>
        </div>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="footer" id="contact">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Toyota Car Selling</h4>
                <p>Premium Toyota cars with clear pricing, no hidden fees, and nationwide delivery in Malaysia.</p>
                <p style="margin-top: 18px;">⭐ Trusted by 10,000+ customers</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="aboutus.php">About Us</a></li>
                    <li><a href="catalogue.php">Catalogue</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact Us</h4>
                <ul class="contact-info">
                    <li>📞 <a href="tel:+60123456789" style="color:#aaa;">+60 12-345 6789</a></li>
                    <li>📧 <a href="mailto:hoo.meng.hui@student.mmu.edu.my" style="color:#aaa;">hoo.meng.hui@student.mmu.edu.my</a></li>
                    <li>📧 <a href="mailto:pang.kang.hormg@student.mmu.edu.my" style="color:#aaa;">pang.kang.hormg@student.mmu.edu.my</a></li>
                    <li>📧 <a href="mailto:ng.meng.xin@student.mmu.edu.my" style="color:#aaa;">ng.meng.xin@student.mmu.edu.my</a></li>
                    <li>📍 Multimedia University, Melaka</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            © 2026 Toyota Car Selling. All rights reserved.
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTopBtn">↑</button>

<script>
    // Hero Slider
    let slides = document.querySelectorAll('.hero .slide');
    let dots = document.querySelectorAll('.hero .dot');
    let currentSlide = 0;

    function showSlide(index) {
        slides.forEach((s, i) => {
            s.classList.toggle('active', i === index);
            if (dots[i]) dots[i].classList.toggle('active', i === index);
        });
        currentSlide = index;
    }

    function nextSlide() {
        let next = (currentSlide + 1) % slides.length;
        showSlide(next);
    }

    function prevSlide() {
        let prev = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(prev);
    }

    function goToSlide(index) {
        showSlide(index);
    }

    setInterval(nextSlide, 5000);

    // FAQ Accordion
    document.querySelectorAll('.faq-item').forEach(item => {
        item.addEventListener('click', () => {
            item.classList.toggle('active');
            let icon = item.querySelector('.icon');
            icon.textContent = item.classList.contains('active') ? '−' : '+';
        });
    });

    // ===== 深色模式切换 =====
    const themeToggle = document.getElementById('themeToggle');
    
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        themeToggle.textContent = '☀️';
    } else {
        themeToggle.textContent = '🌙';
    }
    
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        
        if (document.body.classList.contains('dark')) {
            localStorage.setItem('theme', 'dark');
            themeToggle.textContent = '☀️';
        } else {
            localStorage.setItem('theme', 'light');
            themeToggle.textContent = '🌙';
        }
    });

    // ===== BACK TO TOP BUTTON =====
    const backToTopBtn = document.getElementById('backToTopBtn');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });

    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
</script>

</body>
</html>