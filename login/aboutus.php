<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Toyota Car Selling</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
            background: #ffffff;
            overflow-x: hidden;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: #1a1d21;
            padding: 18px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
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

        .nav-links a:hover,
        .nav-links a.active {
            color: #e31927;
        }

        .nav-right {
            min-width: 220px;
            display: flex;
            justify-content: flex-end;
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

        /* ===== HERO SECTION ===== */
        .about-hero {
            background: linear-gradient(135deg, rgba(26,29,33,0.85), rgba(26,29,33,0.75)), 
                        url('https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1920');
            background-size: cover;
            background-position: center;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .about-hero-content h1 {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .about-hero-content p {
            font-size: 18px;
            opacity: 0.9;
        }

        /* ===== CONTENT SECTION ===== */
        .about-content {
            max-width: 1200px;
            margin: 70px auto;
            padding: 0 40px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 70px;
        }

        .about-card {
            background: #f8f9fc;
            border-radius: 24px;
            padding: 35px 28px;
            text-align: center;
            transition: 0.3s;
            border: 1px solid #eee;
        }

        .about-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(0,0,0,0.1);
            border-color: #e31927;
        }

        .about-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .about-card h3 {
            font-size: 24px;
            color: #1a1d21;
            margin-bottom: 15px;
        }

        .about-card p {
            color: #666;
            line-height: 1.7;
            font-size: 15px;
        }

        /* ===== TIMELINE SECTION ===== */
        .timeline-section {
            max-width: 1200px;
            margin: 70px auto;
            padding: 0 40px;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: 800;
            color: #1a1d21;
            margin-bottom: 15px;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 50px;
            font-size: 16px;
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            flex-wrap: wrap;
            gap: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 5%;
            width: 90%;
            height: 3px;
            background: linear-gradient(90deg, #e31927, #ff6b6b, #e31927);
            border-radius: 3px;
        }

        .timeline-item {
            text-align: center;
            position: relative;
            z-index: 1;
            background: white;
            padding: 20px;
            border-radius: 20px;
            width: 23%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #e8e8e8;
            transition: 0.3s;
        }

        .timeline-item:hover {
            transform: translateY(-5px);
            border-color: #e31927;
        }

        .timeline-year {
            background: #e31927;
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            display: inline-block;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .timeline-title {
            font-weight: 700;
            color: #1a1d21;
            margin-bottom: 8px;
        }

        .timeline-desc {
            font-size: 12px;
            color: #888;
        }

        /* ===== TESTIMONIAL SECTION ===== */
        .testimonial-section {
            background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
            padding: 70px 0;
            margin: 40px 0;
        }

        .testimonial-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .testimonial-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            transition: 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
            border-color: #e31927;
        }

        .testimonial-stars {
            color: #ffc107;
            font-size: 20px;
            margin-bottom: 15px;
            letter-spacing: 3px;
        }

        .testimonial-text {
            font-size: 15px;
            color: #555;
            line-height: 1.7;
            margin-bottom: 20px;
            font-style: italic;
        }

        .testimonial-author {
            font-weight: 700;
            color: #1a1d21;
            margin-bottom: 5px;
        }

        .testimonial-role {
            font-size: 12px;
            color: #e31927;
        }

        /* ===== STATS SECTION ===== */
        .stats-section {
            background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
            padding: 70px 0;
            margin: 40px 0;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 35px 20px;
            text-align: center;
            transition: 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
            border-color: #e31927;
        }

        .stat-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 44px;
            font-weight: 800;
            color: #e31927;
            margin-bottom: 8px;
        }

        .stat-text {
            font-size: 15px;
            color: #666;
            font-weight: 600;
        }

        /* ===== MISSION SECTION ===== */
        .mission-section {
            max-width: 1000px;
            margin: 70px auto;
            padding: 0 40px;
            text-align: center;
        }

        .mission-section h2 {
            font-size: 32px;
            color: #1a1d21;
            margin-bottom: 20px;
        }

        .mission-section p {
            font-size: 17px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .mission-tag {
            display: inline-block;
            background: #e31927;
            color: white;
            padding: 12px 30px;
            border-radius: 40px;
            font-weight: 700;
            margin-top: 15px;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #1a1d21;
            color: #ccc;
            padding: 60px 0 30px;
            margin-top: 50px;
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

        /* ===== 响应式 ===== */
        @media (max-width: 1024px) {
            .about-grid { grid-template-columns: repeat(2, 1fr); }
            .testimonial-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .timeline::before { display: none; }
            .timeline-item { width: calc(50% - 10px); }
        }

        @media (max-width: 768px) {
            .navbar { padding: 18px 20px; flex-direction: column; gap: 15px; }
            .nav-left { flex-direction: column; gap: 15px; }
            .nav-links { gap: 20px; flex-wrap: wrap; justify-content: center; }
            .about-hero { height: 280px; }
            .about-hero-content h1 { font-size: 32px; }
            .about-content, .timeline-section, .testimonial-container, .stats-container, .footer-container { padding: 0 20px; }
            .about-grid { grid-template-columns: 1fr; gap: 25px; }
            .testimonial-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .timeline-item { width: 100%; }
            .footer-grid { grid-template-columns: 1fr; gap: 35px; }
            .mission-section h2 { font-size: 26px; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<div class="navbar">
    <div class="nav-left">
        <div class="logo">TOYOTA <span>Car Selling</span></div>
        <div class="nav-links">
            <a href="homepage.php">Home</a>
            <a href="catalogue.php">Cars</a>
            <a href="aboutus.php" class="active">About Us</a>
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
    </div>
</div>

<!-- ===== HERO ===== -->
<div class="about-hero">
    <div class="about-hero-content">
        <h1>About Our Company</h1>
        <p>Your trusted partner for premium Toyota vehicles in Malaysia</p>
    </div>
</div>

<!-- ===== CONTENT SECTION ===== -->
<div class="about-content">
    <div class="about-grid">
        <div class="about-card">
            <div class="about-icon">🚀</div>
            <h3>Our Beginning</h3>
            <p>NO1 Car Rental was founded with a simple vision — to provide reliable and high-quality Toyota vehicles to customers across Malaysia. Starting from a small local operation, we focused on offering trusted models such as Toyota Vios and Corolla with transparent pricing and flexible options.</p>
        </div>
        <div class="about-card">
            <div class="about-icon">⚙️</div>
            <h3>What We Offer</h3>
            <p>Our platform allows customers to explore a range of Toyota vehicles with different specifications, including G spec and E spec models. We aim to give users full control in selecting the features that suit their needs.</p>
        </div>
        <div class="about-card">
            <div class="about-icon">💎</div>
            <h3>Our Commitment</h3>
            <p>We are committed to delivering a smooth and secure online experience, from registration to booking and account management. Your satisfaction is our priority.</p>
        </div>
    </div>
</div>

<!-- ===== TIMELINE SECTION（发展历程） ===== -->
<div class="timeline-section">
    <div class="section-title">📅 Our Journey</div>
    <div class="section-subtitle">Milestones that shaped who we are today</div>
    <div class="timeline">
        <div class="timeline-item">
            <div class="timeline-year">2018</div>
            <div class="timeline-title">🏁 Company Founded</div>
            <div class="timeline-desc">Started with 10 Toyota vehicles in Melaka</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2020</div>
            <div class="timeline-title">📈 Expansion</div>
            <div class="timeline-desc">Expanded to 5 cities across Malaysia</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2022</div>
            <div class="timeline-title">🏆 Award Winner</div>
            <div class="timeline-desc">Received "Best Car Rental Service" award</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2024</div>
            <div class="timeline-title">🚀 10K+ Customers</div>
            <div class="timeline-desc">Served over 10,000 satisfied customers</div>
        </div>
    </div>
</div>

<!-- ===== TESTIMONIAL SECTION（客户评价） ===== -->
<div class="testimonial-section">
    <div class="testimonial-container">
        <div class="section-title">⭐ What Our Customers Say</div>
        <div class="section-subtitle">Real reviews from real people</div>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <div class="testimonial-text">"Great service! The car was clean and well-maintained. The booking process was super easy and fast. Highly recommend!"</div>
                <div class="testimonial-author">Ahmad Rizman</div>
                <div class="testimonial-role">Toyota Vios Customer</div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <div class="testimonial-text">"Best car rental experience in Malaysia. Transparent pricing, no hidden fees. Will definitely rent again!"</div>
                <div class="testimonial-author">Sarah Lim</div>
                <div class="testimonial-role">Toyota Camry Customer</div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <div class="testimonial-text">"The team was very helpful and responsive. The car was delivered on time. 10/10 experience!"</div>
                <div class="testimonial-author">Chong Wei Ming</div>
                <div class="testimonial-role">Toyota Fortuner Customer</div>
            </div>
        </div>
    </div>
</div>

<!-- ===== STATS SECTION ===== -->
<div class="stats-section">
    <div class="stats-container">
        <h2 class="section-title">Our Achievements</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🚗</div>
                <div class="stat-number">200+</div>
                <div class="stat-text">Toyota Cars Listed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number">5K+</div>
                <div class="stat-text">Registered Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📍</div>
                <div class="stat-number">20+</div>
                <div class="stat-text">Locations Covered</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number">4.8</div>
                <div class="stat-text">Customer Rating</div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MISSION SECTION ===== -->
<div class="mission-section">
    <h2>🚗 Your Journey, Our Priority</h2>
    <p>At Toyota Car Selling, we believe that everyone deserves a reliable and affordable vehicle. That's why we've made it our mission to provide the best selection of Toyota cars with transparent pricing, flexible options, and exceptional customer service.</p>
    <p>Whether you're looking for a daily driver or a family SUV, we have the perfect Toyota waiting for you. Our team is dedicated to making your car buying experience smooth, secure, and enjoyable.</p>
    <div class="mission-tag">Drive Your Dream Toyota Today</div>
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

</body>
</html>