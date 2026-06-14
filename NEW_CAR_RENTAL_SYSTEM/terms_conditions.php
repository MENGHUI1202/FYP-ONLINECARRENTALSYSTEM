<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$termsVersion = "KHCR-2026-01";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terms & Conditions | KH Car Rental</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--sky50:#f5fbff;--sky100:#eaf7ff;--sky500:#28a8ea;--sky600:#1284c6;--dark:#10233d;--muted:#6e8297;--orange:#ff7a1a;--border:#d8ecfb;--red:#e2453b;--green:#16a765;--shadow:0 24px 70px rgba(39,137,199,.13)}*{box-sizing:border-box;margin:0;padding:0}body{font-family:"Segoe UI",Tahoma,sans-serif;color:var(--dark);background:radial-gradient(circle at 8% 0%,rgba(210,239,255,.5),transparent 28%),linear-gradient(180deg,#fff 0%,#f7fcff 55%,#fff 100%)}a{text-decoration:none;color:inherit}.navbar{height:72px;position:sticky;top:0;z-index:20;background:rgba(255,255,255,.9);backdrop-filter:blur(18px);border-bottom:1px solid rgba(184,228,255,.75)}.nav-inner{width:min(1160px,calc(100% - 40px));height:72px;margin:auto;display:flex;align-items:center;justify-content:space-between}.brand{display:flex;align-items:center;gap:13px;font-weight:950}.brand-icon{width:44px;height:44px;border-radius:16px;display:grid;place-items:center;color:var(--sky600);background:linear-gradient(135deg,#dff4ff,#fff);border:1px solid var(--border);box-shadow:0 14px 28px rgba(40,168,234,.14)}.nav-actions{display:flex;gap:10px}.nav-btn{min-height:42px;padding:0 16px;border-radius:999px;background:#fff;border:1px solid var(--border);color:#24415f;font-size:12px;font-weight:950;display:inline-flex;align-items:center;gap:8px}.page{width:min(1160px,calc(100% - 36px));margin:28px auto 70px}.hero{padding:32px;border-radius:30px;background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(241,250,255,.96));border:1px solid rgba(184,228,255,.9);box-shadow:var(--shadow)}.pill{width:fit-content;display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;background:rgba(40,168,234,.12);border:1px solid rgba(40,168,234,.22);color:var(--sky600);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px}.hero h1{font-size:clamp(36px,5vw,62px);line-height:1;font-weight:950;letter-spacing:-1px;margin-bottom:12px}.hero p{max-width:780px;color:var(--muted);font-size:15px;font-weight:750;line-height:1.65}.terms-layout{display:grid;grid-template-columns:260px 1fr;gap:18px;margin-top:18px;align-items:start}.side{position:sticky;top:92px;padding:18px;border-radius:24px;background:rgba(255,255,255,.88);border:1px solid var(--border);box-shadow:0 15px 45px rgba(39,137,199,.09)}.side a{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:16px;color:#24415f;font-size:12px;font-weight:950}.side a:hover{background:var(--sky100);color:var(--sky600)}.content{display:grid;gap:16px}.section{padding:24px;border-radius:26px;background:rgba(255,255,255,.92);border:1px solid var(--border);box-shadow:0 16px 42px rgba(39,137,199,.08)}.section h2{font-size:26px;font-weight:950;margin-bottom:12px;display:flex;gap:10px;align-items:center}.section h2 i{color:var(--sky600)}.section p,.section li{color:#385672;font-size:14px;font-weight:720;line-height:1.68}.section ul{display:grid;gap:9px;padding-left:20px}.warning{border-color:rgba(226,69,59,.28);background:linear-gradient(135deg,#fff8f7,#fff)}.warning h2 i{color:var(--red)}.ok{border-color:rgba(22,167,101,.25);background:linear-gradient(135deg,#f2fff9,#fff)}.version{margin-top:16px;color:var(--muted);font-size:12px;font-weight:850}.footer-note{margin-top:18px;padding:20px;border-radius:24px;background:#12304f;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px}.footer-note p{color:rgba(255,255,255,.78);font-weight:750;line-height:1.55}.footer-note a{min-height:44px;padding:0 18px;border-radius:999px;background:linear-gradient(135deg,var(--sky500),var(--sky600));font-size:12px;font-weight:950;display:inline-flex;align-items:center;gap:8px;white-space:nowrap}@media(max-width:840px){.terms-layout{grid-template-columns:1fr}.side{position:static}.footer-note{align-items:flex-start;flex-direction:column}.nav-actions{display:none}}
</style>
</head>
<body>
<header class="navbar">
    <div class="nav-inner">
        <a class="brand" href="homepage.php"><span class="brand-icon"><i class="fa-solid fa-car-side"></i></span><span>KH Car Rental</span></a>
        <div class="nav-actions">
            <a class="nav-btn" href="homepage.php"><i class="fa-solid fa-house"></i> Home</a>
            <a class="nav-btn" href="contactus.php"><i class="fa-solid fa-envelope"></i> Contact</a>
        </div>
    </div>
</header>

