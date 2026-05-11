<?php
/* Source homepage code shared by user. :contentReference[oaicite:0]{index=0} */
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

$cars = [
    [
        "name" => "Toyota Vios",
        "type" => "Sedan",
        "price" => "From RM 95,500",
        "priceValue" => 95500,
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "image" => "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "name" => "Toyota Corolla Cross",
        "type" => "SUV",
        "price" => "From RM 130,900",
        "priceValue" => 130900,
        "transmission" => "Automatic",
        "fuel" => "Hybrid",
        "image" => "https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "name" => "Toyota Camry",
        "type" => "Sedan",
        "price" => "From RM 219,800",
        "priceValue" => 219800,
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "image" => "https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "name" => "Toyota Hilux",
        "type" => "Pickup",
        "price" => "From RM 103,880",
        "priceValue" => 103880,
        "transmission" => "Automatic",
        "fuel" => "Diesel",
        "image" => "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "name" => "Toyota Yaris",
        "type" => "Hatchback",
        "price" => "From RM 88,000",
        "priceValue" => 88000,
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "image" => "https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "name" => "Toyota Innova Zenix",
        "type" => "MPV",
        "price" => "From RM 165,000",
        "priceValue" => 165000,
        "transmission" => "Automatic",
        "fuel" => "Hybrid",
        "image" => "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=80"
    ]
];

