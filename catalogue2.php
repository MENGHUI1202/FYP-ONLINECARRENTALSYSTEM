<?php
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

$availableCars = [
    [
        "id" => 1,
        "name" => "Toyota Vios",
        "type" => "Sedan",
        "price" => 95500,
        "priceText" => "From RM 95,500",
        "monthly" => "Est. RM 1,250 / month",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "description" => "Compact sedan suitable for daily city driving with comfort, safety and fuel efficiency.",
        "image" => "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 2,
        "name" => "Toyota Yaris",
        "type" => "Hatchback",
        "price" => 88000,
        "priceText" => "From RM 88,000",
        "monthly" => "Est. RM 1,150 / month",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "description" => "Sporty hatchback designed for modern users who prefer compact size and stylish design.",
        "image" => "https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 3,
        "name" => "Toyota Corolla Cross",
        "type" => "SUV",
        "price" => 130400,
        "priceText" => "From RM 130,400",
        "monthly" => "Est. RM 1,700 / month",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Hybrid",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "description" => "Modern SUV that combines comfort, safety, practicality and efficient hybrid performance.",
        "image" => "https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 4,
        "name" => "Toyota Hilux",
        "type" => "Pickup",
        "price" => 110880,
        "priceText" => "From RM 110,880",
        "monthly" => "Est. RM 1,450 / month",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Diesel",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "description" => "Strong and durable pickup truck suitable for work, travel and outdoor adventure.",
        "image" => "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80"
    ]
];

$bookingCars = [
    [
        "id" => 5,
        "name" => "Toyota Camry",
        "type" => "Sedan",
        "price" => 220800,
        "priceText" => "From RM 220,800",
        "waiting" => "2 - 4 weeks",
        "bookingFee" => "RM 1,000",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Booking Required",
        "description" => "Premium sedan with elegant design, advanced comfort and smooth performance.",
        "image" => "https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 6,
        "name" => "Toyota Innova Zenix",
        "type" => "MPV",
        "price" => 165000,
        "priceText" => "From RM 165,000",
        "waiting" => "3 - 6 weeks",
        "bookingFee" => "RM 800",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Hybrid",
        "seats" => "7 Seats",
        "status" => "Booking Required",
        "description" => "Spacious family MPV with comfortable seating and practical driving features.",
        "image" => "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 7,
        "name" => "Toyota Alphard",
        "type" => "MPV",
        "price" => 538000,
        "priceText" => "From RM 538,000",
        "waiting" => "1 - 3 months",
        "bookingFee" => "RM 2,000",
        "year" => "2025",
        "transmission" => "Automatic",
        "fuel" => "Petrol",
        "seats" => "7 Seats",
        "status" => "Booking Required",
        "description" => "Luxury MPV designed for premium comfort, executive travel and spacious cabin experience.",
        "image" => "https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=1200&q=80"
    ],
    [
        "id" => 8,
        "name" => "Toyota GR Corolla",
        "type" => "Hatchback",
        "price" => 355000,
        "priceText" => "From RM 355,000",
        "waiting" => "Limited Stock",
        "bookingFee" => "RM 2,000",
        "year" => "2025",
        "transmission" => "Manual",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Booking Required",
        "description" => "Performance hatchback built for users who enjoy sporty handling and powerful driving.",
        "image" => "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80"
    ]
];

