<?php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

// --- 核心逻辑：查询正在出租的车，并抓取 pickup_state (取车州属) ---
$sql = "SELECT b.booking_reference, u.name as customer_name, c.car_name, COALESCE(c.brand, br.brand_name) AS brand, c.car_id, bi.pickup_location, COALESCE(bi.pickup_state, rs.state_name) AS pickup_state
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN booking_items bi ON b.booking_id = bi.booking_id
        JOIN cars c ON bi.car_id = c.car_id
        LEFT JOIN brands br ON br.brand_id = c.brand_id
        LEFT JOIN rental_states rs ON rs.state_id = bi.pickup_state_id
        WHERE b.booking_status IN ('approved', 'active')";
$active_cars = $conn->query($sql);

$tracking_data = [];
if($active_cars && $active_cars->num_rows > 0) {
    while($row = $active_cars->fetch_assoc()) {
        $tracking_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Fleet Tracking | Fleet Command</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { primary: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 900: '#1e3a8a' } }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; overflow: hidden; } 
        #map { height: 100vh; width: 100%; z-index: 10; background: #f8fafc; }
        
        /* 极简白透玻璃面板 */
        .hud-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .leaflet-control-attribution { display: none; }
        .leaflet-bar { border: none !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important; }
        .leaflet-bar a { background-color: rgba(255, 255, 255, 0.9) !important; color: #3b82f6 !important; border-bottom: 1px solid rgba(226,232,240,0.8) !important; }

        /* 品牌蓝雷达动画图标 */
        .radar-marker {
            width: 24px; height: 24px;
            background-color: rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            position: relative;
            transition: all 1s linear; 
        }
        .radar-core {
            width: 10px; height: 10px;
            background-color: #3b82f6;
            border-radius: 50%;
            box-shadow: 0 0 10px #3b82f6, 0 0 20px #3b82f6;
            z-index: 2;
        }
        .radar-pulse {
            position: absolute; width: 100%; height: 100%;
            background-color: #3b82f6; border-radius: 50%;
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes ping { 75%, 100% { transform: scale(3.5); opacity: 0; } }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: rgba(203, 213, 225, 0.8); border-radius: 10px; }
    </style>
</head>

<body class="text-slate-800 antialiased h-screen flex overflow-hidden">

    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 relative w-full h-full">
        
        <div id="map"></div>

        <div class="absolute top-6 left-6 z-[400] hud-panel rounded-[2rem] p-6 w-[380px] flex flex-col max-h-[90vh]">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse shadow-[0_0_10px_#3b82f6]"></div>
                        <h1 class="text-xl font-black text-slate-900 uppercase tracking-widest">Live GPS Monitor</h1>
                    </div>
                    <p class="text-xs font-bold text-slate-500">Tracking fleet in assigned states</p>
                </div>
                <a href="dashboard.php" class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 hover:bg-red-50 hover:text-red-500 transition-all border border-slate-200">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Active Signals</h4>
                    <div class="text-3xl font-black text-blue-600 leading-none"><?php echo count($tracking_data); ?></div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Network Status</h4>
                    <div class="text-sm font-black text-emerald-500 mt-2 flex items-center gap-2"><i class="fas fa-satellite-dish"></i> Secure</div>
                </div>
            </div>

            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 ml-2 border-b border-slate-100 pb-2">Target Units</h4>
            <div class="flex-1 overflow-y-auto pr-2 space-y-3" id="vehicle-list">
                </div>
        </div>
    </main>

    <script>
        const activeFleet = <?php echo json_encode($tracking_data); ?>;
        
        const map = L.map('map', { zoomControl: false }).setView([3.1, 101.6], 7);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // 【核心修改】替换为浅色版的 CartoDB Positron 极简白地图
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(map);

        const radarIcon = L.divIcon({
            className: '',
            html: '<div class="radar-marker"><div class="radar-pulse"></div><div class="radar-core"></div></div>',
            iconSize: [24, 24], iconAnchor: [12, 12]
        });

        const fleetMarkers = {};
        const vehicleListEl = document.getElementById('vehicle-list');

        if(activeFleet.length === 0) {
            vehicleListEl.innerHTML = '<div class="text-center py-10 text-slate-400 font-bold text-sm"><i class="fas fa-satellite text-3xl mb-3 opacity-30"></i><br>No active fleet signals detected.</div>';
        }

        const stateCoords = {
            'Johor': { lat: 1.4927, lng: 103.7414 },
            'Melaka': { lat: 2.1896, lng: 102.2501 },
            'Malacca': { lat: 2.1896, lng: 102.2501 },
            'Kuala Lumpur': { lat: 3.1390, lng: 101.6869 },
            'Selangor': { lat: 3.0738, lng: 101.5183 },
            'Penang': { lat: 5.4141, lng: 100.3288 },
            'Perak': { lat: 4.5921, lng: 101.0901 },
            'Pahang': { lat: 4.8118, lng: 100.8666 }
        };

        activeFleet.forEach((car, index) => {
            let stateName = car.pickup_state ? car.pickup_state.trim() : 'Johor';
            let center = stateCoords[stateName] || stateCoords['Johor'];
            
            let baseLat = center.lat + (Math.random() - 0.5) * 0.05; 
            let baseLng = center.lng + (Math.random() - 0.5) * 0.05;

            let velocityLat = (Math.random() - 0.5) * 0.0004;
            let velocityLng = (Math.random() - 0.5) * 0.0004;

            const marker = L.marker([baseLat, baseLng], {icon: radarIcon}).addTo(map);
            
            // 弹窗也调整为浅色UI
            marker.bindPopup(`
                <div class="p-1 min-w-[200px] text-slate-800">
                    <div class="text-[9px] font-black uppercase tracking-widest text-blue-500 mb-1">Unit #${car.booking_reference}</div>
                    <div class="font-black text-slate-900 text-base leading-tight">${car.brand} ${car.car_name}</div>
                    <div class="text-[10px] font-bold text-slate-500 mt-1 uppercase"><i class="fas fa-map-marker-alt"></i> Zone: ${stateName}</div>
                    <div class="text-xs font-bold text-slate-600 mt-1 mb-3"><i class="fas fa-user-circle"></i> Driver: ${car.customer_name}</div>
                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-2 flex justify-between items-center shadow-sm">
                        <span class="text-[10px] font-bold uppercase text-slate-500">Speed</span>
                        <span class="font-black text-blue-600" id="speed-popup-${index}">0 km/h</span>
                    </div>
                </div>
            `);

            fleetMarkers[index] = { 
                marker: marker, lat: baseLat, lng: baseLng, 
                centerLat: center.lat, centerLng: center.lng,
                vLat: velocityLat, vLng: velocityLng,
                carInfo: car 
            };

            // 列表调整为白底高对比度
            vehicleListEl.innerHTML += `
                <div class="bg-white border border-slate-200 rounded-2xl p-4 hover:bg-slate-50 transition-colors cursor-pointer group shadow-sm" onclick="focusCar(${index})">
                    <div class="flex justify-between items-start mb-2">
                        <h5 class="font-black text-slate-800 text-sm group-hover:text-blue-600 transition-colors"><i class="fas fa-car-side text-slate-400 mr-2"></i>${car.brand} ${car.car_name}</h5>
                        <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[9px] font-black tracking-widest border border-blue-100 shadow-sm">ONLINE</span>
                    </div>
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><i class="fas fa-map-pin mr-1"></i> ${stateName}</span>
                        <span class="text-xs font-black text-blue-600 bg-blue-50/50 border border-blue-100 px-2 py-1 rounded" id="speed-sidebar-${index}">-- km/h</span>
                    </div>
                </div>
            `;
        });

        setInterval(() => {
            Object.keys(fleetMarkers).forEach(index => {
                let fd = fleetMarkers[index];
                
                fd.lat += fd.vLat;
                fd.lng += fd.vLng;

                if(Math.abs(fd.lat - fd.centerLat) > 0.08) fd.vLat *= -1; 
                if(Math.abs(fd.lng - fd.centerLng) > 0.08) fd.vLng *= -1;

                fd.vLat += (Math.random() - 0.5) * 0.0001;
                fd.vLng += (Math.random() - 0.5) * 0.0001;

                fd.marker.setLatLng([fd.lat, fd.lng]);

                let fakeSpeed = Math.floor(Math.random() * (110 - 40 + 1)) + 40;
                
                let sidebarSpeed = document.getElementById(`speed-sidebar-${index}`);
                if(sidebarSpeed) sidebarSpeed.innerText = fakeSpeed + ' km/h';

                let popupSpeed = document.getElementById(`speed-popup-${index}`);
                if(popupSpeed) popupSpeed.innerText = fakeSpeed + ' km/h';
            });
        }, 2000); 

        window.focusCar = function(index) {
            let data = fleetMarkers[index];
            map.flyTo([data.lat, data.lng], 15, { duration: 1.5 });
            data.marker.openPopup();
        }
    </script>
</body>
</html>