$heroSlides = [
    [
        "title" => "Find Your Dream Toyota Today",
        "text" => "Browse Toyota vehicles, check details, compare models, calculate loan payments and start your car buying journey online.",
        "button" => "View Catalogue",
        "link" => "catalogue.php",
        "image" => "https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=1600&q=80"
    ],
    [
        "title" => "Compare Toyota Models Easily",
        "text" => "Compare Toyota cars side by side based on price, specifications, fuel type, transmission and features.",
        "button" => "Compare Now",
        "link" => "compare.php",
        "image" => "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1600&q=80"
    ],
    [
        "title" => "Calculate Your Monthly Loan Payment",
        "text" => "Estimate your monthly payment by entering car price, down payment, interest rate and loan period.",
        "button" => "Calculate Loan",
        "link" => "loan_calculator.php",
        "image" => "https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1600&q=80"
    ],
    [
        "title" => "Need Help Applying for a Car Loan?",
        "text" => "Submit your personal, vehicle and financial information online. Our company will assist in forwarding your application to our partnered bank.",
        "button" => "Apply Loan Assistance",
        "link" => "loan_application.php",
        "image" => "https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1600&q=80"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Toyota Car Selling</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #ffffff;
            color: #222;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            width: 100%;
            min-height: 86px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(18px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5.5%;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(215, 25, 32, 0.12);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #111;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 1.5px;
            white-space: nowrap;
        }

        .logo-mark {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 25px rgba(215, 25, 32, 0.28);
            position: relative;
            overflow: hidden;
        }

        .logo-mark::before {
            content: "";
            position: absolute;
            width: 34px;
            height: 18px;
            border: 3px solid #fff;
            border-radius: 50%;
        }

        .logo-mark::after {
            content: "";
            position: absolute;
            width: 18px;
            height: 34px;
            border: 3px solid #fff;
            border-radius: 50%;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .logo-text strong {
            color: #d71920;
            font-size: 25px;
            letter-spacing: 2px;
        }

        .logo-text small {
            color: #333;
            font-size: 10px;
            letter-spacing: 2.5px;
            margin-top: 5px;
            font-weight: 800;
        }

        .nav-center {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f7f7f7;
            padding: 8px;
            border-radius: 28px;
            border: 1px solid #eeeeee;
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .nav-center a {
            color: #222;
            font-size: 13.5px;
            font-weight: 800;
            transition: 0.3s;
            white-space: nowrap;
            padding: 12px 16px;
            border-radius: 22px;
            background: transparent;
            border: 1px solid transparent;
        }

        .nav-center a:hover {
            color: #d71920;
            background: #e4e4e4;
            border-color: rgba(215, 25, 32, 0.25);
            box-shadow: 0 6px 16px rgba(215, 25, 32, 0.12);
            transform: translateY(-2px);
        }

        .nav-center a.active {
            color: #ffffff;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            border-color: #d71920;
            box-shadow: 0 10px 24px rgba(215, 25, 32, 0.28);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 128px;
            justify-content: flex-end;
        }

        .username {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            white-space: nowrap;
            background: #f7f7f7;
            padding: 10px 16px;
            border-radius: 22px;
            border: 1px solid #eeeeee;
        }

        .login-btn,
        .logout-btn {
            display: inline-block;
            background: linear-gradient(135deg, #d71920, #a80f15);
            color: #fff;
            padding: 12px 24px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 800;
            transition: 0.3s;
            white-space: nowrap;
            box-shadow: 0 10px 22px rgba(215, 25, 32, 0.24);
        }

        .login-btn:hover,
        .logout-btn:hover {
            background: linear-gradient(135deg, #b7151b, #7e0b10);
            transform: translateY(-2px);
        }

        .menu-btn {
            display: none;
            background: #f7f7f7;
            border: 1px solid #eeeeee;
            font-size: 28px;
            cursor: pointer;
            color: #111;
            width: 48px;
            height: 48px;
            border-radius: 16px;
        }

        .hero {
            width: 100%;
            height: 650px;
            position: relative;
            overflow: hidden;
        }

        .slide {
            width: 100%;
            height: 650px;
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        .slide::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.82), rgba(0, 0, 0, 0.42), rgba(215, 25, 32, 0.18));
        }

        .slide::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 46px 46px;
            opacity: 0.35;
        }

        .slide-content {
            position: absolute;
            top: 50%;
            left: 6%;
            transform: translateY(-50%);
            max-width: 650px;
            color: #fff;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            margin-bottom: 22px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .hero-badge span {
            width: 9px;
            height: 9px;
            background: #d71920;
            border-radius: 50%;
            box-shadow: 0 0 0 6px rgba(215, 25, 32, 0.18);
        }

        .slide-content h1 {
            font-size: 58px;
            line-height: 1.12;
            margin-bottom: 20px;
            font-weight: 900;
        }

        .slide-content p {
            font-size: 18px;
            line-height: 1.8;
            color: #eeeeee;
            margin-bottom: 34px;
        }

        .primary-btn {
            display: inline-block;
            padding: 15px 36px;
            background: linear-gradient(135deg, #d71920, #a80f15);
            color: #fff;
            border-radius: 32px;
            font-weight: 800;
            transition: 0.3s;
            border: 2px solid #d71920;
            box-shadow: 0 14px 28px rgba(215, 25, 32, 0.25);
        }

        .primary-btn:hover {
            background: linear-gradient(135deg, #b7151b, #800c11);
            border-color: #b7151b;
            transform: translateY(-3px);
        }

        .white-btn {
            display: inline-block;
            padding: 15px 36px;
            background: #fff;
            color: #d71920;
            border-radius: 32px;
            font-weight: 800;
            transition: 0.3s;
            border: 2px solid #fff;
            box-shadow: 0 12px 25px rgba(255, 255, 255, 0.18);
        }

        .white-btn:hover {
            background: transparent;
            color: #fff;
            transform: translateY(-3px);
        }

        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 52px;
            height: 52px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            backdrop-filter: blur(12px);
            font-size: 27px;
            cursor: pointer;
            z-index: 5;
            transition: 0.3s;
        }

        .slider-arrow:hover {
            background: #d71920;
            border-color: #d71920;
            color: #fff;
        }

        .prev {
            left: 24px;
        }

        .next {
            right: 24px;
        }

        .dots {
            position: absolute;
            bottom: 30px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 10px;
            z-index: 5;
        }

        .dot {
            width: 13px;
            height: 13px;
            background: rgba(255, 255, 255, 0.62);
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
        }

        .dot.active {
            width: 38px;
            border-radius: 20px;
            background: #d71920;
            box-shadow: 0 0 0 6px rgba(215, 25, 32, 0.18);
        }

        .section {
            padding: 88px 6%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 54px;
        }

        .section-label {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 22px;
            background: #ffe8e9;
            color: #d71920;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }

        .section-title h2 {
            font-size: 42px;
            color: #111;
            margin-bottom: 14px;
            font-weight: 900;
            letter-spacing: -0.8px;
        }

        .section-title p {
            max-width: 780px;
            margin: 0 auto;
            color: #666;
            line-height: 1.75;
            font-size: 16px;
        }

        .quick-section {
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.08), transparent 32%),
                linear-gradient(180deg, #ffffff, #fbfbfb);
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px;
        }

        .quick-card {
            background: rgba(255, 255, 255, 0.88);
            padding: 34px 24px 30px;
            border-radius: 28px;
            text-align: left;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
            transition: 0.35s;
            position: relative;
            overflow: hidden;
            min-height: 280px;
        }

        .quick-card::before {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(215, 25, 32, 0.08);
            top: -75px;
            right: -75px;
            transition: 0.35s;
        }

        .quick-card::after {
            content: "";
            position: absolute;
            left: 24px;
            right: 24px;
            bottom: 0;
            height: 5px;
            background: linear-gradient(90deg, #d71920, #111);
            border-radius: 10px 10px 0 0;
            transform: scaleX(0.35);
            transform-origin: left;
            transition: 0.35s;
        }

        .quick-card:hover {
            transform: translateY(-10px);
            border-color: rgba(215, 25, 32, 0.35);
            box-shadow: 0 25px 55px rgba(215, 25, 32, 0.14);
        }

        .quick-card:hover::before {
            transform: scale(1.25);
            background: rgba(215, 25, 32, 0.12);
        }

        .quick-card:hover::after {
            transform: scaleX(1);
        }

        .quick-icon {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            border-radius: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 22px;
            font-size: 30px;
            box-shadow: 0 15px 30px rgba(215, 25, 32, 0.25);
            position: relative;
            z-index: 2;
        }

        .quick-card h3 {
            font-size: 20px;
            color: #111;
            margin-bottom: 12px;
            position: relative;
            z-index: 2;
        }

        .quick-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }

        .small-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #111;
            color: #fff;
            padding: 11px 19px;
            border-radius: 22px;
            font-size: 14px;
            font-weight: 800;
            transition: 0.3s;
            position: relative;
            z-index: 2;
        }

        .small-btn::after {
            content: "→";
            font-size: 15px;
        }

        .small-btn:hover {
            background: #d71920;
            transform: translateX(3px);
        }

        .cars-section {
            background:
                linear-gradient(135deg, #f7f7f7, #ffffff),
                radial-gradient(circle at bottom right, rgba(215, 25, 32, 0.08), transparent 28%);
        }

        .car-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .car-card {
            background: #fff;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 46px rgba(0, 0, 0, 0.09);
            transition: 0.35s;
            border: 1px solid rgba(215, 25, 32, 0.1);
            position: relative;
        }

        .car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 28px 62px rgba(0, 0, 0, 0.13);
            border-color: rgba(215, 25, 32, 0.32);
        }

        .car-image-wrap {
            position: relative;
            height: 245px;
            overflow: hidden;
        }

        .car-img {
            width: 100%;
            height: 245px;
            object-fit: cover;
            transition: 0.45s;
        }

        .car-card:hover .car-img {
            transform: scale(1.08);
        }

        .car-image-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.58), transparent 60%);
        }

        .car-badge {
            position: absolute;
            top: 18px;
            left: 18px;
            background: rgba(215, 25, 32, 0.92);
            color: #fff;
            padding: 8px 14px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: 900;
            z-index: 2;
            box-shadow: 0 10px 20px rgba(215, 25, 32, 0.24);
        }

        .car-type-floating {
            position: absolute;
            bottom: 18px;
            left: 18px;
            color: #fff;
            font-weight: 900;
            z-index: 2;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(10px);
            padding: 8px 14px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.22);
        }

        .car-info {
            padding: 28px;
        }

        .car-info h3 {
            font-size: 24px;
            color: #111;
            margin-bottom: 10px;
            font-weight: 900;
        }

        .car-price {
            font-size: 20px;
            font-weight: 900;
            color: #d71920;
            margin-bottom: 18px;
        }

        .car-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 22px;
        }

        .car-spec-box {
            background: #f7f7f7;
            border: 1px solid #eeeeee;
            padding: 13px 12px;
            border-radius: 18px;
        }

        .car-spec-box small {
            display: block;
            color: #888;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .car-spec-box span {
            color: #222;
            font-size: 14px;
            font-weight: 900;
        }

        .car-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .outline-btn,
        .red-btn,
        .dark-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 43px;
            padding: 10px 13px;
            border-radius: 22px;
            font-size: 13px;
            font-weight: 900;
            transition: 0.3s;
            text-align: center;
        }

        .outline-btn {
            border: 1.5px solid #d71920;
            color: #d71920;
            background: #fff;
        }

        .outline-btn:hover {
            background: #d71920;
            color: #fff;
            box-shadow: 0 10px 22px rgba(215, 25, 32, 0.2);
            transform: translateY(-2px);
        }

        .red-btn {
            border: 1.5px solid #d71920;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            box-shadow: 0 10px 22px rgba(215, 25, 32, 0.16);
        }

        .red-btn:hover {
            background: #111;
            border-color: #111;
            color: #fff;
            transform: translateY(-2px);
        }

        .dark-btn {
            border: 1.5px solid #111;
            background: #111;
            color: #fff;
        }

        .dark-btn:hover {
            background: #d71920;
            border-color: #d71920;
            transform: translateY(-2px);
        }

        .full-action {
            grid-column: 1 / -1;
        }

        .feature-block {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 58px;
            align-items: center;
            margin-bottom: 90px;
        }

        .feature-block:last-child {
            margin-bottom: 0;
        }

        .feature-content h2 {
            font-size: 40px;
            color: #111;
            margin-bottom: 18px;
            font-weight: 900;
            letter-spacing: -0.8px;
        }

        .feature-content p {
            color: #666;
            line-height: 1.85;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .feature-img {
            position: relative;
        }

        .feature-img::before {
            content: "";
            position: absolute;
            inset: 22px -18px -18px 22px;
            background: linear-gradient(135deg, #d71920, #111);
            border-radius: 28px;
            z-index: 0;
            opacity: 0.22;
        }

        .feature-img img {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 380px;
            object-fit: cover;
            border-radius: 28px;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.14);
        }

        .loan-highlight {
            background:
                linear-gradient(135deg, rgba(215, 25, 32, 0.97), rgba(111, 8, 12, 0.97)),
                url("https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1600&q=80");
            background-size: cover;
            background-position: center;
            color: #fff;
            border-radius: 34px;
            padding: 58px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 42px;
            align-items: center;
            box-shadow: 0 24px 55px rgba(215, 25, 32, 0.25);
            position: relative;
            overflow: hidden;
        }

        .loan-highlight::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 38px 38px;
            opacity: 0.3;
        }

        .loan-highlight > div {
            position: relative;
            z-index: 2;
        }

        .loan-highlight h2 {
            font-size: 40px;
            margin-bottom: 18px;
            font-weight: 900;
        }

        .loan-highlight p {
            line-height: 1.85;
            color: #f5f5f5;
            margin-bottom: 30px;
        }

        .loan-steps {
            background: rgba(255, 255, 255, 0.14);
            border-radius: 26px;
            padding: 28px;
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.14);
        }

        .loan-step {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .loan-step:last-child {
            margin-bottom: 0;
        }

        .step-num {
            width: 36px;
            height: 36px;
            background: #fff;
            color: #d71920;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            flex-shrink: 0;
        }

        .loan-step h4 {
            margin-bottom: 5px;
            font-size: 16px;
        }

        .loan-step p {
            margin: 0;
            font-size: 14px;
            line-height: 1.55;
        }

        .why-section {
            background:
                linear-gradient(135deg, rgba(17, 17, 17, 0.98), rgba(28, 28, 28, 0.98)),
                radial-gradient(circle at top right, rgba(215, 25, 32, 0.35), transparent 30%);
            color: #fff;
        }

        .why-section .section-title h2 {
            color: #fff;
        }

        .why-section .section-title p {
            color: #cfcfcf;
        }

        .why-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px;
        }

        .why-card {
            background: rgba(255, 255, 255, 0.06);
            padding: 34px 22px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            text-align: center;
            transition: 0.3s;
            backdrop-filter: blur(10px);
        }

        .why-card:hover {
            transform: translateY(-8px);
            border-color: rgba(215, 25, 32, 0.8);
            background: rgba(215, 25, 32, 0.14);
        }

        .why-card h3 {
            font-size: 18px;
            margin-bottom: 12px;
            color: #fff;
        }

        .why-card p {
            color: #cfcfcf;
            line-height: 1.65;
            font-size: 14px;
        }

        .faq-section {
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.08), transparent 32%),
                #f7f7f7;
        }

        .quiz-wrapper {
            max-width: 1180px;
            margin: 0 auto 58px;
            background: #ffffff;
            border-radius: 34px;
            border: 1px solid rgba(215, 25, 32, 0.14);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
        }

        .quiz-left {
            padding: 42px;
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.1), transparent 42%),
                linear-gradient(180deg, #ffffff, #fbfbfb);
        }

        .quiz-left h3 {
            font-size: 32px;
            color: #111;
            margin-bottom: 12px;
            font-weight: 900;
        }

        .quiz-left p {
            color: #666;
            line-height: 1.75;
            margin-bottom: 28px;
            font-size: 15px;
        }

        .quiz-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }

        .quiz-field {
            background: #f7f7f7;
            border: 1px solid #eeeeee;
            border-radius: 22px;
            padding: 16px;
            transition: 0.3s;
        }

        .quiz-field:hover {
            border-color: rgba(215, 25, 32, 0.3);
            box-shadow: 0 10px 22px rgba(215, 25, 32, 0.08);
            transform: translateY(-2px);
        }

        .quiz-field label {
            display: block;
            font-size: 13px;
            font-weight: 900;
            color: #333;
            margin-bottom: 10px;
        }

        .quiz-field select {
            width: 100%;
            height: 46px;
            border: none;
            outline: none;
            background: #fff;
            border-radius: 16px;
            padding: 0 14px;
            color: #222;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .quiz-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quiz-btn {
            border: none;
            outline: none;
            padding: 14px 30px;
            border-radius: 28px;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
        }

        .quiz-btn.main {
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            box-shadow: 0 12px 24px rgba(215, 25, 32, 0.22);
        }

        .quiz-btn.main:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #b7151b, #6f080c);
        }

        .quiz-btn.reset {
            background: #111;
            color: #fff;
        }

        .quiz-btn.reset:hover {
            background: #d71920;
            transform: translateY(-2px);
        }

        .quiz-result {
            padding: 42px;
            background:
                linear-gradient(135deg, rgba(17, 17, 17, 0.96), rgba(38, 38, 38, 0.94)),
                radial-gradient(circle at bottom right, rgba(215, 25, 32, 0.32), transparent 38%);
            color: #fff;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .quiz-result::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 38px 38px;
            opacity: 0.35;
        }

        .quiz-result-content {
            position: relative;
            z-index: 2;
        }

        .quiz-result-badge {
            display: inline-block;
            padding: 8px 14px;
            background: rgba(215, 25, 32, 0.9);
            border-radius: 18px;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 18px;
        }

        .quiz-result h3 {
            font-size: 28px;
            margin-bottom: 18px;
            font-weight: 900;
        }

        .quiz-results-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .quiz-result-option {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            overflow: hidden;
            transition: 0.3s;
        }

        .quiz-result-option:hover {
            background: rgba(215, 25, 32, 0.18);
            border-color: rgba(215, 25, 32, 0.65);
            transform: translateY(-3px);
        }

        .quiz-result-img-wrap {
            width: 100%;
            height: 155px;
            position: relative;
            overflow: hidden;
        }

        .quiz-result-img-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.55), transparent 65%);
        }

        .quiz-result-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .quiz-result-body {
            padding: 18px;
        }

        .quiz-result-option-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 14px;
        }

        .quiz-result-rank {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            background: #fff;
            color: #d71920;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            font-size: 16px;
            flex-shrink: 0;
        }

        .quiz-result-option h2 {
            font-size: 26px;
            margin: 0 0 4px;
            color: #fff;
            font-weight: 900;
        }

        .quiz-result-option strong {
            display: block;
            color: #fff;
            font-size: 14px;
        }

        .quiz-result-option p {
            color: #e5e5e5;
            line-height: 1.65;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .quiz-score-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quiz-score-tags span {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 7px 12px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: 800;
        }

        .quiz-result-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quiz-link-white {
            display: inline-block;
            background: #fff;
            color: #d71920;
            padding: 12px 22px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 900;
            transition: 0.3s;
        }

        .quiz-link-white:hover {
            background: #d71920;
            color: #fff;
            transform: translateY(-2px);
        }

        .quiz-link-red {
            display: inline-block;
            background: #d71920;
            color: #fff;
            padding: 12px 22px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 900;
            transition: 0.3s;
        }

        .quiz-link-red:hover {
            background: #fff;
            color: #d71920;
            transform: translateY(-2px);
        }

        .faq-container {
            max-width: 940px;
            margin: 0 auto;
        }

        .faq-item {
            background: #fff;
            border-radius: 20px;
            margin-bottom: 17px;
            border: 1px solid rgba(215, 25, 32, 0.1);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            transition: 0.3s;
        }

        .faq-item:hover {
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.09);
        }

        .faq-question {
            padding: 24px 28px;
            cursor: pointer;
            font-weight: 900;
            color: #111;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question span {
            font-size: 24px;
            color: #fff;
            font-weight: 900;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #d71920;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }

        .faq-answer p {
            padding: 0 28px 24px;
            color: #666;
            line-height: 1.75;
        }

        .faq-item.active .faq-answer {
            max-height: 180px;
        }

        .footer {
            background: #111;
            color: #fff;
            padding: 64px 6% 26px;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(215, 25, 32, 0.16);
            right: -120px;
            top: -120px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.4fr;
            gap: 42px;
            margin-bottom: 36px;
            position: relative;
            z-index: 2;
        }

        .footer h3 {
            font-size: 21px;
            margin-bottom: 18px;
            color: #fff;
        }

        .footer p,
        .footer a {
            color: #cfcfcf;
            line-height: 1.8;
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
        }

        .footer a:hover {
            color: #d71920;
        }

        .footer-bottom {
            border-top: 1px solid #333;
            padding-top: 22px;
            text-align: center;
            color: #aaa;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 1280px) {
            .navbar {
                padding: 0 3%;
            }

            .nav-center {
                gap: 5px;
                padding: 7px;
            }

            .nav-center a {
                font-size: 12.5px;
                padding: 11px 12px;
            }
        }

        @media (max-width: 1150px) {
            .quick-grid,
            .why-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .nav-center {
                position: absolute;
                top: 86px;
                left: 3%;
                right: 3%;
                display: none;
                flex-direction: column;
                align-items: stretch;
                border-radius: 24px;
                padding: 16px;
                background: rgba(255, 255, 255, 0.97);
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.12);
            }

            .nav-center.show {
                display: flex;
            }

            .nav-center a {
                text-align: center;
                font-size: 14px;
                padding: 14px;
            }

            .menu-btn {
                display: block;
            }

            .quiz-wrapper {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .car-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .feature-block,
            .loan-highlight {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar {
                min-height: 82px;
            }

            .quiz-form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero,
            .slide {
                height: 590px;
            }

            .slide-content {
                left: 6%;
                right: 6%;
            }

            .slide-content h1 {
                font-size: 39px;
            }

            .slide-content p {
                font-size: 16px;
            }

            .section {
                padding: 68px 5%;
            }

            .section-title h2 {
                font-size: 34px;
            }

            .quick-grid,
            .car-grid,
            .why-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .feature-block:nth-child(2) .feature-img {
                order: -1;
            }

            .feature-content h2,
            .loan-highlight h2 {
                font-size: 32px;
            }

            .loan-highlight {
                padding: 38px 25px;
                border-radius: 28px;
            }

            .slider-arrow {
                width: 42px;
                height: 42px;
                font-size: 22px;
                border-radius: 14px;
            }

            .prev {
                left: 12px;
            }

            .next {
                right: 12px;
            }

            .logo-text strong {
                font-size: 21px;
            }

            .logo-mark {
                width: 44px;
                height: 44px;
            }

            .quiz-left,
            .quiz-result {
                padding: 32px 24px;
            }

            .quiz-left h3,
            .quiz-result h3 {
                font-size: 27px;
            }

            .quiz-result-option h2 {
                font-size: 23px;
            }
        }

        @media (max-width: 480px) {
            .logo-text small {
                display: none;
            }

            .login-btn,
            .logout-btn {
                padding: 9px 15px;
                font-size: 13px;
            }

            .username {
                display: none;
            }

            .hero,
            .slide {
                height: 550px;
            }

            .slide-content h1 {
                font-size: 32px;
            }

            .slide-content p {
                font-size: 15px;
            }

            .primary-btn,
            .white-btn {
                padding: 12px 24px;
                font-size: 14px;
            }

            .feature-img img {
                height: 265px;
            }

            .car-image-wrap,
            .car-img {
                height: 220px;
            }

            .quiz-actions,
            .quiz-result-links {
                flex-direction: column;
            }

            .quiz-btn,
            .quiz-link-white,
            .quiz-link-red {
                width: 100%;
                text-align: center;
            }

            .car-actions {
                grid-template-columns: 1fr;
            }

            .full-action {
                grid-column: auto;
            }

            .quiz-result-option-header {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <a href="homepage.php" class="logo">
        <div class="logo-mark"></div>
        <div class="logo-text">
            <strong>TOYOTA</strong>
            <small>CAR SELLING</small>
        </div>
    </a>

    <button class="menu-btn" onclick="toggleMenu()">☰</button>

    <div class="nav-center" id="navMenu">
        <a href="homepage.php" class="active">Home</a>
        <a href="about.php">About Us</a>
        <a href="catalogue.php">Catalogue</a>
        <a href="compare.php">Compare Cars</a>
        <a href="loan_calculator.php">Loan Calculator</a>
        <a href="loan_application.php">Loan Assistance</a>
        <a href="test_drive.php">Test Drive</a>
        <a href="contact.php">Contact</a>
    </div>

    <div class="nav-right">
        <?php if ($username): ?>
            <span class="username">Hi, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <?php foreach ($heroSlides as $index => $slide): ?>
        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo $slide['image']; ?>');">
            <div class="slide-content">
                <div class="hero-badge"><span></span> ONLINE TOYOTA CAR SELLING</div>
                <h1><?php echo $slide['title']; ?></h1>
                <p><?php echo $slide['text']; ?></p>
                <a href="<?php echo $slide['link']; ?>" class="primary-btn"><?php echo $slide['button']; ?></a>
            </div>
        </div>
    <?php endforeach; ?>

    <button class="slider-arrow prev" onclick="changeSlide(-1)">&#10094;</button>
    <button class="slider-arrow next" onclick="changeSlide(1)">&#10095;</button>

    <div class="dots">
        <?php foreach ($heroSlides as $index => $slide): ?>
            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></span>
        <?php endforeach; ?>
    </div>
</section>

<section class="section quick-section">
    <div class="section-title">
        <span class="section-label">MAIN FEATURES</span>
        <h2>Quick Access</h2>
        <p>Explore the main features of our Online Toyota Car Selling System through simple and convenient access cards.</p>
    </div>

    <div class="quick-grid">
        <div class="quick-card">
            <div class="quick-icon">🚗</div>
            <h3>Browse Cars</h3>
            <p>View available Toyota models with details, prices and basic specifications.</p>
            <a href="catalogue.php" class="small-btn">View Cars</a>
        </div>

        <div class="quick-card">
            <div class="quick-icon">🔍</div>
            <h3>Compare Cars</h3>
            <p>Compare Toyota vehicles side by side before making a purchase decision.</p>
            <a href="compare.php" class="small-btn">Compare</a>
        </div>

        <div class="quick-card">
            <div class="quick-icon">💰</div>
            <h3>Loan Calculator</h3>
            <p>Calculate estimated monthly loan payment based on your selected car.</p>
            <a href="loan_calculator.php" class="small-btn">Calculate</a>
        </div>

        <div class="quick-card">
            <div class="quick-icon">🏦</div>
            <h3>Loan Assistance</h3>
            <p>Submit your information and let our company assist with bank loan application.</p>
            <a href="loan_application.php" class="small-btn">Apply</a>
        </div>

        <div class="quick-card">
            <div class="quick-icon">📅</div>
            <h3>Book Test Drive</h3>
            <p>Schedule a test drive appointment for your preferred Toyota model.</p>
            <a href="test_drive.php" class="small-btn">Book Now</a>
        </div>
    </div>
</section>

<section class="section cars-section">
    <div class="section-title">
        <span class="section-label">POPULAR MODELS</span>
        <h2>Recommended Toyota Cars</h2>
        <p>Discover some popular Toyota models and continue to view details, compare cars, book test drive, calculate loan or apply for loan assistance.</p>
    </div>

    <div class="car-grid">
        <?php foreach ($cars as $car): ?>
            <div class="car-card">
                <div class="car-image-wrap">
                    <img src="<?php echo $car['image']; ?>" class="car-img" alt="<?php echo $car['name']; ?>">
                    <div class="car-badge">Recommended</div>
                    <div class="car-type-floating"><?php echo $car['type']; ?></div>
                </div>

                <div class="car-info">
                    <h3><?php echo $car['name']; ?></h3>
                    <p class="car-price"><?php echo $car['price']; ?></p>

                    <div class="car-specs">
                        <div class="car-spec-box">
                            <small>TRANSMISSION</small>
                            <span><?php echo $car['transmission']; ?></span>
                        </div>

                        <div class="car-spec-box">
                            <small>FUEL TYPE</small>
                            <span><?php echo $car['fuel']; ?></span>
                        </div>
                    </div>

                    <div class="car-actions">
                        <a href="car_details.php?car=<?php echo urlencode($car['name']); ?>" class="red-btn full-action">View Details</a>
                        <a href="compare.php?car=<?php echo urlencode($car['name']); ?>" class="outline-btn">Compare</a>
                        <a href="test_drive.php?car=<?php echo urlencode($car['name']); ?>" class="outline-btn">Test Drive</a>
                        <a href="loan_application.php?car=<?php echo urlencode($car['name']); ?>" class="dark-btn">Apply Loan</a>
                        <a href="loan_calculator.php?car=<?php echo urlencode($car['name']); ?>&price=<?php echo $car['priceValue']; ?>" class="outline-btn">Calculate</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="feature-block">
        <div class="feature-content">
            <span class="section-label">CAR COMPARISON</span>
            <h2>Compare Toyota Cars Side by Side</h2>
            <p>Not sure which Toyota model suits you best? Use our car comparison feature to compare Toyota models based on price, specifications, fuel type, transmission, seating capacity and features. This helps users make better decisions before choosing a car.</p>
            <a href="compare.php" class="primary-btn">Compare Cars Now</a>
        </div>

        <div class="feature-img">
            <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80" alt="Compare Toyota Cars">
        </div>
    </div>

    <div class="feature-block">
        <div class="feature-img">
            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1200&q=80" alt="Loan Calculator">
        </div>

        <div class="feature-content">
            <span class="section-label">LOAN PLANNING</span>
            <h2>Estimate Your Monthly Car Loan</h2>
            <p>Plan your budget before buying a Toyota car. Our loan calculator helps you estimate your monthly payment based on the car price, down payment, interest rate and loan period.</p>
            <a href="loan_calculator.php" class="primary-btn">Try Loan Calculator</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="loan-highlight">
        <div>
            <h2>Car Loan Application Assistance</h2>
            <p>Our company works with a partnered bank to assist customers in applying for car loans. Customers can submit their personal, vehicle and financial information through our online form. Our team will review the details and help forward the application to the bank for further processing.</p>
            <a href="loan_application.php" class="white-btn">Apply for Loan Assistance</a>
        </div>

        <div class="loan-steps">
            <div class="loan-step">
                <div class="step-num">1</div>
                <div>
                    <h4>Submit Information</h4>
                    <p>Customer fills in personal, vehicle and financial details.</p>
                </div>
            </div>

            <div class="loan-step">
                <div class="step-num">2</div>
                <div>
                    <h4>Company Review</h4>
                    <p>Our team checks whether the submitted information is complete.</p>
                </div>
            </div>

            <div class="loan-step">
                <div class="step-num">3</div>
                <div>
                    <h4>Bank Processing</h4>
                    <p>The application is forwarded to the partnered bank for processing.</p>
                </div>
            </div>

            <div class="loan-step">
                <div class="step-num">4</div>
                <div>
                    <h4>Convenient Process</h4>
                    <p>Customers can apply online without visiting the bank first.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section why-section">
    <div class="section-title">
        <span class="section-label">SYSTEM BENEFITS</span>
        <h2>Why Choose Us</h2>
        <p>Our system provides useful functions to support users throughout the Toyota car buying process.</p>
    </div>

    <div class="why-grid">
        <div class="why-card">
            <h3>Toyota Car Information</h3>
            <p>View detailed Toyota vehicle information in one online platform.</p>
        </div>

        <div class="why-card">
            <h3>Easy Car Comparison</h3>
            <p>Compare different Toyota models before making a decision.</p>
        </div>

        <div class="why-card">
            <h3>Loan Estimation</h3>
            <p>Estimate monthly loan payments using a simple calculator.</p>
        </div>

        <div class="why-card">
            <h3>Bank Loan Assistance</h3>
            <p>Submit information and get assistance for partnered bank loan application.</p>
        </div>

        <div class="why-card">
            <h3>Test Drive Booking</h3>
            <p>Book a test drive appointment online with convenience.</p>
        </div>
    </div>
</section>

<section class="section faq-section">
    <div class="section-title">
        <span class="section-label">FAQ</span>
        <h2>Buying Tips & Questions</h2>
        <p>Find quick answers and use our smart recommendation quiz to discover two suitable Toyota model options.</p>
    </div>

    <div class="quiz-wrapper">
        <div class="quiz-left">
            <span class="section-label">SMART RECOMMENDATION</span>
            <h3>Car Recommendation Quiz</h3>
            <p>Answer detailed questions and the system will recommend two suitable Toyota models based on budget, lifestyle, fuel preference, driving style, cargo needs and personality.</p>

            <div class="quiz-form-grid">
                <div class="quiz-field">
                    <label>What is your budget?</label>
                    <select id="quizBudget">
                        <option value="">Select Budget</option>
                        <option value="below100">Below RM100k</option>
                        <option value="100to150">RM100k - RM150k</option>
                        <option value="150to250">RM150k - RM250k</option>
                        <option value="above250">Above RM250k</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Preferred car type?</label>
                    <select id="quizType">
                        <option value="">Select Car Type</option>
                        <option value="sedan">Sedan</option>
                        <option value="hatchback">Hatchback</option>
                        <option value="suv">SUV</option>
                        <option value="mpv">MPV</option>
                        <option value="pickup">Pickup</option>
                        <option value="sports">Sports / GR Model</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Main usage?</label>
                    <select id="quizUsage">
                        <option value="">Select Usage</option>
                        <option value="daily">Daily driving</option>
                        <option value="family">Family</option>
                        <option value="business">Business</option>
                        <option value="adventure">Adventure</option>
                        <option value="luxury">Luxury / Executive</option>
                        <option value="performance">Sport / Fun driving</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Seats needed?</label>
                    <select id="quizSeats">
                        <option value="">Select Seats</option>
                        <option value="5">5 Seats</option>
                        <option value="7">7 Seats</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Driving preference?</label>
                    <select id="quizStyle">
                        <option value="">Select Preference</option>
                        <option value="fuel">Fuel saving</option>
                        <option value="comfort">Comfort</option>
                        <option value="sport">Sporty handling</option>
                        <option value="strong">Strong and durable</option>
                        <option value="premium">Premium feeling</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Power expectation?</label>
                    <select id="quizPower">
                        <option value="">Select Power</option>
                        <option value="normal">Normal power</option>
                        <option value="balanced">Balanced performance</option>
                        <option value="high">High power</option>
                        <option value="veryhigh">Very high performance</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Preferred fuel type?</label>
                    <select id="quizFuel">
                        <option value="">Select Fuel Type</option>
                        <option value="petrol">Petrol</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="diesel">Diesel</option>
                        <option value="noPreference">No preference</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Driving environment?</label>
                    <select id="quizRoad">
                        <option value="">Select Environment</option>
                        <option value="city">City driving</option>
                        <option value="highway">Highway driving</option>
                        <option value="mixed">City + Highway</option>
                        <option value="rough">Rough road / Outdoor</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Most important factor?</label>
                    <select id="quizPriority">
                        <option value="">Select Priority</option>
                        <option value="price">Affordable price</option>
                        <option value="saving">Fuel saving</option>
                        <option value="space">Interior space</option>
                        <option value="comfort">Comfort</option>
                        <option value="image">Luxury image</option>
                        <option value="power">Power and speed</option>
                        <option value="durability">Durability</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Boot / cargo space?</label>
                    <select id="quizCargo">
                        <option value="">Select Cargo Need</option>
                        <option value="small">Small space is enough</option>
                        <option value="medium">Medium space</option>
                        <option value="large">Large family space</option>
                        <option value="work">Work / heavy cargo</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Model availability?</label>
                    <select id="quizAvailability">
                        <option value="">Select Availability</option>
                        <option value="available">Available now</option>
                        <option value="booking">Booking model is acceptable</option>
                        <option value="any">Any model</option>
                    </select>
                </div>

                <div class="quiz-field">
                    <label>Driver personality?</label>
                    <select id="quizPersonality">
                        <option value="">Select Personality</option>
                        <option value="practical">Practical and simple</option>
                        <option value="modern">Modern and stylish</option>
                        <option value="family">Family focused</option>
                        <option value="professional">Professional image</option>
                        <option value="premium">Premium lifestyle</option>
                        <option value="sporty">Sporty and exciting</option>
                        <option value="tough">Tough and adventurous</option>
                    </select>
                </div>
            </div>

            <div class="quiz-actions">
                <button type="button" class="quiz-btn main" onclick="recommendCar()">Get Recommendation</button>
                <button type="button" class="quiz-btn reset" onclick="resetQuiz()">Reset Quiz</button>
            </div>
        </div>

        <div class="quiz-result">
            <div class="quiz-result-content">
                <div class="quiz-result-badge">RESULT</div>
                <h3>Recommended options for you:</h3>

                <div class="quiz-results-grid">
                    <div class="quiz-result-option">
                        <div class="quiz-result-img-wrap">
                            <img id="recommendedImage1" class="quiz-result-img" src="https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80" alt="Toyota Vios">
                        </div>

                        <div class="quiz-result-body">
                            <div class="quiz-result-option-header">
                                <div class="quiz-result-rank">1</div>
                                <div>
                                    <h2 id="recommendedCar1">Toyota Vios</h2>
                                    <strong id="recommendedTag1">Budget-friendly daily car</strong>
                                </div>
                            </div>

                            <p id="recommendedReason1">Reason: Suitable for daily driving and budget-friendly.</p>
                            <p id="recommendedDetails1">This recommendation will update based on your selected answers.</p>

                            <div class="quiz-score-tags" id="quizTags1">
                                <span>Daily Driving</span>
                                <span>Budget Friendly</span>
                                <span>5 Seats</span>
                            </div>
                        </div>
                    </div>

                    <div class="quiz-result-option">
                        <div class="quiz-result-img-wrap">
                            <img id="recommendedImage2" class="quiz-result-img" src="https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1200&q=80" alt="Toyota Yaris">
                        </div>

                        <div class="quiz-result-body">
                            <div class="quiz-result-option-header">
                                <div class="quiz-result-rank">2</div>
                                <div>
                                    <h2 id="recommendedCar2">Toyota Yaris</h2>
                                    <strong id="recommendedTag2">Compact city hatchback</strong>
                                </div>
                            </div>

                            <p id="recommendedReason2">Reason: Suitable for users who want a compact and affordable Toyota.</p>
                            <p id="recommendedDetails2">This second option gives customers another suitable Toyota choice.</p>

                            <div class="quiz-score-tags" id="quizTags2">
                                <span>Compact</span>
                                <span>City Use</span>
                                <span>Affordable</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="quiz-result-links">
                    <a href="catalogue.php" class="quiz-link-white">View Catalogue</a>
                    <a href="compare.php?cars=Toyota%20Vios,Toyota%20Yaris" class="quiz-link-red" id="quizCompareLink">Compare These 2 Cars</a>
                </div>
            </div>
        </div>
    </div>

    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">
                Can I compare Toyota cars?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, users can compare Toyota cars based on price, specifications and features.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I calculate monthly loan payment?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, the loan calculator can estimate monthly payment based on car price, down payment, interest rate and loan period.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I apply for car loan assistance online?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, customers can submit their information through the loan assistance form, and the company will help forward the application to the partnered bank.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I book a test drive online?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, users can submit a test drive booking form through the system.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Do I need to login to browse cars?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>No, users can browse Toyota cars without login. However, some features may require user account access.</p>
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="footer-grid">
        <div>
            <h3>Toyota Car Selling System</h3>
            <p>Browse Toyota cars, compare models, calculate loan payments and apply for loan assistance through one convenient online platform.</p>
        </div>

        <div>
            <h3>Quick Links</h3>
            <a href="homepage.php">Home</a>
            <a href="catalogue.php">Catalogue</a>
            <a href="compare.php">Compare Cars</a>
            <a href="loan_calculator.php">Loan Calculator</a>
            <a href="loan_application.php">Loan Assistance</a>
        </div>

        <div>
            <h3>Services</h3>
            <a href="test_drive.php">Book Test Drive</a>
            <a href="contact.php">Contact Us</a>
            <a href="about.php">About Us</a>
            <a href="login.php">Login</a>
        </div>

        <div>
            <h3>Contact</h3>
            <p>Email: toyotacars@example.com</p>
            <p>Phone: +60 12-345 6789</p>
            <p>Address: Melaka, Malaysia</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 Toyota Car Selling System. All Rights Reserved.</p>
    </div>
</footer>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove("active"));
        dots.forEach(dot => dot.classList.remove("active"));

        if (index >= slides.length) {
            currentSlide = 0;
        } else if (index < 0) {
            currentSlide = slides.length - 1;
        } else {
            currentSlide = index;
        }

        slides[currentSlide].classList.add("active");
        dots[currentSlide].classList.add("active");
    }

    function changeSlide(step) {
        showSlide(currentSlide + step);
    }

    function goToSlide(index) {
        showSlide(index);
    }

    setInterval(() => {
        changeSlide(1);
    }, 3000);

    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
        const question = item.querySelector(".faq-question");
        const icon = item.querySelector(".faq-question span");

        question.addEventListener("click", () => {
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove("active");
                    otherItem.querySelector(".faq-question span").textContent = "+";
                }
            });

            item.classList.toggle("active");
            icon.textContent = item.classList.contains("active") ? "-" : "+";
        });
    });

    function toggleMenu() {
        document.getElementById("navMenu").classList.toggle("show");
    }

    const recommendationCars = [
        {
            name: "Toyota Vios",
            tag: "Budget-friendly daily sedan",
            reason: "Reason: Suitable for daily driving, affordable budget and easy city use.",
            details: "Toyota Vios is recommended for users who want a practical 5-seat sedan with low running cost, simple maintenance and comfortable daily driving.",
            image: "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80",
            tags: ["Daily Driving", "Budget Friendly", "Sedan", "5 Seats", "Petrol"],
            scoreRules: {
                budget: ["below100"],
                type: ["sedan"],
                usage: ["daily"],
                seats: ["5"],
                style: ["fuel"],
                power: ["normal", "balanced"],
                fuel: ["petrol", "noPreference"],
                road: ["city", "mixed"],
                priority: ["price", "saving"],
                cargo: ["small", "medium"],
                availability: ["available", "any"],
                personality: ["practical"]
            }
        },
        {
            name: "Toyota Yaris",
            tag: "Compact sporty city hatchback",
            reason: "Reason: Suitable for users who want a compact, stylish and easy-to-park Toyota.",
            details: "Toyota Yaris is recommended for city users who prefer a compact hatchback, youthful design and convenient handling for daily driving.",
            image: "https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1200&q=80",
            tags: ["Compact", "City Use", "Hatchback", "Stylish", "Petrol"],
            scoreRules: {
                budget: ["below100"],
                type: ["hatchback"],
                usage: ["daily"],
                seats: ["5"],
                style: ["fuel", "sport"],
                power: ["normal", "balanced"],
                fuel: ["petrol", "noPreference"],
                road: ["city", "mixed"],
                priority: ["price", "saving"],
                cargo: ["small", "medium"],
                availability: ["available", "any"],
                personality: ["modern", "practical"]
            }
        },
        {
            name: "Toyota Corolla Cross",
            tag: "Balanced SUV with hybrid efficiency",
            reason: "Reason: Suitable for users who want SUV practicality, comfort and fuel-saving hybrid performance.",
            details: "Toyota Corolla Cross is recommended for users who want a modern SUV with better space, hybrid fuel efficiency, safety and comfortable driving.",
            image: "https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&w=1200&q=80",
            tags: ["SUV", "Hybrid", "Balanced", "Comfort", "Modern"],
            scoreRules: {
                budget: ["100to150"],
                type: ["suv"],
                usage: ["daily", "family"],
                seats: ["5"],
                style: ["fuel", "comfort"],
                power: ["balanced"],
                fuel: ["hybrid", "noPreference"],
                road: ["city", "highway", "mixed"],
                priority: ["saving", "comfort", "space"],
                cargo: ["medium", "large"],
                availability: ["available", "any"],
                personality: ["modern", "family"]
            }
        },
        {
            name: "Toyota Innova Zenix",
            tag: "Family MPV with 7 seats",
            reason: "Reason: Suitable for family use because it provides more seats and practical cabin space.",
            details: "Toyota Innova Zenix is recommended for families who need 7 seats, better comfort, practical space and a smoother family travel experience.",
            image: "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=80",
            tags: ["Family", "MPV", "7 Seats", "Comfort", "Hybrid"],
            scoreRules: {
                budget: ["100to150", "150to250"],
                type: ["mpv"],
                usage: ["family"],
                seats: ["7"],
                style: ["comfort", "fuel"],
                power: ["balanced"],
                fuel: ["hybrid", "noPreference"],
                road: ["city", "highway", "mixed"],
                priority: ["space", "comfort", "saving"],
                cargo: ["large"],
                availability: ["booking", "any"],
                personality: ["family"]
            }
        },
        {
            name: "Toyota Hilux",
            tag: "Strong pickup for business and adventure",
            reason: "Reason: Suitable for outdoor travel, business use, rough road conditions and durable performance.",
            details: "Toyota Hilux is recommended for users who need a durable pickup for work, business, rough roads, adventure and carrying heavier items.",
            image: "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80",
            tags: ["Pickup", "Adventure", "Durable", "Diesel", "Business"],
            scoreRules: {
                budget: ["100to150"],
                type: ["pickup"],
                usage: ["business", "adventure"],
                seats: ["5"],
                style: ["strong"],
                power: ["balanced", "high"],
                fuel: ["diesel", "noPreference"],
                road: ["rough", "highway"],
                priority: ["durability", "power"],
                cargo: ["work"],
                availability: ["available", "any"],
                personality: ["tough"]
            }
        },
        {
            name: "Toyota Camry",
            tag: "Premium business sedan",
            reason: "Reason: Suitable for users who want a comfortable, premium and professional-looking sedan.",
            details: "Toyota Camry is recommended for business users or drivers who want a premium sedan with elegant design, comfort and strong road presence.",
            image: "https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=1200&q=80",
            tags: ["Premium", "Business", "Sedan", "Comfort", "Executive"],
            scoreRules: {
                budget: ["150to250"],
                type: ["sedan"],
                usage: ["business", "luxury"],
                seats: ["5"],
                style: ["comfort", "premium"],
                power: ["balanced", "high"],
                fuel: ["petrol", "noPreference"],
                road: ["highway", "mixed"],
                priority: ["comfort", "image"],
                cargo: ["medium"],
                availability: ["booking", "any"],
                personality: ["professional", "premium"]
            }
        },
        {
            name: "Toyota Alphard",
            tag: "Luxury executive MPV",
            reason: "Reason: Suitable for users who want luxury, comfort, executive image and 7-seat space.",
            details: "Toyota Alphard is recommended for users with higher budget who want premium comfort, executive travel, luxury image and spacious 7-seat cabin.",
            image: "https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=1200&q=80",
            tags: ["Luxury", "Executive", "7 Seats", "Premium MPV", "Comfort"],
            scoreRules: {
                budget: ["above250"],
                type: ["mpv"],
                usage: ["luxury", "family", "business"],
                seats: ["7"],
                style: ["premium", "comfort"],
                power: ["balanced", "high"],
                fuel: ["petrol", "noPreference"],
                road: ["highway", "mixed"],
                priority: ["comfort", "image", "space"],
                cargo: ["large"],
                availability: ["booking", "any"],
                personality: ["premium", "family", "professional"]
            }
        },
        {
            name: "Toyota GR Corolla",
            tag: "High-performance sport hatchback",
            reason: "Reason: Suitable for users who want sporty handling, stronger power and a more exciting driving experience.",
            details: "Toyota GR Corolla is recommended for users who want a sporty Toyota with strong power, manual driving feel and performance-focused personality.",
            image: "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80",
            tags: ["Sport", "High Power", "GR Model", "Performance", "Manual"],
            scoreRules: {
                budget: ["above250"],
                type: ["sports", "hatchback"],
                usage: ["performance"],
                seats: ["5"],
                style: ["sport"],
                power: ["high", "veryhigh"],
                fuel: ["petrol", "noPreference"],
                road: ["city", "highway", "mixed"],
                priority: ["power"],
                cargo: ["small", "medium"],
                availability: ["booking", "any"],
                personality: ["sporty"]
            }
        },
        {
            name: "Toyota GR Supra",
            tag: "Pure sports performance coupe",
            reason: "Reason: Suitable for users who want very high performance, sporty handling and a more aggressive sports car feel.",
            details: "Toyota GR Supra is recommended for drivers who focus mainly on speed, power, sport design and fun driving instead of family practicality.",
            image: "https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1200&q=80",
            tags: ["Sports Car", "Very High Power", "GR", "Performance", "Coupe"],
            scoreRules: {
                budget: ["above250"],
                type: ["sports"],
                usage: ["performance"],
                seats: ["5"],
                style: ["sport"],
                power: ["veryhigh", "high"],
                fuel: ["petrol", "noPreference"],
                road: ["highway", "mixed"],
                priority: ["power", "image"],
                cargo: ["small"],
                availability: ["booking", "any"],
                personality: ["sporty", "premium"]
            }
        },
        {
            name: "Toyota Fortuner",
            tag: "Powerful 7-seat SUV",
            reason: "Reason: Suitable for users who need SUV space, adventure ability and stronger road presence.",
            details: "Toyota Fortuner is recommended for users who want a 7-seat SUV for family travel, outdoor use, stronger image and adventure driving.",
            image: "https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80",
            tags: ["SUV", "7 Seats", "Adventure", "Strong", "Family"],
            scoreRules: {
                budget: ["150to250"],
                type: ["suv"],
                usage: ["family", "adventure"],
                seats: ["7"],
                style: ["strong", "comfort"],
                power: ["high"],
                fuel: ["diesel", "petrol", "noPreference"],
                road: ["rough", "highway", "mixed"],
                priority: ["space", "durability", "power"],
                cargo: ["large", "work"],
                availability: ["booking", "any"],
                personality: ["tough", "family"]
            }
        }
    ];

    function calculateCarScore(car, answers) {
        let score = 0;

        Object.keys(answers).forEach(key => {
            if (answers[key] && car.scoreRules[key] && car.scoreRules[key].includes(answers[key])) {
                score += 3;
            }
        });

        if (answers.type && car.scoreRules.type && car.scoreRules.type.includes(answers.type)) {
            score += 4;
        }

        if (answers.usage && car.scoreRules.usage && car.scoreRules.usage.includes(answers.usage)) {
            score += 5;
        }

        if (answers.priority && car.scoreRules.priority && car.scoreRules.priority.includes(answers.priority)) {
            score += 5;
        }

        if (answers.personality && car.scoreRules.personality && car.scoreRules.personality.includes(answers.personality)) {
            score += 4;
        }

        if (answers.seats === "7") {
            if (car.name === "Toyota Innova Zenix" || car.name === "Toyota Alphard" || car.name === "Toyota Fortuner") {
                score += 8;
            }

            if (car.name === "Toyota GR Supra" || car.name === "Toyota GR Corolla" || car.name === "Toyota Yaris") {
                score -= 6;
            }
        }

        if (answers.seats === "5") {
            if (car.name === "Toyota Vios" || car.name === "Toyota Yaris" || car.name === "Toyota Corolla Cross" || car.name === "Toyota Camry" || car.name === "Toyota Hilux" || car.name === "Toyota GR Corolla" || car.name === "Toyota GR Supra") {
                score += 3;
            }
        }

        if (answers.budget === "below100") {
            if (car.name === "Toyota Vios" || car.name === "Toyota Yaris") {
                score += 8;
            }

            if (car.name === "Toyota Alphard" || car.name === "Toyota GR Supra" || car.name === "Toyota GR Corolla" || car.name === "Toyota Camry") {
                score -= 8;
            }
        }

        if (answers.budget === "100to150") {
            if (car.name === "Toyota Corolla Cross" || car.name === "Toyota Hilux") {
                score += 7;
            }
        }

        if (answers.budget === "150to250") {
            if (car.name === "Toyota Camry" || car.name === "Toyota Innova Zenix" || car.name === "Toyota Fortuner") {
                score += 7;
            }
        }

        if (answers.budget === "above250") {
            if (car.name === "Toyota Alphard" || car.name === "Toyota GR Corolla" || car.name === "Toyota GR Supra") {
                score += 8;
            }
        }

        if (answers.usage === "performance" || answers.style === "sport" || answers.power === "veryhigh" || answers.personality === "sporty") {
            if (car.name === "Toyota GR Supra") {
                score += 10;
            }

            if (car.name === "Toyota GR Corolla") {
                score += 9;
            }

            if (car.name === "Toyota Vios" || car.name === "Toyota Innova Zenix" || car.name === "Toyota Alphard") {
                score -= 3;
            }
        }

        if (answers.power === "high") {
            if (car.name === "Toyota GR Corolla" || car.name === "Toyota GR Supra" || car.name === "Toyota Fortuner" || car.name === "Toyota Camry") {
                score += 5;
            }
        }

        if (answers.priority === "power") {
            if (car.name === "Toyota GR Supra" || car.name === "Toyota GR Corolla") {
                score += 8;
            }

            if (car.name === "Toyota Hilux" || car.name === "Toyota Fortuner") {
                score += 4;
            }
        }

        if (answers.priority === "space" || answers.cargo === "large") {
            if (car.name === "Toyota Innova Zenix" || car.name === "Toyota Alphard" || car.name === "Toyota Fortuner") {
                score += 7;
            }

            if (car.name === "Toyota GR Supra" || car.name === "Toyota Yaris") {
                score -= 4;
            }
        }

        if (answers.cargo === "work") {
            if (car.name === "Toyota Hilux") {
                score += 10;
            }

            if (car.name === "Toyota Fortuner") {
                score += 4;
            }
        }

        if (answers.road === "rough" || answers.usage === "adventure" || answers.style === "strong" || answers.priority === "durability" || answers.personality === "tough") {
            if (car.name === "Toyota Hilux") {
                score += 10;
            }

            if (car.name === "Toyota Fortuner") {
                score += 8;
            }

            if (car.name === "Toyota GR Supra" || car.name === "Toyota Camry") {
                score -= 3;
            }
        }

        if (answers.fuel === "hybrid" || answers.style === "fuel" || answers.priority === "saving") {
            if (car.name === "Toyota Corolla Cross" || car.name === "Toyota Innova Zenix") {
                score += 8;
            }

            if (car.name === "Toyota Vios" || car.name === "Toyota Yaris") {
                score += 4;
            }

            if (car.name === "Toyota GR Supra" || car.name === "Toyota GR Corolla" || car.name === "Toyota Alphard") {
                score -= 4;
            }
        }

        if (answers.fuel === "diesel") {
            if (car.name === "Toyota Hilux" || car.name === "Toyota Fortuner") {
                score += 8;
            }
        }

        if (answers.usage === "business" || answers.priority === "image" || answers.personality === "professional") {
            if (car.name === "Toyota Camry" || car.name === "Toyota Alphard") {
                score += 8;
            }

            if (car.name === "Toyota GR Supra") {
                score += 3;
            }
        }

        if (answers.usage === "luxury" || answers.style === "premium" || answers.personality === "premium") {
            if (car.name === "Toyota Alphard") {
                score += 10;
            }

            if (car.name === "Toyota Camry") {
                score += 7;
            }

            if (car.name === "Toyota GR Supra") {
                score += 4;
            }
        }

        if (answers.availability === "available") {
            if (car.name === "Toyota Vios" || car.name === "Toyota Yaris" || car.name === "Toyota Corolla Cross" || car.name === "Toyota Hilux") {
                score += 6;
            }

            if (car.name === "Toyota Alphard" || car.name === "Toyota GR Corolla" || car.name === "Toyota Camry" || car.name === "Toyota Innova Zenix") {
                score -= 3;
            }
        }

        if (answers.availability === "booking") {
            if (car.name === "Toyota Camry" || car.name === "Toyota Innova Zenix" || car.name === "Toyota Alphard" || car.name === "Toyota GR Corolla" || car.name === "Toyota GR Supra" || car.name === "Toyota Fortuner") {
                score += 4;
            }
        }

        return score;
    }

    function updateCompareLink(car1, car2) {
        const compareLink = document.getElementById("quizCompareLink");
        compareLink.href = "compare.php?cars=" + encodeURIComponent(car1.name) + "," + encodeURIComponent(car2.name);
    }

    function renderRecommendation(slot, car) {
        document.getElementById("recommendedCar" + slot).textContent = car.name;
        document.getElementById("recommendedTag" + slot).textContent = car.tag;
        document.getElementById("recommendedReason" + slot).textContent = car.reason;
        document.getElementById("recommendedDetails" + slot).textContent = car.details;
        document.getElementById("recommendedImage" + slot).src = car.image;
        document.getElementById("recommendedImage" + slot).alt = car.name;

        const tagBox = document.getElementById("quizTags" + slot);
        tagBox.innerHTML = "";

        car.tags.forEach(tag => {
            const span = document.createElement("span");
            span.textContent = tag;
            tagBox.appendChild(span);
        });
    }

    function recommendCar() {
        const answers = {
            budget: document.getElementById("quizBudget").value,
            type: document.getElementById("quizType").value,
            usage: document.getElementById("quizUsage").value,
            seats: document.getElementById("quizSeats").value,
            style: document.getElementById("quizStyle").value,
            power: document.getElementById("quizPower").value,
            fuel: document.getElementById("quizFuel").value,
            road: document.getElementById("quizRoad").value,
            priority: document.getElementById("quizPriority").value,
            cargo: document.getElementById("quizCargo").value,
            availability: document.getElementById("quizAvailability").value,
            personality: document.getElementById("quizPersonality").value
        };

        const rankedCars = recommendationCars
            .map(car => {
                return {
                    ...car,
                    score: calculateCarScore(car, answers)
                };
            })
            .sort((a, b) => b.score - a.score);

        let firstResult = rankedCars[0];
        let secondResult = rankedCars[1];

        if (firstResult.name === "Toyota GR Corolla") {
            const supra = rankedCars.find(car => car.name === "Toyota GR Supra");
            if (supra && supra.score >= secondResult.score - 8) {
                secondResult = supra;
            }
        }

        if (firstResult.name === "Toyota GR Supra") {
            const grCorolla = rankedCars.find(car => car.name === "Toyota GR Corolla");
            if (grCorolla && grCorolla.score >= secondResult.score - 8) {
                secondResult = grCorolla;
            }
        }

        if (firstResult.name === secondResult.name) {
            secondResult = rankedCars.find(car => car.name !== firstResult.name);
        }

        renderRecommendation(1, firstResult);
        renderRecommendation(2, secondResult);
        updateCompareLink(firstResult, secondResult);
    }

    function resetQuiz() {
        document.getElementById("quizBudget").value = "";
        document.getElementById("quizType").value = "";
        document.getElementById("quizUsage").value = "";
        document.getElementById("quizSeats").value = "";
        document.getElementById("quizStyle").value = "";
        document.getElementById("quizPower").value = "";
        document.getElementById("quizFuel").value = "";
        document.getElementById("quizRoad").value = "";
        document.getElementById("quizPriority").value = "";
        document.getElementById("quizCargo").value = "";
        document.getElementById("quizAvailability").value = "";
        document.getElementById("quizPersonality").value = "";

        const defaultCar1 = {
            name: "Toyota Vios",
            tag: "Budget-friendly daily car",
            reason: "Reason: Suitable for daily driving and budget-friendly.",
            details: "This recommendation will update based on your selected answers.",
            image: "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80",
            tags: ["Daily Driving", "Budget Friendly", "5 Seats"]
        };

        const defaultCar2 = {
            name: "Toyota Yaris",
            tag: "Compact city hatchback",
            reason: "Reason: Suitable for users who want a compact and affordable Toyota.",
            details: "This second option gives customers another suitable Toyota choice.",
            image: "https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1200&q=80",
            tags: ["Compact", "City Use", "Affordable"]
        };

        renderRecommendation(1, defaultCar1);
        renderRecommendation(2, defaultCar2);
        updateCompareLink(defaultCar1, defaultCar2);
    }
</script>

</body>
</html>