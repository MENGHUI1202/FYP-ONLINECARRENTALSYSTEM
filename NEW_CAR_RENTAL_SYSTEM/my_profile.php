<?php
require_once "config.php";

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function strongPassword($p){
    return strlen($p) >= 10
        && preg_match("/[A-Z]/",$p)
        && preg_match("/[a-z]/",$p)
        && preg_match("/[0-9]/",$p)
        && preg_match("/[^A-Za-z0-9]/",$p);
}

function tableExists($conn,$table){
    $stmt=$conn->prepare("SELECT COUNT(*) total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $stmt->bind_param("s",$table);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row["total"] > 0;
}

function columnExists($conn,$table,$column){
    $stmt=$conn->prepare("SELECT COUNT(*) total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $stmt->bind_param("ss",$table,$column);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row["total"] > 0;
}

function firstColumn($conn,$table,$columns,$fallback=null){
    foreach($columns as $column){
        if(columnExists($conn,$table,$column)) return $column;
    }
    return $fallback;
}

function getKycRequirementStatus($conn,$userId){
    $required=[
        "IC Photo"=>"IC document",
        "Driving License Photo"=>"Driving license document"
    ];
    $status=[
        "state"=>"unverified",
        "missing"=>[],
        "pending"=>[],
        "rejected"=>[],
        "verified"=>[],
        "items"=>[]
    ];

    if(!tableExists($conn,"user_documents")){
        $status["missing"]=array_values($required);
        return $status;
    }

    foreach($required as $type=>$label){
        $stmt=$conn->prepare("SELECT document_id, document_type, verification_status, admin_note, uploaded_at FROM user_documents WHERE user_id=? AND document_type=? AND TRIM(COALESCE(file_path,''))<>'' ORDER BY uploaded_at DESC, document_id DESC LIMIT 1");
        if(!$stmt){
            $status["missing"][]=$label;
            continue;
        }
        $stmt->bind_param("is",$userId,$type);
        $stmt->execute();
        $doc=$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$doc){
            $status["missing"][]=$label;
            $status["items"][$type]=["label"=>$label,"status"=>"Not Uploaded","admin_note"=>"","uploaded_at"=>""];
            continue;
        }

        $docStatus=(string)($doc["verification_status"] ?? "Pending Verification");
        $status["items"][$type]=[
            "label"=>$label,
            "status"=>$docStatus,
            "admin_note"=>$doc["admin_note"] ?? "",
            "uploaded_at"=>$doc["uploaded_at"] ?? ""
        ];

        if(strcasecmp($docStatus,"Verified")===0){
            $status["verified"][]=$label;
        }elseif(strcasecmp($docStatus,"Rejected")===0){
            $status["rejected"][]=$label;
        }else{
            $status["pending"][]=$label;
        }
    }

    if(!empty($status["rejected"])){
        $status["state"]="rejected";
    }elseif(!empty($status["missing"])){
        $status["state"]="missing";
    }elseif(!empty($status["pending"])){
        $status["state"]="pending";
    }else{
        $status["state"]="verified";
    }

    return $status;
}

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

$user_id=(int)$_SESSION["user_id"];
$success="";
$errors=[];
$password_errors=[];
$activeTab=$_GET["tab"] ?? "profile";

$stmt=$conn->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$user){
    header("Location: logout.php");
    exit;
}

if(($_SERVER["REQUEST_METHOD"] ?? "")==="POST" && ($_POST["action"] ?? "")==="update_profile"){
    $activeTab="edit";
    $name=trim($_POST["name"] ?? "");
    $phone=trim($_POST["phone"] ?? "");
    $address=trim($_POST["address"] ?? "");
    $license_expiry_date=trim($_POST["license_expiry_date"] ?? "");
    $profile_picture=$user["profile_picture"] ?? null;

    if($name==="") $errors[]="Full Name is required.";
    if($phone==="") $errors[]="Phone Number is required.";
    elseif(!preg_match("/^\\+?\\d{10,13}$/",preg_replace("/[\\s-]/","",$phone))) $errors[]="Phone Number must be 10 to 13 digits. You may include + for country code.";
    if($address==="") $errors[]="Address is required.";
    if($license_expiry_date==="") $errors[]="License Expiry Date is required.";
    elseif(strtotime($license_expiry_date) < strtotime("+6 months")) $errors[]="License Expiry Date must be at least 6 months from today.";

    if(!empty($_POST["remove_profile_picture"])){
        $profile_picture=null;
    }

    if(!empty($_FILES["profile_picture"]["name"])){
        $uploadDir=__DIR__."/assets/uploads/profile/";
        if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

        $tmp=$_FILES["profile_picture"]["tmp_name"];
        $type=mime_content_type($tmp);
        $size=$_FILES["profile_picture"]["size"];
        $allowed=["image/jpeg","image/png","image/webp"];

        if(!in_array($type,$allowed,true)){
            $errors[]="Profile picture must be JPG, PNG or WEBP.";
        }elseif($size > 2*1024*1024){
            $errors[]="Profile picture must be less than 2MB.";
        }else{
            $ext=strtolower(pathinfo($_FILES["profile_picture"]["name"],PATHINFO_EXTENSION));
            $file="profile_".$user_id."_".time().".".$ext;
            if(move_uploaded_file($tmp,$uploadDir.$file)){
                $profile_picture="assets/uploads/profile/".$file;
            }else{
                $errors[]="Profile picture upload failed.";
            }
        }
    }

    if(empty($errors)){
        $stmt=$conn->prepare("UPDATE users SET name=?, phone=?, address=?, profile_picture=?, license_expiry_date=? WHERE user_id=?");
        $stmt->bind_param("sssssi",$name,$phone,$address,$profile_picture,$license_expiry_date,$user_id);
        if($stmt->execute()){
            $_SESSION["user_name"]=$name;
            $_SESSION["profile_picture"]=$profile_picture;
            $success="Profile updated successfully.";
        }else{
            $errors[]="Profile update failed. Please try again.";
        }
        $stmt->close();
    }
}

if(($_SERVER["REQUEST_METHOD"] ?? "")==="POST" && ($_POST["action"] ?? "")==="change_password"){
    $activeTab="security";
    $current=$_POST["current_password"] ?? "";
    $new=$_POST["new_password"] ?? "";
    $confirm=$_POST["confirm_new_password"] ?? "";

    if($current==="") $password_errors[]="Current Password is required.";
    elseif(!password_verify($current,$user["password"])) $password_errors[]="Current Password is incorrect.";

    if($new==="") $password_errors[]="New Password is required.";
    elseif(!strongPassword($new)) $password_errors[]="New Password must be at least 10 characters long and include uppercase letter, lowercase letter, number and special symbol.";

    if($confirm==="") $password_errors[]="Confirm New Password is required.";
    elseif($new!==$confirm) $password_errors[]="Confirm New Password must match New Password.";

    if(empty($password_errors)){
        $hash=password_hash($new,PASSWORD_DEFAULT);
        $stmt=$conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param("si",$hash,$user_id);
        if($stmt->execute()) $success="Password updated successfully.";
        else $password_errors[]="Password update failed. Please try again.";
        $stmt->close();
    }
}

if(($_SERVER["REQUEST_METHOD"] ?? "")==="POST" && ($_POST["action"] ?? "")==="save_emergency"){
    $activeTab="emergency";
    $contact_name=trim($_POST["contact_name"] ?? "");
    $contact_phone=trim($_POST["contact_phone"] ?? "");
    $relationship=trim($_POST["relationship"] ?? "");

    if($contact_name==="") $errors[]="Emergency Contact Name is required.";
    if($contact_phone==="") $errors[]="Emergency Contact Phone is required.";
    if($relationship==="") $errors[]="Relationship is required.";

    if(empty($errors) && tableExists($conn,"emergency_contacts")){
        $stmt=$conn->prepare("SELECT emergency_id FROM emergency_contacts WHERE user_id=? LIMIT 1");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $existing=$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($existing){
            $stmt=$conn->prepare("UPDATE emergency_contacts SET contact_name=?, contact_phone=?, relationship=?, updated_at=NOW() WHERE user_id=?");
            $stmt->bind_param("sssi",$contact_name,$contact_phone,$relationship,$user_id);
        }else{
            $stmt=$conn->prepare("INSERT INTO emergency_contacts (user_id, contact_name, contact_phone, relationship, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())");
            $stmt->bind_param("isss",$user_id,$contact_name,$contact_phone,$relationship);
        }

        if($stmt->execute()) $success="Emergency contact saved successfully.";
        else $errors[]="Emergency contact save failed.";
        $stmt->close();
    }
}

if(($_SERVER["REQUEST_METHOD"] ?? "")==="POST" && ($_POST["action"] ?? "")==="upload_document"){
    $activeTab="documents";
    $doc_type=trim($_POST["document_type"] ?? "");

    if($doc_type==="") $errors[]="Document type is required.";
    if(empty($_FILES["document_file"]["name"])) $errors[]="Document file is required.";

    if(empty($errors) && tableExists($conn,"user_documents")){
        $uploadDir=__DIR__."/assets/uploads/documents/";
        if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

        $tmp=$_FILES["document_file"]["tmp_name"];
        $type=mime_content_type($tmp);
        $size=$_FILES["document_file"]["size"];
        $allowed=["image/jpeg","image/png","image/webp","application/pdf"];

        if(!in_array($type,$allowed,true)){
            $errors[]="Document must be JPG, PNG, WEBP or PDF.";
        }elseif($size > 5*1024*1024){
            $errors[]="Document must be less than 5MB.";
        }else{
            $ext=strtolower(pathinfo($_FILES["document_file"]["name"],PATHINFO_EXTENSION));
            $file="doc_".$user_id."_".preg_replace("/[^A-Za-z0-9]/","_",$doc_type)."_".time().".".$ext;

            if(move_uploaded_file($tmp,$uploadDir.$file)){
                $file_path="assets/uploads/documents/".$file;

                $stmt=$conn->prepare("INSERT INTO user_documents (user_id, document_type, file_path, verification_status, uploaded_at) VALUES (?,?,?,'Pending Verification',NOW())");
                $stmt->bind_param("iss",$user_id,$doc_type,$file_path);

                if($stmt->execute()){
                    $success="Document uploaded successfully. Please wait for admin verification before payment.";
                    $sync=$conn->prepare("UPDATE users SET kyc_status='Pending' WHERE user_id=? AND kyc_status<>'Verified'");
                    if($sync){
                        $sync->bind_param("i",$user_id);
                        $sync->execute();
                        $sync->close();
                    }
                }else{
                    $errors[]="Document upload failed.";
                }

                $stmt->close();
            }else{
                $errors[]="Document upload failed.";
            }
        }
    }
}


if(($_SERVER["REQUEST_METHOD"] ?? "")==="POST" && ($_POST["action"] ?? "")==="cancel_booking"){
    $activeTab="bookings";
    $booking_id=(int)($_POST["booking_id"] ?? 0);

    if($booking_id <= 0){
        $errors[]="Invalid booking selected.";
    }elseif(!tableExists($conn,"bookings")){
        $errors[]="Booking table is not available.";
    }else{
        $bookingIdCol=firstColumn($conn,"bookings",["booking_id","id"],"booking_id");
        $statusCol=firstColumn($conn,"bookings",["booking_status","status"],null);

        $stmt=$conn->prepare("SELECT * FROM bookings WHERE $bookingIdCol=? AND user_id=? LIMIT 1");
        $stmt->bind_param("ii",$booking_id,$user_id);
        $stmt->execute();
        $cancelBooking=$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$cancelBooking){
            $errors[]="Booking record not found.";
        }else{
            $pickupDateTime=null;

            if(tableExists($conn,"booking_items") && columnExists($conn,"booking_items","booking_id") && columnExists($conn,"booking_items","start_datetime")){
                $stmt=$conn->prepare("SELECT MIN(start_datetime) AS pickup_datetime FROM booking_items WHERE booking_id=?");
                $stmt->bind_param("i",$booking_id);
                $stmt->execute();
                $row=$stmt->get_result()->fetch_assoc();
                $stmt->close();
                $pickupDateTime=$row["pickup_datetime"] ?? null;
            }

            $canCancel=true;

            if($pickupDateTime){
                $seconds=strtotime($pickupDateTime)-time();
                if($seconds < 48*60*60){
                    $canCancel=false;
                }
            }

            $currentStatus=strtolower($cancelBooking[$statusCol] ?? "");
            if(str_contains($currentStatus,"complete") || str_contains($currentStatus,"reject") || str_contains($currentStatus,"cancel")){
                $canCancel=false;
            }

            if(!$statusCol){
                $errors[]="Booking status column is not available.";
            }elseif(!$canCancel){
                $errors[]="This booking cannot be cancelled. Cancellation is not allowed within 48 hours before pickup or after completion / rejection / cancellation.";
            }else{
                $newStatus="Cancellation Requested";
                $stmt=$conn->prepare("UPDATE bookings SET $statusCol=? WHERE $bookingIdCol=? AND user_id=?");
                $stmt->bind_param("sii",$newStatus,$booking_id,$user_id);

                if($stmt->execute()){
                    $success="Cancellation request submitted successfully.";
                }else{
                    $errors[]="Cancellation request failed. Please try again.";
                }

                $stmt->close();
            }
        }
    }
}

$stmt=$conn->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();
$stmt->close();

$activeCartVoucher=$_SESSION["cart_voucher"] ?? null;
$activeCartPromoId=is_array($activeCartVoucher) ? (int)($activeCartVoucher["promo_id"] ?? 0) : 0;
$activeCartPromoCode=is_array($activeCartVoucher) ? strtoupper(trim((string)($activeCartVoucher["promo_code"] ?? ""))) : "";

