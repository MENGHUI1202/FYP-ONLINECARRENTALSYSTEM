<?php
if (!function_exists("ensureTermsAcceptanceTable")) {
    function ensureTermsAcceptanceTable($conn) {
        $conn->query("
            CREATE TABLE IF NOT EXISTS terms_acceptances (
                acceptance_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                booking_id INT NULL,
                context VARCHAR(40) NOT NULL,
                terms_version VARCHAR(30) NOT NULL,
                accepted_at DATETIME NOT NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_terms_user (user_id),
                INDEX idx_terms_booking (booking_id),
                INDEX idx_terms_context (context)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
}

if (!function_exists("recordTermsAcceptance")) {
    function recordTermsAcceptance($conn, $userId, $context, $version, $bookingId = null) {
        ensureTermsAcceptanceTable($conn);

        $acceptedAt = date("Y-m-d H:i:s");
        $ipAddress = substr((string)($_SERVER["REMOTE_ADDR"] ?? ""), 0, 64);
        $userAgent = substr((string)($_SERVER["HTTP_USER_AGENT"] ?? ""), 0, 255);
        if ($bookingId !== null) {
            $bookingIdValue = (int)$bookingId;
            $stmt = $conn->prepare("
                INSERT INTO terms_acceptances
                (user_id, booking_id, context, terms_version, accepted_at, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
        } else {
            $stmt = $conn->prepare("
                INSERT INTO terms_acceptances
                (user_id, context, terms_version, accepted_at, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
        }

        if (!$stmt) {
            return false;
        }

        if ($bookingId !== null) {
            $stmt->bind_param(
                "iisssss",
                $userId,
                $bookingIdValue,
                $context,
                $version,
                $acceptedAt,
                $ipAddress,
                $userAgent
            );
        } else {
            $stmt->bind_param(
                "isssss",
                $userId,
                $context,
                $version,
                $acceptedAt,
                $ipAddress,
                $userAgent
            );
        }

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
?>
