<?php
session_start();

// Modified based on uploaded original code: :contentReference[oaicite:0]{index=0}

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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "2NR-VE",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.2L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "15-inch Alloy Wheels",
                "wheelSize" => "185/60 R15",
                "weight" => "1,115 kg",
                "wheelbase" => "2,620 mm",
                "length" => "4,425 mm",
                "width" => "1,740 mm",
                "height" => "1,480 mm",
                "groundClearance" => "160 mm",
                "bootCapacity" => "475 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.1 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "2 USB Ports",
                "features" => ["Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Multi-function Steering", "2 USB Ports"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Brake Assist", "Hill-start Assist", "7 Airbags", "Rear Parking Sensors"],
                "comfort" => ["Fabric Seats", "Manual Air Conditioning", "Spacious Legroom", "Cup Holders", "Foldable Rear Seats", "Quiet Cabin"]
            ],
            [
                "name" => "Vios 1.5G",
                "price" => "RM 101,900",
                "priceNumber" => 101900,
                "monthly" => "Est. RM 1,330 / month",
                "engine" => "1.5L 4-Cylinder Petrol",
                "engineCode" => "2NR-VE",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.4L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "16-inch Alloy Wheels",
                "wheelSize" => "195/50 R16",
                "weight" => "1,130 kg",
                "wheelbase" => "2,620 mm",
                "length" => "4,425 mm",
                "width" => "1,740 mm",
                "height" => "1,480 mm",
                "groundClearance" => "160 mm",
                "bootCapacity" => "475 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.1 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "3 USB Ports",
                "features" => ["Larger Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Digital Video Recorder", "Auto Folding Mirror", "3 USB Ports"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags", "Parking Sensors"],
                "comfort" => ["Leather Combination Seats", "Auto Air Conditioning", "Rear Armrest", "Rear USB Ports", "Better Cabin Trim", "Multi-function Steering"]
            ],
            [
                "name" => "Vios 1.5 GR-S",
                "price" => "RM 109,000",
                "priceNumber" => 109000,
                "monthly" => "Est. RM 1,420 / month",
                "engine" => "1.5L 4-Cylinder Petrol",
                "engineCode" => "2NR-VE",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic with Sport Mode",
                "fuelType" => "Petrol",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Sport-tuned Suspension",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch GR Alloy Wheels",
                "wheelSize" => "205/45 R17",
                "weight" => "1,145 kg",
                "wheelbase" => "2,620 mm",
                "length" => "4,425 mm",
                "width" => "1,740 mm",
                "height" => "1,480 mm",
                "groundClearance" => "155 mm",
                "bootCapacity" => "475 Litres",
                "steering" => "Electric Power Steering with Sport Tune",
                "turningRadius" => "5.2 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "3 USB Ports",
                "features" => ["GR Body Kit", "Sport Mode", "Sport Seats", "Touchscreen Display", "LED Headlamps", "Sport Pedals", "3 USB Ports"],
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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "2NR-FE",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.2L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "15-inch Alloy Wheels",
                "wheelSize" => "185/60 R15",
                "weight" => "1,100 kg",
                "wheelbase" => "2,550 mm",
                "length" => "4,145 mm",
                "width" => "1,730 mm",
                "height" => "1,500 mm",
                "groundClearance" => "150 mm",
                "bootCapacity" => "286 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.1 m",
                "airbags" => "5 Airbags",
                "usbPorts" => "2 USB Ports",
                "features" => ["Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Foldable Rear Seats", "2 USB Ports"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Brake Assist", "Hill-start Assist", "5 Airbags", "Parking Sensors"],
                "comfort" => ["Fabric Seats", "Air Conditioning", "Compact Cabin", "Easy Parking", "Cup Holders", "Multi-function Steering"]
            ],
            [
                "name" => "Yaris 1.5G",
                "price" => "RM 92,000",
                "priceNumber" => 92000,
                "monthly" => "Est. RM 1,200 / month",
                "engine" => "1.5L Petrol",
                "engineCode" => "2NR-FE",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.4L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "16-inch Alloy Wheels",
                "wheelSize" => "195/50 R16",
                "weight" => "1,115 kg",
                "wheelbase" => "2,550 mm",
                "length" => "4,145 mm",
                "width" => "1,730 mm",
                "height" => "1,500 mm",
                "groundClearance" => "150 mm",
                "bootCapacity" => "286 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.1 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "3 USB Ports",
                "features" => ["Larger Touchscreen Display", "Reverse Camera", "Smart Entry", "Auto Folding Mirror", "LED Headlamps", "DVR", "3 USB Ports"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags", "Parking Sensors"],
                "comfort" => ["Better Seat Trim", "Auto Air Conditioning", "Rear Seat Flexibility", "USB Charging", "Quiet Cabin", "Multi-function Steering"]
            ],
            [
                "name" => "Yaris 1.5 GR-S",
                "price" => "RM 99,000",
                "priceNumber" => 99000,
                "monthly" => "Est. RM 1,290 / month",
                "engine" => "1.5L Petrol",
                "engineCode" => "2NR-FE",
                "horsepower" => "106 PS",
                "torque" => "138 Nm",
                "transmission" => "CVT Automatic with Sport Mode",
                "fuelType" => "Petrol",
                "fuelTank" => "42 Litres",
                "fuelConsumption" => "Approx. 5.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Sport-tuned Suspension",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch GR Alloy Wheels",
                "wheelSize" => "205/45 R17",
                "weight" => "1,130 kg",
                "wheelbase" => "2,550 mm",
                "length" => "4,145 mm",
                "width" => "1,730 mm",
                "height" => "1,500 mm",
                "groundClearance" => "145 mm",
                "bootCapacity" => "286 Litres",
                "steering" => "Electric Power Steering with Sport Tune",
                "turningRadius" => "5.2 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "3 USB Ports",
                "features" => ["GR Body Kit", "Sport Mode", "Sport Seats", "Touchscreen Display", "LED Headlamps", "Sport Pedals", "3 USB Ports"],
                "safety" => ["ABS with EBD", "Vehicle Stability Control", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags", "Parking Sensors"],
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
        "fuel" => "Petrol",
        "seats" => "5 Seats",
        "status" => "Available Now",
        "stock" => "In Stock",
        "label" => "SUV Choice",
        "body" => "SUV",
        "waiting" => "-",
        "bookingFee" => "-",
        "warranty" => "5 Years Warranty",
        "short" => "A modern SUV with comfort, safety and practical space.",
        "description" => "The Toyota Corolla Cross is a modern SUV that combines comfort, safety, practicality and efficient performance.",
        "bestFor" => "Small families, daily driving and SUV users.",
        "drivingExperience" => "Comfortable, stable and efficient for city and highway use.",
        "whyChoose" => "Choose this model if you want a balanced SUV with advanced safety and practical cabin space.",
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
                "engineCode" => "2ZR-FE",
                "horsepower" => "139 PS",
                "torque" => "172 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "47 Litres",
                "fuelConsumption" => "Approx. 6.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch Alloy Wheels",
                "wheelSize" => "215/60 R17",
                "weight" => "1,385 kg",
                "wheelbase" => "2,640 mm",
                "length" => "4,460 mm",
                "width" => "1,825 mm",
                "height" => "1,620 mm",
                "groundClearance" => "161 mm",
                "bootCapacity" => "440 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.2 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "3 USB Ports",
                "features" => ["Touchscreen Display", "Smart Entry", "Reverse Camera", "LED Headlamps", "Electric Parking Brake", "Drive Mode Select", "3 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Pre-Collision System", "Lane Departure Alert", "ABS with EBD", "7 Airbags", "Parking Sensors"],
                "comfort" => ["Fabric Seats", "Automatic Air Conditioning", "Spacious Cabin", "Rear Air Vents", "USB Ports", "Large Boot Space"]
            ],
            [
                "name" => "Corolla Cross 1.8V",
                "price" => "RM 138,400",
                "priceNumber" => 138400,
                "monthly" => "Est. RM 1,800 / month",
                "engine" => "1.8L Petrol Engine",
                "engineCode" => "2ZR-FE",
                "horsepower" => "139 PS",
                "torque" => "172 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "47 Litres",
                "fuelConsumption" => "Approx. 6.6L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "wheelSize" => "225/50 R18",
                "weight" => "1,405 kg",
                "wheelbase" => "2,640 mm",
                "length" => "4,460 mm",
                "width" => "1,825 mm",
                "height" => "1,620 mm",
                "groundClearance" => "161 mm",
                "bootCapacity" => "440 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.2 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "4 USB Ports",
                "features" => ["Larger Touchscreen Display", "Smart Entry", "360-degree Camera", "LED Headlamps", "Power Back Door", "Electric Parking Brake", "4 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Pre-Collision System", "Lane Departure Alert", "7 Airbags"],
                "comfort" => ["Leather Seats", "Dual-zone Air Conditioning", "Spacious Cabin", "Rear Air Vents", "Rear USB Ports", "Quiet Cabin"]
            ],
            [
                "name" => "Corolla Cross Hybrid",
                "price" => "RM 145,000",
                "priceNumber" => 145000,
                "monthly" => "Est. RM 1,880 / month",
                "engine" => "1.8L Hybrid Petrol Engine",
                "engineCode" => "2ZR-FXE Hybrid",
                "horsepower" => "122 PS Combined Output",
                "torque" => "142 Nm + Electric Motor Assist",
                "transmission" => "E-CVT Automatic",
                "fuelType" => "Hybrid",
                "fuelTank" => "36 Litres",
                "fuelConsumption" => "Approx. 4.3L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "wheelSize" => "225/50 R18",
                "weight" => "1,430 kg",
                "wheelbase" => "2,640 mm",
                "length" => "4,460 mm",
                "width" => "1,825 mm",
                "height" => "1,620 mm",
                "groundClearance" => "161 mm",
                "bootCapacity" => "440 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.2 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "4 USB Ports",
                "features" => ["Hybrid System", "EV Mode", "360-degree Camera", "Power Back Door", "Smart Entry", "Electric Parking Brake", "4 USB Ports"],
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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "2GD-FTV",
                "horsepower" => "150 PS",
                "torque" => "400 Nm",
                "transmission" => "6-Speed Automatic",
                "fuelType" => "Diesel",
                "fuelTank" => "80 Litres",
                "fuelConsumption" => "Approx. 7.5L / 100km",
                "drivetrain" => "Rear-Wheel Drive",
                "suspension" => "Front Double Wishbone, Rear Leaf Spring",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "17-inch Alloy Wheels",
                "wheelSize" => "265/65 R17",
                "weight" => "1,945 kg",
                "wheelbase" => "3,085 mm",
                "length" => "5,325 mm",
                "width" => "1,855 mm",
                "height" => "1,815 mm",
                "groundClearance" => "286 mm",
                "bootCapacity" => "Cargo Bed",
                "steering" => "Hydraulic Power Steering",
                "turningRadius" => "6.4 m",
                "airbags" => "3 Airbags",
                "usbPorts" => "2 USB Ports",
                "features" => ["Diesel Engine", "Cargo Bed", "Touchscreen Display", "Reverse Camera", "Strong Body", "High Ground Clearance", "2 USB Ports"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Trailer Sway Control", "3 Airbags", "Rear Parking Sensors"],
                "comfort" => ["Durable Seats", "Air Conditioning", "High Driving Position", "USB Port", "Practical Cabin", "Large Cargo Space"]
            ],
            [
                "name" => "Hilux 2.4V 4x4",
                "price" => "RM 145,000",
                "priceNumber" => 145000,
                "monthly" => "Est. RM 1,880 / month",
                "engine" => "2.4L Turbo Diesel",
                "engineCode" => "2GD-FTV",
                "horsepower" => "150 PS",
                "torque" => "400 Nm",
                "transmission" => "6-Speed Automatic",
                "fuelType" => "Diesel",
                "fuelTank" => "80 Litres",
                "fuelConsumption" => "Approx. 8.0L / 100km",
                "drivetrain" => "4x4",
                "suspension" => "Front Double Wishbone, Rear Leaf Spring",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "18-inch Alloy Wheels",
                "wheelSize" => "265/60 R18",
                "weight" => "2,055 kg",
                "wheelbase" => "3,085 mm",
                "length" => "5,325 mm",
                "width" => "1,855 mm",
                "height" => "1,815 mm",
                "groundClearance" => "286 mm",
                "bootCapacity" => "Cargo Bed",
                "steering" => "Hydraulic Power Steering",
                "turningRadius" => "6.4 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "3 USB Ports",
                "features" => ["4x4 Capability", "Touchscreen Display", "Reverse Camera", "LED Headlamps", "Cargo Bed", "Smart Entry", "3 USB Ports"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Downhill Assist Control", "Trailer Sway Control", "7 Airbags"],
                "comfort" => ["Better Seat Trim", "Auto Air Conditioning", "High Driving Position", "USB Ports", "Durable Cabin", "Spacious Interior"]
            ],
            [
                "name" => "Hilux Rogue",
                "price" => "RM 160,000",
                "priceNumber" => 160000,
                "monthly" => "Est. RM 2,080 / month",
                "engine" => "2.8L Turbo Diesel",
                "engineCode" => "1GD-FTV",
                "horsepower" => "204 PS",
                "torque" => "500 Nm",
                "transmission" => "6-Speed Automatic",
                "fuelType" => "Diesel",
                "fuelTank" => "80 Litres",
                "fuelConsumption" => "Approx. 8.5L / 100km",
                "drivetrain" => "4x4",
                "suspension" => "Front Double Wishbone, Rear Leaf Spring",
                "brakes" => "Front Ventilated Disc, Rear Drum",
                "tyres" => "18-inch Rogue Alloy Wheels",
                "wheelSize" => "265/60 R18",
                "weight" => "2,105 kg",
                "wheelbase" => "3,085 mm",
                "length" => "5,325 mm",
                "width" => "1,900 mm",
                "height" => "1,865 mm",
                "groundClearance" => "286 mm",
                "bootCapacity" => "Cargo Bed",
                "steering" => "Hydraulic Power Steering",
                "turningRadius" => "6.4 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "4 USB Ports",
                "features" => ["Rogue Body Kit", "4x4 Capability", "Powerful Diesel Engine", "360-degree Camera", "Smart Entry", "Premium Display", "4 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Hill Start Assist", "Trailer Sway Control", "7 Airbags"],
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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "A25A-FKS",
                "horsepower" => "209 PS",
                "torque" => "253 Nm",
                "transmission" => "8-Speed Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "60 Litres",
                "fuelConsumption" => "Approx. 6.8L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "wheelSize" => "235/45 R18",
                "weight" => "1,555 kg",
                "wheelbase" => "2,825 mm",
                "length" => "4,885 mm",
                "width" => "1,840 mm",
                "height" => "1,445 mm",
                "groundClearance" => "145 mm",
                "bootCapacity" => "524 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.8 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "4 USB Ports",
                "features" => ["Premium Interior", "Smart Entry", "Touchscreen Display", "Reverse Camera", "Power Seats", "LED Headlamps", "4 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Pre-Collision System", "Lane Tracing Assist", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags"],
                "comfort" => ["Power Seats", "Dual-zone Air Conditioning", "Spacious Cabin", "Quiet Cabin", "USB Charging", "Rear Armrest"]
            ],
            [
                "name" => "Camry 2.5 Premium",
                "price" => "RM 235,000",
                "priceNumber" => 235000,
                "monthly" => "Est. RM 3,050 / month",
                "engine" => "2.5L Dynamic Force Petrol",
                "engineCode" => "A25A-FKS",
                "horsepower" => "209 PS",
                "torque" => "253 Nm",
                "transmission" => "8-Speed Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "60 Litres",
                "fuelConsumption" => "Approx. 6.9L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Premium Alloy Wheels",
                "wheelSize" => "235/45 R18",
                "weight" => "1,570 kg",
                "wheelbase" => "2,825 mm",
                "length" => "4,885 mm",
                "width" => "1,840 mm",
                "height" => "1,445 mm",
                "groundClearance" => "145 mm",
                "bootCapacity" => "524 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.8 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "5 USB Ports",
                "features" => ["Premium Audio", "Larger Display", "360-degree Camera", "Power Seats", "Smart Entry", "LED Headlamps", "5 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Lane Tracing Assist", "Parking Support Brake", "7 Airbags"],
                "comfort" => ["Leather Seats", "Dual-zone Air Conditioning", "Quiet Cabin", "Rear Sunshade", "USB Charging", "Executive Rear Space"]
            ],
            [
                "name" => "Camry Hybrid",
                "price" => "RM 240,000",
                "priceNumber" => 240000,
                "monthly" => "Est. RM 3,120 / month",
                "engine" => "2.5L Hybrid Engine",
                "engineCode" => "A25A-FXS Hybrid",
                "horsepower" => "218 PS Combined Output",
                "torque" => "221 Nm + Electric Motor Assist",
                "transmission" => "E-CVT Automatic",
                "fuelType" => "Hybrid",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 4.5L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Hybrid Alloy Wheels",
                "wheelSize" => "235/45 R18",
                "weight" => "1,625 kg",
                "wheelbase" => "2,825 mm",
                "length" => "4,885 mm",
                "width" => "1,840 mm",
                "height" => "1,445 mm",
                "groundClearance" => "145 mm",
                "bootCapacity" => "524 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.8 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "5 USB Ports",
                "features" => ["Hybrid System", "EV Mode", "Premium Audio", "360-degree Camera", "Smart Entry", "Power Seats", "5 USB Ports"],
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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "M20A-FKS",
                "horsepower" => "174 PS",
                "torque" => "205 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "52 Litres",
                "fuelConsumption" => "Approx. 7.0L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "17-inch Alloy Wheels",
                "wheelSize" => "215/60 R17",
                "weight" => "1,620 kg",
                "wheelbase" => "2,850 mm",
                "length" => "4,755 mm",
                "width" => "1,850 mm",
                "height" => "1,795 mm",
                "groundClearance" => "185 mm",
                "bootCapacity" => "300 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.6 m",
                "airbags" => "6 Airbags",
                "usbPorts" => "4 USB Ports",
                "features" => ["7 Seats", "Touchscreen Display", "Reverse Camera", "Smart Entry", "LED Headlamps", "Flexible Seats", "4 USB Ports"],
                "safety" => ["ABS", "Vehicle Stability Control", "6 Airbags", "Hill Start Assist", "Parking Sensors", "Reverse Camera"],
                "comfort" => ["Fabric Seats", "Rear Air Conditioning", "Spacious Legroom", "USB Charging", "Family Cabin", "Flexible Seating"]
            ],
            [
                "name" => "Innova Zenix 2.0X",
                "price" => "RM 172,000",
                "priceNumber" => 172000,
                "monthly" => "Est. RM 2,220 / month",
                "engine" => "2.0L Petrol Engine",
                "engineCode" => "M20A-FKS",
                "horsepower" => "174 PS",
                "torque" => "205 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "52 Litres",
                "fuelConsumption" => "Approx. 7.1L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "wheelSize" => "225/50 R18",
                "weight" => "1,650 kg",
                "wheelbase" => "2,850 mm",
                "length" => "4,755 mm",
                "width" => "1,850 mm",
                "height" => "1,795 mm",
                "groundClearance" => "185 mm",
                "bootCapacity" => "300 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.6 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "5 USB Ports",
                "features" => ["Captain Seats", "Larger Display", "Reverse Camera", "Smart Entry", "Power Back Door", "LED Headlamps", "5 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags", "Parking Sensors", "Vehicle Stability Control"],
                "comfort" => ["Captain Seats", "Rear Air Conditioning", "USB Charging", "Spacious Legroom", "Premium Cabin", "Flexible Seating"]
            ],
            [
                "name" => "Innova Zenix Hybrid",
                "price" => "RM 175,000",
                "priceNumber" => 175000,
                "monthly" => "Est. RM 2,280 / month",
                "engine" => "2.0L Hybrid Engine",
                "engineCode" => "M20A-FXS Hybrid",
                "horsepower" => "186 PS Combined Output",
                "torque" => "188 Nm + Electric Motor Assist",
                "transmission" => "E-CVT Automatic",
                "fuelType" => "Hybrid",
                "fuelTank" => "52 Litres",
                "fuelConsumption" => "Approx. 5.4L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Torsion Beam",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Hybrid Alloy Wheels",
                "wheelSize" => "225/50 R18",
                "weight" => "1,695 kg",
                "wheelbase" => "2,850 mm",
                "length" => "4,755 mm",
                "width" => "1,850 mm",
                "height" => "1,795 mm",
                "groundClearance" => "185 mm",
                "bootCapacity" => "300 Litres",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.6 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "5 USB Ports",
                "features" => ["Hybrid System", "Captain Seats", "Power Back Door", "Smart Entry", "Larger Display", "LED Headlamps", "5 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Pre-Collision System", "7 Airbags", "Parking Sensors"],
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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "2AR-FE",
                "horsepower" => "182 PS",
                "torque" => "235 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "75 Litres",
                "fuelConsumption" => "Approx. 9.0L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Alloy Wheels",
                "wheelSize" => "235/50 R18",
                "weight" => "1,940 kg",
                "wheelbase" => "3,000 mm",
                "length" => "4,945 mm",
                "width" => "1,850 mm",
                "height" => "1,895 mm",
                "groundClearance" => "160 mm",
                "bootCapacity" => "Luxury Cabin",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.8 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "5 USB Ports",
                "features" => ["Power Sliding Door", "Smart Entry", "Touchscreen Display", "Reverse Camera", "LED Headlamps", "Luxury Cabin", "5 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Pre-Collision System", "Lane Departure Alert", "Blind Spot Monitor", "Rear Cross Traffic Alert", "7 Airbags"],
                "comfort" => ["Captain Seats", "Rear Air Conditioning", "USB Charging", "Spacious Legroom", "Quiet Cabin", "Power Sliding Door"]
            ],
            [
                "name" => "Alphard 2.5G",
                "price" => "RM 560,000",
                "priceNumber" => 560000,
                "monthly" => "Est. RM 7,180 / month",
                "engine" => "2.5L Petrol Engine",
                "engineCode" => "2AR-FE",
                "horsepower" => "182 PS",
                "torque" => "235 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "75 Litres",
                "fuelConsumption" => "Approx. 9.2L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "18-inch Premium Alloy Wheels",
                "wheelSize" => "235/50 R18",
                "weight" => "1,970 kg",
                "wheelbase" => "3,000 mm",
                "length" => "4,945 mm",
                "width" => "1,850 mm",
                "height" => "1,895 mm",
                "groundClearance" => "160 mm",
                "bootCapacity" => "Luxury Cabin",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.8 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "6 USB Ports",
                "features" => ["Premium Captain Seats", "Power Sliding Door", "Large Touchscreen", "360-degree Camera", "Power Back Door", "Premium Lighting", "6 USB Ports"],
                "safety" => ["Toyota Safety Sense", "Blind Spot Monitor", "Rear Cross Traffic Alert", "Parking Support Brake", "Lane Tracing Assist", "7 Airbags"],
                "comfort" => ["Premium Captain Seats", "Power Ottoman Seats", "Rear Air Conditioning", "Quiet Luxury Cabin", "USB Charging", "Spacious Legroom"]
            ],
            [
                "name" => "Alphard Executive Lounge",
                "price" => "RM 610,000",
                "priceNumber" => 610000,
                "monthly" => "Est. RM 7,850 / month",
                "engine" => "2.5L Petrol Engine",
                "engineCode" => "2AR-FE",
                "horsepower" => "182 PS",
                "torque" => "235 Nm",
                "transmission" => "CVT Automatic",
                "fuelType" => "Petrol",
                "fuelTank" => "75 Litres",
                "fuelConsumption" => "Approx. 9.5L / 100km",
                "drivetrain" => "Front-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Front Ventilated Disc, Rear Disc",
                "tyres" => "19-inch Executive Alloy Wheels",
                "wheelSize" => "245/45 R19",
                "weight" => "2,010 kg",
                "wheelbase" => "3,000 mm",
                "length" => "4,945 mm",
                "width" => "1,850 mm",
                "height" => "1,895 mm",
                "groundClearance" => "160 mm",
                "bootCapacity" => "Luxury Cabin",
                "steering" => "Electric Power Steering",
                "turningRadius" => "5.8 m",
                "airbags" => "7 Airbags",
                "usbPorts" => "7 USB Ports",
                "features" => ["Executive Lounge Seats", "Rear Entertainment Display", "Premium Audio", "360-degree Camera", "Power Sliding Door", "Power Back Door", "7 USB Ports"],
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
        "warranty" => "5 Years Warranty",
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
                "engineCode" => "G16E-GTS",
                "horsepower" => "304 PS",
                "torque" => "370 Nm",
                "transmission" => "6-Speed Manual",
                "fuelType" => "Petrol",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 8.4L / 100km",
                "drivetrain" => "GR-Four All-Wheel Drive",
                "suspension" => "Front MacPherson Strut, Rear Double Wishbone",
                "brakes" => "Performance Ventilated Disc Brakes",
                "tyres" => "18-inch GR Alloy Wheels",
                "wheelSize" => "235/40 R18",
                "weight" => "1,475 kg",
                "wheelbase" => "2,640 mm",
                "length" => "4,410 mm",
                "width" => "1,850 mm",
                "height" => "1,480 mm",
                "groundClearance" => "130 mm",
                "bootCapacity" => "213 Litres",
                "steering" => "Electric Power Steering with Sport Tune",
                "turningRadius" => "5.3 m",
                "airbags" => "6 Airbags",
                "usbPorts" => "2 USB Ports",
                "features" => ["Turbo Engine", "Manual Transmission", "GR-Four AWD", "Sport Seats", "GR Body Kit", "Performance Display", "2 USB Ports"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Reverse Camera", "Performance Brakes", "6 Airbags"],
                "comfort" => ["Sport Seats", "Air Conditioning", "USB Charging", "Driver-focused Cabin", "Sport Steering", "Performance Pedals"]
            ],
            [
                "name" => "GR Corolla Circuit Edition",
                "price" => "RM 380,000",
                "priceNumber" => 380000,
                "monthly" => "Est. RM 4,900 / month",
                "engine" => "1.6L Turbocharged Petrol",
                "engineCode" => "G16E-GTS",
                "horsepower" => "304 PS",
                "torque" => "370 Nm",
                "transmission" => "6-Speed Manual",
                "fuelType" => "Petrol",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 8.6L / 100km",
                "drivetrain" => "GR-Four All-Wheel Drive",
                "suspension" => "Sport-tuned Suspension",
                "brakes" => "Performance Ventilated Disc Brakes",
                "tyres" => "18-inch Forged Alloy Wheels",
                "wheelSize" => "235/40 R18",
                "weight" => "1,485 kg",
                "wheelbase" => "2,640 mm",
                "length" => "4,410 mm",
                "width" => "1,850 mm",
                "height" => "1,480 mm",
                "groundClearance" => "125 mm",
                "bootCapacity" => "213 Litres",
                "steering" => "Electric Power Steering with Sport Tune",
                "turningRadius" => "5.3 m",
                "airbags" => "6 Airbags",
                "usbPorts" => "2 USB Ports",
                "features" => ["Circuit Aero Kit", "GR-Four AWD", "Sport Seats", "Performance Display", "Manual Transmission", "Sport Exhaust", "2 USB Ports"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Reverse Camera", "Parking Sensors", "Performance Brakes", "6 Airbags"],
                "comfort" => ["Sport Seats", "Premium Trim", "Air Conditioning", "USB Charging", "Driver-focused Cabin", "Sport Steering"]
            ],
            [
                "name" => "GR Corolla Morizo Edition",
                "price" => "RM 420,000",
                "priceNumber" => 420000,
                "monthly" => "Est. RM 5,380 / month",
                "engine" => "1.6L Turbocharged Petrol",
                "engineCode" => "G16E-GTS",
                "horsepower" => "304 PS",
                "torque" => "400 Nm",
                "transmission" => "6-Speed Manual",
                "fuelType" => "Petrol",
                "fuelTank" => "50 Litres",
                "fuelConsumption" => "Approx. 8.8L / 100km",
                "drivetrain" => "GR-Four All-Wheel Drive",
                "suspension" => "Track-tuned Suspension",
                "brakes" => "High-performance Ventilated Disc Brakes",
                "tyres" => "18-inch Lightweight Forged Wheels",
                "wheelSize" => "235/40 R18",
                "weight" => "1,450 kg",
                "wheelbase" => "2,640 mm",
                "length" => "4,410 mm",
                "width" => "1,850 mm",
                "height" => "1,480 mm",
                "groundClearance" => "120 mm",
                "bootCapacity" => "Track Cabin",
                "steering" => "Electric Power Steering with Track Tune",
                "turningRadius" => "5.3 m",
                "airbags" => "6 Airbags",
                "usbPorts" => "2 USB Ports",
                "features" => ["Track-focused Setup", "GR-Four AWD", "Lightweight Body", "Sport Exhaust", "Performance Display", "Manual Transmission", "2 USB Ports"],
                "safety" => ["ABS", "Vehicle Stability Control", "Hill Start Assist", "Performance Brakes", "Reverse Camera", "6 Airbags"],
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

$allFeatures = [];
$allSafety = [];
$allComfort = [];

foreach ($car["variants"] as $variant) {
    $allFeatures = array_merge($allFeatures, $variant["features"]);
    $allSafety = array_merge($allSafety, $variant["safety"]);
    $allComfort = array_merge($allComfort, $variant["comfort"]);
}

$allFeatures = array_values(array_unique($allFeatures));
$allSafety = array_values(array_unique($allSafety));
$allComfort = array_values(array_unique($allComfort));
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
            position: fixed;
            right: 26px;
            bottom: 26px;
            width: 410px;
            max-width: calc(100% - 52px);
            padding: 0;
            background: transparent;
            z-index: 998;
        }

        .status-top-box {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(215, 25, 32, 0.18);
            border-left: 6px solid #d71920;
            border-radius: 22px;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.16);
            backdrop-filter: blur(14px);
        }

        .status-top-box h2 {
            color: #111;
            font-size: 17px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .status-top-box p {
            color: #666;
            line-height: 1.55;
            font-size: 12.5px;
            max-width: 260px;
        }

        .status-top-pill {
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #fff;
            padding: 11px 16px;
            border-radius: 24px;
            font-size: 13px;
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
            align-items: stretch;
        }

        .left-product-box {
            background: #ffffff;
            border-radius: 34px;
            border: 1px solid rgba(215, 25, 32, 0.13);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            height: 100%;
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
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
                linear-gradient(135deg, #050505, #111111 42%, #1a1a1a);
            border-radius: 34px;
            padding: 34px;
            border: 1px solid rgba(255, 255, 255, 0.09);
            box-shadow:
                0 30px 70px rgba(0, 0, 0, 0.34),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
            position: sticky;
            top: 110px;
            height: 100%;
            min-height: 100%;
        }

        .option-panel h2 {
            font-size: 28px;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .option-panel > p {
            color: #bdbdbd;
            line-height: 1.65;
            font-size: 14.5px;
            margin-bottom: 24px;
        }

        .option-box {
            margin-bottom: 28px;
        }

        .option-box h3 {
            font-size: 18px;
            color: #ffffff;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .variant-select-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .variant-option {
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 16px 18px;
            cursor: pointer;
            transition: 0.3s;
            background: rgba(255, 255, 255, 0.06);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
        }

        .variant-option.active,
        .variant-option:hover {
            background: rgba(215, 25, 32, 0.15);
            border-color: #d71920;
            box-shadow: 0 12px 26px rgba(215, 25, 32, 0.18);
            transform: translateY(-2px);
        }

        .variant-option strong {
            display: block;
            color: #ffffff;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .variant-option span {
            color: #ff4a53;
            font-weight: 900;
            font-size: 14px;
        }

        .variant-mini-spec {
            text-align: right;
            color: #bdbdbd;
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
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 18px;
            padding: 13px;
            cursor: pointer;
            transition: 0.3s;
        }

        .colour-option.active,
        .colour-option:hover {
            background: rgba(255, 255, 255, 0.11);
            border-color: #d71920;
            box-shadow: 0 12px 24px rgba(215, 25, 32, 0.16);
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
            color: #ffffff;
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
            background: #ffffff;
            color: #111111;
            border-color: #ffffff;
            transform: translateY(-2px);
        }

        .action-outline {
            background: transparent;
            color: #ffffff;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
        }

        .action-outline:hover {
            background: #ffffff;
            color: #111111;
            border-color: #ffffff;
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

        .spec-tab-box,
        .feature-tab-box {
            background: #ffffff;
            border-radius: 30px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }

        .spec-tab-nav,
        .feature-tab-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            background: #111111;
            padding: 18px;
        }

        .spec-tab-btn,
        .feature-tab-btn {
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 13.5px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
        }

        .spec-tab-btn.active,
        .spec-tab-btn:hover,
        .feature-tab-btn.active,
        .feature-tab-btn:hover {
            background: linear-gradient(135deg, #d71920, #8f0f14);
            border-color: #d71920;
            box-shadow: 0 10px 24px rgba(215, 25, 32, 0.22);
        }

        .spec-tab-content,
        .feature-tab-content {
            display: none;
            padding: 28px;
        }

        .spec-tab-content.active,
        .feature-tab-content.active {
            display: block;
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

        .feature-check-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .feature-check-item {
            background: #ffffff;
            padding: 15px 16px;
            border-radius: 18px;
            color: #333;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 11px;
            line-height: 1.5;
            border: 1px solid rgba(215, 25, 32, 0.11);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.055);
        }

        .feature-check-item::before {
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
            font-weight: 900;
        }

        .feature-check-item.not-included {
            color: #aaa;
            background: #fbfbfb;
        }

        .feature-check-item.not-included::before {
            content: "";
            background: #ffffff;
            border: 2px solid #cfcfcf;
        }

        .compare-variant-section {
            background: #ffffff;
        }

        .compare-variant-box {
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
                linear-gradient(135deg, #050505, #111111 42%, #1a1a1a);
            border-radius: 34px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.09);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.26);
            overflow-x: auto;
        }

        .compare-select-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .compare-select-row select {
            width: 100%;
            padding: 15px 18px;
            border-radius: 18px;
            border: 1.5px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            font-weight: 900;
            outline: none;
            font-size: 14px;
        }

        .compare-select-row option {
            color: #111111;
        }

        .compare-table {
            width: 100%;
            min-width: 780px;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
        }

        .compare-table th {
            background: linear-gradient(135deg, #d71920, #8f0f14);
            color: #ffffff;
            padding: 18px 20px;
            text-align: left;
            font-size: 15px;
            font-weight: 900;
        }

        .compare-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #eeeeee;
            color: #555;
            font-weight: 800;
            line-height: 1.55;
            font-size: 14px;
        }

        .compare-table td:first-child {
            background: #fbfbfb;
            color: #111;
            width: 30%;
            font-weight: 900;
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
            grid-template-columns: 1.1fr 0.9fr;
            gap: 34px;
            align-items: center;
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.3);
        }

        .loan-box h2 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .loan-box p {
            line-height: 1.75;
            color: rgba(255, 255, 255, 0.88);
            margin-bottom: 24px;
            max-width: 720px;
        }

        .loan-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .white-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 50px;
            padding: 0 22px;
            border-radius: 24px;
            background: #ffffff;
            color: #d71920;
            font-weight: 900;
            transition: 0.3s;
            border: 1.5px solid #ffffff;
        }

        .white-btn:hover {
            background: #111;
            color: #ffffff;
            border-color: #111;
            transform: translateY(-2px);
        }

        .loan-summary {
            background: rgba(255, 255, 255, 0.14);
            border-radius: 28px;
            padding: 24px;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.22);
        }

        .loan-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.16);
        }

        .loan-row:last-child {
            border-bottom: none;
        }

        .loan-row span {
            color: rgba(255, 255, 255, 0.78);
            font-weight: 800;
        }

        .loan-row strong {
            color: #ffffff;
            font-weight: 900;
            text-align: right;
        }

        .similar-section {
            background: #ffffff;
        }

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .similar-card {
            background: #fff;
            border-radius: 30px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            position: relative;
            transition: 0.3s;
        }

        .similar-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 55px rgba(0, 0, 0, 0.1);
        }

        .similar-img-wrap {
            position: relative;
            height: 230px;
            overflow: hidden;
        }

        .similar-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.45s;
        }

        .similar-card:hover .similar-img-wrap img {
            transform: scale(1.06);
        }

        .similar-status {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(215, 25, 32, 0.94);
            color: #ffffff;
            border-radius: 20px;
            padding: 8px 13px;
            font-size: 12px;
            font-weight: 900;
        }

        .similar-info {
            padding: 22px;
            padding-bottom: 80px;
        }

        .similar-info h3 {
            font-size: 22px;
            color: #111;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .similar-info p {
            color: #d71920;
            font-weight: 900;
        }

        .similar-actions {
            position: absolute;
            left: 22px;
            right: 22px;
            bottom: 22px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .small-outline,
        .small-red {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 36px;
            padding: 0 13px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: 900;
            transition: 0.3s;
        }

        .small-outline {
            color: #d71920;
            background: #fff;
            border: 1.4px solid #d71920;
        }

        .small-red {
            color: #fff;
            background: #d71920;
            border: 1.4px solid #d71920;
        }

        .small-outline:hover,
        .small-red:hover {
            background: #111;
            color: #ffffff;
            border-color: #111;
        }

        .faq-section {
            background: #f7f7f7;
        }

        .faq-container {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            gap: 14px;
        }

        .faq-item {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(215, 25, 32, 0.12);
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .faq-question {
            padding: 20px 24px;
            font-weight: 900;
            color: #111;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .faq-question span {
            width: 30px;
            height: 30px;
            background: #d71920;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            flex-shrink: 0;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: 0.3s;
        }

        .faq-answer p {
            padding: 0 24px 20px;
            color: #666;
            line-height: 1.7;
        }

        .faq-item.active .faq-answer {
            max-height: 160px;
        }

        .footer {
            background: #111;
            color: #fff;
            padding: 60px 6% 28px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 32px;
            margin-bottom: 38px;
        }

        .footer h3 {
            font-size: 20px;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .footer p {
            color: #bbb;
            line-height: 1.7;
            font-size: 14px;
        }

        .footer a {
            display: block;
            color: #bbb;
            margin-bottom: 10px;
            font-size: 14px;
            transition: 0.3s;
        }

        .footer a:hover {
            color: #ffffff;
            transform: translateX(4px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            padding-top: 22px;
            color: #aaa;
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 1200px) {
            .nav-center {
                display: none;
                position: absolute;
                top: 86px;
                left: 5%;
                right: 5%;
                background: #ffffff;
                border: 1px solid #eeeeee;
                border-radius: 24px;
                padding: 18px;
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.12);
                flex-direction: column;
                align-items: stretch;
            }

            .nav-center.show {
                display: flex;
            }

            .menu-btn {
                display: block;
            }

            .details-layout {
                grid-template-columns: 1fr;
            }

            .option-panel {
                position: static;
            }

            .feature-check-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .description-grid,
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .loan-box {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .similar-grid {
                grid-template-columns: 1fr;
            }

            .status-top-section {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .details-hero,
            .section,
            .breadcrumb-section {
                padding-left: 5%;
                padding-right: 5%;
            }

            .main-image-wrap {
                height: 340px;
            }

            .description-grid,
            .footer-grid,
            .feature-check-grid,
            .compare-select-row {
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

            .spec-tab-nav,
            .feature-tab-nav {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .spec-tab-btn,
            .feature-tab-btn {
                white-space: nowrap;
                flex-shrink: 0;
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
            <h2>Status: <span id="floatingStatus"><?php echo $car["status"]; ?></span></h2>
            <?php if ($isBooking): ?>
                <p id="floatingStatusText">This model requires advance booking. Estimated waiting time: <?php echo $car["waiting"]; ?>. Booking fee: <?php echo $car["bookingFee"]; ?>.</p>
            <?php else: ?>
                <p id="floatingStatusText">This model is currently available for viewing, loan application and test drive booking.</p>
            <?php endif; ?>
        </div>

        <div class="status-top-pill" id="floatingStock">
            <?php echo $car["stock"]; ?>
        </div>
    </div>
</section>

<section class="details-hero">
    <div class="details-layout">
        <div class="left-product-box">
            <div class="main-image-wrap">
                <img src="<?php echo $car["colours"][0]["image"]; ?>" class="main-car-image" id="mainCarImage" alt="<?php echo htmlspecialchars($car["name"]); ?>">
                <div class="image-status-badge" id="imageStatusBadge"><?php echo $car["status"]; ?></div>
            </div>

            <div class="car-summary-under-image">
                <span class="detail-label" id="selectedCarLabel"><?php echo $firstVariant["fuelType"] === "Hybrid" ? "Hybrid SUV Choice" : $car["label"]; ?></span>
                <h1><?php echo htmlspecialchars($car["name"]); ?></h1>
                <div class="car-price" id="selectedVariantPrice"><?php echo $firstVariant["price"]; ?></div>
                <div class="monthly" id="selectedVariantMonthly"><?php echo $firstVariant["monthly"]; ?></div>

                <div class="status-row">
                    <span class="status-pill red" id="selectedStatusPill"><?php echo $car["status"]; ?></span>
                    <span class="status-pill"><?php echo $car["type"]; ?></span>
                    <span class="status-pill" id="selectedStockPill"><?php echo $car["stock"]; ?></span>
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
                    <a href="#variantCompare" class="action-outline">Compare Variant</a>
                    <a href="contact.php?car=<?php echo urlencode($car["name"]); ?>" class="action-red-full">Ask Availability</a>
                <?php else: ?>
                    <a href="test_drive.php?car=<?php echo urlencode($car["name"]); ?>" class="action-main">Book Test Drive</a>
                    <a href="loan_calculator.php?car=<?php echo urlencode($car["name"]); ?>&price=<?php echo $firstVariant["priceNumber"]; ?>" class="action-outline" id="loanCalcTop">Calculate Loan</a>
                    <a href="#variantCompare" class="action-outline">Compare Variant</a>
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
        <p>Choose a specification category. Details will update based on the selected variant.</p>
    </div>

    <div class="spec-tab-box">
        <div class="spec-tab-nav">
            <button class="spec-tab-btn active" onclick="openSpecTab('dimensions', this)">Dimensions & Capacity</button>
            <button class="spec-tab-btn" onclick="openSpecTab('engineDetails', this)">Engine Details</button>
            <button class="spec-tab-btn" onclick="openSpecTab('performance', this)">Performance</button>
            <button class="spec-tab-btn" onclick="openSpecTab('steering', this)">Steering</button>
            <button class="spec-tab-btn" onclick="openSpecTab('suspensionBrakes', this)">Suspension & Brakes</button>
            <button class="spec-tab-btn" onclick="openSpecTab('transmissionTab', this)">Transmission</button>
            <button class="spec-tab-btn" onclick="openSpecTab('wheelTyre', this)">Wheel & Tyre</button>
            <button class="spec-tab-btn" onclick="openSpecTab('bookingOwnership', this)">Booking & Ownership</button>
        </div>

        <div class="spec-tab-content active" id="dimensions">
            <table class="details-table" id="dimensionsTable"></table>
        </div>

        <div class="spec-tab-content" id="engineDetails">
            <table class="details-table" id="engineDetailsTable"></table>
        </div>

        <div class="spec-tab-content" id="performance">
            <table class="details-table" id="performanceTable"></table>
        </div>

        <div class="spec-tab-content" id="steering">
            <table class="details-table" id="steeringTable"></table>
        </div>

        <div class="spec-tab-content" id="suspensionBrakes">
            <table class="details-table" id="suspensionBrakesTable"></table>
        </div>

        <div class="spec-tab-content" id="transmissionTab">
            <table class="details-table" id="transmissionTable"></table>
        </div>

        <div class="spec-tab-content" id="wheelTyre">
            <table class="details-table" id="wheelTyreTable"></table>
        </div>

        <div class="spec-tab-content" id="bookingOwnership">
            <table class="details-table" id="bookingOwnershipTable"></table>
        </div>
    </div>
</section>

<section class="section features-section">
    <div class="section-title">
        <span class="detail-label">FEATURES</span>
        <h2>Features, Safety and Comfort</h2>
        <p>Choose a category to check what is included in the selected variant. Empty circle means that item is not included.</p>
    </div>

    <div class="feature-tab-box">
        <div class="feature-tab-nav">
            <button class="feature-tab-btn active" onclick="openFeatureTab('featurePanel', this)">Car Features</button>
            <button class="feature-tab-btn" onclick="openFeatureTab('safetyPanel', this)">Safety Features</button>
            <button class="feature-tab-btn" onclick="openFeatureTab('comfortPanel', this)">Comfort Features</button>
        </div>

        <div class="feature-tab-content active" id="featurePanel">
            <div class="feature-check-grid" id="featureList"></div>
        </div>

        <div class="feature-tab-content" id="safetyPanel">
            <div class="feature-check-grid" id="safetyList"></div>
        </div>

        <div class="feature-tab-content" id="comfortPanel">
            <div class="feature-check-grid" id="comfortList"></div>
        </div>
    </div>
</section>

<section class="section compare-variant-section" id="variantCompare">
    <div class="section-title">
        <span class="detail-label">VARIANT COMPARE</span>
        <h2>Compare Different Variants</h2>
        <p>Select two variants below to compare their configuration side by side.</p>
    </div>

    <div class="compare-variant-box">
        <div class="compare-select-row">
            <select id="compareVariantA" onchange="updateVariantCompare()">
                <?php foreach ($car["variants"] as $index => $variant): ?>
                    <option value="<?php echo $index; ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                        <?php echo $variant["name"]; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="compareVariantB" onchange="updateVariantCompare()">
                <?php foreach ($car["variants"] as $index => $variant): ?>
                    <option value="<?php echo $index; ?>" <?php echo $index === 1 ? 'selected' : ''; ?>>
                        <?php echo $variant["name"]; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="compare-table" id="variantCompareTable"></table>
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
                <p>Yes. The page updates the engine, engine code, horsepower, torque, transmission, fuel tank, suspension, brakes, tyres, features, safety and comfort details according to the selected variant.</p>
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
            <a href="booking.php">Car Booking</a>
            <a href="loan_application.php">Loan Application</a>
            <a href="contact.php">Customer Support</a>
        </div>

        <div>
            <h3>Contact</h3>
            <p>Email: support@toyotacarselling.com</p>
            <p>Phone: +60 12-345 6789</p>
            <p>Location: Malaysia</p>
        </div>
    </div>

    <div class="footer-bottom">
        © 2026 Toyota Car Selling System. All Rights Reserved.
    </div>
</footer>

<script>
    const variants = <?php echo json_encode($car["variants"]); ?>;
    const carInfo = <?php echo json_encode($car); ?>;
    const allFeatures = <?php echo json_encode($allFeatures); ?>;
    const allSafety = <?php echo json_encode($allSafety); ?>;
    const allComfort = <?php echo json_encode($allComfort); ?>;

    let currentVariantIndex = 0;

    function toggleMenu() {
        document.getElementById("navMenu").classList.toggle("show");
    }

    function selectColour(image, element) {
        document.getElementById("mainCarImage").src = image;

        const colourOptions = document.querySelectorAll(".colour-option");
        colourOptions.forEach(option => option.classList.remove("active"));
        element.classList.add("active");
    }

    function selectVariant(index, element) {
        currentVariantIndex = index;
        const selected = variants[index];

        const options = document.querySelectorAll(".variant-option");
        options.forEach(option => option.classList.remove("active"));
        element.classList.add("active");

        document.getElementById("selectedVariantPrice").textContent = selected.price;
        document.getElementById("selectedVariantMonthly").textContent = selected.monthly;

        document.getElementById("selectedCarLabel").textContent = selected.fuelType === "Hybrid" ? "Hybrid " + carInfo.type + " Choice" : carInfo.label.replace("Hybrid ", "");
        document.getElementById("selectedStatusPill").textContent = carInfo.status;
        document.getElementById("selectedStockPill").textContent = carInfo.stock;
        document.getElementById("imageStatusBadge").textContent = carInfo.status;
        document.getElementById("floatingStatus").textContent = carInfo.status;
        document.getElementById("floatingStock").textContent = carInfo.stock;

        document.getElementById("loanVariant").textContent = selected.name;
        document.getElementById("loanPrice").textContent = selected.price;
        document.getElementById("loanMonthly").textContent = selected.monthly;

        document.getElementById("loanCalcTop").href = "loan_calculator.php?car=" + encodeURIComponent(carInfo.name) + "&price=" + selected.priceNumber;
        document.getElementById("loanCalcBottom").href = "loan_calculator.php?car=" + encodeURIComponent(carInfo.name) + "&price=" + selected.priceNumber;

        updateSpecificationTables(selected);
        updateFeatureLists(selected);
        updateVariantCompare();
    }

    function tableRows(data) {
        let html = "";

        Object.keys(data).forEach(key => {
            html += `
                <tr>
                    <td>${key}</td>
                    <td>${data[key]}</td>
                </tr>
            `;
        });

        return html;
    }

    function updateSpecificationTables(selected) {
        document.getElementById("dimensionsTable").innerHTML = tableRows({
            "Body Type": carInfo.body,
            "Seating Capacity": carInfo.seats,
            "Vehicle Weight": selected.weight,
            "Wheelbase": selected.wheelbase,
            "Length": selected.length,
            "Width": selected.width,
            "Height": selected.height,
            "Ground Clearance": selected.groundClearance,
            "Boot / Cargo Capacity": selected.bootCapacity
        });

        document.getElementById("engineDetailsTable").innerHTML = tableRows({
            "Variant": selected.name,
            "Engine": selected.engine,
            "Engine Code": selected.engineCode,
            "Fuel Type": selected.fuelType,
            "Fuel Tank Capacity": selected.fuelTank,
            "Fuel Consumption": selected.fuelConsumption
        });

        document.getElementById("performanceTable").innerHTML = tableRows({
            "Variant": selected.name,
            "Engine": selected.engine,
            "Horsepower": selected.horsepower,
            "Torque": selected.torque,
            "Transmission": selected.transmission
        });

        document.getElementById("steeringTable").innerHTML = tableRows({
            "Steering System": selected.steering,
            "Turning Radius": selected.turningRadius,
            "Drivetrain": selected.drivetrain
        });

        document.getElementById("suspensionBrakesTable").innerHTML = tableRows({
            "Suspension": selected.suspension,
            "Brakes": selected.brakes,
            "Drivetrain": selected.drivetrain
        });

        document.getElementById("transmissionTable").innerHTML = tableRows({
            "Transmission": selected.transmission,
            "Drivetrain": selected.drivetrain,
            "Engine": selected.engine
        });

        document.getElementById("wheelTyreTable").innerHTML = tableRows({
            "Tyres / Wheels": selected.tyres,
            "Wheel Size": selected.wheelSize,
            "Brakes": selected.brakes
        });

        document.getElementById("bookingOwnershipTable").innerHTML = tableRows({
            "Vehicle Status": carInfo.status,
            "Stock Status": carInfo.stock,
            "Estimated Waiting Time": carInfo.waiting,
            "Booking Fee": carInfo.bookingFee,
            "Warranty": carInfo.warranty,
            "Selected Price": selected.price
        });
    }

    function featureItem(name, included) {
        return `<div class="feature-check-item ${included ? "" : "not-included"}">${name}</div>`;
    }

    function updateFeatureLists(selected) {
        let featureHTML = "";
        let safetyHTML = "";
        let comfortHTML = "";

        allFeatures.forEach(item => {
            featureHTML += featureItem(item, selected.features.includes(item));
        });

        allSafety.forEach(item => {
            safetyHTML += featureItem(item, selected.safety.includes(item));
        });

        allComfort.forEach(item => {
            comfortHTML += featureItem(item, selected.comfort.includes(item));
        });

        document.getElementById("featureList").innerHTML = featureHTML;
        document.getElementById("safetyList").innerHTML = safetyHTML;
        document.getElementById("comfortList").innerHTML = comfortHTML;
    }

    function openSpecTab(tabId, button) {
        const tabs = document.querySelectorAll(".spec-tab-content");
        const buttons = document.querySelectorAll(".spec-tab-btn");

        tabs.forEach(tab => tab.classList.remove("active"));
        buttons.forEach(btn => btn.classList.remove("active"));

        document.getElementById(tabId).classList.add("active");
        button.classList.add("active");
    }

    function openFeatureTab(tabId, button) {
        const tabs = document.querySelectorAll(".feature-tab-content");
        const buttons = document.querySelectorAll(".feature-tab-btn");

        tabs.forEach(tab => tab.classList.remove("active"));
        buttons.forEach(btn => btn.classList.remove("active"));

        document.getElementById(tabId).classList.add("active");
        button.classList.add("active");
    }

    function updateVariantCompare() {
        const indexA = document.getElementById("compareVariantA").value;
        const indexB = document.getElementById("compareVariantB").value;
        const a = variants[indexA];
        const b = variants[indexB];

        document.getElementById("variantCompareTable").innerHTML = `
            <tr>
                <th>Specification</th>
                <th>${a.name}</th>
                <th>${b.name}</th>
            </tr>
            <tr>
                <td>Selected Price</td>
                <td>${a.price}</td>
                <td>${b.price}</td>
            </tr>
            <tr>
                <td>Monthly Estimate</td>
                <td>${a.monthly}</td>
                <td>${b.monthly}</td>
            </tr>
            <tr>
                <td>Engine</td>
                <td>${a.engine}</td>
                <td>${b.engine}</td>
            </tr>
            <tr>
                <td>Engine Code</td>
                <td>${a.engineCode}</td>
                <td>${b.engineCode}</td>
            </tr>
            <tr>
                <td>Horsepower</td>
                <td>${a.horsepower}</td>
                <td>${b.horsepower}</td>
            </tr>
            <tr>
                <td>Torque</td>
                <td>${a.torque}</td>
                <td>${b.torque}</td>
            </tr>
            <tr>
                <td>Transmission</td>
                <td>${a.transmission}</td>
                <td>${b.transmission}</td>
            </tr>
            <tr>
                <td>Fuel Type</td>
                <td>${a.fuelType}</td>
                <td>${b.fuelType}</td>
            </tr>
            <tr>
                <td>Fuel Tank</td>
                <td>${a.fuelTank}</td>
                <td>${b.fuelTank}</td>
            </tr>
            <tr>
                <td>Fuel Consumption</td>
                <td>${a.fuelConsumption}</td>
                <td>${b.fuelConsumption}</td>
            </tr>
            <tr>
                <td>Drivetrain</td>
                <td>${a.drivetrain}</td>
                <td>${b.drivetrain}</td>
            </tr>
            <tr>
                <td>Suspension</td>
                <td>${a.suspension}</td>
                <td>${b.suspension}</td>
            </tr>
            <tr>
                <td>Brakes</td>
                <td>${a.brakes}</td>
                <td>${b.brakes}</td>
            </tr>
            <tr>
                <td>Tyres / Wheels</td>
                <td>${a.tyres}</td>
                <td>${b.tyres}</td>
            </tr>
            <tr>
                <td>Wheel Size</td>
                <td>${a.wheelSize}</td>
                <td>${b.wheelSize}</td>
            </tr>
            <tr>
                <td>Vehicle Weight</td>
                <td>${a.weight}</td>
                <td>${b.weight}</td>
            </tr>
            <tr>
                <td>Wheelbase</td>
                <td>${a.wheelbase}</td>
                <td>${b.wheelbase}</td>
            </tr>
            <tr>
                <td>Airbags</td>
                <td>${a.airbags}</td>
                <td>${b.airbags}</td>
            </tr>
            <tr>
                <td>USB Ports</td>
                <td>${a.usbPorts}</td>
                <td>${b.usbPorts}</td>
            </tr>
        `;
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

    updateSpecificationTables(variants[0]);
    updateFeatureLists(variants[0]);
    updateVariantCompare();
</script>

</body>
</html>