<?php
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

$cars = [
    1 => [
        "id" => 1,
        "name" => "Toyota Vios",
        "type" => "Sedan",
        "price" => 95500,
        "priceText" => "From RM 95,500",
        "monthly" => "Est. RM 1,250 / month",
        "year" => "2025",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "stock" => "In Stock",
        "label" => "Best for Daily Driving",
        "body" => "Sedan",
        "waiting" => "-",
        "bookingFee" => "-",
        "short" => "A compact sedan suitable for daily driving, comfort and fuel efficiency.",
        "description" => "The Toyota Vios is a compact sedan designed for daily driving. It offers good fuel efficiency, easy handling, practical cabin space and Toyota reliability.",
        "bestFor" => "Daily driving, students, working adults and small families.",
        "drivingExperience" => "Smooth, simple and easy to control, especially in city traffic.",
        "whyChoose" => "Choose this model if you want an affordable Toyota sedan with low running cost, useful safety features and practical comfort.",
        "colours" => [
            ["name" => "White Pearl", "code" => "#f7f7f7", "image" => "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Red Mica", "code" => "#b30016", "image" => "https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Silver Metallic", "code" => "#bfc3c7", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Attitude Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Vios 1.5E",
                "price" => "RM 95,500",
                "priceNumber" => 95500,
                "monthly" => "Est. RM 1,250 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4425 mm","width" => "1730 mm","height" => "1475 mm","wheelbase" => "2620 mm","kerbWeight" => "1050 kg","grossWeight" => "1475 kg","cargoVolume" => "326 L","fuelTank" => "42 Litres","frontTread" => "1505 mm","rearTread" => "1490 mm","turningRadius" => "5.0 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "2NR-FE 1.5L 4-Cylinder Petrol","displacement" => "1496 cc","horsepower" => "106 PS @ 6000 rpm","torque" => "138 Nm @ 4200 rpm","compression" => "10.5:1","fuelSystem" => "Electronic Fuel Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.2L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 11.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "10.0 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Drum","abs" => "ABS with EBD"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / L / S"],
                "wheels" => ["size" => "15-inch Alloy Wheels","tyres" => "185/60R15","spare" => "Full-Size Spare"],
                "features" => ["Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Multi-function Steering", "USB Charging Port"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "6 Airbags",
                "comfort" => ["Fabric Seats" => true, "Manual Air Conditioning" => true, "Auto Air Conditioning" => false, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => false]
            ],
            [
                "name" => "Vios 1.5G",
                "price" => "RM 101,900",
                "priceNumber" => 101900,
                "monthly" => "Est. RM 1,330 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4425 mm","width" => "1730 mm","height" => "1475 mm","wheelbase" => "2620 mm","kerbWeight" => "1060 kg","grossWeight" => "1485 kg","cargoVolume" => "326 L","fuelTank" => "42 Litres","frontTread" => "1505 mm","rearTread" => "1490 mm","turningRadius" => "5.0 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "2NR-FE 1.5L 4-Cylinder Petrol","displacement" => "1496 cc","horsepower" => "106 PS @ 6000 rpm","torque" => "138 Nm @ 4200 rpm","compression" => "10.5:1","fuelSystem" => "Electronic Fuel Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.4L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 11.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "10.0 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / L / S / Sport"],
                "wheels" => ["size" => "16-inch Alloy Wheels","tyres" => "195/55R16","spare" => "Full-Size Spare"],
                "features" => ["Larger Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Digital Video Recorder", "Auto Folding Mirror"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => false]
            ],
            [
                "name" => "Vios 1.5 GR-S",
                "price" => "RM 109,000",
                "priceNumber" => 109000,
                "monthly" => "Est. RM 1,420 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4425 mm","width" => "1730 mm","height" => "1470 mm","wheelbase" => "2620 mm","kerbWeight" => "1070 kg","grossWeight" => "1495 kg","cargoVolume" => "326 L","fuelTank" => "42 Litres","frontTread" => "1505 mm","rearTread" => "1490 mm","turningRadius" => "5.0 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "2NR-FE 1.5L 4-Cylinder Petrol","displacement" => "1496 cc","horsepower" => "106 PS @ 6000 rpm","torque" => "138 Nm @ 4200 rpm","compression" => "10.5:1","fuelSystem" => "Electronic Fuel Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic with Sport Mode","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.8L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 11.0s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS) Sport-tuned","turnsLockToLock" => "2.8","turningCircle" => "10.0 m"],
                "suspension" => ["front" => "Sport-tuned MacPherson Strut","rear" => "Sport-tuned Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Sport Tuning"],
                "transmission" => ["type" => "CVT with Sport Mode","gears" => "Stepless + Sport Sequential","mode" => "D / S / Sport Paddle Shift"],
                "wheels" => ["size" => "17-inch GR Alloy Wheels","tyres" => "205/45R17","spare" => "Full-Size Spare"],
                "features" => ["GR Body Kit", "Sport Mode", "Sport Seats", "Touchscreen Display", "LED Headlamps", "Sport Pedals"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => false]
            ]
        ]
    ],
    2 => [
        "id" => 2,
        "name" => "Toyota Yaris",
        "type" => "Hatchback",
        "price" => 88000,
        "priceText" => "From RM 88,000",
        "monthly" => "Est. RM 1,150 / month",
        "year" => "2025",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "stock" => "In Stock",
        "label" => "Stylish City Hatchback",
        "body" => "Hatchback",
        "waiting" => "-",
        "bookingFee" => "-",
        "short" => "A compact hatchback designed for city driving and modern lifestyle.",
        "description" => "The Toyota Yaris is a compact hatchback made for modern city users.",
        "bestFor" => "City driving, young drivers and users who prefer compact cars.",
        "drivingExperience" => "Light, easy to park and suitable for urban movement.",
        "whyChoose" => "Choose this model if you want a stylish Toyota with compact size, practical features and easy handling.",
        "colours" => [
            ["name" => "White Pearl", "code" => "#fafafa", "image" => "https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Red Mica", "code" => "#b30016", "image" => "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Silver Metallic", "code" => "#bfc3c7", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Attitude Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Yaris 1.5E",
                "price" => "RM 88,000",
                "priceNumber" => 88000,
                "monthly" => "Est. RM 1,150 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "3940 mm","width" => "1695 mm","height" => "1500 mm","wheelbase" => "2550 mm","kerbWeight" => "995 kg","grossWeight" => "1385 kg","cargoVolume" => "286 L","fuelTank" => "42 Litres","frontTread" => "1480 mm","rearTread" => "1460 mm","turningRadius" => "4.7 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "2NR-FE 1.5L 4-Cylinder Petrol","displacement" => "1496 cc","horsepower" => "106 PS @ 6000 rpm","torque" => "138 Nm @ 4200 rpm","compression" => "10.5:1","fuelSystem" => "Electronic Fuel Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.2L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 11.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "9.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Drum","abs" => "ABS with EBD"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / L"],
                "wheels" => ["size" => "15-inch Alloy Wheels","tyres" => "175/65R15","spare" => "Full-Size Spare"],
                "features" => ["Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Foldable Rear Seats", "USB Port"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (2)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "2 Airbags",
                "comfort" => ["Fabric Seats" => true, "Manual Air Conditioning" => true, "Auto Air Conditioning" => false, "Spacious Legroom" => false, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x1)" => true, "USB Ports (x2)" => false, "Rear Air Vents" => false]
            ],
            [
                "name" => "Yaris 1.5G",
                "price" => "RM 92,000",
                "priceNumber" => 92000,
                "monthly" => "Est. RM 1,200 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "3940 mm","width" => "1695 mm","height" => "1500 mm","wheelbase" => "2550 mm","kerbWeight" => "1005 kg","grossWeight" => "1395 kg","cargoVolume" => "286 L","fuelTank" => "42 Litres","frontTread" => "1480 mm","rearTread" => "1460 mm","turningRadius" => "4.7 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "2NR-FE 1.5L 4-Cylinder Petrol","displacement" => "1496 cc","horsepower" => "106 PS @ 6000 rpm","torque" => "138 Nm @ 4200 rpm","compression" => "10.5:1","fuelSystem" => "Electronic Fuel Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.4L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 11.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "9.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / L / S"],
                "wheels" => ["size" => "16-inch Alloy Wheels","tyres" => "185/55R16","spare" => "Full-Size Spare"],
                "features" => ["Larger Touchscreen Display", "Reverse Camera", "Smart Entry", "Auto Folding Mirror", "LED Headlamps", "DVR"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Airbags (2)" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x1)" => false, "USB Ports (x2)" => true, "Rear Air Vents" => false]
            ],
            [
                "name" => "Yaris 1.5 GR-S",
                "price" => "RM 99,000",
                "priceNumber" => 99000,
                "monthly" => "Est. RM 1,290 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "3940 mm","width" => "1695 mm","height" => "1495 mm","wheelbase" => "2550 mm","kerbWeight" => "1015 kg","grossWeight" => "1405 kg","cargoVolume" => "286 L","fuelTank" => "42 Litres","frontTread" => "1480 mm","rearTread" => "1460 mm","turningRadius" => "4.7 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "2NR-FE 1.5L 4-Cylinder Petrol","displacement" => "1496 cc","horsepower" => "106 PS @ 6000 rpm","torque" => "138 Nm @ 4200 rpm","compression" => "10.5:1","fuelSystem" => "Electronic Fuel Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic with Sport Mode","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.8L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 11.0s (0-100km/h)"],
                "steering" => ["type" => "Sport-tuned Electric Power Steering","turnsLockToLock" => "2.8","turningCircle" => "9.4 m"],
                "suspension" => ["front" => "Sport-tuned MacPherson Strut","rear" => "Sport-tuned Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Sport"],
                "transmission" => ["type" => "CVT with Sport Mode","gears" => "Stepless + Sequential Sport","mode" => "D / S / Sport"],
                "wheels" => ["size" => "17-inch GR Alloy Wheels","tyres" => "195/45R17","spare" => "Full-Size Spare"],
                "features" => ["GR Body Kit", "Sport Mode", "Sport Seats", "Touchscreen Display", "LED Headlamps", "Sport Pedals"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Airbags (2)" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x1)" => false, "USB Ports (x2)" => true, "Rear Air Vents" => false]
            ]
        ]
    ],
    3 => [
        "id" => 3,
        "name" => "Toyota Corolla Cross",
        "type" => "SUV",
        "price" => 130400,
        "priceText" => "From RM 130,400",
        "monthly" => "Est. RM 1,700 / month",
        "year" => "2025",
        "fuel" => "Hybrid",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "stock" => "In Stock",
        "label" => "Hybrid SUV Choice",
        "body" => "SUV",
        "waiting" => "-",
        "bookingFee" => "-",
        "short" => "A modern hybrid SUV with comfort, safety and practical space.",
        "description" => "The Toyota Corolla Cross is a modern SUV that combines comfort, safety, practicality and efficient hybrid performance.",
        "bestFor" => "Small families, daily driving and fuel-saving SUV users.",
        "drivingExperience" => "Comfortable, stable and efficient for city and highway use.",
        "whyChoose" => "Choose this model if you want a balanced SUV with hybrid efficiency, advanced safety and practical cabin space.",
        "colours" => [
            ["name" => "White Pearl", "code" => "#f7f7f7", "image" => "https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Nebula Blue", "code" => "#1f3a5f", "image" => "https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Red Mica", "code" => "#b30016", "image" => "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Silver Metallic", "code" => "#bfc3c7", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Corolla Cross 1.8G",
                "price" => "RM 130,400",
                "priceNumber" => 130400,
                "monthly" => "Est. RM 1,700 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4460 mm","width" => "1825 mm","height" => "1620 mm","wheelbase" => "2640 mm","kerbWeight" => "1380 kg","grossWeight" => "1855 kg","cargoVolume" => "487 L","fuelTank" => "47 Litres","frontTread" => "1565 mm","rearTread" => "1565 mm","turningRadius" => "5.4 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "2ZR-FAE 1.8L 4-Cylinder Petrol","displacement" => "1798 cc","horsepower" => "139 PS @ 6400 rpm","torque" => "172 Nm @ 4000 rpm","compression" => "10.0:1","fuelSystem" => "Dual VVT-i Electronic Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 6.8L / 100km","topSpeed" => "Approx. 175 km/h","acceleration" => "Approx. 10.8s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.1","turningCircle" => "10.8 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S / Drive Mode Select"],
                "wheels" => ["size" => "17-inch Alloy Wheels","tyres" => "215/60R17","spare" => "Full-Size Spare"],
                "features" => ["Touchscreen Display", "Smart Entry", "Reverse Camera", "LED Headlamps", "Electric Parking Brake", "Drive Mode Select"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => true, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => true, "Large Boot Space" => true]
            ],
            [
                "name" => "Corolla Cross 1.8V",
                "price" => "RM 138,400",
                "priceNumber" => 138400,
                "monthly" => "Est. RM 1,800 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4460 mm","width" => "1825 mm","height" => "1620 mm","wheelbase" => "2640 mm","kerbWeight" => "1395 kg","grossWeight" => "1870 kg","cargoVolume" => "487 L","fuelTank" => "47 Litres","frontTread" => "1565 mm","rearTread" => "1565 mm","turningRadius" => "5.4 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "2ZR-FAE 1.8L 4-Cylinder Petrol","displacement" => "1798 cc","horsepower" => "139 PS @ 6400 rpm","torque" => "172 Nm @ 4000 rpm","compression" => "10.0:1","fuelSystem" => "Dual VVT-i Electronic Injection","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 6.6L / 100km","topSpeed" => "Approx. 175 km/h","acceleration" => "Approx. 10.8s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.1","turningCircle" => "10.8 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S / Drive Mode Select"],
                "wheels" => ["size" => "18-inch Alloy Wheels","tyres" => "215/55R18","spare" => "Full-Size Spare"],
                "features" => ["Larger Touchscreen Display", "Smart Entry", "360-degree Camera", "LED Headlamps", "Power Back Door", "Electric Parking Brake"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => false, "Dual-zone Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => false, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Large Boot Space" => true]
            ],
            [
                "name" => "Corolla Cross Hybrid",
                "price" => "RM 145,000",
                "priceNumber" => 145000,
                "monthly" => "Est. RM 1,880 / month",
                "fuelType" => "Hybrid",
                "dimensions" => ["length" => "4460 mm","width" => "1825 mm","height" => "1620 mm","wheelbase" => "2640 mm","kerbWeight" => "1460 kg","grossWeight" => "1935 kg","cargoVolume" => "475 L","fuelTank" => "36 Litres","frontTread" => "1565 mm","rearTread" => "1565 mm","turningRadius" => "5.4 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "2ZR-FXE 1.8L Hybrid Atkinson-Cycle","displacement" => "1798 cc","horsepower" => "122 PS Combined (Petrol + Electric)","torque" => "142 Nm + Electric Motor Assist","compression" => "13.0:1","fuelSystem" => "VVT-i Hybrid Injection + Electric Motor","aspiration" => "Naturally Aspirated + Electric Motor"],
                "performance" => ["transmission" => "E-CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 4.3L / 100km","topSpeed" => "Approx. 170 km/h","acceleration" => "Approx. 10.6s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.1","turningCircle" => "10.8 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc (Regenerative)","rear" => "Disc","abs" => "ABS with EBD + Regenerative Braking"],
                "transmission" => ["type" => "E-CVT (Electronic Continuously Variable Transmission)","gears" => "Stepless Electronic","mode" => "EV / ECO / Normal / Sport"],
                "wheels" => ["size" => "18-inch Alloy Wheels","tyres" => "215/55R18","spare" => "Full-Size Spare"],
                "features" => ["Hybrid System", "EV Mode", "360-degree Camera", "Power Back Door", "Smart Entry", "Electric Parking Brake"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => false, "Dual-zone Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => false, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Large Boot Space" => true]
            ]
        ]
    ],
    4 => [
        "id" => 4,
        "name" => "Toyota Hilux",
        "type" => "Pickup",
        "price" => 110880,
        "priceText" => "From RM 110,880",
        "monthly" => "Est. RM 1,450 / month",
        "year" => "2025",
        "fuel" => "Diesel",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "stock" => "In Stock",
        "label" => "Strong and Durable",
        "body" => "Pickup",
        "waiting" => "-",
        "bookingFee" => "-",
        "short" => "A strong pickup truck suitable for business, work and adventure.",
        "description" => "The Toyota Hilux is a durable pickup truck built for work, business and outdoor adventure.",
        "bestFor" => "Business use, outdoor driving, adventure and cargo needs.",
        "drivingExperience" => "Powerful, durable and confident on rough road conditions.",
        "whyChoose" => "Choose this model if you need a strong Toyota pickup with durability, diesel power and practical cargo ability.",
        "colours" => [
            ["name" => "Super White", "code" => "#f7f7f7", "image" => "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Dark Grey", "code" => "#555555", "image" => "https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Silver Metallic", "code" => "#bfc3c7", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Attitude Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Hilux 2.4E",
                "price" => "RM 110,880",
                "priceNumber" => 110880,
                "monthly" => "Est. RM 1,450 / month",
                "fuelType" => "Diesel",
                "dimensions" => ["length" => "5330 mm","width" => "1855 mm","height" => "1800 mm","wheelbase" => "3085 mm","kerbWeight" => "1830 kg","grossWeight" => "2800 kg","cargoVolume" => "Cargo Bed","fuelTank" => "80 Litres","frontTread" => "1570 mm","rearTread" => "1565 mm","turningRadius" => "6.4 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "2GD-FTV 2.4L Turbo Diesel","displacement" => "2393 cc","horsepower" => "150 PS @ 3400 rpm","torque" => "400 Nm @ 1400-2600 rpm","compression" => "15.8:1","fuelSystem" => "Direct Injection Common Rail","aspiration" => "Turbocharged + Intercooled"],
                "performance" => ["transmission" => "6-Speed Automatic","drivetrain" => "Rear-Wheel Drive","fuelConsumption" => "Approx. 7.5L / 100km","topSpeed" => "Approx. 175 km/h","acceleration" => "Approx. 12.0s (0-100km/h)"],
                "steering" => ["type" => "Hydraulic Power Steering","turnsLockToLock" => "3.5","turningCircle" => "12.8 m"],
                "suspension" => ["front" => "Double Wishbone with Coil Spring","rear" => "Leaf Spring"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Drum","abs" => "ABS with EBD"],
                "transmission" => ["type" => "6-Speed Automatic","gears" => "6","mode" => "D / L2 / L4"],
                "wheels" => ["size" => "17-inch Alloy Wheels","tyres" => "265/65R17","spare" => "Full-Size Steel Spare"],
                "features" => ["Diesel Engine", "Cargo Bed", "Touchscreen Display", "Reverse Camera", "Strong Body", "High Ground Clearance"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => false, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (2)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Trailer Sway Control" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "2 Airbags",
                "comfort" => ["Fabric Seats" => true, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x1)" => true, "USB Ports (x2)" => false, "Rear Air Vents" => false]
            ],
            [
                "name" => "Hilux 2.4V 4x4",
                "price" => "RM 145,000",
                "priceNumber" => 145000,
                "monthly" => "Est. RM 1,880 / month",
                "fuelType" => "Diesel",
                "dimensions" => ["length" => "5330 mm","width" => "1855 mm","height" => "1815 mm","wheelbase" => "3085 mm","kerbWeight" => "1890 kg","grossWeight" => "2870 kg","cargoVolume" => "Cargo Bed","fuelTank" => "80 Litres","frontTread" => "1570 mm","rearTread" => "1565 mm","turningRadius" => "6.4 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "2GD-FTV 2.4L Turbo Diesel","displacement" => "2393 cc","horsepower" => "150 PS @ 3400 rpm","torque" => "400 Nm @ 1400-2600 rpm","compression" => "15.8:1","fuelSystem" => "Direct Injection Common Rail","aspiration" => "Turbocharged + Intercooled"],
                "performance" => ["transmission" => "6-Speed Automatic","drivetrain" => "4x4 (Switchable)","fuelConsumption" => "Approx. 8.0L / 100km","topSpeed" => "Approx. 172 km/h","acceleration" => "Approx. 12.5s (0-100km/h)"],
                "steering" => ["type" => "Hydraulic Power Steering","turnsLockToLock" => "3.5","turningCircle" => "12.8 m"],
                "suspension" => ["front" => "Double Wishbone with Coil Spring","rear" => "Leaf Spring"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Drum","abs" => "ABS with EBD + Downhill Assist"],
                "transmission" => ["type" => "6-Speed Automatic with 4WD Transfer Case","gears" => "6","mode" => "2H / 4H / 4L"],
                "wheels" => ["size" => "18-inch Alloy Wheels","tyres" => "265/60R18","spare" => "Full-Size Alloy Spare"],
                "features" => ["4x4 Capability", "Touchscreen Display", "Reverse Camera", "LED Headlamps", "Cargo Bed", "Smart Entry"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (2)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Trailer Sway Control" => true, "Downhill Assist Control" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "2 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x1)" => false, "USB Ports (x2)" => true, "Rear Air Vents" => false]
            ],
            [
                "name" => "Hilux Rogue",
                "price" => "RM 160,000",
                "priceNumber" => 160000,
                "monthly" => "Est. RM 2,080 / month",
                "fuelType" => "Diesel",
                "dimensions" => ["length" => "5330 mm","width" => "1855 mm","height" => "1830 mm","wheelbase" => "3085 mm","kerbWeight" => "1940 kg","grossWeight" => "2900 kg","cargoVolume" => "Cargo Bed","fuelTank" => "80 Litres","frontTread" => "1570 mm","rearTread" => "1565 mm","turningRadius" => "6.4 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "1GD-FTV 2.8L Turbo Diesel","displacement" => "2755 cc","horsepower" => "204 PS @ 3400 rpm","torque" => "500 Nm @ 1600-2800 rpm","compression" => "15.6:1","fuelSystem" => "Direct Injection Common Rail","aspiration" => "Turbocharged + Intercooled"],
                "performance" => ["transmission" => "6-Speed Automatic","drivetrain" => "4x4 (Switchable)","fuelConsumption" => "Approx. 8.5L / 100km","topSpeed" => "Approx. 180 km/h","acceleration" => "Approx. 11.0s (0-100km/h)"],
                "steering" => ["type" => "Hydraulic Power Steering","turnsLockToLock" => "3.5","turningCircle" => "12.8 m"],
                "suspension" => ["front" => "Double Wishbone with Coil Spring","rear" => "Leaf Spring"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Drum","abs" => "ABS with EBD + Downhill Assist"],
                "transmission" => ["type" => "6-Speed Automatic with 4WD Transfer Case","gears" => "6","mode" => "2H / 4H / 4L"],
                "wheels" => ["size" => "18-inch Rogue Alloy Wheels","tyres" => "265/60R18","spare" => "Full-Size Alloy Spare"],
                "features" => ["Rogue Body Kit", "4x4 Capability", "Powerful Diesel Engine", "360-degree Camera", "Smart Entry", "Premium Display"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (2)" => true, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Trailer Sway Control" => true, "Downhill Assist Control" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "2 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => true, "Power Seats" => false, "USB Ports (x1)" => false, "USB Ports (x2)" => true, "Rear Air Vents" => false]
            ]
        ]
    ],
    5 => [
        "id" => 5,
        "name" => "Toyota Camry",
        "type" => "Sedan",
        "price" => 220800,
        "priceText" => "From RM 220,800",
        "monthly" => "Est. RM 2,850 / month",
        "year" => "2025",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Booking Required",
        "stock" => "Booking Model",
        "label" => "Premium Business Sedan",
        "body" => "Sedan",
        "waiting" => "2 - 4 weeks",
        "bookingFee" => "RM 1,000",
        "short" => "A premium sedan with comfort, elegant design and business-class style.",
        "description" => "The Toyota Camry is a premium sedan with elegant design, advanced comfort and smooth performance.",
        "bestFor" => "Business users, executives and premium sedan lovers.",
        "drivingExperience" => "Smooth, quiet and comfortable for long-distance driving.",
        "whyChoose" => "Choose this model if you want a premium Toyota sedan with professional image, high comfort and advanced safety.",
        "colours" => [
            ["name" => "Platinum White Pearl", "code" => "#fafafa", "image" => "https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Graphite Metallic", "code" => "#555555", "image" => "https://images.unsplash.com/photo-1550355291-bbee04a92027?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Silver Metallic", "code" => "#bfc3c7", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Attitude Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Camry 2.5V",
                "price" => "RM 220,800",
                "priceNumber" => 220800,
                "monthly" => "Est. RM 2,850 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4885 mm","width" => "1840 mm","height" => "1445 mm","wheelbase" => "2825 mm","kerbWeight" => "1545 kg","grossWeight" => "2030 kg","cargoVolume" => "524 L","fuelTank" => "60 Litres","frontTread" => "1595 mm","rearTread" => "1600 mm","turningRadius" => "5.7 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "A25A-FKS 2.5L Dynamic Force Petrol","displacement" => "2487 cc","horsepower" => "209 PS @ 6600 rpm","torque" => "253 Nm @ 5000 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "8-Speed Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 6.8L / 100km","topSpeed" => "Approx. 190 km/h","acceleration" => "Approx. 8.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "2.7","turningCircle" => "11.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Double Wishbone"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "8-Speed Direct-Shift Automatic","gears" => "8","mode" => "D / S / Paddle Shift"],
                "wheels" => ["size" => "18-inch Alloy Wheels","tyres" => "215/55R18","spare" => "Full-Size Spare"],
                "features" => ["Premium Interior", "Smart Entry", "Touchscreen Display", "Reverse Camera", "Power Seats", "LED Headlamps"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => true],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => false, "Dual-zone Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => true, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Rear Sunshade" => false]
            ],
            [
                "name" => "Camry 2.5 Premium",
                "price" => "RM 235,000",
                "priceNumber" => 235000,
                "monthly" => "Est. RM 3,050 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4885 mm","width" => "1840 mm","height" => "1445 mm","wheelbase" => "2825 mm","kerbWeight" => "1555 kg","grossWeight" => "2040 kg","cargoVolume" => "524 L","fuelTank" => "60 Litres","frontTread" => "1595 mm","rearTread" => "1600 mm","turningRadius" => "5.7 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "A25A-FKS 2.5L Dynamic Force Petrol","displacement" => "2487 cc","horsepower" => "209 PS @ 6600 rpm","torque" => "253 Nm @ 5000 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "8-Speed Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 6.9L / 100km","topSpeed" => "Approx. 190 km/h","acceleration" => "Approx. 8.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "2.7","turningCircle" => "11.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Double Wishbone"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "8-Speed Direct-Shift Automatic","gears" => "8","mode" => "D / S / Paddle Shift"],
                "wheels" => ["size" => "18-inch Premium Alloy Wheels","tyres" => "215/55R18","spare" => "Full-Size Spare"],
                "features" => ["Premium Audio", "Larger Display", "360-degree Camera", "Power Seats", "Smart Entry", "LED Headlamps"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Parking Support Brake" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => true],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => false, "Dual-zone Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => true, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Rear Sunshade" => true]
            ],
            [
                "name" => "Camry Hybrid",
                "price" => "RM 240,000",
                "priceNumber" => 240000,
                "monthly" => "Est. RM 3,120 / month",
                "fuelType" => "Hybrid",
                "dimensions" => ["length" => "4885 mm","width" => "1840 mm","height" => "1455 mm","wheelbase" => "2825 mm","kerbWeight" => "1660 kg","grossWeight" => "2130 kg","cargoVolume" => "493 L","fuelTank" => "50 Litres","frontTread" => "1595 mm","rearTread" => "1600 mm","turningRadius" => "5.7 m","doors" => "4","seats" => "5"],
                "engine" => ["name" => "A25A-FXS 2.5L Hybrid Atkinson-Cycle","displacement" => "2487 cc","horsepower" => "218 PS Combined (Petrol + Electric)","torque" => "221 Nm + Electric Motor Assist","compression" => "14.0:1","fuelSystem" => "VVT-i Hybrid D-4S Injection","aspiration" => "Naturally Aspirated + Electric Motor"],
                "performance" => ["transmission" => "E-CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 4.5L / 100km","topSpeed" => "Approx. 185 km/h","acceleration" => "Approx. 8.3s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "2.7","turningCircle" => "11.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Double Wishbone"],
                "brakes" => ["front" => "Ventilated Disc (Regenerative)","rear" => "Disc","abs" => "ABS with EBD + Regenerative Braking"],
                "transmission" => ["type" => "E-CVT Hybrid Electronic Transmission","gears" => "Stepless Electronic","mode" => "EV / ECO / Normal / Sport"],
                "wheels" => ["size" => "18-inch Hybrid Alloy Wheels","tyres" => "215/55R18","spare" => "Full-Size Spare"],
                "features" => ["Hybrid System", "EV Mode", "Premium Audio", "360-degree Camera", "Smart Entry", "Power Seats"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Parking Support Brake" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => true],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => false, "Dual-zone Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => true, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Rear Sunshade" => true]
            ]
        ]
    ],
    6 => [
        "id" => 6,
        "name" => "Toyota Innova Zenix",
        "type" => "MPV",
        "price" => 165000,
        "priceText" => "From RM 165,000",
        "monthly" => "Est. RM 2,100 / month",
        "year" => "2025",
        "fuel" => "Hybrid",
        "seats" => "7 Seats",
        "status" => "Booking Required",
        "stock" => "Booking Model",
        "label" => "Family MPV",
        "body" => "MPV",
        "waiting" => "3 - 6 weeks",
        "bookingFee" => "RM 800",
        "short" => "A spacious MPV designed for family comfort and practical travel.",
        "description" => "The Toyota Innova Zenix is a spacious family MPV with comfortable seating and practical features.",
        "bestFor" => "Families, long-distance travel and 7-seat users.",
        "drivingExperience" => "Comfortable, practical and smooth for family journeys.",
        "whyChoose" => "Choose this model if you need seven seats, cabin space and hybrid family comfort.",
        "colours" => [
            ["name" => "White Pearl", "code" => "#fafafa", "image" => "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Dark Steel", "code" => "#555555", "image" => "https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Silver Metallic", "code" => "#bfc3c7", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Attitude Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Innova Zenix 2.0V",
                "price" => "RM 165,000",
                "priceNumber" => 165000,
                "monthly" => "Est. RM 2,100 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4755 mm","width" => "1850 mm","height" => "1795 mm","wheelbase" => "2850 mm","kerbWeight" => "1690 kg","grossWeight" => "2390 kg","cargoVolume" => "226 L (3rd row up)","fuelTank" => "52 Litres","frontTread" => "1595 mm","rearTread" => "1595 mm","turningRadius" => "5.7 m","doors" => "5","seats" => "7"],
                "engine" => ["name" => "M20A-FKS 2.0L Dynamic Force Petrol","displacement" => "1987 cc","horsepower" => "174 PS @ 6600 rpm","torque" => "205 Nm @ 4900 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 7.0L / 100km","topSpeed" => "Approx. 170 km/h","acceleration" => "Approx. 10.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "11.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S"],
                "wheels" => ["size" => "17-inch Alloy Wheels","tyres" => "225/55R17","spare" => "Full-Size Spare"],
                "features" => ["7 Seats", "Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Flexible Seats"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => false, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (4)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "4 Airbags",
                "comfort" => ["Fabric Seats" => true, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Rear Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => false, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => true]
            ],
            [
                "name" => "Innova Zenix 2.0X",
                "price" => "RM 172,000",
                "priceNumber" => 172000,
                "monthly" => "Est. RM 2,220 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4755 mm","width" => "1850 mm","height" => "1795 mm","wheelbase" => "2850 mm","kerbWeight" => "1710 kg","grossWeight" => "2410 kg","cargoVolume" => "226 L (3rd row up)","fuelTank" => "52 Litres","frontTread" => "1595 mm","rearTread" => "1595 mm","turningRadius" => "5.7 m","doors" => "5","seats" => "7"],
                "engine" => ["name" => "M20A-FKS 2.0L Dynamic Force Petrol","displacement" => "1987 cc","horsepower" => "174 PS @ 6600 rpm","torque" => "205 Nm @ 4900 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 7.1L / 100km","topSpeed" => "Approx. 170 km/h","acceleration" => "Approx. 10.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "11.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S"],
                "wheels" => ["size" => "18-inch Alloy Wheels","tyres" => "225/50R18","spare" => "Full-Size Spare"],
                "features" => ["Captain Seats", "Larger Display", "Reverse Camera", "Smart Entry", "Power Back Door", "LED Headlamps"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Airbags (4)" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Rear Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true]
            ],
            [
                "name" => "Innova Zenix Hybrid",
                "price" => "RM 175,000",
                "priceNumber" => 175000,
                "monthly" => "Est. RM 2,280 / month",
                "fuelType" => "Hybrid",
                "dimensions" => ["length" => "4755 mm","width" => "1850 mm","height" => "1795 mm","wheelbase" => "2850 mm","kerbWeight" => "1790 kg","grossWeight" => "2490 kg","cargoVolume" => "226 L (3rd row up)","fuelTank" => "52 Litres","frontTread" => "1595 mm","rearTread" => "1595 mm","turningRadius" => "5.7 m","doors" => "5","seats" => "7"],
                "engine" => ["name" => "M20A-FXS 2.0L Hybrid Atkinson-Cycle","displacement" => "1987 cc","horsepower" => "186 PS Combined (Petrol + Electric)","torque" => "188 Nm + Electric Motor Assist","compression" => "14.0:1","fuelSystem" => "VVT-i Hybrid Injection + Electric Motor","aspiration" => "Naturally Aspirated + Electric Motor"],
                "performance" => ["transmission" => "E-CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 5.4L / 100km","topSpeed" => "Approx. 165 km/h","acceleration" => "Approx. 10.0s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.0","turningCircle" => "11.4 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Torsion Beam"],
                "brakes" => ["front" => "Ventilated Disc (Regenerative)","rear" => "Disc","abs" => "ABS with EBD + Regenerative Braking"],
                "transmission" => ["type" => "E-CVT Hybrid Electronic Transmission","gears" => "Stepless Electronic","mode" => "EV / ECO / Normal / Sport"],
                "wheels" => ["size" => "18-inch Hybrid Alloy Wheels","tyres" => "225/50R18","spare" => "Full-Size Spare"],
                "features" => ["Hybrid System", "Captain Seats", "Power Back Door", "Smart Entry", "Larger Display", "LED Headlamps"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Airbags (4)" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Rear Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true]
            ]
        ]
    ],
    7 => [
        "id" => 7,
        "name" => "Toyota Alphard",
        "type" => "MPV",
        "price" => 538000,
        "priceText" => "From RM 538,000",
        "monthly" => "Est. RM 6,900 / month",
        "year" => "2025",
        "fuel" => "Petrol",
        "seats" => "7 Seats",
        "status" => "Booking Required",
        "stock" => "Booking Model",
        "label" => "Luxury Executive MPV",
        "body" => "MPV",
        "waiting" => "1 - 3 months",
        "bookingFee" => "RM 2,000",
        "short" => "A luxury MPV for premium comfort and executive image.",
        "description" => "The Toyota Alphard is a luxury MPV designed for executive travel, premium comfort and spacious cabin experience.",
        "bestFor" => "Executives, luxury users and premium family travel.",
        "drivingExperience" => "Quiet, smooth and luxury-focused with high passenger comfort.",
        "whyChoose" => "Choose this model if you want a premium Toyota MPV with luxury comfort, executive presence, spacious cabin and advanced safety features.",
        "colours" => [
            ["name" => "White Pearl", "code" => "#fafafa", "image" => "https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Luxury Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Graphite Metallic", "code" => "#555555", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Champagne Gold", "code" => "#d6bd87", "image" => "https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "Alphard 2.5X",
                "price" => "RM 538,000",
                "priceNumber" => 538000,
                "monthly" => "Est. RM 6,900 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4995 mm","width" => "1850 mm","height" => "1895 mm","wheelbase" => "3000 mm","kerbWeight" => "2030 kg","grossWeight" => "2640 kg","cargoVolume" => "222 L (3rd row up)","fuelTank" => "75 Litres","frontTread" => "1590 mm","rearTread" => "1600 mm","turningRadius" => "5.8 m","doors" => "5","seats" => "7"],
                "engine" => ["name" => "A25A-FKS 2.5L Dynamic Force Petrol","displacement" => "2487 cc","horsepower" => "182 PS @ 6000 rpm","torque" => "235 Nm @ 4600 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 9.0L / 100km","topSpeed" => "Approx. 170 km/h","acceleration" => "Approx. 10.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.2","turningCircle" => "11.6 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Double Wishbone"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S"],
                "wheels" => ["size" => "18-inch Alloy Wheels","tyres" => "235/55R18","spare" => "Full-Size Spare"],
                "features" => ["Power Sliding Door", "Smart Entry", "Touchscreen Display", "Reverse Camera", "LED Headlamps", "Luxury Cabin"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => false],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Rear Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => false, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => true]
            ],
            [
                "name" => "Alphard 2.5G",
                "price" => "RM 560,000",
                "priceNumber" => 560000,
                "monthly" => "Est. RM 7,180 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4995 mm","width" => "1850 mm","height" => "1895 mm","wheelbase" => "3000 mm","kerbWeight" => "2050 kg","grossWeight" => "2660 kg","cargoVolume" => "222 L (3rd row up)","fuelTank" => "75 Litres","frontTread" => "1590 mm","rearTread" => "1600 mm","turningRadius" => "5.8 m","doors" => "5","seats" => "7"],
                "engine" => ["name" => "A25A-FKS 2.5L Dynamic Force Petrol","displacement" => "2487 cc","horsepower" => "182 PS @ 6000 rpm","torque" => "235 Nm @ 4600 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 9.2L / 100km","topSpeed" => "Approx. 170 km/h","acceleration" => "Approx. 10.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.2","turningCircle" => "11.6 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Double Wishbone"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S"],
                "wheels" => ["size" => "18-inch Premium Alloy Wheels","tyres" => "235/55R18","spare" => "Full-Size Spare"],
                "features" => ["Premium Captain Seats", "Power Sliding Door", "Large Touchscreen", "360-degree Camera", "Power Back Door", "Premium Lighting"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Parking Support Brake" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => true],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Rear Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => true, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Power Ottoman Seats" => true]
            ],
            [
                "name" => "Alphard Executive Lounge",
                "price" => "RM 610,000",
                "priceNumber" => 610000,
                "monthly" => "Est. RM 7,850 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4995 mm","width" => "1850 mm","height" => "1895 mm","wheelbase" => "3000 mm","kerbWeight" => "2080 kg","grossWeight" => "2690 kg","cargoVolume" => "222 L (3rd row up)","fuelTank" => "75 Litres","frontTread" => "1590 mm","rearTread" => "1600 mm","turningRadius" => "5.8 m","doors" => "5","seats" => "7"],
                "engine" => ["name" => "A25A-FKS 2.5L Dynamic Force Petrol","displacement" => "2487 cc","horsepower" => "182 PS @ 6000 rpm","torque" => "235 Nm @ 4600 rpm","compression" => "13.0:1","fuelSystem" => "Direct + Port Injection D-4ST","aspiration" => "Naturally Aspirated"],
                "performance" => ["transmission" => "CVT Automatic","drivetrain" => "Front-Wheel Drive","fuelConsumption" => "Approx. 9.5L / 100km","topSpeed" => "Approx. 170 km/h","acceleration" => "Approx. 10.5s (0-100km/h)"],
                "steering" => ["type" => "Electric Power Steering (EPS)","turnsLockToLock" => "3.2","turningCircle" => "11.6 m"],
                "suspension" => ["front" => "MacPherson Strut with Coil Spring","rear" => "Double Wishbone"],
                "brakes" => ["front" => "Ventilated Disc","rear" => "Disc","abs" => "ABS with EBD + Brake Assist"],
                "transmission" => ["type" => "CVT (Continuously Variable Transmission)","gears" => "Stepless","mode" => "D / S"],
                "wheels" => ["size" => "19-inch Executive Alloy Wheels","tyres" => "235/50R19","spare" => "Full-Size Spare"],
                "features" => ["Executive Lounge Seats", "Rear Entertainment Display", "Premium Audio", "360-degree Camera", "Power Sliding Door", "Power Back Door"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => true, "6 Airbags" => false, "Blind Spot Monitor" => true, "Rear Cross Traffic Alert" => true, "Parking Sensors" => true, "Rear Parking Sensors" => true, "Parking Support Brake" => true, "Toyota Safety Sense" => true, "Pre-Collision System" => true, "Lane Departure Alert" => true, "Lane Tracing Assist" => true],
                "safetyNote" => "7 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Rear Air Conditioning" => true, "Spacious Legroom" => true, "Cup Holders" => true, "Foldable Rear Seats" => true, "Quiet Cabin" => true, "Rear Armrest" => true, "Leather Seats" => true, "Power Seats" => true, "USB Ports (x2)" => false, "USB Ports (x4)" => true, "Rear Air Vents" => true, "Power Ottoman Seats" => true, "Rear Entertainment System" => true, "Ambient Lighting" => true]
            ]
        ]
    ],
    8 => [
        "id" => 8,
        "name" => "Toyota GR Corolla",
        "type" => "Hatchback",
        "price" => 355000,
        "priceText" => "From RM 355,000",
        "monthly" => "Est. RM 4,500 / month",
        "year" => "2025",
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Booking Required",
        "stock" => "Limited Stock",
        "label" => "Performance GR Model",
        "body" => "Hatchback",
        "waiting" => "Limited Stock",
        "bookingFee" => "RM 2,000",
        "short" => "A performance hatchback built for sporty driving and high power.",
        "description" => "The Toyota GR Corolla is a high-performance hatchback designed for users who enjoy sporty handling, stronger power and an exciting driving experience.",
        "bestFor" => "Performance drivers, sporty users and GR fans.",
        "drivingExperience" => "Sporty, responsive and exciting with strong acceleration.",
        "whyChoose" => "Choose this model if you want a Toyota GR model with turbo power, manual driving feel and performance personality.",
        "colours" => [
            ["name" => "Super White", "code" => "#fafafa", "image" => "https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Emotional Red", "code" => "#b30016", "image" => "https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Performance Grey", "code" => "#555555", "image" => "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=80"],
            ["name" => "Attitude Black", "code" => "#111111", "image" => "https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80"]
        ],
        "variants" => [
            [
                "name" => "GR Corolla Core",
                "price" => "RM 355,000",
                "priceNumber" => 355000,
                "monthly" => "Est. RM 4,500 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4360 mm","width" => "1850 mm","height" => "1480 mm","wheelbase" => "2640 mm","kerbWeight" => "1470 kg","grossWeight" => "1880 kg","cargoVolume" => "217 L","fuelTank" => "50 Litres","frontTread" => "1590 mm","rearTread" => "1590 mm","turningRadius" => "5.4 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "G16E-GTS 1.6L 3-Cylinder Turbo","displacement" => "1618 cc","horsepower" => "304 PS @ 6500 rpm","torque" => "370 Nm @ 3000-5550 rpm","compression" => "10.5:1","fuelSystem" => "Direct Injection D-4ST","aspiration" => "Twin-Scroll Turbocharged"],
                "performance" => ["transmission" => "6-Speed Manual","drivetrain" => "GR-Four AWD (Torque Vectoring)","fuelConsumption" => "Approx. 8.4L / 100km","topSpeed" => "Approx. 230 km/h","acceleration" => "Approx. 5.5s (0-100km/h)"],
                "steering" => ["type" => "Sport-tuned Electric Power Steering","turnsLockToLock" => "2.5","turningCircle" => "10.8 m"],
                "suspension" => ["front" => "MacPherson Strut with Sport Coil Spring","rear" => "Double Wishbone with Sport Tuning"],
                "brakes" => ["front" => "Performance Ventilated Disc (356mm)","rear" => "Performance Ventilated Disc (323mm)","abs" => "4-channel ABS + Sport Tuning"],
                "transmission" => ["type" => "6-Speed Manual (Helical LSD)","gears" => "6","mode" => "Track / Sport / Normal (GR-Four Torque Split)"],
                "wheels" => ["size" => "18-inch GR Alloy Wheels","tyres" => "235/40R18","spare" => "Compact Spare"],
                "features" => ["Turbo Engine", "Manual Transmission", "GR-Four AWD", "Sport Seats", "GR Body Kit", "Performance Display"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (4)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => false, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "4 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => false, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => false, "Sport Seats" => true, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => false]
            ],
            [
                "name" => "GR Corolla Circuit Edition",
                "price" => "RM 380,000",
                "priceNumber" => 380000,
                "monthly" => "Est. RM 4,900 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4360 mm","width" => "1850 mm","height" => "1480 mm","wheelbase" => "2640 mm","kerbWeight" => "1480 kg","grossWeight" => "1890 kg","cargoVolume" => "217 L","fuelTank" => "50 Litres","frontTread" => "1590 mm","rearTread" => "1590 mm","turningRadius" => "5.4 m","doors" => "5","seats" => "5"],
                "engine" => ["name" => "G16E-GTS 1.6L 3-Cylinder Turbo","displacement" => "1618 cc","horsepower" => "304 PS @ 6500 rpm","torque" => "370 Nm @ 3000-5550 rpm","compression" => "10.5:1","fuelSystem" => "Direct Injection D-4ST","aspiration" => "Twin-Scroll Turbocharged"],
                "performance" => ["transmission" => "6-Speed Manual","drivetrain" => "GR-Four AWD (Sport Torque Split)","fuelConsumption" => "Approx. 8.6L / 100km","topSpeed" => "Approx. 230 km/h","acceleration" => "Approx. 5.4s (0-100km/h)"],
                "steering" => ["type" => "Sport-tuned Electric Power Steering","turnsLockToLock" => "2.5","turningCircle" => "10.8 m"],
                "suspension" => ["front" => "Circuit-tuned MacPherson Strut","rear" => "Double Wishbone Circuit Tuned"],
                "brakes" => ["front" => "Performance Ventilated Disc (356mm)","rear" => "Performance Ventilated Disc (323mm)","abs" => "4-channel ABS + Sport Tuning"],
                "transmission" => ["type" => "6-Speed Manual (Helical LSD front + rear)","gears" => "6","mode" => "Track / Sport / Normal + Torque Split Dial"],
                "wheels" => ["size" => "18-inch Forged Alloy Wheels","tyres" => "235/40R18","spare" => "Compact Spare"],
                "features" => ["Circuit Aero Kit", "GR-Four AWD", "Sport Seats", "Performance Display", "Manual Transmission", "Sport Exhaust"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => true, "7 Airbags" => false, "6 Airbags" => false, "Airbags (4)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => false, "Rear Parking Sensors" => true, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "4 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => false, "Cup Holders" => true, "Foldable Rear Seats" => false, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => false, "Sport Seats" => true, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => false]
            ],
            [
                "name" => "GR Corolla Morizo Edition",
                "price" => "RM 420,000",
                "priceNumber" => 420000,
                "monthly" => "Est. RM 5,380 / month",
                "fuelType" => "Petrol",
                "dimensions" => ["length" => "4360 mm","width" => "1850 mm","height" => "1475 mm","wheelbase" => "2640 mm","kerbWeight" => "1440 kg","grossWeight" => "1855 kg","cargoVolume" => "217 L","fuelTank" => "50 Litres","frontTread" => "1590 mm","rearTread" => "1590 mm","turningRadius" => "5.4 m","doors" => "5","seats" => "2"],
                "engine" => ["name" => "G16E-GTS 1.6L 3-Cylinder Turbo (Morizo Tune)","displacement" => "1618 cc","horsepower" => "304 PS @ 6500 rpm","torque" => "400 Nm @ 3000-5550 rpm","compression" => "10.5:1","fuelSystem" => "Direct Injection D-4ST","aspiration" => "Twin-Scroll Turbocharged (Morizo Spec)"],
                "performance" => ["transmission" => "6-Speed Manual","drivetrain" => "GR-Four AWD (Track-focused Torque Split)","fuelConsumption" => "Approx. 8.8L / 100km","topSpeed" => "Approx. 235 km/h","acceleration" => "Approx. 5.2s (0-100km/h)"],
                "steering" => ["type" => "Track-tuned Electric Power Steering","turnsLockToLock" => "2.4","turningCircle" => "10.8 m"],
                "suspension" => ["front" => "Track-tuned MacPherson Strut (Sachs)","rear" => "Double Wishbone Track-tuned (Sachs)"],
                "brakes" => ["front" => "High-performance Ventilated Disc (356mm)","rear" => "High-performance Ventilated Disc (323mm)","abs" => "4-channel ABS Track-tuned"],
                "transmission" => ["type" => "6-Speed Manual (Torsen LSD front + rear)","gears" => "6","mode" => "Track Only + Manual Torque Distribution"],
                "wheels" => ["size" => "18-inch Lightweight Forged Wheels","tyres" => "235/40R18 Track Compound","spare" => "No Spare (Run-Flat Foam)"],
                "features" => ["Track-focused Setup", "GR-Four AWD", "Lightweight Body", "Sport Exhaust", "Performance Display", "Manual Transmission"],
                "safety" => ["ABS with EBD" => true, "Vehicle Stability Control" => true, "Brake Assist" => true, "Hill-start Assist" => false, "7 Airbags" => false, "6 Airbags" => false, "Airbags (2)" => true, "Blind Spot Monitor" => false, "Rear Cross Traffic Alert" => false, "Parking Sensors" => false, "Rear Parking Sensors" => false, "Toyota Safety Sense" => false, "Pre-Collision System" => false, "Lane Departure Alert" => false, "Lane Tracing Assist" => false],
                "safetyNote" => "2 Airbags",
                "comfort" => ["Fabric Seats" => false, "Manual Air Conditioning" => false, "Auto Air Conditioning" => true, "Spacious Legroom" => false, "Cup Holders" => false, "Foldable Rear Seats" => false, "Quiet Cabin" => false, "Rear Armrest" => false, "Leather Seats" => false, "Sport Seats" => true, "Power Seats" => false, "USB Ports (x2)" => true, "USB Ports (x4)" => false, "Rear Air Vents" => false]
            ]
        ]
    ]
];

$carId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
if (!isset($cars[$carId])) { $carId = 1; }
$car = $cars[$carId];
$isBooking = $car["status"] === "Booking Required";
$firstVariant = $car["variants"][0];

$similarCars = array_filter($cars, function ($item) use ($carId) { return $item["id"] !== $carId; });
$similarCars = array_slice($similarCars, 0, 3, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car["name"]); ?> - Car Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #d71920;
            --red-dark: #8f0f14;
            --black: #0a0a0a;
            --black-2: #111111;
            --black-3: #1a1a1a;
            --black-4: #222222;
            --grey: #888888;
            --grey-light: #cccccc;
            --white: #ffffff;
            --off-white: #f5f5f5;
            --border: rgba(255,255,255,0.08);
            --border-red: rgba(215,25,32,0.2);
            --shadow: 0 20px 60px rgba(0,0,0,0.4);
            --shadow-red: 0 15px 40px rgba(215,25,32,0.25);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { background: var(--black); color: var(--white); font-family: 'Outfit', sans-serif; }
        a { text-decoration:none; color:inherit; }

        /* NAVBAR */
        .navbar {
            width:100%; min-height:80px;
            background: rgba(10,10,10,0.95);
            backdrop-filter: blur(20px);
            display:flex; justify-content:space-between; align-items:center;
            padding:0 5%;
            border-bottom:1px solid var(--border);
            position:sticky; top:0; z-index:999;
        }
        .logo { display:flex; align-items:center; gap:12px; }
        .logo-mark {
            width:44px; height:44px; border-radius:12px;
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            display:flex; justify-content:center; align-items:center;
            box-shadow: var(--shadow-red); position:relative; overflow:hidden;
        }
        .logo-mark::before { content:""; position:absolute; width:30px; height:16px; border:2.5px solid #fff; border-radius:50%; }
        .logo-mark::after { content:""; position:absolute; width:16px; height:30px; border:2.5px solid #fff; border-radius:50%; }
        .logo-text strong { color:var(--white); font-size:22px; font-weight:900; letter-spacing:2px; display:block; }
        .logo-text small { color:var(--grey); font-size:9px; letter-spacing:3px; font-weight:600; }
        .nav-center { display:flex; align-items:center; gap:4px; }
        .nav-center a {
            color:var(--grey-light); font-size:13px; font-weight:600;
            padding:10px 14px; border-radius:20px; transition:0.3s;
            border:1px solid transparent;
        }
        .nav-center a:hover { color:var(--white); background:rgba(255,255,255,0.06); }
        .nav-center a.active { color:var(--white); background:var(--red); border-color:var(--red); }
        .nav-right { display:flex; align-items:center; gap:10px; }
        .username { font-size:13px; font-weight:600; color:var(--grey-light); background:rgba(255,255,255,0.06); padding:9px 14px; border-radius:20px; border:1px solid var(--border); }
        .login-btn, .logout-btn { background:var(--red); color:#fff; padding:10px 20px; border-radius:20px; font-size:13px; font-weight:700; transition:0.3s; }
        .login-btn:hover, .logout-btn:hover { background:var(--red-dark); }
        .menu-btn { display:none; background:rgba(255,255,255,0.06); border:1px solid var(--border); font-size:22px; cursor:pointer; color:var(--white); width:44px; height:44px; border-radius:12px; }

        /* BREADCRUMB */
        .breadcrumb-section { padding:20px 5%; background:var(--black-2); border-bottom:1px solid var(--border); }
        .breadcrumb { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--grey); font-weight:500; }
        .breadcrumb a { color:var(--grey); transition:0.2s; }
        .breadcrumb a:hover { color:var(--red); }
        .breadcrumb span { color:var(--grey-light); }

        /* STATUS TOP */
        .status-top-section { padding:24px 5%; background:var(--black-2); }
        .status-top-box {
            background: linear-gradient(135deg, var(--black-3), var(--black-4));
            border:1px solid var(--border);
            border-left:4px solid var(--red);
            border-radius:20px; padding:20px 24px;
            display:flex; justify-content:space-between; align-items:center; gap:16px;
        }
        .status-top-box h2 { color:var(--white); font-size:18px; font-weight:800; margin-bottom:6px; }
        .status-top-box p { color:var(--grey); line-height:1.6; font-size:13.5px; max-width:800px; }
        .status-top-pill {
            background: var(--black-3);
            color: var(--white);
            border: 1px solid rgba(255,255,255,0.15);
            padding:11px 18px; border-radius:20px; font-size:13px; font-weight:700; white-space:nowrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .status-top-pill.green { border-color: rgba(34,197,94,0.3); color:#4ade80; }
        .status-top-pill.yellow { border-color: rgba(234,179,8,0.3); color:#facc15; }

        /* DETAILS HERO */
        .details-hero { padding:40px 5% 70px; background:var(--black-2); }
        .details-layout { display:grid; grid-template-columns:1.1fr 0.9fr; gap:36px; align-items:start; }
        .left-product-box { background:var(--black-3); border-radius:28px; border:1px solid var(--border); overflow:hidden; }
        .main-image-wrap { position:relative; height:420px; overflow:hidden; background:var(--black); }
        .main-image-wrap::after { content:""; position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,0.7), transparent 55%); pointer-events:none; }
        .main-car-image { width:100%; height:100%; object-fit:cover; transition:0.5s; }
        .main-image-wrap:hover .main-car-image { transform:scale(1.04); }
        .image-status-badge { position:absolute; top:18px; left:18px; z-index:3; padding:8px 14px; border-radius:16px; background:rgba(10,10,10,0.85); color:#fff; font-size:12px; font-weight:700; border:1px solid var(--border); backdrop-filter:blur(10px); }
        .fuel-badge { position:absolute; top:18px; right:18px; z-index:3; padding:8px 14px; border-radius:16px; font-size:12px; font-weight:700; border:1px solid; backdrop-filter:blur(10px); }
        .fuel-badge.petrol { background:rgba(215,25,32,0.15); color:#ff6b6b; border-color:rgba(215,25,32,0.3); }
        .fuel-badge.diesel { background:rgba(251,191,36,0.1); color:#fbbf24; border-color:rgba(251,191,36,0.25); }
        .fuel-badge.hybrid { background:rgba(34,197,94,0.1); color:#4ade80; border-color:rgba(34,197,94,0.25); }
        .car-summary { padding:26px; }
        .detail-label { display:inline-block; padding:6px 12px; border-radius:16px; background:rgba(215,25,32,0.12); color:var(--red); font-size:12px; font-weight:700; letter-spacing:1px; margin-bottom:12px; border:1px solid var(--border-red); }
        .car-summary h1 { font-size:38px; font-weight:900; line-height:1.1; margin-bottom:10px; }
        .car-price { font-size:26px; color:var(--red); font-weight:900; margin-bottom:6px; }
        .monthly { color:var(--grey); font-size:14px; font-weight:600; margin-bottom:16px; }
        .status-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .status-pill { padding:7px 12px; border-radius:14px; background:rgba(255,255,255,0.06); color:var(--grey-light); font-size:12px; font-weight:700; border:1px solid var(--border); }
        .status-pill.red { background:rgba(215,25,32,0.15); color:#ff6b6b; border-color:var(--border-red); }
        .short-desc { color:var(--grey); line-height:1.7; font-size:14px; }

        /* STICKY FLOAT BADGE (bottom right) */
        .sticky-float {
            position:fixed; bottom:24px; right:24px; z-index:998;
            background:rgba(10,10,10,0.92); border:1px solid var(--border);
            border-radius:20px; padding:14px 18px;
            backdrop-filter:blur(20px);
            box-shadow:0 20px 50px rgba(0,0,0,0.5);
            display:flex; align-items:center; gap:14px;
            transform:translateY(100px); opacity:0;
            transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);
            max-width:400px;
        }
        .sticky-float.visible { transform:translateY(0); opacity:1; }
        .sticky-float-info h4 { font-size:13px; font-weight:700; color:var(--white); }
        .sticky-float-info p { font-size:12px; color:var(--red); font-weight:700; }
        .sticky-float-actions { display:flex; gap:8px; }
        .sf-btn { padding:8px 14px; border-radius:14px; font-size:12px; font-weight:700; white-space:nowrap; transition:0.2s; cursor:pointer; border:none; }
        .sf-btn.red { background:var(--red); color:#fff; }
        .sf-btn.outline { background:transparent; color:var(--grey-light); border:1px solid var(--border); }
        .sf-btn:hover { opacity:0.85; transform:scale(0.97); }

        /* OPTION PANEL */
        .option-panel {
            background:var(--black-3); border-radius:28px; padding:28px;
            border:1px solid var(--border); position:sticky; top:100px;
        }
        .option-panel h2 { font-size:22px; font-weight:800; margin-bottom:6px; }
        .option-panel > p { color:var(--grey); font-size:13px; line-height:1.6; margin-bottom:22px; }
        .option-box { margin-bottom:24px; }
        .option-box h3 { font-size:14px; font-weight:700; color:var(--grey-light); text-transform:uppercase; letter-spacing:1px; margin-bottom:12px; }
        .variant-select-grid { display:grid; gap:10px; }
        .variant-option {
            border:1px solid var(--border); border-radius:16px; padding:14px 16px;
            cursor:pointer; transition:0.3s; background:rgba(255,255,255,0.03);
            display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center;
        }
        .variant-option.active, .variant-option:hover { background:rgba(215,25,32,0.08); border-color:var(--red); }
        .variant-option strong { display:block; color:var(--white); font-size:14px; margin-bottom:4px; }
        .variant-option .v-price { color:var(--red); font-weight:700; font-size:13px; }
        .variant-mini-spec { text-align:right; color:var(--grey); font-size:11px; line-height:1.5; }
        .colour-select-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
        .colour-option {
            display:flex; align-items:center; gap:10px;
            border:1px solid var(--border); background:rgba(255,255,255,0.03);
            border-radius:14px; padding:11px 13px; cursor:pointer; transition:0.3s;
        }
        .colour-option.active, .colour-option:hover { background:rgba(215,25,32,0.08); border-color:var(--red); }
        .colour-circle { width:26px; height:26px; border-radius:50%; border:2px solid rgba(255,255,255,0.15); flex-shrink:0; }
        .colour-option span { font-size:12px; color:var(--grey-light); font-weight:600; }
        .quick-actions { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
        .action-main, .action-red-full { grid-column:1/-1; }
        .action-main, .action-red-full, .action-outline {
            min-height:48px; border-radius:18px; display:inline-flex;
            justify-content:center; align-items:center; font-size:13.5px; font-weight:700;
            text-align:center; transition:0.3s; cursor:pointer;
        }
        .action-main, .action-red-full { background:var(--red); color:#fff; border:1px solid var(--red); }
        .action-main:hover, .action-red-full:hover { background:var(--red-dark); transform:translateY(-1px); }
        .action-outline { background:transparent; color:var(--grey-light); border:1px solid var(--border); }
        .action-outline:hover { border-color:var(--red); color:var(--red); background:rgba(215,25,32,0.06); }

        /* TABBED SPEC SECTION */
        .section { padding:70px 5%; }
        .section-title { text-align:center; margin-bottom:36px; }
        .section-title .detail-label { margin-bottom:12px; }
        .section-title h2 { font-size:36px; font-weight:900; margin-bottom:10px; }
        .section-title p { color:var(--grey); line-height:1.7; max-width:700px; margin:0 auto; }
        .spec-section { background:var(--black); }

        /* TAB NAV */
        .tab-nav {
            display:flex; gap:6px; overflow-x:auto; padding-bottom:4px;
            margin-bottom:28px; border-bottom:1px solid var(--border);
            scrollbar-width:none;
        }
        .tab-nav::-webkit-scrollbar { display:none; }
        .tab-btn {
            padding:10px 18px; border-radius:14px; font-size:13px; font-weight:600;
            border:1px solid transparent; background:transparent; color:var(--grey);
            cursor:pointer; transition:0.25s; white-space:nowrap;
        }
        .tab-btn:hover { color:var(--white); background:rgba(255,255,255,0.04); }
        .tab-btn.active { background:rgba(215,25,32,0.12); color:var(--red); border-color:var(--border-red); }

        /* TAB CONTENT */
        .tab-content { display:none; }
        .tab-content.active { display:block; }

        .spec-grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .spec-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
        .spec-card {
            background:var(--black-3); border:1px solid var(--border); border-radius:20px; overflow:hidden;
        }
        .spec-card-header {
            background:var(--black-4); padding:16px 20px; border-bottom:1px solid var(--border);
            display:flex; align-items:center; gap:10px;
        }
        .spec-card-header h3 { font-size:15px; font-weight:700; color:var(--white); }
        .spec-card-icon { width:32px; height:32px; border-radius:10px; background:rgba(215,25,32,0.12); display:flex; justify-content:center; align-items:center; font-size:16px; flex-shrink:0; }
        .spec-table { width:100%; border-collapse:collapse; }
        .spec-table tr { border-bottom:1px solid var(--border); }
        .spec-table tr:last-child { border-bottom:none; }
        .spec-table td { padding:13px 20px; font-size:13.5px; line-height:1.5; }
        .spec-table td:first-child { color:var(--grey); font-weight:600; width:45%; }
        .spec-table td:last-child { color:var(--grey-light); font-weight:500; }
        .spec-table td strong { color:var(--white); font-weight:700; }

        /* FEATURES SECTION */
        .features-section { background:var(--black-2); }

        /* Feature/Safety/Comfort tabs */
        .feat-tab-nav { display:flex; gap:6px; margin-bottom:24px; }
        .feat-tab-btn { padding:9px 16px; border-radius:12px; font-size:13px; font-weight:600; border:1px solid var(--border); background:transparent; color:var(--grey); cursor:pointer; transition:0.25s; }
        .feat-tab-btn.active { background:rgba(215,25,32,0.12); color:var(--red); border-color:var(--border-red); }
        .feat-tab-content { display:none; }
        .feat-tab-content.active { display:block; }

        .feature-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:10px; }
        .feature-item {
            background:var(--black-3); border:1px solid var(--border); border-radius:14px;
            padding:13px 16px; display:flex; align-items:center; gap:12px;
            transition:0.2s;
        }
        .feature-item.has { border-color:rgba(34,197,94,0.15); }
        .feature-item.hasnt { opacity:0.45; }
        .feature-check {
            width:28px; height:28px; border-radius:8px; flex-shrink:0;
            display:flex; justify-content:center; align-items:center; font-size:13px;
        }
        .feature-check.yes { background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2); }
        .feature-check.no { background:rgba(255,255,255,0.04); color:var(--grey); border:1px solid var(--border); }
        .feature-item span { font-size:13.5px; font-weight:600; color:var(--grey-light); }
        .feature-item.has span { color:var(--white); }

        /* COMPARE VARIANTS */
        .compare-section { background:var(--black); }
        .compare-wrapper { overflow-x:auto; }
        .compare-table { width:100%; border-collapse:collapse; min-width:600px; }
        .compare-table th, .compare-table td { padding:13px 16px; text-align:left; font-size:13px; border-bottom:1px solid var(--border); }
        .compare-table thead th { background:var(--black-3); color:var(--grey); font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; }
        .compare-table thead th:first-child { color:var(--grey); }
        .compare-table thead th.highlight { background:rgba(215,25,32,0.1); color:var(--red); border-bottom:2px solid var(--red); }
        .compare-table tbody tr:hover td { background:rgba(255,255,255,0.02); }
        .compare-table tbody td:first-child { color:var(--grey); font-weight:600; font-size:12px; }
        .compare-table tbody td { color:var(--grey-light); }
        .compare-table .check-y { color:#4ade80; font-size:15px; }
        .compare-table .check-n { color:var(--grey); font-size:13px; opacity:0.4; }
        .compare-table .cat-row td { background:var(--black-4); color:var(--grey); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; padding:10px 16px; }
        .compare-table .price-row td { color:var(--red); font-weight:800; font-size:14px; }

        /* DESCRIPTION */
        .description-section { background:var(--black-2); }
        .desc-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .desc-card { background:var(--black-3); border:1px solid var(--border); border-radius:20px; padding:22px; }
        .desc-card h3 { font-size:16px; font-weight:700; margin-bottom:10px; color:var(--white); }
        .desc-card p { color:var(--grey); line-height:1.7; font-size:13.5px; }

        /* LOAN SECTION */
        .loan-section { background:var(--black); padding:70px 5%; }
        .loan-box {
            background: linear-gradient(135deg, rgba(215,25,32,0.12), rgba(143,15,20,0.06));
            border:1px solid var(--border-red); border-radius:28px; padding:44px;
            display:grid; grid-template-columns:1fr 1fr; gap:36px; align-items:center;
        }
        .loan-box h2 { font-size:34px; font-weight:900; margin-bottom:12px; }
        .loan-box > div > p { color:var(--grey); line-height:1.7; margin-bottom:22px; font-size:14px; }
        .loan-summary { background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:20px; padding:22px; }
        .loan-row { display:flex; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); font-size:14px; }
        .loan-row:last-child { border-bottom:none; }
        .loan-row span { color:var(--grey); font-weight:500; }
        .loan-row strong { color:var(--white); font-weight:700; }
        .loan-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .white-btn { display:inline-flex; justify-content:center; align-items:center; padding:13px 28px; background:var(--white); color:var(--black); border-radius:24px; font-weight:700; transition:0.3s; border:2px solid var(--white); font-size:14px; }
        .white-btn:hover { background:transparent; color:var(--white); }
        .outline-btn { display:inline-flex; justify-content:center; align-items:center; padding:13px 28px; background:transparent; color:var(--grey-light); border-radius:24px; font-weight:700; transition:0.3s; border:2px solid var(--border); font-size:14px; }
        .outline-btn:hover { border-color:var(--red); color:var(--red); }

        /* SIMILAR */
        .similar-section { background:var(--black-2); }
        .similar-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; }
        .similar-card { background:var(--black-3); border-radius:22px; overflow:hidden; border:1px solid var(--border); transition:0.3s; }
        .similar-card:hover { transform:translateY(-6px); border-color:var(--border-red); }
        .similar-img-wrap { height:180px; overflow:hidden; position:relative; }
        .similar-img-wrap::after { content:""; position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,0.65), transparent 55%); }
        .similar-img-wrap img { width:100%; height:100%; object-fit:cover; }
        .similar-status { position:absolute; top:12px; left:12px; background:rgba(215,25,32,0.85); color:#fff; z-index:2; padding:6px 11px; border-radius:12px; font-size:11px; font-weight:700; }
        .similar-info { padding:18px; }
        .similar-info h3 { font-size:18px; font-weight:800; margin-bottom:6px; }
        .similar-info p { color:var(--red); font-size:15px; font-weight:800; margin-bottom:14px; }
        .similar-actions { display:flex; gap:7px; flex-wrap:wrap; }
        .small-red, .small-outline { min-height:34px; border-radius:14px; display:inline-flex; justify-content:center; align-items:center; font-size:12px; font-weight:700; transition:0.25s; padding:0 12px; }
        .small-red { background:var(--red); color:#fff; border:1px solid var(--red); }
        .small-red:hover { background:var(--red-dark); }
        .small-outline { background:transparent; color:var(--grey-light); border:1px solid var(--border); }
        .small-outline:hover { border-color:var(--red); color:var(--red); }

        /* FAQ */
        .faq-section { background:var(--black); }
        .faq-container { max-width:880px; margin:0 auto; }
        .faq-item { background:var(--black-3); border-radius:16px; margin-bottom:12px; border:1px solid var(--border); overflow:hidden; }
        .faq-question { padding:20px 24px; cursor:pointer; font-weight:700; color:var(--white); display:flex; justify-content:space-between; align-items:center; font-size:15px; }
        .faq-question span { font-size:18px; color:var(--red); width:28px; height:28px; border-radius:8px; background:rgba(215,25,32,0.1); display:flex; justify-content:center; align-items:center; flex-shrink:0; }
        .faq-answer { max-height:0; overflow:hidden; transition:max-height 0.35s ease; }
        .faq-answer p { padding:0 24px 20px; color:var(--grey); line-height:1.75; font-size:14px; }
        .faq-item.active .faq-answer { max-height:180px; }

        /* FOOTER */
        .footer { background:var(--black-2); color:var(--white); padding:56px 5% 24px; border-top:1px solid var(--border); }
        .footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1.4fr; gap:36px; margin-bottom:30px; }
        .footer h3 { font-size:18px; margin-bottom:14px; font-weight:700; }
        .footer p, .footer a { color:var(--grey); line-height:1.9; font-size:13.5px; margin-bottom:4px; display:block; transition:0.2s; }
        .footer a:hover { color:var(--red); }
        .footer-bottom { border-top:1px solid var(--border); padding-top:20px; text-align:center; color:var(--grey); font-size:13px; }

        /* RESPONSIVE */
        @media (max-width:1200px) {
            .desc-grid { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:1100px) {
            .nav-center { position:absolute; top:80px; left:5%; right:5%; display:none; flex-direction:column; border-radius:20px; padding:12px; background:rgba(17,17,17,0.98); box-shadow:var(--shadow); border:1px solid var(--border); }
            .nav-center.show { display:flex; }
            .nav-center a { text-align:center; padding:13px; border-radius:12px; }
            .menu-btn { display:block; }
            .details-layout, .loan-box { grid-template-columns:1fr; }
            .status-top-box { flex-direction:column; align-items:flex-start; }
            .option-panel { position:relative; top:auto; }
        }
        @media (max-width:900px) {
            .spec-grid-2, .spec-grid-3, .similar-grid, .footer-grid { grid-template-columns:1fr; }
        }
        @media (max-width:768px) {
            .main-image-wrap { height:300px; }
            .car-summary h1 { font-size:30px; }
            .desc-grid, .footer-grid { grid-template-columns:1fr; }
            .colour-select-grid, .quick-actions { grid-template-columns:1fr; }
            .loan-box { padding:28px 20px; }
            .loan-box h2 { font-size:26px; }
            .similar-grid { grid-template-columns:1fr; }
            .spec-table td { display:block; width:100%; }
            .spec-table td:first-child { padding-bottom:4px; }
            .sticky-float { max-width:calc(100vw - 32px); right:16px; }
        }
    </style>
</head>
<body>

<!-- STICKY FLOAT -->
<div class="sticky-float" id="stickyFloat">
    <div class="sticky-float-info">
        <h4 id="sfCarName"><?php echo htmlspecialchars($car["name"]); ?></h4>
        <p id="sfPrice"><?php echo $firstVariant["price"]; ?></p>
    </div>
    <div class="sticky-float-actions">
        <?php if($isBooking): ?>
            <a href="booking.php?car=<?php echo urlencode($car["name"]); ?>" class="sf-btn red">Book Now</a>
        <?php else: ?>
            <a href="test_drive.php?car=<?php echo urlencode($car["name"]); ?>" class="sf-btn red">Test Drive</a>
        <?php endif; ?>
        <a href="loan_calculator.php?car=<?php echo urlencode($car["name"]); ?>&price=<?php echo $firstVariant["priceNumber"]; ?>" class="sf-btn outline" id="sfLoanBtn">Loan</a>
    </div>
</div>

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
        <?php if($username): ?>
            <span class="username">Hi, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav>

<section class="breadcrumb-section">
    <div class="breadcrumb">
        <a href="homepage.php">Home</a><span>›</span>
        <a href="catalogue.php">Catalogue</a><span>›</span>
        <span><?php echo htmlspecialchars($car["name"]); ?></span>
    </div>
</section>

<section class="status-top-section">
    <div class="status-top-box">
        <div>
            <h2>Status: <?php echo $car["status"]; ?></h2>
            <?php if($isBooking): ?>
                <p>This model requires advance booking. Estimated waiting time: <?php echo $car["waiting"]; ?>. Booking fee: <?php echo $car["bookingFee"]; ?>. Our team will contact you after your booking request is submitted.</p>
            <?php else: ?>
                <p>This model is currently available for viewing, loan application and test drive booking. You may book a test drive or submit loan assistance information online.</p>
            <?php endif; ?>
        </div>
        <div class="status-top-pill <?php echo $isBooking ? 'yellow' : 'green'; ?>">
            <?php echo $car["stock"]; ?>
        </div>
    </div>
</section>

<section class="details-hero">
    <div class="details-layout">
        <div class="left-product-box">
            <div class="main-image-wrap">
                <img src="<?php echo $car["colours"][0]["image"]; ?>" class="main-car-image" id="mainCarImage" alt="<?php echo htmlspecialchars($car["name"]); ?>">
                <div class="image-status-badge"><?php echo $car["status"]; ?></div>
                <div class="fuel-badge <?php echo strtolower($firstVariant["fuelType"]); ?>" id="fuelBadge"><?php echo $firstVariant["fuelType"]; ?></div>
            </div>
            <div class="car-summary">
                <span class="detail-label"><?php echo $car["label"]; ?></span>
                <h1><?php echo htmlspecialchars($car["name"]); ?></h1>
                <div class="car-price" id="selectedVariantPrice"><?php echo $firstVariant["price"]; ?></div>
                <div class="monthly" id="selectedVariantMonthly"><?php echo $firstVariant["monthly"]; ?></div>
                <div class="status-row">
                    <span class="status-pill red"><?php echo $car["status"]; ?></span>
                    <span class="status-pill"><?php echo $car["type"]; ?></span>
                    <span class="status-pill"><?php echo $car["stock"]; ?></span>
                </div>
                <p class="short-desc"><?php echo $car["short"]; ?></p>
            </div>
        </div>

        <div class="option-panel">
            <h2>Configure Your Car</h2>
            <p>Choose a variant and colour. All specifications, features and pricing update instantly.</p>
            <div class="option-box">
                <h3>Variant</h3>
                <div class="variant-select-grid">
                    <?php foreach($car["variants"] as $idx => $v): ?>
                    <div class="variant-option <?php echo $idx===0?'active':''; ?>" onclick="selectVariant(<?php echo $idx; ?>, this)">
                        <div>
                            <strong><?php echo $v["name"]; ?></strong>
                            <span class="v-price"><?php echo $v["price"]; ?></span>
                        </div>
                        <div class="variant-mini-spec">
                            <?php echo $v["engine"]["name"]; ?><br>
                            <?php echo $v["engine"]["horsepower"]; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="option-box">
                <h3>Colour</h3>
                <div class="colour-select-grid">
                    <?php foreach($car["colours"] as $idx => $colour): ?>
                    <div class="colour-option <?php echo $idx===0?'active':''; ?>" onclick="selectColour('<?php echo $colour["image"]; ?>', this)">
                        <div class="colour-circle" style="background:<?php echo $colour["code"]; ?>;"></div>
                        <span><?php echo $colour["name"]; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="quick-actions">
                <?php if($isBooking): ?>
                    <a href="booking.php?car=<?php echo urlencode($car["name"]); ?>" class="action-main">Book Now</a>
                    <a href="loan_calculator.php?car=<?php echo urlencode($car["name"]); ?>&price=<?php echo $firstVariant["priceNumber"]; ?>" class="action-outline" id="loanCalcTop">Calculate Loan</a>
                    <a href="compare.php?car=<?php echo urlencode($car["name"]); ?>" class="action-outline">Compare This Car</a>
                    <a href="contact.php?car=<?php echo urlencode($car["name"]); ?>" class="action-red-full">Ask Availability</a>
                <?php else: ?>
                    <a href="test_drive.php?car=<?php echo urlencode($car["name"]); ?>" class="action-main">Book Test Drive</a>
                    <a href="loan_calculator.php?car=<?php echo urlencode($car["name"]); ?>&price=<?php echo $firstVariant["priceNumber"]; ?>" class="action-outline" id="loanCalcTop">Calculate Loan</a>
                    <a href="compare.php?car=<?php echo urlencode($car["name"]); ?>" class="action-outline">Compare This Car</a>
                    <a href="loan_application.php?car=<?php echo urlencode($car["name"]); ?>" class="action-red-full">Apply Loan Assistance</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- VEHICLE SPECIFICATIONS - TABBED -->
<section class="section spec-section">
    <div class="section-title">
        <span class="detail-label">VEHICLE DETAILS</span>
        <h2>Vehicle Specifications</h2>
        <p>Click each tab to explore detailed specifications. All data updates when you select a different variant.</p>
    </div>

    <div class="tab-nav" id="specTabNav">
        <button class="tab-btn active" onclick="switchTab('dimensions', this)">Dimensions &amp; Capacity</button>
        <button class="tab-btn" onclick="switchTab('engine', this)">Engine Details</button>
        <button class="tab-btn" onclick="switchTab('performance', this)">Performance</button>
        <button class="tab-btn" onclick="switchTab('steering', this)">Steering</button>
        <button class="tab-btn" onclick="switchTab('suspension', this)">Suspension &amp; Brakes</button>
        <button class="tab-btn" onclick="switchTab('transmission', this)">Transmission</button>
        <button class="tab-btn" onclick="switchTab('wheels', this)">Wheel &amp; Tyre</button>
        <button class="tab-btn" onclick="switchTab('ownership', this)">Ownership</button>
    </div>

    <!-- DIMENSIONS & CAPACITY -->
    <div class="tab-content active" id="tab-dimensions">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">📐</div><h3>Body Dimensions</h3></div>
                <table class="spec-table">
                    <tr><td>Length</td><td><strong id="dim-length"><?php echo $firstVariant["dimensions"]["length"]; ?></strong></td></tr>
                    <tr><td>Width</td><td><strong id="dim-width"><?php echo $firstVariant["dimensions"]["width"]; ?></strong></td></tr>
                    <tr><td>Height</td><td><strong id="dim-height"><?php echo $firstVariant["dimensions"]["height"]; ?></strong></td></tr>
                    <tr><td>Wheelbase</td><td><strong id="dim-wheelbase"><?php echo $firstVariant["dimensions"]["wheelbase"]; ?></strong></td></tr>
                    <tr><td>Front Tread</td><td><strong id="dim-frontTread"><?php echo $firstVariant["dimensions"]["frontTread"]; ?></strong></td></tr>
                    <tr><td>Rear Tread</td><td><strong id="dim-rearTread"><?php echo $firstVariant["dimensions"]["rearTread"]; ?></strong></td></tr>
                    <tr><td>No. of Doors</td><td><strong id="dim-doors"><?php echo $firstVariant["dimensions"]["doors"]; ?></strong></td></tr>
                </table>
            </div>
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">⚖️</div><h3>Weight &amp; Capacity</h3></div>
                <table class="spec-table">
                    <tr><td>Kerb Weight</td><td><strong id="dim-kerbWeight"><?php echo $firstVariant["dimensions"]["kerbWeight"]; ?></strong></td></tr>
                    <tr><td>Gross Weight</td><td><strong id="dim-grossWeight"><?php echo $firstVariant["dimensions"]["grossWeight"]; ?></strong></td></tr>
                    <tr><td>Seating Capacity</td><td><strong id="dim-seats"><?php echo $firstVariant["dimensions"]["seats"]; ?></strong></td></tr>
                    <tr><td>Cargo Volume</td><td><strong id="dim-cargoVolume"><?php echo $firstVariant["dimensions"]["cargoVolume"]; ?></strong></td></tr>
                    <tr><td>Fuel Tank</td><td><strong id="dim-fuelTank"><?php echo $firstVariant["dimensions"]["fuelTank"]; ?></strong></td></tr>
                    <tr><td>Turning Radius</td><td><strong id="dim-turningRadius"><?php echo $firstVariant["dimensions"]["turningRadius"]; ?></strong></td></tr>
                    <tr><td>Body Type</td><td><strong><?php echo $car["body"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- ENGINE DETAILS -->
    <div class="tab-content" id="tab-engine">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">🔧</div><h3>Engine Specification</h3></div>
                <table class="spec-table">
                    <tr><td>Engine Code / Name</td><td><strong id="eng-name"><?php echo $firstVariant["engine"]["name"]; ?></strong></td></tr>
                    <tr><td>Displacement</td><td><strong id="eng-displacement"><?php echo $firstVariant["engine"]["displacement"]; ?></strong></td></tr>
                    <tr><td>Max Horsepower</td><td><strong id="eng-horsepower"><?php echo $firstVariant["engine"]["horsepower"]; ?></strong></td></tr>
                    <tr><td>Max Torque</td><td><strong id="eng-torque"><?php echo $firstVariant["engine"]["torque"]; ?></strong></td></tr>
                    <tr><td>Compression Ratio</td><td><strong id="eng-compression"><?php echo $firstVariant["engine"]["compression"]; ?></strong></td></tr>
                    <tr><td>Fuel System</td><td><strong id="eng-fuelSystem"><?php echo $firstVariant["engine"]["fuelSystem"]; ?></strong></td></tr>
                    <tr><td>Aspiration</td><td><strong id="eng-aspiration"><?php echo $firstVariant["engine"]["aspiration"]; ?></strong></td></tr>
                </table>
            </div>
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">⛽</div><h3>Fuel Details</h3></div>
                <table class="spec-table">
                    <tr><td>Fuel Type</td><td><strong id="eng-fuelType"><?php echo $firstVariant["fuelType"]; ?></strong></td></tr>
                    <tr><td>Fuel Tank Capacity</td><td><strong id="eng-fuelTank2"><?php echo $firstVariant["dimensions"]["fuelTank"]; ?></strong></td></tr>
                    <tr><td>Fuel Consumption</td><td><strong id="eng-fuelConsumption"><?php echo $firstVariant["performance"]["fuelConsumption"]; ?></strong></td></tr>
                    <tr><td>Drivetrain</td><td><strong id="eng-drivetrain"><?php echo $firstVariant["performance"]["drivetrain"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE -->
    <div class="tab-content" id="tab-performance">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">🏎️</div><h3>Driving Performance</h3></div>
                <table class="spec-table">
                    <tr><td>Top Speed</td><td><strong id="perf-topSpeed"><?php echo $firstVariant["performance"]["topSpeed"]; ?></strong></td></tr>
                    <tr><td>0 - 100 km/h</td><td><strong id="perf-acceleration"><?php echo $firstVariant["performance"]["acceleration"]; ?></strong></td></tr>
                    <tr><td>Fuel Consumption</td><td><strong id="perf-fuelConsumption"><?php echo $firstVariant["performance"]["fuelConsumption"]; ?></strong></td></tr>
                    <tr><td>Drivetrain</td><td><strong id="perf-drivetrain"><?php echo $firstVariant["performance"]["drivetrain"]; ?></strong></td></tr>
                    <tr><td>Transmission</td><td><strong id="perf-transmission"><?php echo $firstVariant["performance"]["transmission"]; ?></strong></td></tr>
                </table>
            </div>
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">📊</div><h3>Power Output</h3></div>
                <table class="spec-table">
                    <tr><td>Horsepower</td><td><strong id="perf-horsepower"><?php echo $firstVariant["engine"]["horsepower"]; ?></strong></td></tr>
                    <tr><td>Torque</td><td><strong id="perf-torque"><?php echo $firstVariant["engine"]["torque"]; ?></strong></td></tr>
                    <tr><td>Displacement</td><td><strong id="perf-displacement"><?php echo $firstVariant["engine"]["displacement"]; ?></strong></td></tr>
                    <tr><td>Aspiration</td><td><strong id="perf-aspiration"><?php echo $firstVariant["engine"]["aspiration"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- STEERING -->
    <div class="tab-content" id="tab-steering">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">🎯</div><h3>Steering System</h3></div>
                <table class="spec-table">
                    <tr><td>Steering Type</td><td><strong id="steer-type"><?php echo $firstVariant["steering"]["type"]; ?></strong></td></tr>
                    <tr><td>Turns Lock-to-Lock</td><td><strong id="steer-turns"><?php echo $firstVariant["steering"]["turnsLockToLock"]; ?></strong></td></tr>
                    <tr><td>Turning Circle</td><td><strong id="steer-circle"><?php echo $firstVariant["steering"]["turningCircle"]; ?></strong></td></tr>
                    <tr><td>Turning Radius</td><td><strong id="steer-radius"><?php echo $firstVariant["dimensions"]["turningRadius"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- SUSPENSION & BRAKES -->
    <div class="tab-content" id="tab-suspension">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">🔩</div><h3>Suspension</h3></div>
                <table class="spec-table">
                    <tr><td>Front Suspension</td><td><strong id="susp-front"><?php echo $firstVariant["suspension"]["front"]; ?></strong></td></tr>
                    <tr><td>Rear Suspension</td><td><strong id="susp-rear"><?php echo $firstVariant["suspension"]["rear"]; ?></strong></td></tr>
                </table>
            </div>
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">🛑</div><h3>Braking System</h3></div>
                <table class="spec-table">
                    <tr><td>Front Brakes</td><td><strong id="brake-front"><?php echo $firstVariant["brakes"]["front"]; ?></strong></td></tr>
                    <tr><td>Rear Brakes</td><td><strong id="brake-rear"><?php echo $firstVariant["brakes"]["rear"]; ?></strong></td></tr>
                    <tr><td>ABS System</td><td><strong id="brake-abs"><?php echo $firstVariant["brakes"]["abs"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- TRANSMISSION -->
    <div class="tab-content" id="tab-transmission">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">⚙️</div><h3>Transmission Details</h3></div>
                <table class="spec-table">
                    <tr><td>Transmission Type</td><td><strong id="trans-type"><?php echo $firstVariant["transmission"]["type"]; ?></strong></td></tr>
                    <tr><td>Number of Gears</td><td><strong id="trans-gears"><?php echo $firstVariant["transmission"]["gears"]; ?></strong></td></tr>
                    <tr><td>Drive Modes</td><td><strong id="trans-mode"><?php echo $firstVariant["transmission"]["mode"]; ?></strong></td></tr>
                    <tr><td>Drivetrain</td><td><strong id="trans-drivetrain"><?php echo $firstVariant["performance"]["drivetrain"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- WHEEL & TYRE -->
    <div class="tab-content" id="tab-wheels">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">🔵</div><h3>Wheel &amp; Tyre</h3></div>
                <table class="spec-table">
                    <tr><td>Wheel Size</td><td><strong id="wheel-size"><?php echo $firstVariant["wheels"]["size"]; ?></strong></td></tr>
                    <tr><td>Tyre Size</td><td><strong id="wheel-tyres"><?php echo $firstVariant["wheels"]["tyres"]; ?></strong></td></tr>
                    <tr><td>Spare Tyre</td><td><strong id="wheel-spare"><?php echo $firstVariant["wheels"]["spare"]; ?></strong></td></tr>
                    <tr><td>Front Tread Width</td><td><strong id="wheel-frontTread"><?php echo $firstVariant["dimensions"]["frontTread"]; ?></strong></td></tr>
                    <tr><td>Rear Tread Width</td><td><strong id="wheel-rearTread"><?php echo $firstVariant["dimensions"]["rearTread"]; ?></strong></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- OWNERSHIP -->
    <div class="tab-content" id="tab-ownership">
        <div class="spec-grid-2">
            <div class="spec-card">
                <div class="spec-card-header"><div class="spec-card-icon">📋</div><h3>Booking &amp; Ownership</h3></div>
                <table class="spec-table">
                    <tr><td>Vehicle Status</td><td><strong><?php echo $car["status"]; ?></strong></td></tr>
                    <tr><td>Stock Status</td><td><strong><?php echo $car["stock"]; ?></strong></td></tr>
                    <tr><td>Waiting Time</td><td><strong><?php echo $car["waiting"]; ?></strong></td></tr>
                    <tr><td>Booking Fee</td><td><strong><?php echo $car["bookingFee"]; ?></strong></td></tr>
                    <tr><td>Selected Variant</td><td><strong id="own-variant"><?php echo $firstVariant["name"]; ?></strong></td></tr>
                    <tr><td>Selected Price</td><td><strong id="own-price"><?php echo $firstVariant["price"]; ?></strong></td></tr>
                    <tr><td>Warranty</td><td><strong>5 Years Manufacturer Warranty</strong></td></tr>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES, SAFETY, COMFORT - TABBED -->
<section class="section features-section">
    <div class="section-title">
        <span class="detail-label">FEATURES</span>
        <h2>Features, Safety &amp; Comfort</h2>
        <p>All items reflect the selected variant. ✓ = Included &nbsp;○ = Not Available in this variant.</p>
    </div>

    <div class="feat-tab-nav">
        <button class="feat-tab-btn active" onclick="switchFeatTab('carfeatures', this)">Car Features</button>
        <button class="feat-tab-btn" onclick="switchFeatTab('safety', this)">Safety Features</button>
        <button class="feat-tab-btn" onclick="switchFeatTab('comfort', this)">Comfort Features</button>
    </div>

    <div class="feat-tab-content active" id="feat-carfeatures">
        <div class="feature-grid" id="featureList"></div>
    </div>
    <div class="feat-tab-content" id="feat-safety">
        <div class="feature-grid" id="safetyList"></div>
    </div>
    <div class="feat-tab-content" id="feat-comfort">
        <div class="feature-grid" id="comfortList"></div>
    </div>
</section>

<!-- VARIANT COMPARE -->
<section class="section compare-section">
    <div class="section-title">
        <span class="detail-label">COMPARE VARIANTS</span>
        <h2>Compare All Variants</h2>
        <p>Side-by-side comparison of all <?php echo htmlspecialchars($car["name"]); ?> variants. Currently selected variant is highlighted.</p>
    </div>
    <div class="compare-wrapper">
        <table class="compare-table" id="compareTable">
        </table>
    </div>
</section>

<!-- ABOUT -->
<section class="section description-section">
    <div class="section-title">
        <span class="detail-label">ABOUT THIS MODEL</span>
        <h2>About <?php echo htmlspecialchars($car["name"]); ?></h2>
        <p><?php echo $car["description"]; ?></p>
    </div>
    <div class="desc-grid">
        <div class="desc-card"><h3>Overview</h3><p><?php echo $car["description"]; ?></p></div>
        <div class="desc-card"><h3>Best For</h3><p><?php echo $car["bestFor"]; ?></p></div>
        <div class="desc-card"><h3>Driving Experience</h3><p><?php echo $car["drivingExperience"]; ?></p></div>
        <div class="desc-card"><h3>Why Choose This Car</h3><p><?php echo $car["whyChoose"]; ?></p></div>
    </div>
</section>

<!-- LOAN -->
<section class="loan-section">
    <div class="loan-box">
        <div>
            <h2>Estimated Monthly Payment</h2>
            <p>Based on 10% down payment and 7-year loan period. Adjust settings in the full loan calculator.</p>
            <div class="loan-actions">
                <a href="loan_calculator.php?car=<?php echo urlencode($car["name"]); ?>&price=<?php echo $firstVariant["priceNumber"]; ?>" class="white-btn" id="loanCalcBottom">Full Loan Calculator</a>
                <a href="loan_application.php?car=<?php echo urlencode($car["name"]); ?>" class="outline-btn">Apply Loan Assistance</a>
            </div>
        </div>
        <div class="loan-summary">
            <div class="loan-row"><span>Selected Variant</span><strong id="loanVariant"><?php echo $firstVariant["name"]; ?></strong></div>
            <div class="loan-row"><span>Selected Price</span><strong id="loanPrice"><?php echo $firstVariant["price"]; ?></strong></div>
            <div class="loan-row"><span>Down Payment</span><strong>10%</strong></div>
            <div class="loan-row"><span>Loan Period</span><strong>7 Years</strong></div>
            <div class="loan-row"><span>Est. Monthly</span><strong id="loanMonthly"><?php echo $firstVariant["monthly"]; ?></strong></div>
        </div>
    </div>
</section>

<!-- SIMILAR -->
<section class="section similar-section">
    <div class="section-title">
        <span class="detail-label">SIMILAR MODELS</span>
        <h2>Other Toyota Models</h2>
        <p>Explore other Toyota models before making your final decision.</p>
    </div>
    <div class="similar-grid">
        <?php foreach($similarCars as $similar): ?>
        <div class="similar-card">
            <div class="similar-img-wrap">
                <img src="<?php echo $similar["colours"][0]["image"]; ?>" alt="<?php echo htmlspecialchars($similar["name"]); ?>">
                <div class="similar-status"><?php echo $similar["status"]; ?></div>
            </div>
            <div class="similar-info">
                <h3><?php echo $similar["name"]; ?></h3>
                <p><?php echo $similar["priceText"]; ?></p>
                <div class="similar-actions">
                    <a href="compare.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-outline">Compare</a>
                    <?php if($similar["status"]==="Booking Required"): ?>
                        <a href="booking.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-red">Book Now</a>
                    <?php else: ?>
                        <a href="test_drive.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-red">Test Drive</a>
                    <?php endif; ?>
                    <a href="loan_application.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-outline">Apply Loan</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- FAQ -->
<section class="section faq-section">
    <div class="section-title">
        <span class="detail-label">FAQ</span>
        <h2>Frequently Asked Questions</h2>
        <p>Quick answers about this model, loan, test drive and booking.</p>
    </div>
    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">Will specifications change when I select a different variant? <span>+</span></div>
            <div class="faq-answer"><p>Yes. All tabs including Engine, Performance, Dimensions, Transmission, Wheels and the Features/Safety/Comfort section all update according to the selected variant.</p></div>
        </div>
        <div class="faq-item">
            <div class="faq-question">What does the grey circle ○ mean in the features list? <span>+</span></div>
            <div class="faq-answer"><p>The grey circle means that specific feature is not available in the currently selected variant. Only features available in that variant show a green checkmark ✓.</p></div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Can I compare different variants of the same car? <span>+</span></div>
            <div class="faq-answer"><p>Yes. The Compare Variants section shows all variants side by side with a full specification comparison so you can easily identify differences.</p></div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Can I calculate the loan for this car? <span>+</span></div>
            <div class="faq-answer"><p>Yes. Click Calculate Loan to estimate monthly payment based on the selected variant price, down payment, interest rate and loan period.</p></div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Can the company help with loan application? <span>+</span></div>
            <div class="faq-answer"><p>Yes. Submit your information through the loan assistance form and our team will help forward your application to partnered banks.</p></div>
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
    <div class="footer-bottom"><p>&copy; 2026 Toyota Car Selling System. All Rights Reserved.</p></div>
</footer>

<script>
const carName = <?php echo json_encode($car["name"]); ?>;
const isBooking = <?php echo json_encode($isBooking); ?>;
const variants = <?php echo json_encode($car["variants"]); ?>;
let currentVariantIndex = 0;

/* ========== NAV ========== */
function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}

/* ========== STICKY FLOAT ========== */
window.addEventListener('scroll', () => {
    const float = document.getElementById('stickyFloat');
    if(window.scrollY > 500) float.classList.add('visible');
    else float.classList.remove('visible');
});

/* ========== COLOUR SELECT ========== */
function selectColour(imageSrc, el) {
    document.getElementById("mainCarImage").src = imageSrc;
    document.querySelectorAll(".colour-option").forEach(o => o.classList.remove("active"));
    el.classList.add("active");
}

/* ========== VARIANT SELECT ========== */
function selectVariant(idx, el) {
    currentVariantIndex = idx;
    const v = variants[idx];
    document.querySelectorAll(".variant-option").forEach(o => o.classList.remove("active"));
    el.classList.add("active");

    // Hero
    document.getElementById("selectedVariantPrice").textContent = v.price;
    document.getElementById("selectedVariantMonthly").textContent = v.monthly;

    // Fuel badge
    const fb = document.getElementById("fuelBadge");
    fb.textContent = v.fuelType;
    fb.className = "fuel-badge " + v.fuelType.toLowerCase();

    // Sticky float
    document.getElementById("sfPrice").textContent = v.price;
    document.getElementById("sfLoanBtn").href = "loan_calculator.php?car=" + encodeURIComponent(carName) + "&price=" + v.priceNumber;

    // Loan section
    document.getElementById("loanVariant").textContent = v.name;
    document.getElementById("loanPrice").textContent = v.price;
    document.getElementById("loanMonthly").textContent = v.monthly;
    document.getElementById("loanCalcTop").href = "loan_calculator.php?car=" + encodeURIComponent(carName) + "&price=" + v.priceNumber;
    document.getElementById("loanCalcBottom").href = "loan_calculator.php?car=" + encodeURIComponent(carName) + "&price=" + v.priceNumber;

    // DIMENSIONS
    setText("dim-length", v.dimensions.length);
    setText("dim-width", v.dimensions.width);
    setText("dim-height", v.dimensions.height);
    setText("dim-wheelbase", v.dimensions.wheelbase);
    setText("dim-frontTread", v.dimensions.frontTread);
    setText("dim-rearTread", v.dimensions.rearTread);
    setText("dim-doors", v.dimensions.doors);
    setText("dim-kerbWeight", v.dimensions.kerbWeight);
    setText("dim-grossWeight", v.dimensions.grossWeight);
    setText("dim-seats", v.dimensions.seats);
    setText("dim-cargoVolume", v.dimensions.cargoVolume);
    setText("dim-fuelTank", v.dimensions.fuelTank);
    setText("dim-turningRadius", v.dimensions.turningRadius);

    // ENGINE
    setText("eng-name", v.engine.name);
    setText("eng-displacement", v.engine.displacement);
    setText("eng-horsepower", v.engine.horsepower);
    setText("eng-torque", v.engine.torque);
    setText("eng-compression", v.engine.compression);
    setText("eng-fuelSystem", v.engine.fuelSystem);
    setText("eng-aspiration", v.engine.aspiration);
    setText("eng-fuelType", v.fuelType);
    setText("eng-fuelTank2", v.dimensions.fuelTank);
    setText("eng-fuelConsumption", v.performance.fuelConsumption);
    setText("eng-drivetrain", v.performance.drivetrain);

    // PERFORMANCE
    setText("perf-topSpeed", v.performance.topSpeed);
    setText("perf-acceleration", v.performance.acceleration);
    setText("perf-fuelConsumption", v.performance.fuelConsumption);
    setText("perf-drivetrain", v.performance.drivetrain);
    setText("perf-transmission", v.performance.transmission);
    setText("perf-horsepower", v.engine.horsepower);
    setText("perf-torque", v.engine.torque);
    setText("perf-displacement", v.engine.displacement);
    setText("perf-aspiration", v.engine.aspiration);

    // STEERING
    setText("steer-type", v.steering.type);
    setText("steer-turns", v.steering.turnsLockToLock);
    setText("steer-circle", v.steering.turningCircle);
    setText("steer-radius", v.dimensions.turningRadius);

    // SUSPENSION / BRAKES
    setText("susp-front", v.suspension.front);
    setText("susp-rear", v.suspension.rear);
    setText("brake-front", v.brakes.front);
    setText("brake-rear", v.brakes.rear);
    setText("brake-abs", v.brakes.abs);

    // TRANSMISSION
    setText("trans-type", v.transmission.type);
    setText("trans-gears", v.transmission.gears);
    setText("trans-mode", v.transmission.mode);
    setText("trans-drivetrain", v.performance.drivetrain);

    // WHEELS
    setText("wheel-size", v.wheels.size);
    setText("wheel-tyres", v.wheels.tyres);
    setText("wheel-spare", v.wheels.spare);
    setText("wheel-frontTread", v.dimensions.frontTread);
    setText("wheel-rearTread", v.dimensions.rearTread);

    // OWNERSHIP
    setText("own-variant", v.name);
    setText("own-price", v.price);

    // FEATURES / SAFETY / COMFORT
    renderFeatures("featureList", v.features);
    renderSafetyComfort("safetyList", v.safety);
    renderSafetyComfort("comfortList", v.comfort);

    // COMPARE TABLE
    buildCompareTable(idx);
}

function setText(id, val) {
    const el = document.getElementById(id);
    if(el) el.textContent = val;
}

/* ========== RENDER FEATURE LISTS ========== */
function renderFeatures(id, items) {
    const el = document.getElementById(id);
    el.innerHTML = "";
    items.forEach(item => {
        const div = document.createElement("div");
        div.className = "feature-item has";
        div.innerHTML = `<div class="feature-check yes">✓</div><span>${item}</span>`;
        el.appendChild(div);
    });
}

function renderSafetyComfort(id, items) {
    const el = document.getElementById(id);
    el.innerHTML = "";
    Object.entries(items).forEach(([key, val]) => {
        const div = document.createElement("div");
        div.className = val ? "feature-item has" : "feature-item hasnt";
        div.innerHTML = `<div class="feature-check ${val?'yes':'no'}">${val?'✓':'○'}</div><span>${key}</span>`;
        el.appendChild(div);
    });
}

/* ========== TAB SWITCHING ========== */
function switchTab(tabId, btn) {
    document.querySelectorAll(".tab-content").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    document.getElementById("tab-" + tabId).classList.add("active");
    btn.classList.add("active");
}

function switchFeatTab(tabId, btn) {
    document.querySelectorAll(".feat-tab-content").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".feat-tab-btn").forEach(b => b.classList.remove("active"));
    document.getElementById("feat-" + tabId).classList.add("active");
    btn.classList.add("active");
}

/* ========== BUILD COMPARE TABLE ========== */
function buildCompareTable(activeIdx) {
    const table = document.getElementById("compareTable");
    const vList = variants;

    let html = "<thead><tr><th>Specification</th>";
    vList.forEach((v, i) => {
        html += `<th class="${i===activeIdx?'highlight':''}">${v.name}${i===activeIdx?' ★':''}</th>`;
    });
    html += "</tr></thead><tbody>";

    // PRICE
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Pricing</td></tr>`;
    html += buildRow("Price", vList.map(v => v.price), activeIdx);
    html += buildRow("Est. Monthly", vList.map(v => v.monthly), activeIdx);

    // ENGINE
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Engine</td></tr>`;
    html += buildRow("Engine", vList.map(v => v.engine.name), activeIdx);
    html += buildRow("Horsepower", vList.map(v => v.engine.horsepower), activeIdx);
    html += buildRow("Torque", vList.map(v => v.engine.torque), activeIdx);
    html += buildRow("Fuel Type", vList.map(v => v.fuelType), activeIdx);
    html += buildRow("Fuel Consumption", vList.map(v => v.performance.fuelConsumption), activeIdx);

    // PERFORMANCE
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Performance</td></tr>`;
    html += buildRow("Transmission", vList.map(v => v.performance.transmission), activeIdx);
    html += buildRow("Drivetrain", vList.map(v => v.performance.drivetrain), activeIdx);
    html += buildRow("0-100 km/h", vList.map(v => v.performance.acceleration), activeIdx);
    html += buildRow("Top Speed", vList.map(v => v.performance.topSpeed), activeIdx);

    // DIMENSIONS
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Dimensions & Weight</td></tr>`;
    html += buildRow("Kerb Weight", vList.map(v => v.dimensions.kerbWeight), activeIdx);
    html += buildRow("Wheelbase", vList.map(v => v.dimensions.wheelbase), activeIdx);
    html += buildRow("Fuel Tank", vList.map(v => v.dimensions.fuelTank), activeIdx);

    // WHEELS
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Wheels & Tyres</td></tr>`;
    html += buildRow("Wheel Size", vList.map(v => v.wheels.size), activeIdx);
    html += buildRow("Tyre Size", vList.map(v => v.wheels.tyres), activeIdx);

    // SAFETY BOOL ITEMS - pick all unique keys
    const safetyKeys = [...new Set(vList.flatMap(v => Object.keys(v.safety)))];
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Safety Features</td></tr>`;
    safetyKeys.forEach(key => {
        html += `<tr><td>${key}</td>`;
        vList.forEach((v, i) => {
            const has = v.safety[key];
            const cls = i===activeIdx ? 'style="background:rgba(215,25,32,0.05)"' : '';
            html += `<td ${cls}>${has===true?'<span class="check-y">✓</span>':(has===false?'<span class="check-n">—</span>':'-')}</td>`;
        });
        html += "</tr>";
    });

    // COMFORT BOOL ITEMS
    const comfortKeys = [...new Set(vList.flatMap(v => Object.keys(v.comfort)))];
    html += `<tr class="cat-row"><td colspan="${vList.length+1}">Comfort Features</td></tr>`;
    comfortKeys.forEach(key => {
        html += `<tr><td>${key}</td>`;
        vList.forEach((v, i) => {
            const has = v.comfort[key];
            const cls = i===activeIdx ? 'style="background:rgba(215,25,32,0.05)"' : '';
            html += `<td ${cls}>${has===true?'<span class="check-y">✓</span>':(has===false?'<span class="check-n">—</span>':'-')}</td>`;
        });
        html += "</tr>";
    });

    html += "</tbody>";
    table.innerHTML = html;
}

function buildRow(label, values, activeIdx) {
    let html = `<tr><td>${label}</td>`;
    values.forEach((val, i) => {
        const cls = i===activeIdx ? 'style="background:rgba(215,25,32,0.05);color:#fff;font-weight:600"' : '';
        html += `<td ${cls}>${val}</td>`;
    });
    html += "</tr>";
    return html;
}

/* ========== FAQ ========== */
document.querySelectorAll(".faq-item").forEach(item => {
    const q = item.querySelector(".faq-question");
    const icon = q.querySelector("span");
    q.addEventListener("click", () => {
        document.querySelectorAll(".faq-item").forEach(o => {
            if(o !== item) { o.classList.remove("active"); o.querySelector(".faq-question span").textContent = "+"; }
        });
        item.classList.toggle("active");
        icon.textContent = item.classList.contains("active") ? "−" : "+";
    });
});

/* ========== INIT ========== */
(function init() {
    const v = variants[0];
    renderFeatures("featureList", v.features);
    renderSafetyComfort("safetyList", v.safety);
    renderSafetyComfort("comfortList", v.comfort);
    buildCompareTable(0);
})();
</script>
</body>
</html>