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
                "engine" => "1.5L 4-Cylinder Petrol",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.2L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "15-inch Alloy Wheels",
                "features" => ["Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Multi-function Steering", "USB Charging Port"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Brake Assist", "Hill-start Assist", "7 Airbags", "Rear Parking Sensors"],
                "comfort" => ["Fabric Seats", "Manual Air Conditioning", "Spacious Legroom", "Cup Holders", "Foldable Rear Seats", "Quiet Cabin"]
            ],
            [
                "name" => "Vios 1.5G",
                "price" => "RM 101,900",
                "priceNumber" => 101900,
                "monthly" => "Est. RM 1,330 / month",
                "engine" => "1.5L 4-Cylinder Petrol",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.4L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "16-inch Alloy Wheels",
                "features" => ["Larger Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Digital Video Recorder", "Auto Folding Mirror"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags", "Parking Sensors"],
                "comfort" => ["Leather Combination Seats", "Auto Air Conditioning", "Rear Armrest", "USB Ports", "Better Cabin Trim", "Multi-function Steering"]
            ],
            [
                "name" => "Vios 1.5 GR-S",
                "price" => "RM 109,000",
                "priceNumber" => 109000,
                "monthly" => "Est. RM 1,420 / month",
                "engine" => "1.5L 4-Cylinder Petrol",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic with Sport Mode",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Sport-tuned Suspension",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch GR Alloy Wheels",
                "features" => ["GR Body Kit", "Sport Mode", "Sport Seats", "Touchscreen Display", "LED Headlamps", "Sport Pedals"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags", "Parking Sensors"],
                "comfort" => ["Sport Seats", "Auto Air Conditioning", "Premium Interior Trim", "USB Charging", "Sport Steering", "Quiet Cabin"]
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
        "description" => "The Toyota Yaris is a compact hatchback made for modern city users. It offers stylish design, easy parking, practical boot space and daily comfort.",
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
                "engine" => "1.5L Petrol",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.2L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "15-inch Alloy Wheels",
                "features" => ["Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Foldable Rear Seats", "USB Port"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Brake Assist", "Hill-start Assist", "Airbags", "Parking Sensors"],
                "comfort" => ["Fabric Seats", "Air Conditioning", "Compact Cabin", "Easy Parking", "Cup Holders", "Multi-function Steering"]
            ],
            [
                "name" => "Yaris 1.5G",
                "price" => "RM 92,000",
                "priceNumber" => 92000,
                "monthly" => "Est. RM 1,200 / month",
                "engine" => "1.5L Petrol",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.4L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "16-inch Alloy Wheels",
                "features" => ["Larger Touchscreen Display", "Reverse Camera", "Smart Entry", "Auto Folding Mirror", "LED Headlamps", "DVR"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Airbags", "Parking Sensors"],
                "comfort" => ["Better Seat Trim", "Auto Air Conditioning", "Rear Seat Flexibility", "USB Charging", "Quiet Cabin", "Multi-function Steering"]
            ],
            [
                "name" => "Yaris 1.5 GR-S",
                "price" => "RM 99,000",
                "priceNumber" => 99000,
                "monthly" => "Est. RM 1,290 / month",
                "engine" => "1.5L Petrol",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic with Sport Mode",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Sport-tuned Suspension",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch GR Alloy Wheels",
                "features" => ["GR Body Kit", "Sport Mode", "Sport Seats", "Touchscreen Display", "LED Headlamps", "Sport Pedals"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Airbags", "Parking Sensors"],
                "comfort" => ["Sport Seats", "Auto Air Conditioning", "Sport Steering", "USB Charging", "Compact Cabin", "Premium Trim"]
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
                "engine" => "1.8L Petrol Engine",
                "horsepower" => "139 PS",
                "torque" => "172 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "47 Litres",
                "fuelConsumption" => "Approx. 6.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch Alloy Wheels",
                "features" => ["Touchscreen Display", "Smart Entry", "Reverse Camera", "LED Headlamps", "Electric Parking Brake", "Drive Mode Select"],
                "safety" => ["Toyota Safety Sense", "Pre-Collision System", "Lane Departure Alert", "ABS with EBD", "7 Airbags", "Parking Sensors"],
                "comfort" => ["Fabric Seats", "Automatic Air Conditioning", "Spacious Cabin", "Rear Air Vents", "USB Ports", "Large Boot Space"]
            ],
            [
                "name" => "Corolla Cross 1.8V",
                "price" => "RM 138,400",
                "priceNumber" => 138400,
                "monthly" => "Est. RM 1,800 / month",
                "engine" => "1.8L Petrol Engine",
                "horsepower" => "139 PS",
                "torque" => "172 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "47 Litres",
                "fuelConsumption" => "Approx. 6.6L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "features" => ["Larger Touchscreen Display", "Smart Entry", "360-degree Camera", "LED Headlamps", "Power Back Door", "Electric Parking Brake"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Pre-Collision System", "Lane Departure Alert", "7 Airbags"],
                "comfort" => ["Leather Seats", "Dual-zone Air Conditioning", "Spacious Cabin", "Rear Air Vents", "USB Ports", "Quiet Cabin"]
            ],
            [
                "name" => "Corolla Cross Hybrid",
                "price" => "RM 145,000",
                "priceNumber" => 145000,
                "monthly" => "Est. RM 1,880 / month",
                "engine" => "1.8L Hybrid Petrol Engine",
                "horsepower" => "122 PS Combined Output",
                "torque" => "142 Nm + Electric Motor Assist",
                "transmission" => "E-CVT Automatic",
                "fuelTank" => "36 Litres",
                "fuelConsumption" => "Approx. 4.3L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "features" => ["Hybrid System", "EV Mode", "360-degree Camera", "Power Back Door", "Smart Entry", "Electric Parking Brake"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Pre-Collision System", "Lane Departure Alert", "7 Airbags"],
                "comfort" => ["Leather Seats", "Quiet Hybrid Driving", "Dual-zone Air Conditioning", "Rear Air Vents", "Spacious Cabin", "Large Boot Space"]
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
                "engine" => "2.4L Turbo Diesel",
                "horsepower" => "150 PS",
                "torque" => "400 Nm",
                "transmission" => "6-Speed Automatic",
                "fuelTank" => "80 Litres",
                "fuelConsumption" => "Approx. 7.5L / 100km",
                "drivetrain" => "Rear-Wheel Drive",
                "suspension" => "Front Double Wishbone, Rear Leaf Spring",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "17-inch Alloy Wheels",
                "features" => ["Diesel Engine", "Cargo Bed", "Touchscreen Display", "Reverse Camera", "Strong Body", "High Ground Clearance"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Trailer Sway Control", "Airbags", "Rear Parking Sensors"],
                "comfort" => ["Durable Seats", "Air Conditioning", "High Driving Position", "USB Port", "Practical Cabin", "Large Cargo Space"]
            ],
            [
                "name" => "Hilux 2.4V 4x4",
                "price" => "RM 145,000",
                "priceNumber" => 145000,
                "monthly" => "Est. RM 1,880 / month",
                "engine" => "2.4L Turbo Diesel",
                "horsepower" => "150 PS",
                "torque" => "400 Nm",
                "transmission" => "6-Speed Automatic",
                "fuelTank" => "80 Litres",
                "fuelConsumption" => "Approx. 8.0L / 100km",
                "drivetrain" => "4x4",
                "suspension" => "Front Double Wishbone, Rear Leaf Spring",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "18-inch Alloy Wheels",
                "features" => ["4x4 Capability", "Touchscreen Display", "Reverse Camera", "LED Headlamps", "Cargo Bed", "Smart Entry"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Downhill Assist Control", "Trailer Sway Control", "Airbags"],
                "comfort" => ["Better Seat Trim", "Auto Air Conditioning", "High Driving Position", "USB Ports", "Durable Cabin", "Spacious Interior"]
            ],
            [
                "name" => "Hilux Rogue",
                "price" => "RM 160,000",
                "priceNumber" => 160000,
                "monthly" => "Est. RM 2,080 / month",
                "engine" => "2.8L Turbo Diesel",
                "horsepower" => "204 PS",
                "torque" => "500 Nm",
                "transmission" => "6-Speed Automatic",
                "fuelTank" => "80 Litres",
                "fuelConsumption" => "Approx. 8.5L / 100km",
                "drivetrain" => "4x4",
                "suspension" => "Front Double Wishbone, Rear Leaf Spring",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "18-inch Rogue Alloy Wheels",
                "features" => ["Rogue Body Kit", "4x4 Capability", "Powerful Diesel Engine", "360-degree Camera", "Smart Entry", "Premium Display"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Hill Start Assist", "Trailer Sway Control", "Airbags"],
                "comfort" => ["Leather Seats", "Auto Air Conditioning", "Premium Cabin", "USB Charging", "High Driving Position", "Large Cargo Bed"]
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
                "engine" => "2.5L Dynamic Force Petrol",
                "horsepower" => "209 PS",
                "torque" => "253 Nm",
                "transmission" => "8-Speed Automatic",
                "fuelTank" => "60 Litres",
                "fuelConsumption" => "Approx. 6.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "features" => ["Premium Interior", "Smart Entry", "Touchscreen Display", "Reverse Camera", "Power Seats", "LED Headlamps"],
                "safety" => ["Toyota Safety Sense", "Pre-Collision System", "Lane Tracing Assist", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags"],
                "comfort" => ["Power Seats", "Dual-zone Air Conditioning", "Spacious Cabin", "Quiet Cabin", "USB Charging", "Rear Armrest"]
            ],
            [
                "name" => "Camry 2.5 Premium",
                "price" => "RM 235,000",
                "priceNumber" => 235000,
                "monthly" => "Est. RM 3,050 / month",
                "engine" => "2.5L Dynamic Force Petrol",
                "horsepower" => "209 PS",
                "torque" => "253 Nm",
                "transmission" => "8-Speed Automatic",
                "fuelTank" => "60 Litres",
                "fuelConsumption" => "Approx. 6.9L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Premium Alloy Wheels",
                "features" => ["Premium Audio", "Larger Display", "360-degree Camera", "Power Seats", "Smart Entry", "LED Headlamps"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Lane Tracing Assist", "Parking Support Brake", "7 Airbags"],
                "comfort" => ["Leather Seats", "Dual-zone Air Conditioning", "Quiet Cabin", "Rear Sunshade", "USB Charging", "Executive Rear Space"]
            ],
            [
                "name" => "Camry Hybrid",
                "price" => "RM 240,000",
                "priceNumber" => 240000,
                "monthly" => "Est. RM 3,120 / month",
                "engine" => "2.5L Hybrid Engine",
                "horsepower" => "218 PS Combined Output",
                "torque" => "221 Nm + Electric Motor Assist",
                "transmission" => "E-CVT Automatic",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 4.5L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Hybrid Alloy Wheels",
                "features" => ["Hybrid System", "EV Mode", "Premium Audio", "360-degree Camera", "Smart Entry", "Power Seats"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Pre-Collision System", "Lane Tracing Assist", "7 Airbags"],
                "comfort" => ["Leather Seats", "Quiet Hybrid Driving", "Dual-zone Air Conditioning", "Rear Sunshade", "Executive Comfort", "USB Charging"]
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
                "engine" => "2.0L Petrol Engine",
                "horsepower" => "174 PS",
                "torque" => "205 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "52 Litres",
                "fuelConsumption" => "Approx. 7.0L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch Alloy Wheels",
                "features" => ["7 Seats", "Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Flexible Seats"],
                "safety" => ["ABS", "Vehicle Stability Control", "Airbags", "Hill Start Assist", "Parking Sensors", "Reverse Camera"],
                "comfort" => ["Fabric Seats", "Rear Air Conditioning", "Spacious Legroom", "USB Charging", "Family Cabin", "Flexible Seating"]
            ],
            [
                "name" => "Innova Zenix 2.0X",
                "price" => "RM 172,000",
                "priceNumber" => 172000,
                "monthly" => "Est. RM 2,220 / month",
                "engine" => "2.0L Petrol Engine",
                "horsepower" => "174 PS",
                "torque" => "205 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "52 Litres",
                "fuelConsumption" => "Approx. 7.1L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "features" => ["Captain Seats", "Larger Display", "Reverse Camera", "Smart Entry", "Power Back Door", "LED Headlamps"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Airbags", "Parking Sensors", "Vehicle Stability Control"],
                "comfort" => ["Captain Seats", "Rear Air Conditioning", "USB Charging", "Spacious Legroom", "Premium Cabin", "Flexible Seating"]
            ],
            [
                "name" => "Innova Zenix Hybrid",
                "price" => "RM 175,000",
                "priceNumber" => 175000,
                "monthly" => "Est. RM 2,280 / month",
                "engine" => "2.0L Hybrid Engine",
                "horsepower" => "186 PS Combined Output",
                "torque" => "188 Nm + Electric Motor Assist",
                "transmission" => "E-CVT Automatic",
                "fuelTank" => "52 Litres",
                "fuelConsumption" => "Approx. 5.4L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Hybrid Alloy Wheels",
                "features" => ["Hybrid System", "Captain Seats", "Power Back Door", "Smart Entry", "Larger Display", "LED Headlamps"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Pre-Collision System", "Airbags", "Parking Sensors"],
                "comfort" => ["Captain Seats", "Quiet Hybrid Driving", "Rear Air Conditioning", "USB Charging", "Spacious Cabin", "Family Comfort"]
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
                "engine" => "2.5L Petrol Engine",
                "horsepower" => "182 PS",
                "torque" => "235 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "75 Litres",
                "fuelConsumption" => "Approx. 9.0L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "features" => ["Power Sliding Door", "Smart Entry", "Touchscreen Display", "Reverse Camera", "LED Headlamps", "Luxury Cabin"],
                "safety" => ["Toyota Safety Sense", "Pre-Collision System", "Lane Departure Alert", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags"],
                "comfort" => ["Captain Seats", "Rear Air Conditioning", "USB Charging", "Spacious Legroom", "Quiet Cabin", "Power Sliding Door"]
            ],
            [
                "name" => "Alphard 2.5G",
                "price" => "RM 560,000",
                "priceNumber" => 560000,
                "monthly" => "Est. RM 7,180 / month",
                "engine" => "2.5L Petrol Engine",
                "horsepower" => "182 PS",
                "torque" => "235 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "75 Litres",
                "fuelConsumption" => "Approx. 9.2L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Premium Alloy Wheels",
                "features" => ["Premium Captain Seats", "Power Sliding Door", "Large Touchscreen", "360-degree Camera", "Power Back Door", "Premium Lighting"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Parking Support Brake", "Lane Tracing Assist", "7 Airbags"],
                "comfort" => ["Premium Captain Seats", "Power Ottoman Seats", "Rear Air Conditioning", "Quiet Luxury Cabin", "USB Charging", "Spacious Legroom"]
            ],
            [
                "name" => "Alphard Executive Lounge",
                "price" => "RM 610,000",
                "priceNumber" => 610000,
                "monthly" => "Est. RM 7,850 / month",
                "engine" => "2.5L Petrol Engine",
                "horsepower" => "182 PS",
                "torque" => "235 Nm",
                "transmission" => "CVT Automatic",
                "fuelTank" => "75 Litres",
                "fuelConsumption" => "Approx. 9.5L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "19-inch Executive Alloy Wheels",
                "features" => ["Executive Lounge Seats", "Rear Entertainment Display", "Premium Audio", "360-degree Camera", "Power Sliding Door", "Power Back Door"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Parking Support Brake", "Lane Tracing Assist", "7 Airbags"],
                "comfort" => ["Executive Lounge Seats", "Power Ottoman", "Premium Leather", "Rear Entertainment", "Quiet Luxury Cabin", "Ambient Lighting"]
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
                "engine" => "1.6L Turbocharged Petrol",
                "horsepower" => "304 PS",
                "torque" => "370 Nm",
                "transmission" => "6-Speed Manual",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 8.4L / 100km",
                "drivetrain" => "GR-Four All-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Performance Ventilated Disc Brakes",
                "tyres" => "18-inch GR Alloy Wheels",
                "features" => ["Turbo Engine", "Manual Transmission", "GR-Four AWD", "Sport Seats", "GR Body Kit", "Performance Display"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Reverse Camera", "Performance Brakes", "Airbags"],
                "comfort" => ["Sport Seats", "Air Conditioning", "USB Charging", "Driver-focused Cabin", "Sport Steering", "Performance Pedals"]
            ],
            [
                "name" => "GR Corolla Circuit Edition",
                "price" => "RM 380,000",
                "priceNumber" => 380000,
                "monthly" => "Est. RM 4,900 / month",
                "engine" => "1.6L Turbocharged Petrol",
                "horsepower" => "304 PS",
                "torque" => "370 Nm",
                "transmission" => "6-Speed Manual",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 8.6L / 100km",
                "drivetrain" => "GR-Four All-Wheel Drive",
                "suspension" => "Sport-tuned Suspension",
                "brakes" => "Performance Ventilated Disc Brakes",
                "tyres" => "18-inch Forged Alloy Wheels",
                "features" => ["Circuit Aero Kit", "GR-Four AWD", "Sport Seats", "Performance Display", "Manual Transmission", "Sport Exhaust"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Reverse Camera", "Parking Sensors", "Performance Brakes"],
                "comfort" => ["Sport Seats", "Premium Trim", "Air Conditioning", "USB Charging", "Driver-focused Cabin", "Sport Steering"]
            ],
            [
                "name" => "GR Corolla Morizo Edition",
                "price" => "RM 420,000",
                "priceNumber" => 420000,
                "monthly" => "Est. RM 5,380 / month",
                "engine" => "1.6L Turbocharged Petrol",
                "horsepower" => "304 PS",
                "torque" => "400 Nm",
                "transmission" => "6-Speed Manual",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 8.8L / 100km",
                "drivetrain" => "GR-Four All-Wheel Drive",
                "suspension" => "Track-tuned Suspension",
                "brakes" => "High-performance Ventilated Disc Brakes",
                "tyres" => "18-inch Lightweight Forged Wheels",
                "features" => ["Track-focused Setup", "GR-Four AWD", "Lightweight Body", "Sport Exhaust", "Performance Display", "Manual Transmission"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Performance Brakes", "Reverse Camera", "Airbags"],
                "comfort" => ["Sport Bucket Seats", "Driver-focused Cabin", "Sport Steering", "Performance Pedals", "USB Charging", "Track Interior"]
            ]
        ]
    ]
];

$carId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

if (!isset($cars[$carId])) {
    $carId = 1;
}

$car = $cars[$carId];
$isBooking = $car["status"] === "Booking Required";
$firstVariant = $car["variants"][0];

$similarCars = array_filter($cars, function ($item) use ($carId) {
    return $item["id"] !== $carId;
});

$similarCars = array_slice($similarCars, 0, 3, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car["name"]); ?> - Car Details</title>

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
            padding-bottom: 30px;
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

        .breadcrumb-section {
            padding: 28px 6%;
            background: #fbfbfb;
            border-bottom: 1px solid #eeeeee;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #666;
            font-weight: 800;
        }

        .breadcrumb a {
            color: #d71920;
        }

        .breadcrumb span {
            color: #111;
        }

        .status-top-section {
            padding: 30px 6% 0;
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.08), transparent 34%),
                linear-gradient(180deg, #ffffff, #fafafa);
        }

        .status-top-box {
            background: #ffffff;
            border: 1px solid rgba(215, 25, 32, 0.16);
            border-left: 7px solid #d71920;
            border-radius: 24px;
            padding: 22px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            box-shadow: 0 16px 38px rgba(0, 0, 0, 0.07);
        }

        .status-top-box h2 {
            color: #111;
            font-size: 23px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .status-top-box p {
            color: #666;
            line-height: 1.7;
            font-size: 14.5px;
            max-width: 900px;
        }

        .status-top-pill {
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            padding: 13px 20px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 900;
            white-space: nowrap;
            box-shadow: 0 12px 24px rgba(215, 25, 32, 0.22);
        }

        .details-hero {
            padding: 42px 6% 76px;
            background:
                radial-gradient(circle at top left, rgba(215, 25, 32, 0.08), transparent 34%),
                linear-gradient(180deg, #fafafa, #ffffff);
        }

        .details-layout {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 42px;
            align-items: start;
        }

        .left-product-box {
            background: #ffffff;
            border-radius: 34px;
            border: 1px solid rgba(215, 25, 32, 0.13);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .main-image-wrap {
            position: relative;
            height: 460px;
            overflow: hidden;
            background: #111;
        }

        .main-image-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent 60%);
            pointer-events: none;
        }

        .main-car-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.45s;
        }

        .main-image-wrap:hover .main-car-image {
            transform: scale(1.05);
        }

        .image-status-badge {
            position: absolute;
            top: 22px;
            left: 22px;
            z-index: 3;
            padding: 10px 16px;
            border-radius: 20px;
            background: <?php echo $isBooking ? "rgba(17,17,17,0.9)" : "rgba(215,25,32,0.92)"; ?>;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
        }

        .car-summary-under-image {
            padding: 30px;
            background: #fff;
        }

        .detail-label {
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

        .car-summary-under-image h1 {
            font-size: 42px;
            color: #111;
            font-weight: 900;
            line-height: 1.12;
            margin-bottom: 11px;
        }

        .car-price {
            font-size: 28px;
            color: #d71920;
            font-weight: 900;
            margin-bottom: 7px;
        }

        .monthly {
            color: #666;
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .status-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .status-pill {
            padding: 9px 14px;
            border-radius: 20px;
            background: #111;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
        }

        .status-pill.red {
            background: #d71920;
        }

        .short-desc {
            color: #666;
            line-height: 1.75;
            font-size: 15.5px;
        }

        .option-panel {
            background: #ffffff;
            border-radius: 34px;
            padding: 34px;
            border: 1px solid rgba(215, 25, 32, 0.13);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 110px;
        }

        .option-panel h2 {
            font-size: 28px;
            font-weight: 900;
            color: #111;
            margin-bottom: 8px;
        }

        .option-panel > p {
            color: #666;
            line-height: 1.65;
            font-size: 14.5px;
            margin-bottom: 24px;
        }

        .option-box {
            margin-bottom: 28px;
        }

        .option-box h3 {
            font-size: 18px;
            color: #111;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .variant-select-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .variant-option {
            border: 1.5px solid #eeeeee;
            border-radius: 20px;
            padding: 16px 18px;
            cursor: pointer;
            transition: 0.3s;
            background: #f8f8f8;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
        }

        .variant-option.active,
        .variant-option:hover {
            background: #fff;
            border-color: #d71920;
            box-shadow: 0 12px 26px rgba(215, 25, 32, 0.1);
            transform: translateY(-2px);
        }

        .variant-option strong {
            display: block;
            color: #111;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .variant-option span {
            color: #d71920;
            font-weight: 900;
            font-size: 14px;
        }

        .variant-mini-spec {
            text-align: right;
            color: #666;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.4;
        }

        .colour-select-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .colour-option {
            display: flex;
            align-items: center;
            gap: 11px;
            border: 1.5px solid #eeeeee;
            background: #f8f8f8;
            border-radius: 18px;
            padding: 13px;
            cursor: pointer;
            transition: 0.3s;
        }

        .colour-option.active,
        .colour-option:hover {
            background: #fff;
            border-color: #d71920;
            box-shadow: 0 12px 24px rgba(215, 25, 32, 0.1);
            transform: translateY(-2px);
        }

        .colour-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 1px #dddddd, 0 8px 18px rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .colour-option span {
            font-size: 13px;
            color: #333;
            font-weight: 900;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .action-main,
        .action-outline,
        .action-red-full {
            min-height: 50px;
            border-radius: 22px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: 900;
            text-align: center;
            transition: 0.3s;
            cursor: pointer;
        }

        .action-main,
        .action-red-full {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            border: 1.5px solid #d71920;
            box-shadow: 0 12px 24px rgba(215, 25, 32, 0.2);
        }

        .action-main:hover,
        .action-red-full:hover {
            background: #111;
            border-color: #111;
            transform: translateY(-2px);
        }

        .action-outline {
            background: #fff;
            color: #d71920;
            border: 1.5px solid #d71920;
        }

        .action-outline:hover {
            background: #d71920;
            color: #fff;
            transform: translateY(-2px);
        }

        .section {
            padding: 76px 6%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 42px;
        }

        .section-title .detail-label {
            margin-bottom: 14px;
        }

        .section-title h2 {
            font-size: 40px;
            color: #111;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: -0.8px;
        }

        .section-title p {
            max-width: 760px;
            margin: 0 auto;
            color: #666;
            line-height: 1.7;
        }

        .spec-section {
            background: #ffffff;
        }

        .details-focus-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .details-focus-card {
            background: #fff;
            border-radius: 28px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }

        .details-focus-card h3 {
            background: #111;
            color: #fff;
            padding: 20px 24px;
            font-size: 20px;
            font-weight: 900;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table tr {
            border-bottom: 1px solid #eeeeee;
        }

        .details-table tr:last-child {
            border-bottom: none;
        }

        .details-table td {
            padding: 18px 24px;
            font-size: 14.5px;
            vertical-align: top;
            line-height: 1.55;
        }

        .details-table td:first-child {
            width: 38%;
            background: #fbfbfb;
            color: #111;
            font-weight: 900;
        }

        .details-table td:last-child {
            color: #555;
            font-weight: 700;
        }

        .features-section {
            background:
                radial-gradient(circle at top right, rgba(215, 25, 32, 0.08), transparent 32%),
                #f7f7f7;
        }

        .feature-lists {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .feature-list-card {
            background: #fff;
            border-radius: 30px;
            padding: 30px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.07);
        }

        .feature-list-card h3 {
            color: #111;
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 18px;
        }

        .feature-list-card ul {
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .feature-list-card li {
            background: #f7f7f7;
            padding: 13px 15px;
            border-radius: 18px;
            color: #333;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.5;
        }

        .feature-list-card li::before {
            content: "✓";
            width: 24px;
            height: 24px;
            background: #d71920;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .description-section {
            background: #ffffff;
        }

        .description-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .desc-card {
            background: #fff;
            border: 1px solid rgba(215, 25, 32, 0.12);
            border-radius: 28px;
            padding: 26px;
            box-shadow: 0 16px 38px rgba(0, 0, 0, 0.07);
        }

        .desc-card h3 {
            font-size: 20px;
            color: #111;
            margin-bottom: 12px;
            font-weight: 900;
        }

        .desc-card p {
            color: #666;
            line-height: 1.7;
            font-size: 14.5px;
        }

        .loan-section {
            background: #111;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .loan-section::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(215, 25, 32, 0.18);
            right: -160px;
            top: -150px;
        }

        .loan-box {
            position: relative;
            z-index: 2;
            background:
                linear-gradient(135deg, rgba(215, 25, 32, 0.94), rgba(111, 8, 12, 0.94)),
                url("https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1600&q=80");
            background-size: cover;
            background-position: center;
            border-radius: 34px;
            padding: 48px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 34px;
            align-items: center;
            box-shadow: 0 24px 55px rgba(215, 25, 32, 0.24);
        }

        .loan-box h2 {
            font-size: 38px;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .loan-box p {
            color: #f2f2f2;
            line-height: 1.75;
            margin-bottom: 24px;
        }

        .loan-summary {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 28px;
            padding: 26px;
            backdrop-filter: blur(12px);
        }

        .loan-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.16);
            color: #fff;
            font-weight: 800;
        }

        .loan-row:last-child {
            border-bottom: none;
        }

        .loan-row strong {
            color: #fff;
            text-align: right;
        }

        .loan-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .white-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 15px 36px;
            background: #fff;
            color: #d71920;
            border-radius: 32px;
            font-weight: 900;
            transition: 0.3s;
            border: 2px solid #fff;
            box-shadow: 0 12px 25px rgba(255, 255, 255, 0.18);
        }

        .white-btn:hover {
            background: transparent;
            color: #fff;
            transform: translateY(-3px);
        }

        .similar-section {
            background:
                linear-gradient(135deg, #f7f7f7, #ffffff),
                radial-gradient(circle at bottom right, rgba(215, 25, 32, 0.08), transparent 28%);
        }

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .similar-card {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(215, 25, 32, 0.1);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
            transition: 0.3s;
            position: relative;
        }

        .similar-card:hover {
            transform: translateY(-8px);
            border-color: rgba(215, 25, 32, 0.32);
            box-shadow: 0 24px 52px rgba(0, 0, 0, 0.12);
        }

        .similar-img-wrap {
            height: 190px;
            overflow: hidden;
            position: relative;
        }

        .similar-img-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.55), transparent 60%);
        }

        .similar-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .similar-status {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #d71920;
            color: #fff;
            z-index: 2;
            padding: 8px 13px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: 900;
        }

        .similar-info {
            padding: 22px;
            padding-bottom: 84px;
        }

        .similar-info h3 {
            font-size: 21px;
            color: #111;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .similar-info p {
            color: #d71920;
            font-size: 17px;
            font-weight: 900;
        }

        .similar-actions {
            position: absolute;
            right: 18px;
            bottom: 18px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .small-red,
        .small-outline {
            min-height: 36px;
            border-radius: 18px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: 900;
            transition: 0.3s;
            padding: 0 13px;
        }

        .small-red {
            background: #d71920;
            color: #fff;
            border: 1.5px solid #d71920;
        }

        .small-red:hover {
            background: #111;
            border-color: #111;
        }

        .small-outline {
            background: #fff;
            color: #d71920;
            border: 1.5px solid #d71920;
        }

        .small-outline:hover {
            background: #d71920;
            color: #fff;
        }

        .faq-section {
            background: #ffffff;
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
            flex-shrink: 0;
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

            .description-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .details-layout,
            .loan-box,
            .status-top-box {
                grid-template-columns: 1fr;
            }

            .status-top-box {
                flex-direction: column;
                align-items: flex-start;
            }

            .option-panel {
                position: relative;
                top: auto;
            }
        }

        @media (max-width: 992px) {
            .details-focus-grid,
            .feature-lists,
            .similar-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar {
                min-height: 82px;
            }
        }

        @media (max-width: 768px) {
            .details-hero,
            .section,
            .breadcrumb-section,
            .status-top-section {
                padding-left: 5%;
                padding-right: 5%;
            }

            .main-image-wrap {
                height: 340px;
            }

            .description-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .car-summary-under-image {
                padding: 25px;
            }

            .car-summary-under-image h1 {
                font-size: 34px;
            }

            .option-panel {
                padding: 26px;
            }

            .section-title h2 {
                font-size: 32px;
            }

            .quick-actions,
            .colour-select-grid {
                grid-template-columns: 1fr;
            }

            .loan-box {
                padding: 30px 24px;
                border-radius: 28px;
            }

            .loan-box h2 {
                font-size: 30px;
            }

            .details-table td {
                display: block;
                width: 100%;
            }

            .details-table td:first-child {
                width: 100%;
                padding-bottom: 8px;
            }

            .details-table td:last-child {
                padding-top: 8px;
            }

            .similar-actions {
                position: static;
                padding: 0 22px 22px;
                justify-content: flex-start;
            }

            .similar-info {
                padding-bottom: 18px;
            }
        }

        @media (max-width: 480px) {
            .username,
            .logo-text small {
                display: none;
            }

            .login-btn,
            .logout-btn {
                padding: 9px 15px;
                font-size: 13px;
            }

            .main-image-wrap {
                height: 280px;
            }

            .variant-option {
                grid-template-columns: 1fr;
            }

            .variant-mini-spec {
                text-align: left;
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

<section class="breadcrumb-section">
    <div class="breadcrumb">
        <a href="homepage.php">Home</a>
        <span>›</span>
        <a href="catalogue.php">Catalogue</a>
        <span>›</span>
        <span><?php echo htmlspecialchars($car["name"]); ?></span>
    </div>
</section>

<section class="status-top-section">
    <div class="status-top-box">
        <div>
            <h2>Status: <?php echo $car["status"]; ?></h2>
            <?php if ($isBooking): ?>
                <p>This model requires advance booking. Estimated waiting time: <?php echo $car["waiting"]; ?>. Booking fee: <?php echo $car["bookingFee"]; ?>. Our team will contact you after your booking request is submitted.</p>
            <?php else: ?>
                <p>This model is currently available for viewing, loan application and test drive booking. You may book a test drive or submit loan assistance information online.</p>
            <?php endif; ?>
        </div>

        <div class="status-top-pill">
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
            </div>

            <div class="car-summary-under-image">
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
            <h2>Customize Your Car</h2>
            <p>Select a variant and colour. The image, price, specifications, features, safety and comfort information will update automatically.</p>

            <div class="option-box">
                <h3>Choose Variant</h3>

                <div class="variant-select-grid">
                    <?php foreach ($car["variants"] as $index => $variant): ?>
                        <div class="variant-option <?php echo $index === 0 ? 'active' : ''; ?>" onclick="selectVariant(<?php echo $index; ?>, this)">
                            <div>
                                <strong><?php echo $variant["name"]; ?></strong>
                                <span><?php echo $variant["price"]; ?></span>
                            </div>
                            <div class="variant-mini-spec">
                                <?php echo $variant["engine"]; ?><br>
                                <?php echo $variant["horsepower"]; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="option-box">
                <h3>Choose Colour</h3>

                <div class="colour-select-grid">
                    <?php foreach ($car["colours"] as $index => $colour): ?>
                        <div class="colour-option <?php echo $index === 0 ? 'active' : ''; ?>" onclick="selectColour('<?php echo $colour["image"]; ?>', this)">
                            <div class="colour-circle" style="background: <?php echo $colour["code"]; ?>;"></div>
                            <span><?php echo $colour["name"]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="quick-actions">
                <?php if ($isBooking): ?>
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

<section class="section spec-section">
    <div class="section-title">
        <span class="detail-label">VEHICLE DETAILS</span>
        <h2>Vehicle Specifications</h2>
        <p>Detailed specifications will update based on the selected variant.</p>
    </div>

    <div class="details-focus-grid">
        <div class="details-focus-card">
            <h3>Performance</h3>
            <table class="details-table">
                <tr>
                    <td>Variant</td>
                    <td id="specVariant"><?php echo $firstVariant["name"]; ?></td>
                </tr>
                <tr>
                    <td>Engine</td>
                    <td id="specEngine"><?php echo $firstVariant["engine"]; ?></td>
                </tr>
                <tr>
                    <td>Horsepower</td>
                    <td id="specHorsepower"><?php echo $firstVariant["horsepower"]; ?></td>
                </tr>
                <tr>
                    <td>Torque</td>
                    <td id="specTorque"><?php echo $firstVariant["torque"]; ?></td>
                </tr>
                <tr>
                    <td>Transmission</td>
                    <td id="specTransmission"><?php echo $firstVariant["transmission"]; ?></td>
                </tr>
            </table>
        </div>

        <div class="details-focus-card">
            <h3>Fuel and Body</h3>
            <table class="details-table">
                <tr>
                    <td>Fuel Type</td>
                    <td><?php echo $car["fuel"]; ?></td>
                </tr>
                <tr>
                    <td>Fuel Tank Capacity</td>
                    <td id="specFuelTank"><?php echo $firstVariant["fuelTank"]; ?></td>
                </tr>
                <tr>
                    <td>Fuel Consumption</td>
                    <td id="specFuelConsumption"><?php echo $firstVariant["fuelConsumption"]; ?></td>
                </tr>
                <tr>
                    <td>Body Type</td>
                    <td><?php echo $car["body"]; ?></td>
                </tr>
                <tr>
                    <td>Seating Capacity</td>
                    <td><?php echo $car["seats"]; ?></td>
                </tr>
            </table>
        </div>

        <div class="details-focus-card">
            <h3>Chassis</h3>
            <table class="details-table">
                <tr>
                    <td>Drivetrain</td>
                    <td id="specDrivetrain"><?php echo $firstVariant["drivetrain"]; ?></td>
                </tr>
                <tr>
                    <td>Suspension</td>
                    <td id="specSuspension"><?php echo $firstVariant["suspension"]; ?></td>
                </tr>
                <tr>
                    <td>Brakes</td>
                    <td id="specBrakes"><?php echo $firstVariant["brakes"]; ?></td>
                </tr>
                <tr>
                    <td>Tyres / Wheels</td>
                    <td id="specTyres"><?php echo $firstVariant["tyres"]; ?></td>
                </tr>
                <tr>
                    <td>Warranty</td>
                    <td>5 Years Warranty</td>
                </tr>
            </table>
        </div>

        <div class="details-focus-card">
            <h3>Booking and Ownership</h3>
            <table class="details-table">
                <tr>
                    <td>Vehicle Status</td>
                    <td><?php echo $car["status"]; ?></td>
                </tr>
                <tr>
                    <td>Stock Status</td>
                    <td><?php echo $car["stock"]; ?></td>
                </tr>
                <tr>
                    <td>Estimated Waiting Time</td>
                    <td><?php echo $car["waiting"]; ?></td>
                </tr>
                <tr>
                    <td>Booking Fee</td>
                    <td><?php echo $car["bookingFee"]; ?></td>
                </tr>
                <tr>
                    <td>Selected Price</td>
                    <td id="specPrice"><?php echo $firstVariant["price"]; ?></td>
                </tr>
            </table>
        </div>
    </div>
</section>

<section class="section features-section">
    <div class="section-title">
        <span class="detail-label">FEATURES</span>
        <h2>Features, Safety and Comfort</h2>
        <p>Features, safety and comfort items will update based on the selected variant.</p>
    </div>

    <div class="feature-lists">
        <div class="feature-list-card">
            <h3>Car Features</h3>
            <ul id="featureList">
                <?php foreach ($firstVariant["features"] as $feature): ?>
                    <li><?php echo $feature; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="feature-list-card">
            <h3>Safety Features</h3>
            <ul id="safetyList">
                <?php foreach ($firstVariant["safety"] as $safety): ?>
                    <li><?php echo $safety; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="feature-list-card">
            <h3>Comfort Features</h3>
            <ul id="comfortList">
                <?php foreach ($firstVariant["comfort"] as $comfort): ?>
                    <li><?php echo $comfort; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<section class="section description-section">
    <div class="section-title">
        <span class="detail-label">ABOUT THIS MODEL</span>
        <h2>About <?php echo htmlspecialchars($car["name"]); ?></h2>
        <p><?php echo $car["description"]; ?></p>
    </div>

    <div class="description-grid">
        <div class="desc-card">
            <h3>Overview</h3>
            <p><?php echo $car["description"]; ?></p>
        </div>

        <div class="desc-card">
            <h3>Best For</h3>
            <p><?php echo $car["bestFor"]; ?></p>
        </div>

        <div class="desc-card">
            <h3>Driving Experience</h3>
            <p><?php echo $car["drivingExperience"]; ?></p>
        </div>

        <div class="desc-card">
            <h3>Why Choose This Car</h3>
            <p><?php echo $car["whyChoose"]; ?></p>
        </div>
    </div>
</section>

<section class="section loan-section">
    <div class="loan-box">
        <div>
            <h2>Estimated Monthly Payment</h2>
            <p>This estimate is based on 10% down payment and a 7-year loan period. You can use the loan calculator to adjust the interest rate, down payment and loan duration.</p>

            <div class="loan-actions">
                <a href="loan_calculator.php?car=<?php echo urlencode($car["name"]); ?>&price=<?php echo $firstVariant["priceNumber"]; ?>" class="white-btn" id="loanCalcBottom">Calculate Your Loan</a>
                <a href="loan_application.php?car=<?php echo urlencode($car["name"]); ?>" class="white-btn">Apply Loan Assistance</a>
            </div>
        </div>

        <div class="loan-summary">
            <div class="loan-row">
                <span>Selected Variant</span>
                <strong id="loanVariant"><?php echo $firstVariant["name"]; ?></strong>
            </div>

            <div class="loan-row">
                <span>Selected Price</span>
                <strong id="loanPrice"><?php echo $firstVariant["price"]; ?></strong>
            </div>

            <div class="loan-row">
                <span>Down Payment Example</span>
                <strong>10%</strong>
            </div>

            <div class="loan-row">
                <span>Loan Period</span>
                <strong>7 Years</strong>
            </div>

            <div class="loan-row">
                <span>Estimated Monthly Payment</span>
                <strong id="loanMonthly"><?php echo $firstVariant["monthly"]; ?></strong>
            </div>
        </div>
    </div>
</section>

<section class="section similar-section">
    <div class="section-title">
        <span class="detail-label">SIMILAR MODELS</span>
        <h2>Similar Toyota Models</h2>
        <p>You may also explore other Toyota models before making your final decision.</p>
    </div>

    <div class="similar-grid">
        <?php foreach ($similarCars as $similar): ?>
            <div class="similar-card">
                <div class="similar-img-wrap">
                    <img src="<?php echo $similar["colours"][0]["image"]; ?>" alt="<?php echo htmlspecialchars($similar["name"]); ?>">
                    <div class="similar-status"><?php echo $similar["status"]; ?></div>
                </div>

                <div class="similar-info">
                    <h3><?php echo $similar["name"]; ?></h3>
                    <p><?php echo $similar["priceText"]; ?></p>
                </div>

                <div class="similar-actions">
                    <a href="compare.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-outline">Compare</a>

                    <?php if ($similar["status"] === "Booking Required"): ?>
                        <a href="booking.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-red">Book Now</a>
                    <?php else: ?>
                        <a href="test_drive.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-red">Book Test Drive</a>
                    <?php endif; ?>

                    <a href="loan_application.php?car=<?php echo urlencode($similar["name"]); ?>" class="small-outline">Apply Loan</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section faq-section">
    <div class="section-title">
        <span class="detail-label">FAQ</span>
        <h2>Car Details Questions</h2>
        <p>Find quick answers about this Toyota model, loan, comparison, test drive and booking process.</p>
    </div>

    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">
                Will the specifications change when I choose a different variant?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes. The page updates the engine, horsepower, torque, transmission, fuel tank, suspension, brakes, features, safety and comfort details according to the selected variant.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Will the car image change when I choose a colour?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes. When the customer selects a colour option, the main car image will change to match the selected colour style.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I calculate the loan for this car?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, you can click Calculate Loan to estimate monthly payment based on car price, down payment, interest rate and loan period.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can the company help with loan application?
                <span>+</span>
            </div>
            <div class="faq-answer">
                <p>Yes, users can submit their information through the loan assistance form, and the company will help forward the application to the partnered bank.</p>
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
    const carName = <?php echo json_encode($car["name"]); ?>;
    const variants = <?php echo json_encode($car["variants"]); ?>;

    function toggleMenu() {
        document.getElementById("navMenu").classList.toggle("show");
    }

    function selectColour(imageSrc, element) {
        document.getElementById("mainCarImage").src = imageSrc;

        document.querySelectorAll(".colour-option").forEach(option => {
            option.classList.remove("active");
        });

        element.classList.add("active");
    }

    function selectVariant(index, element) {
        const variant = variants[index];

        document.querySelectorAll(".variant-option").forEach(option => {
            option.classList.remove("active");
        });

        element.classList.add("active");

        document.getElementById("selectedVariantPrice").textContent = variant.price;
        document.getElementById("selectedVariantMonthly").textContent = variant.monthly;

        document.getElementById("specVariant").textContent = variant.name;
        document.getElementById("specEngine").textContent = variant.engine;
        document.getElementById("specHorsepower").textContent = variant.horsepower;
        document.getElementById("specTorque").textContent = variant.torque;
        document.getElementById("specTransmission").textContent = variant.transmission;
        document.getElementById("specFuelTank").textContent = variant.fuelTank;
        document.getElementById("specFuelConsumption").textContent = variant.fuelConsumption;
        document.getElementById("specDrivetrain").textContent = variant.drivetrain;
        document.getElementById("specSuspension").textContent = variant.suspension;
        document.getElementById("specBrakes").textContent = variant.brakes;
        document.getElementById("specTyres").textContent = variant.tyres;
        document.getElementById("specPrice").textContent = variant.price;

        document.getElementById("loanVariant").textContent = variant.name;
        document.getElementById("loanPrice").textContent = variant.price;
        document.getElementById("loanMonthly").textContent = variant.monthly;

        document.getElementById("loanCalcTop").href = "loan_calculator.php?car=" + encodeURIComponent(carName) + "&price=" + variant.priceNumber;
        document.getElementById("loanCalcBottom").href = "loan_calculator.php?car=" + encodeURIComponent(carName) + "&price=" + variant.priceNumber;

        renderList("featureList", variant.features);
        renderList("safetyList", variant.safety);
        renderList("comfortList", variant.comfort);
    }

    function renderList(elementId, items) {
        const list = document.getElementById(elementId);
        list.innerHTML = "";

        items.forEach(item => {
            const li = document.createElement("li");
            li.textContent = item;
            list.appendChild(li);
        });
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