$allCars = array_merge($availableCars, $bookingCars);
$totalCars = count($allCars);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - Online Toyota Car Selling</title>

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
            background: #ffffff;
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

        .nav-center a.active:hover {
            color: #ffffff;
            background: linear-gradient(135deg, #b7151b, #6f080c);
            border-color: #b7151b;
            box-shadow: 0 12px 28px rgba(215, 25, 32, 0.35);
            transform: translateY(-2px);
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

        .catalogue-hero {
            min-height: 610px;
            background:
                linear-gradient(to right, rgba(0, 0, 0, 0.84), rgba(0, 0, 0, 0.45), rgba(215, 25, 32, 0.18)),
                url("https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1800&q=80");
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding: 82px 6%;
            position: relative;
            overflow: hidden;
        }

        .catalogue-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 46px 46px;
            opacity: 0.35;
        }

        .catalogue-hero-content {
            max-width: 780px;
            color: #fff;
            position: relative;
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

        .catalogue-hero h1 {
            font-size: 58px;
            line-height: 1.12;
            margin-bottom: 20px;
            font-weight: 900;
        }

        .catalogue-hero p {
            font-size: 18px;
            line-height: 1.8;
            color: #eeeeee;
            margin-bottom: 34px;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 30px;
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
            cursor: pointer;
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
            cursor: pointer;
        }

        .white-btn:hover {
            background: transparent;
            color: #fff;
            transform: translateY(-3px);
        }

        .hero-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-tags span {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 9px 15px;
            border-radius: 22px;
            font-size: 13px;
            font-weight: 800;
        }

        .section {
            padding: 72px 6%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 42px;
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
            margin-bottom: 12px;
        }

        .section-title h2 {
            font-size: 40px;
            color: #111;
            margin-bottom: 12px;
            font-weight: 900;
            letter-spacing: -0.8px;
        }

        .section-title p {
            max-width: 780px;
            margin: 0 auto;
            color: #666;
            line-height: 1.65;
            font-size: 16px;
        }

        .filter-section {
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.08), transparent 32%),
                linear-gradient(180deg, #ffffff, #fbfbfb);
            padding-top: 0;
            padding-bottom: 58px;
            overflow: visible;
        }

        .filter-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 34px;
            padding: 28px;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.065);
            border: 1px solid rgba(215, 25, 32, 0.12);
            margin-top: -85px;
            position: relative;
            z-index: 10;
            backdrop-filter: blur(12px);
            overflow: hidden;
        }

        .filter-box::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(215, 25, 32, 0.06);
            top: -150px;
            right: -90px;
        }

        .filter-box::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(17, 17, 17, 0.035);
            bottom: -100px;
            left: -65px;
        }

        .filter-inner {
            position: relative;
            z-index: 2;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .filter-heading {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .filter-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 26px;
            box-shadow: 0 14px 28px rgba(215, 25, 32, 0.24);
        }

        .filter-header h2 {
            font-size: 29px;
            color: #111;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .filter-header p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .result-count {
            background: #111;
            border: 1px solid rgba(215, 25, 32, 0.12);
            border-radius: 22px;
            padding: 12px 18px;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
        }

        .result-count span {
            color: #ffb7bb;
        }

        .filter-panel {
            background: #f8f8f8;
            border: 1px solid #eeeeee;
            border-radius: 28px;
            padding: 20px;
        }

        .search-row {
            display: grid;
            grid-template-columns: 1.5fr auto auto auto;
            gap: 14px;
            margin-bottom: 16px;
        }

        .search-field {
            position: relative;
        }

        .search-field input {
            width: 100%;
            height: 58px;
            padding: 0 20px 0 54px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            border-radius: 22px;
            background: #fff;
            color: #222;
            font-size: 15px;
            font-weight: 800;
            outline: none;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
        }

        .search-field input:focus {
            border-color: rgba(215, 25, 32, 0.55);
            box-shadow: 0 12px 25px rgba(215, 25, 32, 0.1);
        }

        .search-field span {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
        }

        .filter-action-main {
            height: 58px;
            padding: 0 30px;
            border: none;
            border-radius: 22px;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(215, 25, 32, 0.22);
            transition: 0.3s;
            white-space: nowrap;
        }

        .filter-action-main:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #b7151b, #6f080c);
        }

        .filter-action-loan {
            height: 58px;
            padding: 0 28px;
            border: none;
            border-radius: 22px;
            background: #111;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.14);
            transition: 0.3s;
            white-space: nowrap;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .filter-action-loan:hover {
            transform: translateY(-2px);
            background: #d71920;
        }

        .filter-action-reset {
            height: 58px;
            padding: 0 26px;
            border: 1.5px solid #d71920;
            border-radius: 22px;
            background: #fff;
            color: #d71920;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
            white-space: nowrap;
        }

        .filter-action-reset:hover {
            background: #111;
            border-color: #111;
            color: #fff;
            transform: translateY(-2px);
        }

        .advanced-filters {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .filter-card {
            position: relative;
            background: #fff;
            border: 1px solid #eeeeee;
            border-radius: 22px;
            padding: 13px 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            transition: 0.3s;
        }

        .filter-card:hover {
            border-color: rgba(215, 25, 32, 0.28);
            box-shadow: 0 12px 25px rgba(215, 25, 32, 0.08);
            transform: translateY(-2px);
        }

        .filter-card label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #333;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .filter-card label span {
            width: 26px;
            height: 26px;
            border-radius: 10px;
            background: #ffe8e9;
            color: #d71920;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
        }

        .filter-card select {
            width: 100%;
            height: 38px;
            padding: 0 12px;
            border: none;
            border-radius: 14px;
            background: #f7f7f7;
            color: #222;
            font-size: 13px;
            font-weight: 800;
            outline: none;
            cursor: pointer;
        }

        .quick-filter-chips {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .chip-btn {
            border: 1px solid rgba(215, 25, 32, 0.18);
            background: #fff;
            color: #333;
            padding: 9px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
        }

        .chip-btn:hover,
        .chip-btn.active {
            background: #d71920;
            color: #fff;
            border-color: #d71920;
            box-shadow: 0 8px 18px rgba(215, 25, 32, 0.16);
        }

        .status-note-under-filter {
            max-width: 1080px;
            margin: 58px auto 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            position: relative;
            z-index: 20;
        }

        .status-note-card {
            background: #ffffff;
            border: 1px solid rgba(215, 25, 32, 0.16);
            border-radius: 24px;
            padding: 22px 24px;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.045);
            position: relative;
            z-index: 21;
        }

        .status-note-card h3 {
            font-size: 19px;
            color: #111;
            margin-bottom: 9px;
            font-weight: 900;
        }

        .status-note-card p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }

        .toolbar-section {
            background: #fff;
            padding: 0 6% 34px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            background: #111;
            color: #fff;
            padding: 15px 18px;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
        }

        .toolbar-left {
            font-weight: 900;
            font-size: 14px;
        }

        .toolbar-left span {
            color: #ffb7bb;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar select {
            height: 40px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            padding: 0 14px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-weight: 800;
            outline: none;
        }

        .toolbar select option {
            color: #111;
        }

        .view-mode-group {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px;
            border-radius: 18px;
            gap: 4px;
        }

        .view-btn {
            width: 40px;
            height: 40px;
            border-radius: 15px;
            border: 1px solid transparent;
            background: transparent;
            color: #fff;
            cursor: pointer;
            font-weight: 900;
            transition: 0.3s;
        }

        .view-btn.active,
        .view-btn:hover {
            background: #d71920;
            border-color: #d71920;
        }

        .models-wrapper {
            background:
                linear-gradient(135deg, #f7f7f7, #ffffff),
                radial-gradient(circle at bottom right, rgba(215, 25, 32, 0.08), transparent 28%);
            padding-top: 58px;
        }

        .models-section {
            margin-bottom: 55px;
        }

        .models-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .models-header h2 {
            font-size: 36px;
            color: #111;
            font-weight: 900;
            margin-bottom: 7px;
        }

        .models-header p {
            color: #666;
            line-height: 1.6;
            max-width: 760px;
        }

        .model-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .model-grid.list-view {
            grid-template-columns: 1fr;
        }

        .model-card {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.085);
            transition: 0.35s;
            border: 1px solid rgba(215, 25, 32, 0.1);
            position: relative;
        }

        .model-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 52px rgba(0, 0, 0, 0.13);
            border-color: rgba(215, 25, 32, 0.32);
        }

        .model-grid.list-view .model-card {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 260px;
            border-radius: 26px;
        }

        .model-image-wrap {
            position: relative;
            height: 218px;
            overflow: hidden;
        }

        .model-grid.list-view .model-image-wrap {
            width: 260px;
            height: 260px;
            min-height: 260px;
            aspect-ratio: 1 / 1;
            border-radius: 0;
        }

        .model-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.45s;
        }

        .model-card:hover .model-img {
            transform: scale(1.08);
        }

        .model-image-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.62), transparent 60%);
        }

        .status-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(215, 25, 32, 0.92);
            color: #fff;
            padding: 8px 13px;
            border-radius: 18px;
            font-size: 11.5px;
            font-weight: 900;
            z-index: 2;
            box-shadow: 0 10px 20px rgba(215, 25, 32, 0.24);
        }

        .status-badge.booking {
            background: rgba(17, 17, 17, 0.88);
        }

        .type-badge {
            position: absolute;
            bottom: 16px;
            left: 16px;
            color: #fff;
            font-weight: 900;
            z-index: 2;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(10px);
            padding: 8px 13px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            font-size: 12.5px;
        }

        .year-badge {
            position: absolute;
            bottom: 16px;
            right: 16px;
            color: #fff;
            font-weight: 900;
            z-index: 2;
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(10px);
            padding: 8px 13px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 12.5px;
        }

        .model-info {
            padding: 22px;
        }

        .model-grid.list-view .model-info {
            padding: 24px;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            column-gap: 22px;
            align-items: start;
        }

        .model-info h3 {
            font-size: 22px;
            color: #111;
            margin-bottom: 8px;
            font-weight: 900;
        }

        .model-price {
            font-size: 19px;
            font-weight: 900;
            color: #d71920;
            margin-bottom: 6px;
        }

        .model-extra-price {
            color: #666;
            font-size: 13.5px;
            font-weight: 800;
            margin-bottom: 13px;
        }

        .model-description {
            color: #666;
            font-size: 13.5px;
            line-height: 1.6;
            margin-bottom: 17px;
            min-height: 43px;
        }

        .model-specs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .spec-box {
            background: #f7f7f7;
            border: 1px solid #eeeeee;
            padding: 11px 8px;
            border-radius: 16px;
            text-align: center;
        }

        .spec-box small {
            display: block;
            color: #888;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .spec-box span {
            color: #222;
            font-size: 12.5px;
            font-weight: 900;
        }

        .model-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .action-view {
            grid-column: 1 / -1;
            min-height: 46px;
            font-size: 14px !important;
            border-radius: 18px !important;
        }

        .outline-btn,
        .solid-btn,
        .black-btn,
        .loan-calc-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 18px;
            font-size: 12.5px;
            font-weight: 900;
            transition: 0.3s;
            text-align: center;
            cursor: pointer;
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

        .solid-btn {
            border: 1.5px solid #d71920;
            color: #fff;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            box-shadow: 0 10px 22px rgba(215, 25, 32, 0.18);
        }

        .solid-btn:hover {
            background: #111;
            border-color: #111;
            color: #fff;
            transform: translateY(-2px);
        }

        .black-btn {
            border: 1.5px solid #111;
            color: #fff;
            background: #111;
        }

        .black-btn:hover {
            background: #d71920;
            border-color: #d71920;
            transform: translateY(-2px);
        }

        .loan-calc-btn {
            border: 1.5px solid #111;
            color: #111;
            background: #fff;
        }

        .loan-calc-btn:hover {
            background: #111;
            color: #fff;
            border-color: #111;
            transform: translateY(-2px);
        }

        .no-result {
            display: none;
            text-align: center;
            background: #fff;
            padding: 38px 25px;
            border-radius: 28px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
            margin-top: 25px;
        }

        .no-result h3 {
            font-size: 25px;
            color: #111;
            margin-bottom: 10px;
        }

        .no-result p {
            color: #666;
            margin-bottom: 18px;
        }

        .cta-section {
            background: #fff;
            padding-top: 58px;
            padding-bottom: 58px;
        }

        .cta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .cta-card {
            border-radius: 32px;
            padding: 40px;
            color: #fff;
            position: relative;
            overflow: hidden;
            min-height: 285px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 24px 55px rgba(0, 0, 0, 0.12);
        }

        .cta-card.loan {
            background:
                linear-gradient(135deg, rgba(215, 25, 32, 0.96), rgba(111, 8, 12, 0.94)),
                url("https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80");
            background-size: cover;
            background-position: center;
        }

        .cta-card.booking {
            background:
                linear-gradient(135deg, rgba(17, 17, 17, 0.95), rgba(70, 70, 70, 0.82)),
                url("https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80");
            background-size: cover;
            background-position: center;
        }

        .cta-card h2,
        .cta-card p,
        .cta-card a {
            position: relative;
            z-index: 2;
        }

        .cta-card h2 {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 14px;
            line-height: 1.2;
        }

        .cta-card p {
            color: #f1f1f1;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .quiz-section {
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.08), transparent 32%),
                #f7f7f7;
            padding-top: 70px;
            padding-bottom: 70px;
        }

        .quiz-wrapper {
            max-width: 1180px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 34px;
            border: 1px solid rgba(215, 25, 32, 0.14);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
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

        .faq-section {
            background: #ffffff;
            padding-top: 58px;
            padding-bottom: 58px;
        }

        .faq-container {
            max-width: 940px;
            margin: 0 auto;
        }

        .faq-item {
            background: #fff;
            border-radius: 20px;
            margin-bottom: 14px;
            border: 1px solid rgba(215, 25, 32, 0.1);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            transition: 0.3s;
        }

        .faq-question {
            padding: 21px 26px;
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
            flex-shrink: 0;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }

        .faq-answer p {
            padding: 0 26px 21px;
            color: #666;
            line-height: 1.7;
        }

        .faq-item.active .faq-answer {
            max-height: 190px;
        }

        .compare-tray {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(130px);
            width: min(920px, calc(100% - 30px));
            background: rgba(17, 17, 17, 0.96);
            color: #fff;
            border-radius: 28px;
            padding: 18px 22px;
            box-shadow: 0 24px 55px rgba(0, 0, 0, 0.28);
            z-index: 998;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            transition: 0.35s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .compare-tray.show {
            transform: translateX(-50%) translateY(0);
        }

        .compare-list {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .compare-pill {
            background: rgba(215, 25, 32, 0.9);
            padding: 7px 12px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 800;
        }

        .tray-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .tray-btn {
            border: none;
            border-radius: 20px;
            padding: 11px 18px;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
        }

        .tray-btn.compare-now {
            background: #d71920;
            color: #fff;
        }

        .tray-btn.clear {
            background: #fff;
            color: #111;
        }

        .footer {
            background: #111;
            color: #fff;
            padding: 56px 6% 24px;
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
            gap: 36px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .footer h3 {
            font-size: 21px;
            margin-bottom: 16px;
            color: #fff;
        }

        .footer p,
        .footer a {
            color: #cfcfcf;
            line-height: 1.7;
            font-size: 14px;
            margin-bottom: 7px;
            display: block;
        }

        .footer a:hover {
            color: #d71920;
        }

        .footer-bottom {
            border-top: 1px solid #333;
            padding-top: 20px;
            text-align: center;
            color: #aaa;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 1450px) {
            .model-grid {
                grid-template-columns: repeat(3, 1fr);
            }
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

            .advanced-filters {
                grid-template-columns: repeat(3, 1fr);
            }

            .search-row {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .search-field {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 1150px) {
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

            .status-note-under-filter,
            .quiz-wrapper {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .model-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cta-grid,
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .advanced-filters {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar {
                min-height: 82px;
            }
        }

        @media (max-width: 768px) {
            .catalogue-hero {
                min-height: 570px;
                padding: 75px 5%;
            }

            .catalogue-hero h1 {
                font-size: 39px;
            }

            .catalogue-hero p {
                font-size: 16px;
            }

            .section {
                padding: 58px 5%;
            }

            .filter-box {
                margin-top: -70px;
                padding: 22px;
            }

            .search-row,
            .advanced-filters,
            .model-grid,
            .footer-grid,
            .cta-grid,
            .quiz-form-grid {
                grid-template-columns: 1fr;
            }

            .filter-action-main,
            .filter-action-reset,
            .filter-action-loan {
                width: 100%;
            }

            .toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .toolbar-right,
            .toolbar select {
                width: 100%;
            }

            .section-title h2,
            .models-header h2 {
                font-size: 33px;
            }

            .model-specs {
                grid-template-columns: 1fr 1fr;
            }

            .cta-card {
                padding: 32px 24px;
                border-radius: 28px;
            }

            .compare-tray {
                flex-direction: column;
                align-items: flex-start;
            }

            .tray-actions {
                width: 100%;
            }

            .tray-btn {
                flex: 1;
            }

            .quiz-left,
            .quiz-result {
                padding: 32px 24px;
            }
        }

        @media (max-width: 480px) {
            .logo-text small,
            .username {
                display: none;
            }

            .login-btn,
            .logout-btn {
                padding: 9px 15px;
                font-size: 13px;
            }

            .catalogue-hero h1 {
                font-size: 33px;
            }

            .primary-btn,
            .white-btn {
                padding: 12px 24px;
                font-size: 14px;
            }

            .model-actions,
            .model-specs {
                grid-template-columns: 1fr;
            }

            .filter-icon {
                display: none;
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
        <a href="homepage.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="catalogue.php" class="active">Catalogue</a>
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

<section class="catalogue-hero">
    <div class="catalogue-hero-content">
        <div class="hero-badge"><span></span> TOYOTA MODEL CATALOGUE</div>
        <h1>Explore Toyota Models</h1>
        <p>Browse available Toyota cars or book upcoming Toyota models based on your needs, budget and lifestyle.</p>

        <div class="hero-buttons">
            <a href="#availableModels" class="primary-btn">View Available Models</a>
            <a href="#bookingModels" class="white-btn">View Booking Models</a>
            <a href="#carQuiz" class="white-btn">Try Car Quiz</a>
        </div>

        <div class="hero-tags">
            <span>Sedan</span>
            <span>SUV</span>
            <span>MPV</span>
            <span>Pickup</span>
            <span>Hybrid</span>
        </div>
    </div>
</section>

<section class="section filter-section">
    <div class="filter-box">
        <div class="filter-inner">
            <div class="filter-header">
                <div class="filter-heading">
                    <div class="filter-icon">⚙</div>
                    <div>
                        <h2>Advanced Toyota Finder</h2>
                        <p>Search smarter by model name, body type, price, fuel, transmission, seats and budget.</p>
                    </div>
                </div>

                <div class="result-count">
                    Showing <span id="resultCount"><?php echo $totalCars; ?></span> Toyota Models
                </div>
            </div>

            <div class="filter-panel">
                <div class="search-row">
                    <div class="search-field">
                        <span>🔍</span>
                        <input type="text" id="searchInput" placeholder="Search Toyota model, e.g. Vios, Camry, Hilux..." onkeyup="applyFilters()">
                    </div>

                    <button class="filter-action-main" onclick="applyFilters()">Search Models</button>
                    <a href="loan_calculator.php" class="filter-action-loan">Calculate Loan / Monthly Fee</a>
                    <button class="filter-action-reset" onclick="resetFilters()">Reset Filter</button>
                </div>

                <div class="advanced-filters">
                    <div class="filter-card">
                        <label><span>🚗</span> Body Type</label>
                        <select id="typeFilter" onchange="applyFilters()">
                            <option value="all">All Body Types</option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="MPV">MPV</option>
                            <option value="Pickup">Pickup</option>
                            <option value="Hatchback">Hatchback</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>💰</span> Price Range</label>
                        <select id="priceFilter" onchange="applyFilters()">
                            <option value="all">All Prices</option>
                            <option value="below100">Below RM 100,000</option>
                            <option value="100to150">RM 100,000 - RM 150,000</option>
                            <option value="above150">Above RM 150,000</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>⛽</span> Fuel Type</label>
                        <select id="fuelFilter" onchange="applyFilters()">
                            <option value="all">All Fuel Types</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Diesel">Diesel</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>⚙</span> Transmission</label>
                        <select id="transmissionFilter" onchange="applyFilters()">
                            <option value="all">All Transmission</option>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>👥</span> Seats</label>
                        <select id="seatFilter" onchange="applyFilters()">
                            <option value="all">All Seats</option>
                            <option value="5 Seats">5 Seats</option>
                            <option value="7 Seats">7 Seats</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>📌</span> Model Status</label>
                        <select id="statusFilter" onchange="applyFilters()">
                            <option value="all">All Status</option>
                            <option value="available">Available</option>
                            <option value="booking">Booking</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>📅</span> Monthly Budget</label>
                        <select id="budgetFilter" onchange="applyFilters()">
                            <option value="all">All Budgets</option>
                            <option value="below1000">Below RM 1,000</option>
                            <option value="below1500">Below RM 1,500</option>
                            <option value="below2000">Below RM 2,000</option>
                        </select>
                    </div>

                    <div class="filter-card">
                        <label><span>⭐</span> Quick Match</label>
                        <select id="quickMatchFilter" onchange="quickMatch()">
                            <option value="all">All Toyota Cars</option>
                            <option value="family">Family Friendly</option>
                            <option value="budget">Budget Choice</option>
                            <option value="premium">Premium Models</option>
                            <option value="performance">Performance</option>
                        </select>
                    </div>
                </div>

                <div class="quick-filter-chips">
                    <button class="chip-btn active" onclick="setQuickChip(this, 'all')">All</button>
                    <button class="chip-btn" onclick="setQuickChip(this, 'available')">Available Now</button>
                    <button class="chip-btn" onclick="setQuickChip(this, 'booking')">Booking Required</button>
                    <button class="chip-btn" onclick="setQuickChip(this, 'Hybrid')">Hybrid</button>
                    <button class="chip-btn" onclick="setQuickChip(this, 'Petrol')">Petrol</button>
                    <button class="chip-btn" onclick="setQuickChip(this, 'Diesel')">Diesel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="status-note-under-filter">
        <div class="status-note-card">
            <h3>Available Models</h3>
            <p>Cars that are currently available for viewing, test drive booking and loan application assistance.</p>
        </div>

        <div class="status-note-card">
            <h3>Booking Models</h3>
            <p>Cars that require customers to submit a booking request first before further arrangement.</p>
        </div>
    </div>
</section>

<section class="toolbar-section">
    <div class="toolbar">
        <div class="toolbar-left">
            Showing <span id="toolbarCount"><?php echo $totalCars; ?></span> Toyota models
        </div>

        <div class="toolbar-right">
            <select id="sortFilter" onchange="sortModels()">
                <option value="default">Sort By: Default</option>
                <option value="low">Price Low to High</option>
                <option value="high">Price High to Low</option>
                <option value="az">Name A-Z</option>
            </select>

            <div class="view-mode-group">
                <button class="view-btn active" id="gridViewBtn" onclick="setViewMode('grid')">▦</button>
                <button class="view-btn" id="listViewBtn" onclick="setViewMode('list')">☰</button>
            </div>
        </div>
    </div>
</section>

<section class="section models-wrapper">
    <div class="models-section" id="availableModels">
        <div class="models-header">
            <div>
                <span class="section-label">AVAILABLE NOW</span>
                <h2>Available Toyota Models</h2>
                <p>These Toyota models are currently available for viewing, loan application and test drive booking.</p>
            </div>
        </div>

        <div class="model-grid" id="availableGrid">
            <?php foreach ($availableCars as $car): ?>
                <div class="model-card"
                     data-status="available"
                     data-name="<?php echo strtolower($car['name']); ?>"
                     data-display-name="<?php echo $car['name']; ?>"
                     data-type="<?php echo $car['type']; ?>"
                     data-price="<?php echo $car['price']; ?>"
                     data-fuel="<?php echo $car['fuel']; ?>"
                     data-transmission="<?php echo $car['transmission']; ?>"
                     data-seats="<?php echo $car['seats']; ?>"
                     data-monthly="<?php echo preg_replace('/[^0-9]/', '', $car['monthly']); ?>">

                    <div class="model-image-wrap">
                        <img src="<?php echo $car['image']; ?>" class="model-img" alt="<?php echo $car['name']; ?>">
                        <div class="status-badge"><?php echo $car['status']; ?></div>
                        <div class="type-badge"><?php echo $car['type']; ?></div>
                        <div class="year-badge"><?php echo $car['year']; ?></div>
                    </div>

                    <div class="model-info">
                        <div class="model-main-content">
                            <h3><?php echo $car['name']; ?></h3>
                            <p class="model-price"><?php echo $car['priceText']; ?></p>
                            <p class="model-extra-price"><?php echo $car['monthly']; ?></p>
                            <p class="model-description"><?php echo $car['description']; ?></p>
                        </div>

                        <div class="model-side-content">
                            <div class="model-specs">
                                <div class="spec-box">
                                    <small>FUEL</small>
                                    <span><?php echo $car['fuel']; ?></span>
                                </div>

                                <div class="spec-box">
                                    <small>GEAR</small>
                                    <span><?php echo $car['transmission']; ?></span>
                                </div>

                                <div class="spec-box">
                                    <small>SEATS</small>
                                    <span><?php echo $car['seats']; ?></span>
                                </div>
                            </div>

                            <div class="model-actions">
                                <a href="car_details.php?id=<?php echo $car['id']; ?>" class="solid-btn action-view">View Details</a>
                                <button type="button" class="outline-btn" onclick="addCompare('<?php echo $car['name']; ?>')">Compare</button>
                                <a href="test_drive.php?car=<?php echo urlencode($car['name']); ?>" class="outline-btn">Test Drive</a>
                                <a href="loan_application.php?car=<?php echo urlencode($car['name']); ?>" class="black-btn">Apply Loan</a>
                                <a href="loan_calculator.php?car=<?php echo urlencode($car['name']); ?>&price=<?php echo $car['price']; ?>" class="loan-calc-btn">Calculate Loan</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="models-section" id="bookingModels">
        <div class="models-header">
            <div>
                <span class="section-label">BOOKING REQUIRED</span>
                <h2>Booking Toyota Models</h2>
                <p>These Toyota models require advance booking. Customers can submit a booking request and our team will contact them for availability and further arrangement.</p>
            </div>
        </div>

        <div class="model-grid" id="bookingGrid">
            <?php foreach ($bookingCars as $car): ?>
                <div class="model-card"
                     data-status="booking"
                     data-name="<?php echo strtolower($car['name']); ?>"
                     data-display-name="<?php echo $car['name']; ?>"
                     data-type="<?php echo $car['type']; ?>"
                     data-price="<?php echo $car['price']; ?>"
                     data-fuel="<?php echo $car['fuel']; ?>"
                     data-transmission="<?php echo $car['transmission']; ?>"
                     data-seats="<?php echo $car['seats']; ?>"
                     data-monthly="999999">

                    <div class="model-image-wrap">
                        <img src="<?php echo $car['image']; ?>" class="model-img" alt="<?php echo $car['name']; ?>">
                        <div class="status-badge booking"><?php echo $car['status']; ?></div>
                        <div class="type-badge"><?php echo $car['type']; ?></div>
                        <div class="year-badge"><?php echo $car['year']; ?></div>
                    </div>

                    <div class="model-info">
                        <div class="model-main-content">
                            <h3><?php echo $car['name']; ?></h3>
                            <p class="model-price"><?php echo $car['priceText']; ?></p>
                            <p class="model-extra-price">Waiting: <?php echo $car['waiting']; ?> | Fee: <?php echo $car['bookingFee']; ?></p>
                            <p class="model-description"><?php echo $car['description']; ?></p>
                        </div>

                        <div class="model-side-content">
                            <div class="model-specs">
                                <div class="spec-box">
                                    <small>FUEL</small>
                                    <span><?php echo $car['fuel']; ?></span>
                                </div>

                                <div class="spec-box">
                                    <small>GEAR</small>
                                    <span><?php echo $car['transmission']; ?></span>
                                </div>

                                <div class="spec-box">
                                    <small>SEATS</small>
                                    <span><?php echo $car['seats']; ?></span>
                                </div>
                            </div>

                            <div class="model-actions">
                                <a href="car_details.php?id=<?php echo $car['id']; ?>" class="solid-btn action-view">View Details</a>
                                <button type="button" class="outline-btn" onclick="addCompare('<?php echo $car['name']; ?>')">Compare</button>
                                <a href="booking.php?car=<?php echo urlencode($car['name']); ?>" class="outline-btn">Book Now</a>
                                <a href="contact.php?car=<?php echo urlencode($car['name']); ?>" class="black-btn">Ask Availability</a>
                                <a href="loan_calculator.php?car=<?php echo urlencode($car['name']); ?>&price=<?php echo $car['price']; ?>" class="loan-calc-btn">Calculate Loan</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="no-result" id="noResult">
        <h3>No Toyota models found.</h3>
        <p>Try changing your search keyword or filter options.</p>
        <button class="filter-action-reset" onclick="resetFilters()">Reset Filters</button>
    </div>
</section>

<section class="section cta-section">
    <div class="cta-grid">
        <div class="cta-card loan">
            <h2>Need Help Applying for a Car Loan?</h2>
            <p>Submit your details online and our team will help review your information before forwarding your application to our partnered bank.</p>
            <a href="loan_application.php" class="white-btn">Apply for Loan Assistance</a>
        </div>

        <div class="cta-card booking">
            <h2>Interested in a Booking Model?</h2>
            <p>Submit a booking request and our team will contact you about stock availability, waiting time and next steps.</p>
            <a href="booking.php" class="white-btn">Book a Toyota Model</a>
        </div>
    </div>
</section>

<section class="section quiz-section" id="carQuiz">
    <div class="section-title">
        <span class="section-label">SMART RECOMMENDATION</span>
        <h2>Car Recommendation Quiz</h2>
        <p>Answer a few simple questions and the system will recommend two suitable Toyota model options for you.</p>
    </div>

    <div class="quiz-wrapper">
        <div class="quiz-left">
            <span class="section-label">FIND YOUR MATCH</span>
            <h3>Choose Your Toyota Smartly</h3>
            <p>This quiz can recommend available, booking, premium and sport models based on your needs.</p>

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
</section>

<section class="section faq-section">
    <div class="section-title">
        <span class="section-label">FAQ</span>
        <h2>Buying Tips & Questions</h2>
        <p>Understand the difference between available and booking models before choosing your Toyota vehicle.</p>
    </div>

    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">
                What is the difference between Available Models and Booking Models?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Available models are currently ready for viewing, test drive booking and loan application, while booking models require customers to submit a booking request first.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I book a test drive for available models?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, available models include a Book Test Drive button for users to submit a test drive request.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I apply for loan assistance from the catalogue page?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, users can click Apply Loan to submit their information for company-assisted bank loan application.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I compare available and booking models?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, users can compare both available and booking models side by side through the compare function.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                How long does booking take?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Waiting time depends on the selected Toyota model and stock availability. Our team will contact customers after the booking request is submitted.</p>
            </div>
        </div>
    </div>
</section>

<div class="compare-tray" id="compareTray">
    <div>
        <strong>Compare Selected:</strong>
        <div class="compare-list" id="compareList"></div>
    </div>

    <div class="tray-actions">
        <button class="tray-btn compare-now" onclick="goCompare()">Compare Now</button>
        <button class="tray-btn clear" onclick="clearCompare()">Clear</button>
    </div>
</div>

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
            <a href="booking.php">Booking Model</a>
            <a href="contact.php">Contact Us</a>
            <a href="about.php">About Us</a>
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
    const availableGrid = document.getElementById("availableGrid");
    const bookingGrid = document.getElementById("bookingGrid");
    const allCards = Array.from(document.querySelectorAll(".model-card"));
    const resultCount = document.getElementById("resultCount");
    const toolbarCount = document.getElementById("toolbarCount");
    const noResult = document.getElementById("noResult");
    const compareTray = document.getElementById("compareTray");
    const compareList = document.getElementById("compareList");
    let compareSelected = [];

    function toggleMenu() {
        document.getElementById("navMenu").classList.toggle("show");
    }

    function applyFilters() {
        const search = document.getElementById("searchInput").value.toLowerCase();
        const type = document.getElementById("typeFilter").value;
        const price = document.getElementById("priceFilter").value;
        const fuel = document.getElementById("fuelFilter").value;
        const transmission = document.getElementById("transmissionFilter").value;
        const seats = document.getElementById("seatFilter").value;
        const status = document.getElementById("statusFilter").value;
        const budget = document.getElementById("budgetFilter").value;

        let visibleCount = 0;

        allCards.forEach(card => {
            const cardName = card.dataset.name;
            const cardType = card.dataset.type;
            const cardPrice = parseInt(card.dataset.price);
            const cardFuel = card.dataset.fuel;
            const cardTransmission = card.dataset.transmission;
            const cardSeats = card.dataset.seats;
            const cardStatus = card.dataset.status;
            const cardMonthly = parseInt(card.dataset.monthly);

            let matchSearch = cardName.includes(search);
            let matchType = type === "all" || cardType === type;
            let matchFuel = fuel === "all" || cardFuel === fuel;
            let matchTransmission = transmission === "all" || cardTransmission === transmission;
            let matchSeats = seats === "all" || cardSeats === seats;
            let matchStatus = status === "all" || cardStatus === status;

            let matchPrice = true;
            if (price === "below100") {
                matchPrice = cardPrice < 100000;
            } else if (price === "100to150") {
                matchPrice = cardPrice >= 100000 && cardPrice <= 150000;
            } else if (price === "above150") {
                matchPrice = cardPrice > 150000;
            }

            let matchBudget = true;
            if (budget === "below1000") {
                matchBudget = cardMonthly < 1000;
            } else if (budget === "below1500") {
                matchBudget = cardMonthly < 1500;
            } else if (budget === "below2000") {
                matchBudget = cardMonthly < 2000;
            }

            if (matchSearch && matchType && matchPrice && matchFuel && matchTransmission && matchSeats && matchStatus && matchBudget) {
                card.style.display = "";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        resultCount.textContent = visibleCount;
        toolbarCount.textContent = visibleCount;
        noResult.style.display = visibleCount === 0 ? "block" : "none";
    }

    function resetFilters() {
        document.getElementById("searchInput").value = "";
        document.getElementById("typeFilter").value = "all";
        document.getElementById("priceFilter").value = "all";
        document.getElementById("fuelFilter").value = "all";
        document.getElementById("transmissionFilter").value = "all";
        document.getElementById("seatFilter").value = "all";
        document.getElementById("statusFilter").value = "all";
        document.getElementById("budgetFilter").value = "all";
        document.getElementById("quickMatchFilter").value = "all";
        document.getElementById("sortFilter").value = "default";

        document.querySelectorAll(".chip-btn").forEach(btn => btn.classList.remove("active"));
        document.querySelectorAll(".chip-btn")[0].classList.add("active");

        sortModels();
        applyFilters();
    }

    function setQuickChip(button, value) {
        document.querySelectorAll(".chip-btn").forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        document.getElementById("fuelFilter").value = "all";
        document.getElementById("statusFilter").value = "all";

        if (value === "available") {
            document.getElementById("statusFilter").value = "available";
        } else if (value === "booking") {
            document.getElementById("statusFilter").value = "booking";
        } else if (value === "Hybrid" || value === "Petrol" || value === "Diesel") {
            document.getElementById("fuelFilter").value = value;
        }

        applyFilters();
    }

    function quickMatch() {
        const match = document.getElementById("quickMatchFilter").value;

        document.getElementById("typeFilter").value = "all";
        document.getElementById("priceFilter").value = "all";
        document.getElementById("fuelFilter").value = "all";
        document.getElementById("transmissionFilter").value = "all";
        document.getElementById("seatFilter").value = "all";
        document.getElementById("statusFilter").value = "all";
        document.getElementById("budgetFilter").value = "all";

        if (match === "family") {
            document.getElementById("seatFilter").value = "7 Seats";
        } else if (match === "budget") {
            document.getElementById("priceFilter").value = "below100";
        } else if (match === "premium") {
            document.getElementById("priceFilter").value = "above150";
        } else if (match === "performance") {
            document.getElementById("transmissionFilter").value = "Manual";
        }

        applyFilters();
    }

    function sortModels() {
        const sortValue = document.getElementById("sortFilter").value;

        [availableGrid, bookingGrid].forEach(grid => {
            const cards = Array.from(grid.querySelectorAll(".model-card"));

            if (sortValue === "low") {
                cards.sort((a, b) => parseInt(a.dataset.price) - parseInt(b.dataset.price));
            } else if (sortValue === "high") {
                cards.sort((a, b) => parseInt(b.dataset.price) - parseInt(a.dataset.price));
            } else if (sortValue === "az") {
                cards.sort((a, b) => a.dataset.displayName.localeCompare(b.dataset.displayName));
            } else {
                cards.sort((a, b) => parseInt(a.querySelector(".solid-btn").href.split("id=")[1]) - parseInt(b.querySelector(".solid-btn").href.split("id=")[1]));
            }

            cards.forEach(card => grid.appendChild(card));
        });

        applyFilters();
    }

    function setViewMode(mode) {
        const grids = document.querySelectorAll(".model-grid");

        if (mode === "list") {
            grids.forEach(grid => grid.classList.add("list-view"));
            document.getElementById("listViewBtn").classList.add("active");
            document.getElementById("gridViewBtn").classList.remove("active");
        } else {
            grids.forEach(grid => grid.classList.remove("list-view"));
            document.getElementById("gridViewBtn").classList.add("active");
            document.getElementById("listViewBtn").classList.remove("active");
        }
    }

    function addCompare(carName) {
        if (compareSelected.includes(carName)) {
            compareSelected = compareSelected.filter(car => car !== carName);
        } else {
            if (compareSelected.length >= 3) {
                alert("You can compare maximum 3 cars only.");
                return;
            }

            compareSelected.push(carName);
        }

        updateCompareTray();
    }

    function updateCompareTray() {
        compareList.innerHTML = "";

        compareSelected.forEach(car => {
            const pill = document.createElement("div");
            pill.className = "compare-pill";
            pill.textContent = car;
            compareList.appendChild(pill);
        });

        if (compareSelected.length > 0) {
            compareTray.classList.add("show");
        } else {
            compareTray.classList.remove("show");
        }
    }

    function clearCompare() {
        compareSelected = [];
        updateCompareTray();
    }

    function goCompare() {
        if (compareSelected.length === 0) {
            alert("Please select at least one car to compare.");
            return;
        }

        const query = compareSelected.map(car => encodeURIComponent(car)).join(",");
        window.location.href = "compare.php?cars=" + query;
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
</script>

</body>
</html>