<main class="page">
    <section class="hero">
        <span class="pill"><i class="fa-solid fa-file-contract"></i> Rental Terms Version <?= e($termsVersion) ?></span>
        <h1>Terms & Conditions</h1>
        <p>These terms explain customer responsibilities, vehicle care rules, payment conditions, KYC requirements, insurance coverage limits, traffic summons handling, accident responsibility and rental return rules for KH Car Rental.</p>
        <div class="version">Last updated: 10 June 2026</div>
    </section>

    <div class="terms-layout">
        <nav class="side">
            <a href="#eligibility"><i class="fa-solid fa-id-card"></i> Eligibility & KYC</a>
            <a href="#vehicle"><i class="fa-solid fa-car-burst"></i> Vehicle Responsibility</a>
            <a href="#summons"><i class="fa-solid fa-file-invoice-dollar"></i> Summons & Fines</a>
            <a href="#accident"><i class="fa-solid fa-triangle-exclamation"></i> Accident & Damage</a>
            <a href="#payment"><i class="fa-solid fa-credit-card"></i> Payment & Deposit</a>
            <a href="#insurance"><i class="fa-solid fa-shield-heart"></i> Insurance</a>
            <a href="#return"><i class="fa-solid fa-clock"></i> Return Rules</a>
            <a href="#privacy"><i class="fa-solid fa-lock"></i> Privacy</a>
        </nav>

        <div class="content">
            <section class="section" id="eligibility">
                <h2><i class="fa-solid fa-id-card"></i> Eligibility & KYC</h2>
                <ul>
                    <li>Customers must register using accurate personal information, IC number, driving license number, phone number and email address.</li>
                    <li>The driving license expiry date must be valid for at least 6 months from the registration or rental date.</li>
                    <li>IC photo and driving license photo must be uploaded and approved before payment can proceed.</li>
                    <li>KH Car Rental may reject unclear, expired, edited or mismatched KYC documents.</li>
                </ul>
            </section>

            <section class="section warning" id="vehicle">
                <h2><i class="fa-solid fa-car-burst"></i> Vehicle Responsibility</h2>
                <ul>
                    <li>The customer is responsible for taking care of the rented vehicle from handover until return.</li>
                    <li>The vehicle must not be used for illegal activity, racing, towing, off-road driving, overloaded travel or sub-rental to another person.</li>
                    <li>Customers must return the vehicle in a reasonable condition. Extra cleaning or repair charges may apply for stains, smoke smell, missing items or misuse.</li>
                    <li>Only the approved customer or approved additional driver may drive the rented vehicle.</li>
                </ul>
            </section>

            <section class="section warning" id="summons">
                <h2><i class="fa-solid fa-file-invoice-dollar"></i> Summons, Parking Tickets & Fines</h2>
                <ul>
                    <li>Any traffic summons, speeding fine, parking ticket, toll penalty or related government charge during the rental period is the customer&apos;s responsibility.</li>
                    <li>If KH Car Rental receives a summons after the vehicle is returned, the customer may still be contacted and charged based on the rental record.</li>
                    <li>Administrative processing fees may be added when KH Car Rental needs to handle official summons documentation.</li>
                </ul>
            </section>

            <section class="section warning" id="accident">
                <h2><i class="fa-solid fa-triangle-exclamation"></i> Accident, Damage & Breakdown</h2>
                <ul>
                    <li>Customers must contact KH Car Rental immediately if an accident, theft, damage, tyre issue, breakdown or warning light occurs.</li>
                    <li>Customers must not repair the vehicle without permission from KH Car Rental.</li>
                    <li>Damage caused by negligence, unsafe driving, drunk driving, unauthorized drivers, illegal use or breach of terms may be fully charged to the customer.</li>
                    <li>A police report may be required for accidents, theft or third-party claims.</li>
                </ul>
            </section>

            <section class="section" id="payment">
                <h2><i class="fa-solid fa-credit-card"></i> Payment, Booking & Cancellation</h2>
                <ul>
                    <li>Payment can only proceed after vehicle availability and KYC verification are completed.</li>
                    <li>Bookings may require admin approval before handover is confirmed.</li>
                    <li>KH Car Rental may reject bookings with invalid customer information, failed verification or unavailable vehicles.</li>
                    <li>Refunds, cancellations and booking changes are subject to admin review and system records.</li>
                </ul>
            </section>

            <section class="section ok" id="insurance">
                <h2><i class="fa-solid fa-shield-heart"></i> Insurance & Coverage</h2>
                <ul>
                    <li>Insurance coverage is an additional protection package and does not replace these Terms & Conditions.</li>
                    <li>Different insurance packages may have different coverage limits, excess amounts and exclusions.</li>
                    <li>Insurance may not cover negligence, illegal driving, unauthorized drivers, missing police reports or breach of rental terms.</li>
                </ul>
            </section>

            <section class="section" id="return">
                <h2><i class="fa-solid fa-clock"></i> Return, Late Return & Fuel</h2>
                <ul>
                    <li>The vehicle must be returned on or before the agreed return date and time.</li>
                    <li>Late returns may cause additional daily charges or admin review because the vehicle may already be reserved by another customer.</li>
                    <li>Customers must follow the selected fuel option and return location stated in the booking.</li>
                </ul>
            </section>

            <section class="section" id="privacy">
                <h2><i class="fa-solid fa-lock"></i> Privacy & Record Keeping</h2>
                <ul>
                    <li>KH Car Rental stores registration, KYC, booking, payment and terms acceptance records for rental management and FYP system audit purposes.</li>
                    <li>Customer documents are used for verification and booking safety only.</li>
                    <li>Acceptance records may include user ID, booking ID, accepted time, terms version, IP address and browser information.</li>
                </ul>
            </section>
        </div>
    </div>

    <div class="footer-note">
        <p><strong>Important:</strong> By registering or paying for a booking, the customer agrees to these rental responsibilities and system rules.</p>
        <a href="homepage.php"><i class="fa-solid fa-car-side"></i> Back to KH Car Rental</a>
    </div>
</main>
</body>
</html>