$vouchers=[];
if(tableExists($conn,"promo_codes")){
    $promoIdCol=firstColumn($conn,"promo_codes",["promo_id","id"],"id");
    $promoCodeCol=firstColumn($conn,"promo_codes",["promo_code","code"],"promo_code");
    $promoNameCol=firstColumn($conn,"promo_codes",["promo_name","name"],null);
    $promoDescCol=firstColumn($conn,"promo_codes",["description","promo_description"],null);
    $discountCol=firstColumn($conn,"promo_codes",["discount_percent","discount_percentage"],"discount_percent");
    $statusCol=firstColumn($conn,"promo_codes",["status"],null);
    $validFromCol=firstColumn($conn,"promo_codes",["valid_from","start_date"],null);
    $validToCol=firstColumn($conn,"promo_codes",["valid_to","end_date"],null);

    $nameSelect=$promoNameCol ? "pc.$promoNameCol" : "pc.$promoCodeCol";
    $descSelect=$promoDescCol ? "pc.$promoDescCol" : "'Special rental promotion for KH Car Rental customers.'";
    $validFromSelect=$validFromCol ? "pc.$validFromCol" : "NULL";
    $validToSelect=$validToCol ? "pc.$validToCol" : "NULL";
    $wherePromo=$statusCol ? "WHERE LOWER(pc.$statusCol)='active'" : "";
    if(columnExists($conn,"promo_codes","deleted_at")){
        $wherePromo .= ($wherePromo ? " AND " : " WHERE ") . "pc.deleted_at IS NULL";
    }
    $assignmentFilter="";
    if(tableExists($conn,"promo_code_assignments")){
        $assignmentFilter=($wherePromo ? " AND " : " WHERE ") . "
            (
                NOT EXISTS (SELECT 1 FROM promo_code_assignments pca_all WHERE pca_all.promo_id=pc.$promoIdCol)
                OR EXISTS (
                    SELECT 1 FROM promo_code_assignments pca_user
                    WHERE pca_user.promo_id=pc.$promoIdCol
                    AND pca_user.user_id=?
                    AND LOWER(COALESCE(pca_user.status,'active'))='active'
                )
            )";
    }

    if(tableExists($conn,"promo_code_usage")){
        $usagePromoCol=firstColumn($conn,"promo_code_usage",["promo_id"],"promo_id");
        $usageUserCol=firstColumn($conn,"promo_code_usage",["user_id"],"user_id");
        $usageUsedAtCol=firstColumn($conn,"promo_code_usage",["used_at","created_at"],"used_at");

        $stmt=$conn->prepare("
            SELECT
                pc.$promoIdCol AS promo_id,
                pc.$promoCodeCol AS promo_code,
                $nameSelect AS promo_name,
                $descSelect AS description,
                pc.$discountCol AS discount_percent,
                $validFromSelect AS valid_from,
                $validToSelect AS valid_to,
                MAX(pcu.$usageUsedAtCol) AS used_at
            FROM promo_codes pc
            LEFT JOIN promo_code_usage pcu
                ON pcu.$usagePromoCol=pc.$promoIdCol
                AND pcu.$usageUserCol=?
            $wherePromo
            $assignmentFilter
            GROUP BY pc.$promoIdCol
            ORDER BY pc.$discountCol DESC, pc.$promoIdCol ASC
            LIMIT 6
        ");
        if($assignmentFilter){
            $stmt->bind_param("ii",$user_id,$user_id);
        }else{
            $stmt->bind_param("i",$user_id);
        }
    }else{
        $stmt=$conn->prepare("
            SELECT
                pc.$promoIdCol AS promo_id,
                pc.$promoCodeCol AS promo_code,
                $nameSelect AS promo_name,
                $descSelect AS description,
                pc.$discountCol AS discount_percent,
                $validFromSelect AS valid_from,
                $validToSelect AS valid_to,
                NULL AS used_at
            FROM promo_codes pc
            $wherePromo
            $assignmentFilter
            ORDER BY pc.$discountCol DESC, pc.$promoIdCol ASC
            LIMIT 6
        ");
        if($assignmentFilter){
            $stmt->bind_param("i",$user_id);
        }
    }

    if($stmt){
        $stmt->execute();
        $vouchers=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$bookings=[];
if(tableExists($conn,"bookings")){
    $bookingIdCol=firstColumn($conn,"bookings",["booking_id","id"],"booking_id");
    $referenceCol=firstColumn($conn,"bookings",["booking_reference","reference_no","booking_no"],null);
    $pickupCol=firstColumn($conn,"bookings",["pickup_location"],null);
    $returnCol=firstColumn($conn,"bookings",["return_location","dropoff_location","drop_off_location"],null);
    $totalCol=firstColumn($conn,"bookings",["grand_total","total_amount","total_price"],null);
    $bookingStatusCol=firstColumn($conn,"bookings",["booking_status","status"],null);
    $paymentStatusCol=firstColumn($conn,"bookings",["payment_status"],null);
    $createdCol=firstColumn($conn,"bookings",["created_at","booking_date"],null);

    $selects=["b.*","b.$bookingIdCol AS dashboard_booking_id"];
    if(tableExists($conn,"booking_items") && tableExists($conn,"cars") && columnExists($conn,"booking_items","booking_id") && columnExists($conn,"booking_items","car_id")){
        $carIdCol=firstColumn($conn,"cars",["car_id","id"],"car_id");
        $carNameCol=firstColumn($conn,"cars",["car_name","name"],"car_name");
        $selects[]="GROUP_CONCAT(DISTINCT c.$carNameCol SEPARATOR ', ') AS car_names";
        $selects[]="MIN(bi.start_datetime) AS pickup_datetime";
        $selects[]="MAX(bi.end_datetime) AS return_datetime";

        $locationJoin="";
        if(tableExists($conn,"rental_locations")){
            $selects[]="GROUP_CONCAT(DISTINCT COALESCE(pl.location_name, bi.pickup_location) SEPARATOR ', ') AS pickup_location_names";
            $selects[]="GROUP_CONCAT(DISTINCT COALESCE(dl.location_name, bi.dropoff_location) SEPARATOR ', ') AS dropoff_location_names";
            $locationJoin="
            LEFT JOIN rental_locations pl ON pl.location_id=bi.pickup_location
            LEFT JOIN rental_locations dl ON dl.location_id=bi.dropoff_location";
        }else{
            $selects[]="GROUP_CONCAT(DISTINCT bi.pickup_location SEPARATOR ', ') AS pickup_location_names";
            $selects[]="GROUP_CONCAT(DISTINCT bi.dropoff_location SEPARATOR ', ') AS dropoff_location_names";
        }

        $sql="
            SELECT ".implode(", ",$selects)."
            FROM bookings b
            LEFT JOIN booking_items bi ON bi.booking_id=b.$bookingIdCol
            LEFT JOIN cars c ON c.$carIdCol=bi.car_id
            $locationJoin
            WHERE b.user_id=?
            GROUP BY b.$bookingIdCol
            ORDER BY ".($createdCol ? "b.$createdCol" : "b.$bookingIdCol")." DESC
            LIMIT 10
        ";
    }else{
        $sql="
            SELECT ".implode(", ",$selects)."
            FROM bookings b
            WHERE b.user_id=?
            ORDER BY ".($createdCol ? "b.$createdCol" : "b.$bookingIdCol")." DESC
            LIMIT 10
        ";
    }

    $stmt=$conn->prepare($sql);
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $bookings=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$cartItems=[];
$cartTable=null;
foreach(["cart_items","cart"] as $t){
    if(tableExists($conn,$t)){ $cartTable=$t; break; }
}
if($cartTable){
    $stmt=$conn->prepare("SELECT * FROM $cartTable WHERE user_id=? ORDER BY 1 DESC LIMIT 8");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $cartItems=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$documents=[];
if(tableExists($conn,"user_documents")){
    $stmt=$conn->prepare("SELECT * FROM user_documents WHERE user_id=? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $documents=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$latestDocuments=[];
$documentHistory=[];
foreach($documents as $doc){
    $type=(string)($doc["document_type"] ?? "Document");
    if(!isset($latestDocuments[$type])){
        $latestDocuments[$type]=$doc;
    }else{
        $documentHistory[]=$doc;
    }
}

$emergency=null;
if(tableExists($conn,"emergency_contacts")){
    $stmt=$conn->prepare("SELECT * FROM emergency_contacts WHERE user_id=? LIMIT 1");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $emergency=$stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$recentViews=[];
if(tableExists($conn,"recent_viewed_cars") && tableExists($conn,"cars")){
    $carIdCol=firstColumn($conn,"cars",["car_id","id"],"car_id");
    $carNameCol=firstColumn($conn,"cars",["car_name","name"],"car_name");
    $imageCol=firstColumn($conn,"cars",["main_image","image"],null);

    $imageSelect=$imageCol ? "c.$imageCol AS car_image" : "NULL AS car_image";

    $stmt=$conn->prepare("
        SELECT rvc.*, c.$carNameCol AS car_name, $imageSelect
        FROM recent_viewed_cars rvc
        JOIN cars c ON c.$carIdCol=rvc.car_id
        WHERE rvc.user_id=?
        ORDER BY rvc.viewed_at DESC
        LIMIT 8
    ");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $recentViews=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$payments=[];
if(tableExists($conn,"payments")){
    $stmt=$conn->prepare("
        SELECT p.*, b.booking_reference
        FROM payments p
        LEFT JOIN bookings b ON b.booking_id=p.booking_id
        WHERE b.user_id=?
        ORDER BY p.payment_date DESC, p.payment_id DESC
        LIMIT 8
    ");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $payments=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$avatar=$user["profile_picture"] ?? "";
$initial=strtoupper(substr($user["name"] ?? "U",0,1));
$customerSince=!empty($user["created_at"]) ? date("d M Y",strtotime($user["created_at"])) : "-";
$dob=!empty($user["date_of_birth"]) ? date("d M Y",strtotime($user["date_of_birth"])) : "-";
$licenseExpiry=!empty($user["license_expiry_date"]) ? date("d M Y",strtotime($user["license_expiry_date"])) : "-";
$kycRequirement=getKycRequirementStatus($conn,$user_id);
$kycNeedsAttention=$kycRequirement["state"]!=="verified";
$kycMissingText=!empty($kycRequirement["missing"]) ? implode(" and ",$kycRequirement["missing"]) : "";
$kycPendingText=!empty($kycRequirement["pending"]) ? implode(" and ",$kycRequirement["pending"]) : "";
$kycRejectedText=!empty($kycRequirement["rejected"]) ? implode(" and ",$kycRequirement["rejected"]) : "";
$tabs=["profile","voucher","bookings","edit","documents","recent","emergency","payments","security"];
if(!in_array($activeTab,$tabs,true)) $activeTab="profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | KH Car Rental</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{
    --sky50:#f5fbff;--sky100:#eaf7ff;--sky200:#d6efff;--sky500:#28a8ea;--sky600:#1284c6;
    --dark:#17304f;--muted:#6e8297;--orange:#ff8a3d;--orange2:#f15f12;--green:#21b573;--danger:#ff4d4f;
    --border:#d8ecfb;--shadow:0 24px 70px rgba(39,137,199,.16);--soft:0 12px 35px rgba(39,137,199,.10);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
    min-height:100vh;font-family:"Segoe UI",Tahoma,sans-serif;color:var(--dark);
    background:
    radial-gradient(circle at 8% 0%,rgba(184,228,255,.8),transparent 32%),
    radial-gradient(circle at 95% 8%,rgba(214,239,255,.86),transparent 34%),
    linear-gradient(180deg,#fff 0%,var(--sky50) 42%,#fff 100%);
}
a{text-decoration:none;color:inherit}
button,input,textarea,select{font-family:inherit}
input[type="password"]::-ms-reveal,input[type="password"]::-ms-clear{display:none!important}

.topbar{
    position:sticky;top:0;z-index:20;padding:14px 28px;
    background:linear-gradient(135deg,rgba(215,244,255,.92),rgba(255,255,255,.96),rgba(236,248,255,.9));
    border-bottom:1px solid rgba(142,207,244,.42);backdrop-filter:blur(18px);box-shadow:0 14px 36px rgba(52,139,195,.08);
}
.nav{width:min(1440px,100%);margin:auto;display:flex;align-items:center;justify-content:space-between;gap:20px}
.brand,.nav-links,.nav-user{display:flex;align-items:center}
.brand{gap:13px;font-size:18px;font-weight:950}
.brand-logo,.side-avatar,.nav-avatar{
    display:grid;place-items:center;background:linear-gradient(135deg,#d8f2ff,#fff);color:var(--sky600);
    border:1px solid rgba(142,207,244,.46);box-shadow:0 14px 28px rgba(40,168,234,.13);
}
.brand-logo{width:48px;height:48px;border-radius:18px}
.nav-links{list-style:none;gap:8px}
.nav-links a{padding:11px 14px;border-radius:999px;font-size:13px;font-weight:950;color:#2b4969;transition:.24s}
.nav-links a:hover,.nav-links a.active{background:rgba(40,168,234,.1);color:var(--sky600);transform:translateY(-1px)}
.nav-user{gap:12px}
.nav-avatar{width:42px;height:42px;border-radius:50%;overflow:hidden;color:#fff;background:linear-gradient(135deg,var(--sky500),#0d3f82);border:3px solid #fff;font-weight:950}
.nav-avatar img,.side-avatar img,.car-thumb img{width:100%;height:100%;object-fit:cover}
.avatar-alert-wrap{position:relative;display:inline-grid;place-items:center}
.avatar-kyc-dot{position:absolute;right:-2px;top:-2px;width:13px;height:13px;border-radius:999px;background:#ff3b30;border:3px solid #fff;box-shadow:0 0 0 5px rgba(255,59,48,.12),0 10px 22px rgba(255,59,48,.25);z-index:4}
.avatar-kyc-dot:after{content:"";position:absolute;inset:-5px;border-radius:999px;background:rgba(255,59,48,.32);animation:kycPulse 1.6s infinite}
@keyframes kycPulse{0%{transform:scale(.6);opacity:.8}100%{transform:scale(1.7);opacity:0}}

.dashboard{
    width:min(1440px,100%);margin:0 auto;padding:28px;
    display:grid;grid-template-columns:330px 1fr;gap:24px;align-items:start;
}
.sidebar{
    position:sticky;top:102px;border-radius:34px;padding:24px;
    background:linear-gradient(160deg,rgba(255,255,255,.96),rgba(234,247,255,.92));
    border:1px solid rgba(184,228,255,.95);box-shadow:var(--shadow);overflow:hidden;
}
.sidebar:before{
    content:"";position:absolute;width:260px;height:260px;border-radius:50%;right:-94px;top:-100px;
    background:linear-gradient(135deg,rgba(40,168,234,.18),rgba(255,255,255,.1));
}
.side-profile{position:relative;z-index:1;text-align:center;padding-bottom:14px;border-bottom:1px solid var(--border);margin-bottom:12px}
.side-avatar{
    width:118px;height:118px;border-radius:34px;margin:0 auto 14px;overflow:hidden;color:#fff;
    background:linear-gradient(135deg,var(--sky500),#0d3f82);border:5px solid #fff;font-size:44px;font-weight:950;
    transform:translateZ(0);
    transition:.32s cubic-bezier(.2,.8,.2,1);
}
.side-avatar:hover{
    transform:translateY(-6px) scale(1.025);
    box-shadow:0 28px 58px rgba(40,168,234,.26);
}
.side-profile h2{font-size:26px;font-weight:950;letter-spacing:-.9px;margin-bottom:0;text-shadow:0 12px 32px rgba(23,48,79,.08)}
.side-profile:after{
    content:"";
    display:block;
    width:64px;
    height:4px;
    border-radius:999px;
    margin:15px auto 0;
    background:linear-gradient(90deg,var(--sky500),#0d3f82);
    box-shadow:0 12px 28px rgba(40,168,234,.22);
}

.side-menu{
    position:relative;
    z-index:1;
    display:grid;
    gap:10px;
    max-height:calc(100vh - 265px);
    overflow-y:auto;
    overflow-x:hidden;
    padding-right:4px;
    padding-bottom:0;
    scrollbar-width:thin;
    scrollbar-color:rgba(18,132,198,.28) transparent;
}
.side-menu::-webkit-scrollbar{width:5px}
.side-menu::-webkit-scrollbar-thumb{background:rgba(18,132,198,.28);border-radius:999px}
.side-menu button,.side-menu a{
    width:100%;border:0;text-align:left;padding:14px 15px;border-radius:17px;background:rgba(255,255,255,.66);
    color:#2b4969;font-size:13.5px;font-weight:950;display:flex;align-items:center;gap:11px;cursor:pointer;
    transition:.28s cubic-bezier(.2,.8,.2,1);
    border:1px solid rgba(216,236,251,.76);
    box-shadow:0 8px 20px rgba(40,168,234,.045);
    position:relative;
    overflow:hidden;
}
.side-menu button:before,.side-menu a:before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(120deg,transparent,rgba(255,255,255,.42),transparent);
    transform:translateX(-120%);
    transition:.45s;
}
.side-menu button:hover:before,.side-menu a:hover:before{
    transform:translateX(120%);
}
.side-menu button i,.side-menu a i{width:22px;color:var(--sky600);font-size:16px}
.kyc-dot{margin-left:auto;width:10px;height:10px;border-radius:999px;background:#ff4d4f;box-shadow:0 0 0 4px rgba(255,77,79,.12),0 10px 18px rgba(255,77,79,.25)}
.kyc-mini-label{margin-left:auto;padding:4px 8px;border-radius:999px;background:rgba(255,77,79,.1);color:#c92a2a;font-size:9px;font-weight:950;letter-spacing:.6px;text-transform:uppercase}
.side-menu button:hover,.side-menu button.active,.side-menu a:hover{
    background:linear-gradient(135deg,#2ab3f2,var(--sky600));color:#fff;transform:translateX(6px) translateY(-2px);box-shadow:0 18px 34px rgba(40,168,234,.22);
}
.side-menu button:hover i,.side-menu button.active i,.side-menu a:hover i{color:#fff}
.side-menu button:hover .kyc-mini-label,.side-menu button.active .kyc-mini-label{background:rgba(255,255,255,.22);color:#fff}
.side-menu .logout-link{background:rgba(255,138,61,.1);color:var(--orange2);border-color:rgba(255,138,61,.22)}
.side-menu .logout-link:hover{background:linear-gradient(135deg,#ff9a4a,#f15f12);color:#fff}

.main-content{min-width:0}
.panel{
    display:none;border-radius:34px;padding:34px;background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(244,251,255,.92));
    border:1px solid rgba(184,228,255,.95);box-shadow:var(--shadow);animation:show .25s ease;
}
.panel.active{display:block}
@keyframes show{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

.section-head{margin-bottom:24px}
.pill{
    display:inline-flex;align-items:center;gap:8px;width:fit-content;padding:9px 14px;border-radius:999px;
    background:rgba(40,168,234,.12);color:var(--sky600);border:1px solid rgba(40,168,234,.22);
    font-size:12px;font-weight:950;letter-spacing:.8px;text-transform:uppercase;margin-bottom:12px;
}
.section-head h1{font-size:clamp(34px,4.3vw,58px);line-height:1;letter-spacing:-2px;font-weight:950;margin-bottom:10px}
.section-head h2{font-size:34px;line-height:1.1;letter-spacing:-1.2px;font-weight:950;margin-bottom:8px}
.section-head p,.muted{color:var(--muted);font-weight:650;line-height:1.65}

.alert{display:flex;gap:12px;padding:14px 16px;border-radius:18px;margin-bottom:18px;font-size:14px;line-height:1.5;font-weight:700}
.alert-success{background:rgba(33,181,115,.1);border:1px solid rgba(33,181,115,.22);color:#087f5b}
.alert-danger{background:#fff5f5;border:1px solid rgba(255,77,79,.22);color:#c92a2a}
.alert-warning{background:#fff8e6;border:1px solid rgba(255,176,32,.35);color:#9a5b00}
.alert ul{margin-left:18px}

.stats-grid,.info-grid,.quick-grid,.mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.stats-grid{grid-template-columns:repeat(4,1fr);margin-bottom:22px}
.info-card,.booking-card,.voucher-card,.quick-card,.stat-card,.doc-card,.car-card,.payment-card,.cart-card,.security-card{
    border-radius:24px;background:rgba(255,255,255,.78);border:1px solid var(--border);box-shadow:var(--soft);
}
.stat-card{padding:18px}
.stat-card i{width:44px;height:44px;border-radius:16px;display:grid;place-items:center;background:var(--sky100);color:var(--sky600);margin-bottom:14px}
.stat-card small{display:block;color:var(--muted);font-weight:950;text-transform:uppercase;font-size:11px;letter-spacing:.6px;margin-bottom:4px}
.stat-card strong{font-size:22px;font-weight:950}

.info-card{display:flex;gap:14px;padding:18px}
.info-card i,.quick-icon,.doc-icon{
    width:46px;height:46px;min-width:46px;border-radius:17px;display:grid;place-items:center;color:var(--sky600);
    background:linear-gradient(135deg,#d8f2ff,#fff);border:1px solid var(--border);
}
.info-card small{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;font-weight:950;letter-spacing:.6px;margin-bottom:4px}
.info-card strong{font-size:15px;font-weight:950;word-break:break-word}
.info-card,.stat-card,.doc-card,.payment-card,.security-card,.cart-card,.booking-card,.voucher-card{
    transition:.28s cubic-bezier(.2,.8,.2,1);
}
.info-card:hover,.stat-card:hover,.doc-card:hover,.payment-card:hover,.security-card:hover,.voucher-card:hover{
    transform:translateY(-6px);
    box-shadow:0 24px 52px rgba(40,168,234,.16);
    border-color:rgba(40,168,234,.34);
}
.info-card:hover i,.quick-card:hover .quick-icon,.stat-card:hover i,.doc-card:hover .doc-icon{
    transform:scale(1.08) rotate(-3deg);
}
.info-card i,.quick-icon,.stat-card i,.doc-icon{
    transition:.28s cubic-bezier(.2,.8,.2,1);
}

.quick-grid{margin-top:22px}
.quick-card{padding:22px;transition:.25s}
.quick-card:hover,.booking-card:hover,.cart-card:hover,.car-card:hover{transform:translateY(-6px);box-shadow:0 22px 46px rgba(40,168,234,.16)}
.quick-icon{margin-bottom:14px}
.quick-card h3{font-size:19px;font-weight:950;margin-bottom:6px}

.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
.form-group.full{grid-column:1/-1}
.form-group label{display:block;font-size:12px;font-weight:950;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
.input-wrap{position:relative}
.input-wrap i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--sky600);font-size:14px;z-index:2}
.textarea-icon{top:22px!important}
.form-control{
    width:100%;min-height:52px;border:2px solid #e2f2ff;background:rgba(255,255,255,.82);color:var(--dark);
    border-radius:16px;padding:13px 15px 13px 42px;outline:none;font-size:14px;font-weight:750;transition:.24s;
}
textarea.form-control{min-height:105px;padding-top:15px;resize:vertical}
.form-control:focus{border-color:var(--sky500);box-shadow:0 0 0 .22rem rgba(40,168,234,.13);background:#fff}
.form-control[readonly]{background:rgba(244,248,252,.78);color:var(--muted);cursor:not-allowed}
.file-input{padding:13px 15px}
.password-wrap .form-control{padding-right:48px}
.password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:var(--muted);cursor:pointer;width:34px;height:34px;border-radius:10px;z-index:3}
.password-toggle:hover{background:var(--sky100);color:var(--sky600)}
.note{margin-top:7px;color:var(--muted);font-size:12px;line-height:1.45;font-weight:650}
.submit-btn,.mini-btn{
    display:inline-flex;align-items:center;justify-content:center;gap:9px;border:0;cursor:pointer;color:#fff;text-decoration:none;
    background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);font-weight:950;transition:.25s;box-shadow:0 18px 34px rgba(255,122,26,.28);
}
.submit-btn{min-height:52px;padding:0 24px;border-radius:18px;font-size:14px;margin-top:18px}
.submit-btn:hover,.mini-btn:hover{transform:translateY(-3px)}
.password-box{margin-top:12px;padding:14px;border-radius:18px;background:rgba(255,255,255,.72);border:1px solid var(--border)}
.password-box strong{display:block;font-size:12px;font-weight:950;margin-bottom:9px;text-transform:uppercase}
.requirement-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px 12px;list-style:none;color:var(--muted);font-size:12px;font-weight:650}
.requirement-list li{display:flex;align-items:center;gap:8px;line-height:1.25}
.requirement-list i{color:#a2b4c6}
.requirement-list li.pass,.requirement-list li.pass i{color:var(--green)}

.voucher-card{
    position:relative;overflow:hidden;padding:28px;margin-bottom:16px;
    background:radial-gradient(circle at 100% 0%,rgba(255,138,61,.22),transparent 30%),linear-gradient(135deg,rgba(255,255,255,.96),rgba(255,247,239,.92));
    border:1px solid rgba(255,138,61,.28);box-shadow:0 22px 52px rgba(255,122,26,.12);
}
.voucher-top{display:flex;justify-content:space-between;gap:16px;margin-bottom:18px}
.voucher-card h3{font-size:24px;font-weight:950;letter-spacing:-.8px;margin-bottom:5px}
.discount{min-width:96px;min-height:86px;border-radius:24px;display:grid;place-items:center;text-align:center;background:linear-gradient(135deg,#ff9a4a,#f15f12);color:#fff;font-size:30px;font-weight:950;box-shadow:0 18px 36px rgba(255,122,26,.28)}
.discount small{display:block;font-size:12px;letter-spacing:1.6px;margin-top:4px}
.voucher-code{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:18px;background:#fff;border:1px dashed rgba(255,122,26,.46);margin-bottom:16px}
.voucher-code span{color:var(--muted);font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.6px}
.voucher-code strong{color:var(--orange2);font-size:18px;font-weight:950;letter-spacing:1px}
.voucher-meta,.voucher-actions{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
.tag{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border-radius:999px;background:rgba(40,168,234,.1);color:var(--sky600);font-size:12px;font-weight:950}
.tag.available{background:rgba(33,181,115,.1);color:#087f5b}
.tag.used{background:rgba(108,117,125,.1);color:#6c757d}
.tag.warning{background:rgba(255,138,61,.12);color:var(--orange2)}
.tag.danger{background:rgba(255,77,79,.1);color:#c92a2a}
.mini-btn{padding:12px 16px;border-radius:16px;background:linear-gradient(135deg,var(--sky500),var(--sky600));font-size:13px}
.mini-btn.outline{background:#fff;color:var(--sky600);border:1px solid var(--border);box-shadow:var(--soft)}
.empty{padding:22px;border-radius:22px;border:1px dashed var(--border);background:rgba(255,255,255,.65);color:var(--muted);font-weight:650;line-height:1.6}

.list{display:grid;gap:14px}
.booking-card,.cart-card,.payment-card,.security-card,.doc-card{padding:18px;transition:.25s}
.booking-card{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:center}
.booking-card h3,.cart-card h3,.payment-card h3,.doc-card h3,.security-card h3{font-size:18px;font-weight:950;margin-bottom:6px}
.booking-card p,.cart-card p,.payment-card p,.doc-card p,.security-card p{color:var(--muted);font-size:13px;font-weight:650;line-height:1.5}
.status{padding:8px 12px;border-radius:999px;background:rgba(40,168,234,.1);color:var(--sky600);font-size:12px;font-weight:950;white-space:nowrap}
.status.pending{background:rgba(255,138,61,.12);color:var(--orange2)}
.status.approved{background:rgba(33,181,115,.1);color:#087f5b}
.status.rejected{background:rgba(255,77,79,.1);color:#c92a2a}

.stepper{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:16px}
.step{position:relative;padding:14px;border-radius:18px;background:#fff;border:1px solid var(--border);text-align:center}
.step i{width:38px;height:38px;border-radius:14px;display:grid;place-items:center;margin:0 auto 9px;background:var(--sky100);color:var(--sky600)}
.step strong{display:block;font-size:12px;line-height:1.25}
.step.done{border-color:rgba(33,181,115,.25);background:rgba(33,181,115,.06)}
.step.done i{background:rgba(33,181,115,.12);color:#087f5b}
.step.process{border-color:rgba(255,193,7,.35);background:rgba(255,193,7,.08)}
.step.process i{background:rgba(255,193,7,.16);color:#d97706}
.step.reject{border-color:rgba(255,77,79,.25);background:#fff5f5}
.step.reject i{background:rgba(255,77,79,.1);color:#c92a2a}

.car-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.car-card{overflow:hidden;transition:.25s}
.car-thumb{height:150px;background:linear-gradient(135deg,#d8f2ff,#fff);display:grid;place-items:center;color:var(--sky600);font-size:34px}
.car-body{padding:16px}
.car-body h3{font-size:18px;font-weight:950;margin-bottom:7px}

.document-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.doc-card{display:flex;gap:14px;align-items:flex-start}
.doc-icon{min-width:46px}
.doc-card.current-doc{border-color:rgba(40,168,234,.26);background:linear-gradient(135deg,rgba(255,255,255,.88),rgba(240,250,255,.74))}
.doc-card.rejected-doc{border-color:rgba(255,77,79,.24);background:linear-gradient(135deg,#fff8f8,rgba(255,255,255,.86))}
.doc-history{margin-top:18px;border-radius:24px;background:rgba(255,255,255,.72);border:1px dashed var(--border);padding:16px}
.doc-history summary{cursor:pointer;font-weight:950;color:#24415f;display:flex;align-items:center;gap:9px}
.doc-history-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}
.doc-history .doc-card{box-shadow:none;background:rgba(248,252,255,.7)}
.kyc-requirement-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:18px}
.kyc-requirement{padding:16px;border-radius:22px;background:rgba(255,255,255,.78);border:1px solid var(--border);box-shadow:var(--soft)}
.kyc-requirement h3{font-size:16px;font-weight:950;margin-bottom:7px}
.kyc-requirement p{font-size:13px;color:var(--muted);font-weight:700;line-height:1.45}
.kyc-requirement .tag{margin-top:12px}
.cart-alert{padding:14px;border-radius:18px;background:#fff5f5;color:#c92a2a;border:1px solid rgba(255,77,79,.22);font-weight:750;margin-top:12px}

@media(max-width:1180px){
    .dashboard{grid-template-columns:1fr}
    .sidebar{position:relative;top:0}
    .side-menu{grid-template-columns:repeat(2,1fr);max-height:none}
    .nav-links{display:none}
}
@media(max-width:760px){
    .topbar,.dashboard{padding-left:16px;padding-right:16px}
    .side-menu,.stats-grid,.info-grid,.quick-grid,.form-grid,.requirement-list,.car-grid,.document-grid,.doc-history-list,.mini-grid,.kyc-requirement-grid{grid-template-columns:1fr}
    .panel,.sidebar{padding:22px;border-radius:28px}
    .voucher-top,.booking-card{display:grid}
    .stepper{grid-template-columns:1fr}
    .nav-user strong{display:none}
}

.stat-link{color:inherit;text-decoration:none}
.security-overview{margin-bottom:24px}
.security-password-box{margin-top:24px;padding:24px;border-radius:28px;background:rgba(255,255,255,.72);border:1px solid var(--border);box-shadow:var(--soft)}
.small-head{margin-bottom:16px}

.edit-avatar-zone{display:flex;align-items:center;gap:18px;padding:18px;border-radius:26px;background:rgba(255,255,255,.75);border:1px solid var(--border);box-shadow:var(--soft);margin-bottom:22px}
.edit-avatar-btn{position:relative;width:96px;height:96px;border:0;background:transparent;cursor:pointer;border-radius:28px;transition:.28s cubic-bezier(.2,.8,.2,1)}
.edit-avatar-btn:hover{transform:translateY(-5px) scale(1.02)}
.edit-avatar-preview{width:96px;height:96px;border-radius:28px;display:grid;place-items:center;overflow:hidden;color:#fff;font-size:38px;font-weight:950;background:linear-gradient(135deg,var(--sky500),#0d3f82);border:5px solid #fff;box-shadow:0 18px 36px rgba(40,168,234,.22)}
.edit-avatar-preview img{width:100%;height:100%;object-fit:cover}
.edit-pencil{position:absolute;right:-5px;bottom:-5px;width:34px;height:34px;border-radius:13px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#ff9a4a,#f15f12);border:3px solid #fff;box-shadow:0 12px 24px rgba(255,122,26,.26)}
.hidden-file{display:none}

.avatar-modal{position:fixed;inset:0;z-index:999;display:none;place-items:center;background:rgba(13,31,55,.38);backdrop-filter:blur(10px);padding:18px}
.avatar-modal.show{display:grid}
.avatar-modal-card{position:relative;width:min(430px,100%);border-radius:30px;padding:28px;text-align:center;background:radial-gradient(circle at 100% 0%,rgba(184,228,255,.44),transparent 28%),linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.94));border:1px solid rgba(184,228,255,.95);box-shadow:0 34px 90px rgba(23,48,79,.24)}
.avatar-modal-close{position:absolute;right:16px;top:16px;width:36px;height:36px;border:0;border-radius:13px;background:var(--sky100);color:var(--sky600);cursor:pointer}
.avatar-modal-icon{width:70px;height:70px;border-radius:24px;display:grid;place-items:center;margin:0 auto 16px;color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600));box-shadow:0 18px 34px rgba(40,168,234,.22);font-size:26px}
.avatar-modal-card h2{font-size:28px;font-weight:950;letter-spacing:-1px;margin-bottom:8px}
.avatar-modal-card p{color:var(--muted);font-weight:650;line-height:1.6;margin-bottom:18px}
.avatar-modal-card .submit-btn,.avatar-modal-card .mini-btn{width:100%;margin-top:10px}

.booking-dashboard-list{display:grid;gap:16px}
.booking-history-card{border-radius:26px;background:rgba(255,255,255,.78);border:1px solid var(--border);box-shadow:var(--soft);overflow:hidden;transition:.28s cubic-bezier(.2,.8,.2,1)}
.booking-history-card:hover{transform:translateY(-5px);box-shadow:0 26px 56px rgba(40,168,234,.16);border-color:rgba(40,168,234,.35)}
.booking-summary{width:100%;border:0;background:transparent;padding:20px;display:flex;align-items:center;justify-content:space-between;gap:18px;text-align:left;cursor:pointer;color:var(--dark)}
.booking-summary h3{font-size:20px;font-weight:950;margin-bottom:6px}
.booking-summary p{color:var(--muted);font-size:13px;font-weight:650;line-height:1.55}
.booking-summary-right{display:grid;justify-items:end;gap:8px;min-width:170px}
.booking-summary-right strong{font-size:18px;font-weight:950;color:var(--sky600)}
.booking-detail{display:none;padding:0 20px 20px;border-top:1px solid var(--border)}
.booking-detail.open{display:block}
.booking-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px}
.booking-detail-grid div{padding:14px;border-radius:18px;background:rgba(255,255,255,.72);border:1px solid var(--border)}
.booking-detail-grid small{display:block;color:var(--muted);font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.55px;margin-bottom:5px}
.booking-detail-grid strong{font-size:14px;font-weight:950;word-break:break-word}
.booking-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:18px}
.booking-actions form{display:inline-flex}
.danger-btn{background:linear-gradient(135deg,#ff6b6b,#d63031)!important;box-shadow:0 18px 34px rgba(214,48,49,.22)!important}
@media(max-width:760px){.booking-summary{display:grid}.booking-summary-right{justify-items:start}.booking-detail-grid{grid-template-columns:1fr}}


.sidebar{
    overflow:hidden!important;
}
.side-menu::-webkit-scrollbar{
    width:5px;
    height:0!important;
}
.side-menu::-webkit-scrollbar-track{
    background:transparent!important;
}
.side-menu::-webkit-scrollbar-thumb{
    background:rgba(18,132,198,.26)!important;
    border-radius:999px!important;
}
.side-menu::-webkit-scrollbar-corner{
    background:transparent!important;
}
.side-menu a,
.side-menu button{
    flex-shrink:0;
}

/* ===== Refined premium dashboard polish ===== */
.dashboard{
    gap:28px!important;
}
.sidebar{
    border-radius:36px!important;
    background:
        radial-gradient(circle at 92% 0%,rgba(40,168,234,.16),transparent 34%),
        linear-gradient(155deg,rgba(255,255,255,.98),rgba(235,248,255,.94))!important;
    box-shadow:0 30px 82px rgba(39,137,199,.18)!important;
}
.side-profile{
    padding-bottom:16px!important;
    margin-bottom:14px!important;
}
.side-avatar{
    box-shadow:0 22px 54px rgba(40,168,234,.22)!important;
}
.side-menu button,.side-menu a{
    min-height:54px!important;
    padding:14px 16px!important;
    border-radius:19px!important;
    background:linear-gradient(135deg,rgba(255,255,255,.84),rgba(247,252,255,.72))!important;
    border:1px solid rgba(191,228,250,.78)!important;
    box-shadow:0 10px 26px rgba(40,168,234,.06)!important;
}
.side-menu button.active{
    background:linear-gradient(135deg,#2bb2f0,#1284c6)!important;
    color:#fff!important;
    box-shadow:0 18px 40px rgba(18,132,198,.24)!important;
}
.side-menu button.active i{
    color:#fff!important;
}
.side-menu .logout-link{
    background:linear-gradient(135deg,rgba(255,255,255,.86),rgba(255,245,240,.76))!important;
    border-color:rgba(255,138,61,.28)!important;
}
.panel{
    border-radius:38px!important;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.52),transparent 30%),
        radial-gradient(circle at 0% 100%,rgba(235,248,255,.52),transparent 32%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(246,252,255,.94))!important;
    box-shadow:0 32px 88px rgba(39,137,199,.17)!important;
}
.section-head h1,.section-head h2{
    letter-spacing:-1.6px!important;
}
.stat-card,.info-card,.booking-history-card,.security-card,.doc-card,.payment-card,.voucher-card{
    background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(252,254,255,.78))!important;
    border:1px solid rgba(191,228,250,.82)!important;
    box-shadow:0 14px 36px rgba(40,168,234,.08)!important;
}
.stat-card:hover,.info-card:hover,.booking-history-card:hover,.security-card:hover,.doc-card:hover,.payment-card:hover,.voucher-card:hover{
    transform:translateY(-6px)!important;
    box-shadow:0 28px 62px rgba(40,168,234,.16)!important;
    border-color:rgba(40,168,234,.36)!important;
}
.mini-btn,.submit-btn{
    border-radius:17px!important;
}
.receipt-help-card{
    display:flex;
    align-items:center;
    gap:16px;
    padding:18px;
    margin-bottom:18px;
    border-radius:24px;
    background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(234,247,255,.78));
    border:1px solid rgba(191,228,250,.88);
    box-shadow:0 14px 34px rgba(40,168,234,.08);
}
.receipt-help-icon{
    width:58px;
    height:58px;
    min-width:58px;
    border-radius:20px;
    display:grid;
    place-items:center;
    color:#fff;
    background:linear-gradient(135deg,#2bb2f0,#1284c6);
    box-shadow:0 18px 34px rgba(40,168,234,.22);
    font-size:22px;
}
.receipt-help-card h3{
    font-size:20px;
    font-weight:950;
    margin-bottom:5px;
}
.receipt-help-card p{
    color:var(--muted);
    font-weight:650;
    line-height:1.55;
}
.booking-summary{
    transition:.25s cubic-bezier(.2,.8,.2,1);
}
.booking-summary:hover{
    background:rgba(234,247,255,.45);
}
.booking-summary-right .fa-chevron-down{
    transition:.25s cubic-bezier(.2,.8,.2,1);
}
@media(max-width:760px){
    .receipt-help-card{align-items:flex-start}
}


/* ===== Final UI fixes requested ===== */

/* Voucher promo code: clearer and closer */
.compact-voucher-code{
    justify-content:flex-start!important;
    gap:16px!important;
    padding:16px 18px!important;
    background:linear-gradient(135deg,#fff,rgba(255,247,239,.92))!important;
    border:1.5px dashed rgba(255,122,26,.48)!important;
}
.voucher-code-left{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:9px 12px;
    border-radius:999px;
    background:rgba(40,168,234,.1);
    color:var(--sky600);
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.5px;
    font-size:12px;
}
.voucher-code-left i{
    color:var(--sky600);
}
.compact-voucher-code strong{
    margin-left:auto;
    padding:10px 16px;
    border-radius:16px;
    background:rgba(255,122,26,.12);
    color:var(--orange2)!important;
    font-size:20px!important;
    letter-spacing:1.6px!important;
    box-shadow:inset 0 0 0 1px rgba(255,122,26,.16);
}

/* Profile address full row */
.info-grid .info-card-full{
    grid-column:1 / -1;
}
.info-card-full strong{
    font-size:16px!important;
}

/* Sidebar button behavior: no blank hover */
.side-menu button,
.side-menu a{
    color:#24415f!important;
}
.side-menu button:not(.active):hover,
.side-menu a:not(.logout-link):hover{
    background:linear-gradient(135deg,rgba(234,247,255,.98),rgba(255,255,255,.92))!important;
    color:#1284c6!important;
    transform:translateX(5px) translateY(-2px)!important;
    box-shadow:0 18px 38px rgba(40,168,234,.16)!important;
    border-color:rgba(40,168,234,.32)!important;
}
.side-menu button:not(.active):hover i,
.side-menu a:not(.logout-link):hover i{
    color:#1284c6!important;
}
.side-menu button.active{
    color:#fff!important;
}
.side-menu button.active i{
    color:#fff!important;
}


/* More balanced profile page spacing */
.info-grid{
    align-items:stretch;
}
.info-card{
    min-height:94px;
}
.stat-card{
    min-height:138px;
}

@media(max-width:760px){
    .compact-voucher-code{
        display:grid!important;
        gap:12px!important;
    }
    .compact-voucher-code strong{
        margin-left:0!important;
        width:100%;
        text-align:center;
    }
}


/* ===== Collapsible Change Password ===== */
.password-collapse-card{
    padding:0!important;
    overflow:hidden;
}
.password-collapse-header{
    width:100%;
    border:0;
    background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(234,247,255,.82));
    padding:22px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    text-align:left;
    cursor:pointer;
    color:var(--dark);
    transition:.28s cubic-bezier(.2,.8,.2,1);
}
.password-collapse-header:hover{
    background:linear-gradient(135deg,rgba(234,247,255,.98),rgba(255,255,255,.92));
}
.password-collapse-header span{
    display:grid;
    gap:8px;
}
.password-collapse-header strong{
    font-size:26px;
    font-weight:950;
    letter-spacing:-.8px;
}
.password-collapse-header small{
    color:var(--muted);
    font-weight:700;
    font-size:13px;
}
.password-collapse-icon{
    width:42px;
    height:42px;
    border-radius:16px;
    display:grid!important;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    transition:.28s cubic-bezier(.2,.8,.2,1);
}
.password-collapse-card.open .password-collapse-icon{
    transform:rotate(180deg);
    background:var(--sky600);
    color:#fff;
}
.password-collapse-body{
    display:none;
    padding:0 24px 24px;
    animation:collapseOpen .24s ease;
}
.password-collapse-card.open .password-collapse-body{
    display:block;
}
.password-collapse-desc{
    margin:0 0 18px;
    color:var(--muted);
    font-weight:650;
    line-height:1.6;
}
@keyframes collapseOpen{
    from{opacity:0;transform:translateY(-8px)}
    to{opacity:1;transform:translateY(0)}
}


/* ===== Catalogue Navbar Exact Style ===== */
.navbar{
    position:sticky;
    top:0;
    z-index:100;
    height:64px;
    padding:0;
    background:linear-gradient(135deg,rgba(224,247,255,.94),rgba(255,255,255,.96),rgba(240,250,255,.94));
    border-bottom:1px solid rgba(142,207,244,.42);
    backdrop-filter:blur(18px);
    box-shadow:none;
}
.nav-inner{
    width:min(1320px,calc(100% - 24px));
    height:64px;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.navbar .brand{
    display:flex;
    align-items:center;
    gap:13px;
    font-size:15px;
    font-weight:950;
    white-space:nowrap;
    margin-right:10px;
    flex-shrink:0;
}
.brand-icon{
    width:42px;
    height:42px;
    display:grid;
    place-items:center;
    border-radius:15px;
    color:var(--sky600);
    background:linear-gradient(135deg,#d8f2ff,#fff);
    border:1px solid rgba(142,207,244,.46);
    box-shadow:0 14px 28px rgba(40,168,234,.13);
}
.navbar .nav-links{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    list-style:none;
    flex-wrap:nowrap;
    min-width:0;
}
.navbar .nav-links a{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 5px;
    border-radius:999px;
    font-size:11.5px;
    font-weight:950;
    color:#2b4969;
    letter-spacing:.2px;
    text-transform:uppercase;
    background:transparent;
    white-space:nowrap;
}
.navbar .nav-links a i{color:#2b4969;font-size:13px}
.navbar .nav-links a.active,
.navbar .nav-links a.active i,
.navbar .nav-links a:hover,
.navbar .nav-links a:hover i{color:var(--sky600);background:transparent;transform:none}
.avatar-wrap{position:relative;margin-left:0;flex-shrink:0}
.avatar-btn{
    border:0;
    background:transparent;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    font-weight:950;
    color:var(--dark);
}
.avatar-circle{
    width:40px;
    height:40px;
    border-radius:50%;
    display:grid;
    place-items:center;
    overflow:hidden;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),#0d3f82);
    border:3px solid #fff;
    box-shadow:0 14px 28px rgba(40,168,234,.18);
}
.avatar-circle img{width:100%;height:100%;object-fit:cover}
.dropdown{
    position:absolute;
    right:0;
    top:62px;
    width:260px;
    display:none;
    padding:12px;
    border-radius:24px;
    background:rgba(255,255,255,.96);
    border:1px solid var(--border);
    box-shadow:0 24px 70px rgba(39,137,199,.18);
}
.dropdown.show{display:block}
.dropdown a{
    min-height:54px;
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    border-radius:17px;
    font-weight:900;
    color:#24415f;
}
.dropdown a:hover{background:var(--sky100);color:var(--sky600)}
.login-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:13px 18px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    font-weight:950;
    white-space:nowrap;
    flex-shrink:0;
    min-width:max-content;
}
@media(max-width:1180px){
    .navbar .nav-links{display:none!important;}
}

/* ===== Voucher Three Stage Status ===== */
.voucher-card.voucher-using{
    border-color:rgba(255,138,61,.42);
    background:radial-gradient(circle at 100% 0%,rgba(255,138,61,.24),transparent 30%),linear-gradient(135deg,rgba(255,255,255,.96),rgba(255,248,240,.94));
}
.voucher-card.voucher-used{
    border-color:rgba(108,117,125,.22);
    background:radial-gradient(circle at 100% 0%,rgba(108,117,125,.10),transparent 30%),linear-gradient(135deg,rgba(255,255,255,.94),rgba(246,248,250,.90));
}
.mini-btn.disabled{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    padding:12px 16px;
    border-radius:16px;
    background:#eef2f6;
    color:#6c757d;
    box-shadow:none;
    cursor:not-allowed;
    font-size:13px;
    font-weight:950;
}


.invoice-preview-modal{
    position:fixed;
    inset:0;
    z-index:9999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:22px;
    background:rgba(16,35,61,.58);
    backdrop-filter:blur(10px);
}
.invoice-preview-modal.show{display:flex}
.invoice-preview-dialog{
    width:min(1180px,96vw);
    height:min(860px,92vh);
    border-radius:30px;
    background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.96));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 35px 90px rgba(8,50,88,.32);
    overflow:hidden;
    display:flex;
    flex-direction:column;
}
.invoice-preview-head{
    min-height:72px;
    padding:16px 22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    border-bottom:1px solid var(--border);
    background:linear-gradient(135deg,rgba(234,247,255,.95),rgba(255,255,255,.98));
}
.invoice-preview-title{
    display:flex;
    align-items:center;
    gap:12px;
}
.invoice-preview-title i{
    width:42px;
    height:42px;
    border-radius:16px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    border:1px solid var(--border);
}
.invoice-preview-title h3{
    font-size:20px;
    font-weight:950;
    letter-spacing:-.4px;
}
.invoice-preview-actions{
    display:flex;
    align-items:center;
    gap:10px;
}
.invoice-preview-frame{
    flex:1;
    width:100%;
    border:0;
    background:#fff;
}
.invoice-close-btn{
    width:44px;
    height:44px;
    border-radius:16px;
    border:0;
    cursor:pointer;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    font-size:18px;
    transition:.24s;
}
.invoice-close-btn:hover{
    background:#dff3ff;
    transform:translateY(-2px);
}
.invoice-print-frame{
    position:fixed;
    width:1200px;
    height:900px;
    border:0;
    opacity:0.01;
    pointer-events:none;
    left:0;
    top:0;
    z-index:-1;
    background:#fff;
}
@media(max-width:760px){
    .invoice-preview-modal{padding:10px}
    .invoice-preview-dialog{height:94vh;border-radius:24px}
    .invoice-preview-head{display:grid;gap:10px}
    .invoice-preview-actions{justify-content:space-between}
}



</style>
</head>
<body>
<header class="navbar">
    <div class="nav-inner">
        <a href="homepage.php" class="brand">
            <span class="brand-icon"><i class="fa-solid fa-car-side"></i></span>
            <span>KH Car Rental</span>
        </a>

        <ul class="nav-links">
            <li><a href="homepage.php"><i class="fa-solid fa-house"></i> HOME</a></li>
            <li><a href="catalogue.php"><i class="fa-solid fa-car"></i> CATALOGUE</a></li>
            <li><a href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> FIND CAR SMART</a></li>
            <li><a href="compare_car.php"><i class="fa-solid fa-code-compare"></i> COMPARE CAR</a></li>
            <li><a href="aboutus.php"><i class="fa-solid fa-circle-info"></i> ABOUT US</a></li>
            <li><a href="contactus.php"><i class="fa-solid fa-envelope"></i> CONTACT US</a></li>
            <li><a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> CART</a></li>
        </ul>

        <div class="avatar-wrap">
            <button class="avatar-btn" type="button" id="avatarBtn">
                <span class="avatar-alert-wrap">
                    <span class="avatar-circle">
                        <?php if($avatar): ?>
                            <img src="<?= e($avatar) ?>" alt="Profile">
                        <?php else: ?>
                            <?= e($initial) ?>
                        <?php endif; ?>
                    </span>
                    <?php if($kycNeedsAttention): ?><span class="avatar-kyc-dot" title="KYC verification required"></span><?php endif; ?>
                </span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="dropdown" id="profileDropdown">
                <a href="my_profile.php"><i class="fa-solid fa-user"></i> Manage My Profile</a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<main class="dashboard">
    <aside class="sidebar">
        <div class="side-profile">
            <div class="avatar-alert-wrap">
                <div class="side-avatar">
                    <?php if($avatar): ?><img src="<?= e($avatar) ?>" alt="Profile"><?php else: ?><?= e($initial) ?><?php endif; ?>
                </div>
                <?php if($kycNeedsAttention): ?><span class="avatar-kyc-dot" title="KYC verification required"></span><?php endif; ?>
            </div>
            <h2><?= e($user["name"]) ?></h2>
        </div>

        <div class="side-menu">
            <button type="button" class="tab-btn <?= $activeTab==='profile'?'active':'' ?>" data-tab="profile"><i class="fa-solid fa-user"></i> Profile</button>
            <button type="button" class="tab-btn <?= $activeTab==='voucher'?'active':'' ?>" data-tab="voucher"><i class="fa-solid fa-ticket"></i> My Voucher</button>
            <button type="button" class="tab-btn <?= $activeTab==='bookings'?'active':'' ?>" data-tab="bookings"><i class="fa-solid fa-calendar-check"></i> My Booking / Receipt</button>
            <button type="button" class="tab-btn <?= $activeTab==='edit'?'active':'' ?>" data-tab="edit"><i class="fa-solid fa-user-pen"></i> Edit Profile</button>
            <button type="button" class="tab-btn <?= $activeTab==='documents'?'active':'' ?>" data-tab="documents"><i class="fa-solid fa-file-shield"></i> Uploaded Documents<?php if($kycNeedsAttention): ?><span class="kyc-mini-label">Action</span><?php endif; ?></button>
            <button type="button" class="tab-btn <?= $activeTab==='recent'?'active':'' ?>" data-tab="recent"><i class="fa-solid fa-clock-rotate-left"></i> Recent Viewed</button>
            <button type="button" class="tab-btn <?= $activeTab==='emergency'?'active':'' ?>" data-tab="emergency"><i class="fa-solid fa-phone-volume"></i> Emergency Contact</button>
            <button type="button" class="tab-btn <?= $activeTab==='payments'?'active':'' ?>" data-tab="payments"><i class="fa-solid fa-credit-card"></i> Payment History</button>
            <button type="button" class="tab-btn <?= $activeTab==='security'?'active':'' ?>" data-tab="security"><i class="fa-solid fa-shield-halved"></i> Account Security</button>
            <a class="logout-link" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <section class="main-content">
        <?php if($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><div><?= e($success) ?></div></div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><div><strong>Please check your details:</strong><ul><?php foreach($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div></div>
        <?php endif; ?>

        <?php if(!empty($password_errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><div><strong>Please check your password:</strong><ul><?php foreach($password_errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div></div>
        <?php endif; ?>

        <?php if($kycNeedsAttention): ?>
            <?php
                $kycAlertClass=$kycRequirement["state"]==="pending" ? "alert-warning" : "alert-danger";
                $kycTitle="Identity verification required before payment.";
                $kycBody="Upload your IC document and driving license document in My Profile > Uploaded Documents. Payment will unlock after admin verification.";
                if($kycRequirement["state"]==="pending"){
                    $kycTitle="KYC documents waiting for admin approval.";
                    $kycBody="Your ".$kycPendingText." is under review. Payment will unlock automatically once both IC and driving license are verified.";
                }elseif($kycRequirement["state"]==="rejected"){
                    $kycTitle="KYC document needs re-upload.";
                    $kycBody="Your ".$kycRejectedText." was rejected. Please upload a clearer document before making payment.";
                }elseif($kycMissingText!==""){
                    $kycBody="Please upload your ".$kycMissingText.". Payment is disabled until both IC and driving license documents are verified by admin.";
                }
            ?>
            <div class="alert <?= e($kycAlertClass) ?>"><i class="fa-solid fa-id-card"></i><div><strong><?= e($kycTitle) ?></strong><br><?= e($kycBody) ?><br><button class="mini-btn tab-jump" type="button" data-tab="documents" style="margin-top:10px"><i class="fa-solid fa-upload"></i> Go to Documents</button></div></div>
        <?php endif; ?>

        <div class="panel <?= $activeTab==='profile'?'active':'' ?>" id="panel-profile">
            <div class="section-head">
                <span class="pill"><i class="fa-solid fa-id-card-clip"></i> Profile Overview</span>
                <h1>Welcome back, <?= e($user["name"]) ?></h1>
                <p>Manage your customer information, booking status, vouchers and account settings.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><i class="fa-solid fa-id-card"></i><small>Customer ID</small><strong>#<?= str_pad((string)$user["user_id"],5,"0",STR_PAD_LEFT) ?></strong></div>
                <div class="stat-card"><i class="fa-solid fa-calendar-check"></i><small>Total Bookings</small><strong><?= count($bookings) ?></strong></div>
                <a class="stat-card stat-link" href="cart.php"><i class="fa-solid fa-cart-shopping"></i><small>Cart Items</small><strong><?= count($cartItems) ?></strong></a>
                <div class="stat-card"><i class="fa-solid fa-ticket"></i><small>Vouchers</small><strong><?= count($vouchers) ?></strong></div>
            </div>

            <div class="info-grid">
                <div class="info-card"><i class="fa-solid fa-user"></i><div><small>Full Name</small><strong><?= e($user["name"]) ?></strong></div></div>
                <div class="info-card"><i class="fa-solid fa-envelope"></i><div><small>Email Address</small><strong><?= e($user["email"]) ?></strong></div></div>
                <div class="info-card"><i class="fa-solid fa-phone"></i><div><small>Phone Number</small><strong><?= e($user["phone"]) ?></strong></div></div>
                <div class="info-card"><i class="fa-solid fa-calendar-days"></i><div><small>Customer Since</small><strong><?= e($customerSince) ?></strong></div></div>
                <div class="info-card"><i class="fa-solid fa-id-card"></i><div><small>IC Number</small><strong><?= e($user["ic_number"]) ?></strong></div></div>
                <div class="info-card"><i class="fa-solid fa-id-badge"></i><div><small>Driving License</small><strong><?= e($user["license_number"]) ?></strong></div></div>
                <div class="info-card"><i class="fa-solid fa-calendar-check"></i><div><small>License Expiry Date</small><strong><?= e($licenseExpiry) ?></strong></div></div>
                <div class="info-card info-card-full"><i class="fa-solid fa-location-dot"></i><div><small>Address</small><strong><?= e($user["address"]) ?></strong></div></div>
            </div>
        </div>

        <div class="panel <?= $activeTab==='voucher'?'active':'' ?>" id="panel-voucher">
            <div class="section-head"><span class="pill"><i class="fa-solid fa-ticket"></i> My Voucher</span><h2>Promotion Voucher</h2><p>Promo codes are shown only after login. Use your available voucher during booking or checkout.</p></div>
            <?php if(!empty($vouchers)): ?>
                <?php foreach($vouchers as $v): ?>
                    <?php
                        $used=!empty($v["used_at"]);
                        $promoId=(int)($v["promo_id"] ?? 0);
                        $promoCode=strtoupper(trim((string)($v["promo_code"] ?? "NEWUSER5")));
                        $using=!$used && (($activeCartPromoId>0 && $activeCartPromoId===$promoId) || ($activeCartPromoCode!=="" && $activeCartPromoCode===$promoCode));
                        $discount=rtrim(rtrim(number_format((float)($v["discount_percent"] ?? 0),2),"0"),".")."%";
                        $statusClass=$used ? "used" : ($using ? "warning" : "available");
                        $statusIcon=$used ? "fa-circle-check" : ($using ? "fa-cart-shopping" : "fa-gift");
                        $statusText=$used ? "Already Used" : ($using ? "Using in Cart" : "Available to Use");
                    ?>
                    <div class="voucher-card <?= $used ? 'voucher-used' : ($using ? 'voucher-using' : 'voucher-available') ?>">
                        <div class="voucher-top">
                            <div><h3><?= e($v["promo_name"] ?? "New User Promotion") ?></h3><p><?= e($v["description"] ?? "Special rental promotion for KH Car Rental customers.") ?></p></div>
                            <div class="discount"><div><?= e($discount) ?><small>OFF</small></div></div>
                        </div>
                        <div class="voucher-code compact-voucher-code">
                            <div class="voucher-code-left">
                                <i class="fa-solid fa-ticket"></i>
                                <span>Promo Code</span>
                            </div>
                            <strong><?= e($promoCode) ?></strong>
                        </div>
                        <div class="voucher-meta">
                            <span class="tag <?= e($statusClass) ?>"><i class="fa-solid <?= e($statusIcon) ?>"></i> <?= e($statusText) ?></span>
                            <?php if($used): ?>
                                <span class="tag used"><i class="fa-solid fa-clock"></i> Used on <?= e(date("d M Y",strtotime($v["used_at"]))) ?></span>
                            <?php elseif($using): ?>
                                <span class="tag warning"><i class="fa-solid fa-circle-info"></i> Applied in cart, not used yet</span>
                            <?php else: ?>
                                <span class="tag available"><i class="fa-solid fa-circle-check"></i> Can apply at cart checkout</span>
                            <?php endif; ?>
                            <span class="tag"><i class="fa-solid fa-calendar-days"></i> Valid for first booking</span>
                        </div>
                        <div class="voucher-actions">
                            <?php if($used): ?>
                                <span class="mini-btn disabled"><i class="fa-solid fa-check"></i> Voucher Used</span>
                            <?php elseif($using): ?>
                                <a class="mini-btn" href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Continue in Cart</a>
                            <?php else: ?>
                                <a class="mini-btn" href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Apply in Cart</a>
                                <a class="mini-btn outline" href="catalogue.php"><i class="fa-solid fa-car"></i> Browse Cars</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?><div class="empty"><i class="fa-solid fa-circle-info"></i> No active voucher is available now. Please check again later.</div><?php endif; ?>
        </div>

        <div class="panel <?= $activeTab==='bookings'?'active':'' ?>" id="panel-bookings">
            <div class="section-head">
                <span class="pill"><i class="fa-solid fa-calendar-check"></i> My Booking</span>
                <h2>My Booking History & Receipt</h2>
                <p>View booking history, track status progress, request cancellation, and view or print invoice in one place.</p>
            </div>


            <div class="receipt-help-card" id="bookingReceiptArea">
                <div class="receipt-help-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div>
                    <h3>Booking Receipt / Invoice</h3>
                    <p>Open any booking below, then click <strong>View Invoice</strong> or <strong>Print Invoice</strong>. Completed bookings will show as completed invoice records, while active bookings will show their latest booking status.</p>
                </div>
            </div>

            <?php if(!empty($bookings)): ?>
                <div class="booking-dashboard-list">
                    <?php foreach($bookings as $index=>$b): ?>
                        <?php
                            $bookingId=(int)($b["dashboard_booking_id"] ?? $b["booking_id"] ?? $b["id"] ?? 0);
                            $ref=$b["booking_reference"] ?? ("BOOKING #".$bookingId);
                            $cars=$b["car_names"] ?? "Rental Car";
                            $pickup=$b["pickup_location_names"] ?? ($b["pickup_location"] ?? "-");
                            $drop=$b["dropoff_location_names"] ?? ($b["return_location"] ?? ($b["dropoff_location"] ?? "-"));
                            $start=$b["pickup_datetime"] ?? ($b["start_datetime"] ?? "-");
                            $end=$b["return_datetime"] ?? ($b["end_datetime"] ?? "-");
                            $amount=$b["grand_total"] ?? $b["total_amount"] ?? 0;
                            $pay="Success";
                            $rawStatus=$b["booking_status"] ?? ($b["status"] ?? "pending_admin_approval");
                            $statusLower=strtolower(str_replace([" ","-"],"_",(string)$rawStatus));
                            $paymentLower="success";

                            $isRejected=str_contains($statusLower,"reject");
                            $isCancelled=str_contains($statusLower,"cancel");
                            $isCompleted=str_contains($statusLower,"complete");
                            $isActive=$statusLower==="active" || str_contains($statusLower,"active");
                            $isApproved=str_contains($statusLower,"approve") && !str_contains($statusLower,"pending");
                            $isWaiting=!$isRejected && !$isCancelled && !$isCompleted && !$isApproved && !$isActive;
                            $isPaid=true;
                            $status=$isWaiting ? "Waiting Approval" : ($isCompleted ? "Completed" : ($isActive ? "Car Picked Up" : ($isApproved ? "Approved" : ($isRejected ? "Rejected" : ($isCancelled ? "Cancellation Requested" : "Waiting Approval")))));

                            $statusClass=($isRejected || $isCancelled) ? "rejected" : (($isApproved || $isActive || $isCompleted) ? "approved" : "pending");

                            $canCancel=true;
                            if($start && $start !== "-"){
                                $pickupTime=strtotime($start);
                                if($pickupTime && ($pickupTime-time()) < 48*60*60){
                                    $canCancel=false;
                                }
                            }

                            if($isCompleted || $isRejected || $isCancelled || $isActive){
                                $canCancel=false;
                            }

                            $panelId="bookingDetail".$index;
                        ?>

                        <article class="booking-history-card">
                            <button class="booking-summary" type="button" data-toggle-booking="<?= e($panelId) ?>">
                                <div>
                                    <h3><?= e($ref) ?></h3>
                                    <p><strong><?= e($cars) ?></strong></p>
                                    <p><i class="fa-solid fa-location-dot"></i> <?= e($pickup) ?> → <?= e($drop) ?></p>
                                    <p><i class="fa-solid fa-calendar-days"></i> <?= e($start) ?> → <?= e($end) ?></p>
                                </div>

                                <div class="booking-summary-right">
                                    <span class="status <?= e($statusClass) ?>"><?= e($status) ?></span>
                                    <strong>RM <?= e(number_format((float)$amount,2)) ?></strong>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </button>

                            <div class="booking-detail" id="<?= e($panelId) ?>">
                                <div class="booking-detail-grid">
                                    <div><small>Booking Reference</small><strong><?= e($ref) ?></strong></div>
                                    <div><small>Car Name</small><strong><?= e($cars) ?></strong></div>
                                    <div><small>Pickup Location</small><strong><?= e($pickup) ?></strong></div>
                                    <div><small>Drop-off Location</small><strong><?= e($drop) ?></strong></div>
                                    <div><small>Pickup Date / Time</small><strong><?= e($start) ?></strong></div>
                                    <div><small>Return Date / Time</small><strong><?= e($end) ?></strong></div>
                                    <div><small>Total Amount</small><strong>RM <?= e(number_format((float)$amount,2)) ?></strong></div>
                                    <div><small>Payment Status</small><strong><?= e($pay) ?></strong></div>
                                    <div><small>Booking Status</small><strong><?= e($status) ?></strong></div>
                                    <div><small>Cancellation Rule</small><strong>Cancellation is not allowed within 48 hours before pickup.</strong></div>
                                </div>

                                <div class="stepper booking-stepper">
                                    <div class="step done"><i class="fa-solid fa-file-circle-check"></i><strong>Booking Submitted</strong></div>
                                    <div class="step done"><i class="fa-solid fa-credit-card"></i><strong>Payment Completed</strong></div>
                                    <div class="step <?= ($isApproved || $isActive || $isCompleted) ? 'done' : ($isRejected || $isCancelled ? 'reject' : 'process') ?>"><i class="fa-solid fa-user-shield"></i><strong>Pending Admin Approval</strong></div>
                                    <div class="step <?= ($isRejected || $isCancelled) ? 'reject':($isApproved || $isActive || $isCompleted ? 'done':'') ?>">
                                        <i class="fa-solid <?= ($isRejected || $isCancelled) ? 'fa-xmark':'fa-circle-check' ?>"></i>
                                        <strong><?= $isCancelled ? 'Cancellation Requested' : ($isRejected ? 'Rejected':'Approved') ?></strong>
                                    </div>
                                    <div class="step <?= ($isActive || $isCompleted) ? 'done':($isApproved ? 'process':'') ?>"><i class="fa-solid fa-car-side"></i><strong><?= $isCompleted ? 'Completed':($isActive ? 'Car Picked Up':'Ready for Pickup') ?></strong></div>
                                </div>

                                <div class="booking-actions">
                                    <button type="button" class="mini-btn" onclick="openInvoiceModal(<?= (int)$bookingId ?>)">
                                        <i class="fa-solid fa-eye"></i> View Invoice
                                    </button>
                                    <button type="button" class="mini-btn outline" onclick="printInvoiceFromProfile(<?= (int)$bookingId ?>)">
                                        <i class="fa-solid fa-print"></i> Print Invoice
                                    </button>

                                    <?php if($canCancel): ?>
                                        <form method="POST" action="my_profile.php?tab=bookings" onsubmit="return confirm('Submit cancellation request for this booking?');">
                                            <input type="hidden" name="action" value="cancel_booking">
                                            <input type="hidden" name="booking_id" value="<?= e($bookingId) ?>">
                                            <button class="mini-btn danger-btn" type="submit"><i class="fa-solid fa-ban"></i> Request Cancel Booking</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="tag warning"><i class="fa-solid fa-lock"></i> Cannot cancel within 48 hours before pickup or after final status</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-solid fa-circle-info"></i>
                    No booking record found.
                    <br><br>
                    <a class="mini-btn" href="catalogue.php"><i class="fa-solid fa-car"></i> Browse Cars</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel <?= $activeTab==='edit'?'active':'' ?>" id="panel-edit">
            <div class="section-head"><span class="pill"><i class="fa-solid fa-user-pen"></i> Edit Profile</span><h2>Personal Information</h2><p>You can update your contact details, address, profile picture and license expiry date. Email, IC and license number are locked for verification safety.</p></div>

            <div class="edit-avatar-zone">
                <button class="edit-avatar-btn" type="button" id="openAvatarModal" title="Change Profile Picture">
                    <span class="edit-avatar-preview" id="editAvatarPreview">
                        <?php if($avatar): ?><img src="<?= e($avatar) ?>" alt="Profile"><?php else: ?><span class="avatar-initial-text"><?= e($initial) ?></span><?php endif; ?>
                    </span>
                    <span class="edit-pencil"><i class="fa-solid fa-pen"></i></span>
                </button>
                <div>
                    <h3>Profile Picture</h3>
                    <p class="muted">Click the avatar to change your profile picture. You can also remove it and return to initials.</p>
                    <?php if($avatar): ?>
                        <label class="tag danger" style="margin-top:10px;cursor:pointer"><input type="checkbox" name="remove_profile_picture" value="1" form="editProfileForm" style="accent-color:#c92a2a"> Remove current picture</label>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="my_profile.php?tab=edit" enctype="multipart/form-data" id="editProfileForm">
                <input type="hidden" name="action" value="update_profile">
                <input class="hidden-file" type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/webp">
                <div class="form-grid">
                    <div class="form-group"><label for="name">Full Name</label><div class="input-wrap"><i class="fa-solid fa-user"></i><input class="form-control" type="text" name="name" id="name" value="<?= e($user["name"]) ?>" required></div></div>
                    <div class="form-group"><label for="phone">Phone Number</label><div class="input-wrap"><i class="fa-solid fa-phone"></i><input class="form-control" type="text" name="phone" id="phone" value="<?= e($user["phone"]) ?>" required></div></div>
                    <div class="form-group"><label>Email Address</label><div class="input-wrap"><i class="fa-solid fa-envelope"></i><input class="form-control" type="text" value="<?= e($user["email"]) ?>" readonly></div></div>
                    <div class="form-group"><label>Date of Birth</label><div class="input-wrap"><i class="fa-solid fa-calendar-days"></i><input class="form-control" type="text" value="<?= e($dob) ?>" readonly></div></div>
                    <div class="form-group"><label>IC Number</label><div class="input-wrap"><i class="fa-solid fa-id-card"></i><input class="form-control" type="text" value="<?= e($user["ic_number"]) ?>" readonly></div></div>
                    <div class="form-group"><label>Driving License Number</label><div class="input-wrap"><i class="fa-solid fa-id-badge"></i><input class="form-control" type="text" value="<?= e($user["license_number"]) ?>" readonly></div></div>
                    <div class="form-group"><label for="license_expiry_date">License Expiry Date</label><div class="input-wrap"><i class="fa-solid fa-calendar-check"></i><input class="form-control" type="date" name="license_expiry_date" id="license_expiry_date" value="<?= e($user["license_expiry_date"] ?? "") ?>" min="<?= e(date("Y-m-d",strtotime("+6 months"))) ?>" required></div><p class="note">Your license must remain valid for at least 6 months.</p></div>
                    <div class="form-group"><label>Profile Safety</label><div class="password-box"><strong>Locked fields</strong><ul class="requirement-list"><li class="pass"><i class="fa-solid fa-lock"></i>Email stays fixed for login security</li><li class="pass"><i class="fa-solid fa-lock"></i>IC and license number stay fixed for KYC safety</li><li class="pass"><i class="fa-solid fa-circle-check"></i>Phone and address can be updated anytime</li><li class="<?= $kycNeedsAttention ? '' : 'pass' ?>"><i class="fa-solid <?= $kycNeedsAttention ? 'fa-circle' : 'fa-circle-check' ?>"></i>KYC status: <?= e(ucfirst($kycRequirement["state"])) ?></li></ul></div></div>
                    <div class="form-group full"><label for="address">Address</label><div class="input-wrap"><i class="fa-solid fa-location-dot textarea-icon"></i><textarea class="form-control" name="address" id="address" required><?= e($user["address"]) ?></textarea></div></div>
                </div>
                <button class="submit-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </form>
        </div>

        <div class="panel <?= $activeTab==='documents'?'active':'' ?>" id="panel-documents">
            <div class="section-head"><span class="pill"><i class="fa-solid fa-file-shield"></i> Uploaded Documents</span><h2>Identity Documents</h2><p>Upload IC photo and driving license photo for admin verification. Both documents must be verified before payment.</p></div>
            <div class="kyc-requirement-grid">
                <?php foreach($kycRequirement["items"] as $type=>$item): ?>
                    <?php
                        $docStatus=$item["status"] ?? "Not Uploaded";
                        $tagClass="warning";
                        $icon="fa-clock";
                        if(strcasecmp($docStatus,"Verified")===0){ $tagClass="available"; $icon="fa-circle-check"; }
                        elseif(strcasecmp($docStatus,"Rejected")===0){ $tagClass="danger"; $icon="fa-circle-xmark"; }
                    ?>
                    <div class="kyc-requirement">
                        <h3><i class="fa-solid fa-file-shield"></i> <?= e($item["label"]) ?></h3>
                        <p><?= strcasecmp($docStatus,"Verified")===0 ? "This document has been approved by admin." : (strcasecmp($docStatus,"Rejected")===0 ? "Please upload a clearer replacement document." : "Upload this document and wait for admin verification.") ?></p>
                        <?php if(!empty($item["admin_note"])): ?><p><strong>Admin note:</strong> <?= e($item["admin_note"]) ?></p><?php endif; ?>
                        <span class="tag <?= e($tagClass) ?>"><i class="fa-solid <?= e($icon) ?>"></i> <?= e($docStatus) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" action="my_profile.php?tab=documents" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_document">
                <div class="form-grid">
                    <div class="form-group"><label for="document_type">Document Type</label><div class="input-wrap"><i class="fa-solid fa-file"></i><select class="form-control" name="document_type" id="document_type" required><option value="">Select Document</option><option value="IC Photo">IC Photo</option><option value="Driving License Photo">Driving License Photo</option></select></div></div>
                    <div class="form-group"><label for="document_file">Document File</label><input class="form-control file-input" type="file" name="document_file" id="document_file" accept="image/jpeg,image/png,image/webp,application/pdf" required></div>
                </div>
                <button class="submit-btn" type="submit"><i class="fa-solid fa-upload"></i> Upload Document</button>
            </form>
            <br>
            <?php if(!empty($latestDocuments)): ?>
                <div class="document-grid">
                    <?php foreach($latestDocuments as $doc): ?>
                        <?php
                            $docStatus=(string)($doc["verification_status"] ?? "");
                            $docClass=strcasecmp($docStatus,"Rejected")===0 ? "rejected-doc" : "current-doc";
                        ?>
                        <div class="doc-card <?= e($docClass) ?>">
                            <div class="doc-icon"><i class="fa-solid fa-file-shield"></i></div>
                            <div>
                                <span class="tag <?= strcasecmp($docStatus,"Verified")===0 ? 'available' : (strcasecmp($docStatus,"Rejected")===0 ? 'danger' : 'warning') ?>"><i class="fa-solid <?= strcasecmp($docStatus,"Verified")===0 ? 'fa-circle-check' : (strcasecmp($docStatus,"Rejected")===0 ? 'fa-circle-xmark' : 'fa-clock') ?>"></i> Current</span>
                                <h3><?= e($doc["document_type"]) ?></h3>
                                <p>Status: <?= e($docStatus) ?></p>
                                <p>Uploaded: <?= e($doc["uploaded_at"]) ?></p>
                                <?php if(!empty($doc["admin_note"])): ?><p><strong>Admin note:</strong> <?= e($doc["admin_note"]) ?></p><?php endif; ?>
                                <a class="mini-btn outline" href="<?= e($doc["file_path"]) ?>" target="_blank"><i class="fa-solid fa-eye"></i> View Current File</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty"><i class="fa-solid fa-circle-info"></i> No document uploaded yet.</div><?php endif; ?>

            <?php if(!empty($documentHistory)): ?>
                <details class="doc-history">
                    <summary><i class="fa-solid fa-clock-rotate-left"></i> View previous upload history</summary>
                    <div class="doc-history-list">
                        <?php foreach($documentHistory as $doc): ?>
                            <div class="doc-card">
                                <div class="doc-icon"><i class="fa-solid fa-file-shield"></i></div>
                                <div>
                                    <h3><?= e($doc["document_type"]) ?></h3>
                                    <p>Status: <?= e($doc["verification_status"]) ?></p>
                                    <p>Uploaded: <?= e($doc["uploaded_at"]) ?></p>
                                    <?php if(!empty($doc["admin_note"])): ?><p><strong>Admin note:</strong> <?= e($doc["admin_note"]) ?></p><?php endif; ?>
                                    <a class="mini-btn outline" href="<?= e($doc["file_path"]) ?>" target="_blank"><i class="fa-solid fa-eye"></i> View History File</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>

        <div class="panel <?= $activeTab==='recent'?'active':'' ?>" id="panel-recent">
            <div class="section-head"><span class="pill"><i class="fa-solid fa-clock-rotate-left"></i> Recent Viewed</span><h2>Recently Viewed Cars</h2><p>Cars you recently checked in the catalogue or car details page.</p></div>
            <?php if(!empty($recentViews)): ?>
                <div class="car-grid">
                    <?php foreach($recentViews as $car): ?>
                        <div class="car-card"><div class="car-thumb"><?php if(!empty($car["car_image"])): ?><img src="<?= e($car["car_image"]) ?>" alt="Car"><?php else: ?><i class="fa-solid fa-car-side"></i><?php endif; ?></div><div class="car-body"><h3><?= e($car["car_name"]) ?></h3><p>Viewed: <?= e($car["viewed_at"]) ?></p><br><a class="mini-btn" href="car_details.php?car_id=<?= e($car["car_id"]) ?>">View Again</a></div></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty"><i class="fa-solid fa-circle-info"></i> No recently viewed car yet.</div><?php endif; ?>
        </div>

        <div class="panel <?= $activeTab==='emergency'?'active':'' ?>" id="panel-emergency">
            <div class="section-head"><span class="pill"><i class="fa-solid fa-phone-volume"></i> Emergency Contact</span><h2>Emergency Contact</h2><p>Save a contact person for rental emergency support.</p></div>
            <form method="POST" action="my_profile.php?tab=emergency">
                <input type="hidden" name="action" value="save_emergency">
                <div class="form-grid">
                    <div class="form-group"><label for="contact_name">Contact Name</label><div class="input-wrap"><i class="fa-solid fa-user"></i><input class="form-control" type="text" name="contact_name" id="contact_name" value="<?= e($emergency["contact_name"] ?? "") ?>" required></div></div>
                    <div class="form-group"><label for="contact_phone">Contact Phone</label><div class="input-wrap"><i class="fa-solid fa-phone"></i><input class="form-control" type="text" name="contact_phone" id="contact_phone" value="<?= e($emergency["contact_phone"] ?? "") ?>" required></div></div>
                    <div class="form-group full"><label for="relationship">Relationship</label><div class="input-wrap"><i class="fa-solid fa-users"></i><input class="form-control" type="text" name="relationship" id="relationship" value="<?= e($emergency["relationship"] ?? "") ?>" placeholder="Parent / Sibling / Friend" required></div></div>
                </div>
                <button class="submit-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Emergency Contact</button>
            </form>
        </div>

        <div class="panel <?= $activeTab==='payments'?'active':'' ?>" id="panel-payments">
            <div class="section-head"><span class="pill"><i class="fa-solid fa-credit-card"></i> Payment History</span><h2>Payment Records</h2><p>View payment amount, payment method, payment date and payment status.</p></div>
            <?php if(!empty($payments)): ?>
                <div class="list">
                    <?php foreach($payments as $p): ?>
                        <div class="payment-card"><h3><?= e($p["booking_reference"] ?? "Booking Payment") ?></h3><p>Amount: RM <?= e(number_format((float)($p["amount"] ?? 0),2)) ?></p><p>Method: <?= e($p["payment_method"] ?? "-") ?></p><p>Date: <?= e($p["payment_date"] ?? "-") ?></p><span class="status"><?= e($p["payment_status"] ?? "Pending") ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty"><i class="fa-solid fa-circle-info"></i> No payment history found.</div><?php endif; ?>
        </div>

        <div class="panel <?= $activeTab==='security'?'active':'' ?>" id="panel-security">
            <div class="section-head">
                <span class="pill"><i class="fa-solid fa-shield-halved"></i> Account Security</span>
                <h2>Account Security & Password</h2>
                <p>View your account protection status and update your password in the same section.</p>
            </div>

            <div class="mini-grid security-overview">
                <div class="security-card"><h3><i class="fa-solid fa-envelope-circle-check"></i> Email Verified</h3><p>Your account was created through email OTP verification.</p><br><span class="tag available"><i class="fa-solid fa-circle-check"></i> Verified</span></div>
                <div class="security-card"><h3><i class="fa-solid fa-lock"></i> Password Protection</h3><p>Your password is stored securely using PHP password_hash().</p><br><span class="tag available"><i class="fa-solid fa-shield"></i> Protected</span></div>
                <div class="security-card"><h3><i class="fa-solid fa-id-card"></i> Identity Data Locked</h3><p>Email, IC Number and Driving License Number are readonly for verification safety.</p><br><span class="tag warning"><i class="fa-solid fa-lock"></i> Readonly</span></div>
                <div class="security-card"><h3><i class="fa-solid fa-file-shield"></i> Document Verification</h3><p><?= $kycNeedsAttention ? "Payment is locked until your IC and driving license documents are verified by admin." : "Your IC and driving license documents are verified for payment." ?></p><br><span class="tag <?= $kycNeedsAttention ? 'warning' : 'available' ?>"><i class="fa-solid <?= $kycNeedsAttention ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i> <?= e(ucfirst($kycRequirement["state"])) ?></span><br><br><button class="mini-btn tab-jump" type="button" data-tab="documents"><i class="fa-solid fa-upload"></i> Upload Documents</button></div>
                <div class="security-card"><h3><i class="fa-solid fa-calendar-days"></i> Customer Since</h3><p><?= e($customerSince) ?></p><br><span class="tag"><i class="fa-solid fa-user-check"></i> Active Customer</span></div>
                <div class="security-card"><h3><i class="fa-solid fa-user-shield"></i> Account Role</h3><p><?= e(ucfirst($user["role"] ?? "Customer")) ?></p><br><span class="tag"><i class="fa-solid fa-id-badge"></i> Customer Access</span></div>
            </div>

            <div class="security-password-box password-collapse-card">
                <button class="password-collapse-header" type="button" id="togglePasswordBox">
                    <span>
                        <span class="pill"><i class="fa-solid fa-key"></i> Change Password</span>
                        <strong>Update Password</strong>
                        <small>Click to expand password settings.</small>
                    </span>
                    <i class="fa-solid fa-chevron-down password-collapse-icon"></i>
                </button>

                <div class="password-collapse-body" id="passwordCollapseBody">
                    <p class="password-collapse-desc">New password must follow the same strong password rule as registration.</p>

                    <form method="POST" action="my_profile.php?tab=security" id="passwordForm">
                <input type="hidden" name="action" value="change_password">
                <div class="form-grid">
                    <div class="form-group full"><label for="current_password">Current Password</label><div class="input-wrap password-wrap"><i class="fa-solid fa-lock"></i><input class="form-control" type="password" name="current_password" id="current_password" placeholder="Enter current password" required><button class="password-toggle" type="button" data-target="current_password"><i class="fa-solid fa-eye"></i></button></div></div>
                    <div class="form-group full">
                        <label for="new_password">New Password</label>
                        <div class="input-wrap password-wrap"><i class="fa-solid fa-key"></i><input class="form-control" type="password" name="new_password" id="new_password" placeholder="Create new password" required><button class="password-toggle" type="button" data-target="new_password"><i class="fa-solid fa-eye"></i></button></div>
                        <div class="password-box"><strong>Password Requirement</strong><ul class="requirement-list"><li id="reqLength"><i class="fa-solid fa-circle"></i> At least 10 characters</li><li id="reqUpper"><i class="fa-solid fa-circle"></i> At least 1 uppercase letter</li><li id="reqLower"><i class="fa-solid fa-circle"></i> At least 1 lowercase letter</li><li id="reqNumber"><i class="fa-solid fa-circle"></i> At least 1 number</li><li id="reqSymbol"><i class="fa-solid fa-circle"></i> At least 1 special symbol</li></ul></div>
                    </div>
                    <div class="form-group full"><label for="confirm_new_password">Confirm New Password</label><div class="input-wrap password-wrap"><i class="fa-solid fa-shield-halved"></i><input class="form-control" type="password" name="confirm_new_password" id="confirm_new_password" placeholder="Re-enter new password" required><button class="password-toggle" type="button" data-target="confirm_new_password"><i class="fa-solid fa-eye"></i></button></div><p class="note" id="matchText">Confirm New Password must match New Password.</p></div>
                </div>
                <button class="submit-btn" type="submit"><i class="fa-solid fa-shield-halved"></i> Update Password</button>
            </form>
                </div><!-- password-collapse-body-close-marker -->
            </div>
        </div>
    </section>
</main>

<div class="avatar-modal" id="avatarModal" aria-hidden="true">
    <div class="avatar-modal-card" data-current-avatar="<?= e($avatar) ?>">
        <button class="avatar-modal-close" type="button" id="closeAvatarModal"><i class="fa-solid fa-xmark"></i></button>
        <div class="avatar-modal-icon"><i class="fa-solid fa-image"></i></div>
        <h2>Change Profile Picture</h2>
        <p>Choose a JPG, PNG or WEBP image. Maximum size: 2MB.</p>
        <button class="submit-btn" type="button" id="chooseAvatarFile"><i class="fa-solid fa-upload"></i> Choose Picture</button>
        <button class="mini-btn outline" type="button" id="downloadAvatarBtn"><i class="fa-solid fa-download"></i> Download Current Picture</button>
        <p class="avatar-modal-note">After choosing a new picture, press <strong>Save Changes</strong> in Edit Profile to save it.</p>
    </div>
</div>


<div class="invoice-preview-modal" id="invoicePreviewModal" aria-hidden="true">
    <div class="invoice-preview-dialog">
        <div class="invoice-preview-head">
            <div class="invoice-preview-title">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <div>
                    <h3>Invoice Preview</h3>
                    <p class="muted" style="font-size:12px;line-height:1.3;">This preview uses the same invoice layout from booking details.</p>
                </div>
            </div>
            <div class="invoice-preview-actions">
                <button type="button" class="invoice-close-btn" onclick="closeInvoiceModal()" aria-label="Close invoice preview">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <iframe class="invoice-preview-frame" id="invoicePreviewFrame" title="Invoice Preview"></iframe>
    </div>
</div>
<iframe class="invoice-print-frame" id="invoicePrintFrame" title="Print Invoice"></iframe>
<script>
function printInvoiceFromProfile(bookingId){
    bookingId = parseInt(bookingId, 10) || 0;
    if(!bookingId) return;

    const frame = document.getElementById("invoicePrintFrame");
    if(!frame){
        window.open("booking_details.php?booking_id=" + bookingId + "&download=1", "_blank");
        return;
    }

    frame.src = "booking_details.php?booking_id=" + bookingId + "&download=1";

    frame.onload = function(){
        setTimeout(function(){
            try{
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }catch(error){
                window.open("booking_details.php?booking_id=" + bookingId + "&download=1", "_blank");
            }
        }, 500);
    };
}


let activeInvoiceBookingId = 0;

function openInvoiceModal(bookingId){
    activeInvoiceBookingId = parseInt(bookingId, 10) || 0;
    if(!activeInvoiceBookingId) return;

    const modal = document.getElementById("invoicePreviewModal");
    const frame = document.getElementById("invoicePreviewFrame");
    if(!modal || !frame) return;

    frame.src = "booking_details.php?booking_id=" + activeInvoiceBookingId + "&profile_invoice=1";
    modal.classList.add("show");
    modal.setAttribute("aria-hidden","false");
    document.body.style.overflow = "hidden";

    frame.onload = function(){
        try{
            const doc = frame.contentDocument || frame.contentWindow.document;
            const style = doc.createElement("style");
            style.textContent = `
                .navbar,
                .footer,
                .simple-header,
                .status-card,
                .info-card,
                .trip-card,
                .print-row{
                    display:none!important;
                }
                body{
                    background:#fff!important;
                    overflow:auto!important;
                }
                .page{
                    width:min(1120px,100%)!important;
                    margin:0 auto!important;
                    padding:14px!important;
                }
                .invoice{
                    margin:0!important;
                }
            `;
            doc.head.appendChild(style);
        }catch(error){}
    };
}

function closeInvoiceModal(){
    const modal = document.getElementById("invoicePreviewModal");
    const frame = document.getElementById("invoicePreviewFrame");
    if(modal){
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden","true");
    }
    if(frame){
        frame.src = "about:blank";
    }
    document.body.style.overflow = "";
}



document.addEventListener("keydown", function(event){
    if(event.key === "Escape"){
        closeInvoiceModal();
    }
});

document.addEventListener("click", function(event){
    const modal = document.getElementById("invoicePreviewModal");
    if(modal && event.target === modal){
        closeInvoiceModal();
    }
});



const avatarModal=document.getElementById("avatarModal");
const openAvatarModal=document.getElementById("openAvatarModal");
const closeAvatarModal=document.getElementById("closeAvatarModal");
const chooseAvatarFile=document.getElementById("chooseAvatarFile");
const downloadAvatarBtn=document.getElementById("downloadAvatarBtn");
const editAvatarPreview=document.getElementById("editAvatarPreview");
const profilePictureInput=document.getElementById("profile_picture");
const editProfileForm=document.getElementById("editProfileForm");

if(openAvatarModal&&avatarModal){openAvatarModal.addEventListener("click",()=>avatarModal.classList.add("show"))}
if(closeAvatarModal&&avatarModal){closeAvatarModal.addEventListener("click",()=>avatarModal.classList.remove("show"))}
if(avatarModal){avatarModal.addEventListener("click",e=>{if(e.target===avatarModal) avatarModal.classList.remove("show")})}
if(chooseAvatarFile&&profilePictureInput){chooseAvatarFile.addEventListener("click",()=>profilePictureInput.click())}
if(profilePictureInput){
    profilePictureInput.addEventListener("change",()=>{
        const file=profilePictureInput.files && profilePictureInput.files[0];
        if(!file) return;

        const previewURL=URL.createObjectURL(file);

        if(editAvatarPreview){
            editAvatarPreview.innerHTML=`<img src="${previewURL}" alt="Selected Profile Picture">`;
        }

document.querySelectorAll(".side-avatar,.nav-avatar,.avatar-circle").forEach(avatarBox=>{
            avatarBox.innerHTML=`<img src="${previewURL}" alt="Selected Profile Picture">`;
        });

        if(avatarModal){
            avatarModal.classList.remove("show");
        }
    });
}

if(downloadAvatarBtn){
    downloadAvatarBtn.addEventListener("click",()=>{
        const modalCard=document.querySelector(".avatar-modal-card");
        const currentAvatar=modalCard ? modalCard.dataset.currentAvatar : "";

        if(!currentAvatar){
            alert("No profile picture available to download yet.");
            return;
        }

        const link=document.createElement("a");
        link.href=currentAvatar;
        link.download="KH_Car_Rental_Profile_Picture";
        document.body.appendChild(link);
        link.click();
        link.remove();
    });
}

document.querySelectorAll("[data-toggle-booking]").forEach(button=>{
    button.addEventListener("click",()=>{
        const target=document.getElementById(button.dataset.toggleBooking);
        if(!target) return;
        target.classList.toggle("open");
        const icon=button.querySelector(".fa-chevron-down");
        if(icon) icon.style.transform=target.classList.contains("open") ? "rotate(180deg)" : "rotate(0deg)";
    });
});


const tabButtons=document.querySelectorAll(".tab-btn");
const panels=document.querySelectorAll(".panel");

function openTab(tab){
    tabButtons.forEach(b=>b.classList.toggle("active",b.dataset.tab===tab));
    panels.forEach(p=>p.classList.remove("active"));
    const panel=document.getElementById("panel-"+tab);
    if(panel) panel.classList.add("active");
    const url=new URL(window.location);
    url.searchParams.set("tab",tab);
    window.history.replaceState({}, "", url);
    window.scrollTo({top:0,behavior:"smooth"});
}

tabButtons.forEach(btn=>btn.addEventListener("click",()=>openTab(btn.dataset.tab)));
document.querySelectorAll(".tab-jump").forEach(btn=>btn.addEventListener("click",()=>openTab(btn.dataset.tab)));

const passwordForm=document.getElementById("passwordForm");
const newPassword=document.getElementById("new_password");
const confirmNewPassword=document.getElementById("confirm_new_password");
const matchText=document.getElementById("matchText");

const requirements={
    reqLength:v=>v.length>=10,
    reqUpper:v=>/[A-Z]/.test(v),
    reqLower:v=>/[a-z]/.test(v),
    reqNumber:v=>/[0-9]/.test(v),
    reqSymbol:v=>/[^A-Za-z0-9]/.test(v)
};

function updateRequirement(id,passed){
    const item=document.getElementById(id);
    if(!item) return;
    const icon=item.querySelector("i");
    item.classList.toggle("pass",passed);
    icon.className=passed ? "fa-solid fa-circle-check" : "fa-solid fa-circle";
}

function validatePassword(value){
    let ok=true;
    Object.keys(requirements).forEach(id=>{
        const passed=requirements[id](value);
        updateRequirement(id,passed);
        if(!passed) ok=false;
    });
    return ok;
}

function updatePasswordMatch(){
    if(!newPassword||!confirmNewPassword||!matchText) return;
    validatePassword(newPassword.value);
    if(confirmNewPassword.value===""){
        matchText.textContent="Confirm New Password must match New Password.";
        matchText.style.color="var(--muted)";
        return;
    }
    if(newPassword.value===confirmNewPassword.value){
        matchText.textContent="Password matched.";
        matchText.style.color="var(--green)";
    }else{
        matchText.textContent="Password does not match.";
        matchText.style.color="var(--danger)";
    }
}

if(newPassword&&confirmNewPassword){
    newPassword.addEventListener("input",updatePasswordMatch);
    confirmNewPassword.addEventListener("input",updatePasswordMatch);
}

document.querySelectorAll(".password-toggle").forEach(btn=>{
    btn.addEventListener("click",()=>{
        const input=document.getElementById(btn.dataset.target);
        const icon=btn.querySelector("i");
        input.type=input.type==="password" ? "text" : "password";
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    });
});

if(passwordForm){
    passwordForm.addEventListener("submit",e=>{
        if(!validatePassword(newPassword.value)||newPassword.value!==confirmNewPassword.value){
            e.preventDefault();
            updatePasswordMatch();
            window.scrollTo({top:0,behavior:"smooth"});
        }
    });
}


const togglePasswordBox=document.getElementById("togglePasswordBox");
const passwordCollapseBody=document.getElementById("passwordCollapseBody");

if(togglePasswordBox&&passwordCollapseBody){
    const card=togglePasswordBox.closest(".password-collapse-card");

    togglePasswordBox.addEventListener("click",()=>{
        card.classList.toggle("open");
    });

    if(new URL(window.location.href).searchParams.get("tab")==="security" && document.querySelector(".alert-danger")){
        card.classList.add("open");
    }
}


const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");
if(avatarBtn && profileDropdown){
    avatarBtn.addEventListener("click", event=>{
        event.stopPropagation();
        profileDropdown.classList.toggle("show");
    });
    document.addEventListener("click", ()=>{
        profileDropdown.classList.remove("show");
    });
}

</script>
</body>
</html>
