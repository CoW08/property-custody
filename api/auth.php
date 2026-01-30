<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        http_response_code(500);
        echo json_encode(array("error" => "Database connection failed"));
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("error" => "Server error", "message" => $e->getMessage()));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        if(isset($_GET['action'])) {
            if($_GET['action'] === 'login') {
                login($db);
            } elseif($_GET['action'] === 'logout') {
                logout();
            } elseif($_GET['action'] === 'register') {
                register($db);
            } elseif($_GET['action'] === 'verify_otp') {
                verifyOtp($db);
            } elseif($_GET['action'] === 'resend_otp') {
                resendOtp($db);
            }
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function register($db) {
    try {
        $data = json_decode(file_get_contents("php://input"));

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("error" => "Invalid JSON data"));
            return;
        }

        // Validate required fields
        if(empty($data->username) || empty($data->password) || empty($data->full_name) || empty($data->email)) {
            http_response_code(400);
            echo json_encode(array("error" => "All fields are required: username, password, full_name, email"));
            return;
        }

        // Validate email format
        if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(array("error" => "Invalid email format"));
            return;
        }

        // Check if username already exists
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(array("error" => "Username already exists"));
            return;
        }

        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(array("error" => "Email already exists"));
            return;
        }

        // Set default role if not provided
        $role = isset($data->role) ? $data->role : 'staff';
        $department = isset($data->department) ? $data->department : 'General';

        // Hash password for production use
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);

        // Insert new user
        $query = "INSERT INTO users (username, password, full_name, email, role, department, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $db->prepare($query);

        if($stmt->execute([$data->username, $hashedPassword, $data->full_name, $data->email, $role, $department])) {
            $userId = $db->lastInsertId();

            // Log the registration
            logActivity($db, $userId, 'register', 'users', $userId);

            http_response_code(201);
            echo json_encode(array(
                "message" => "User registered successfully",
                "user" => array(
                    "id" => $userId,
                    "username" => $data->username,
                    "full_name" => $data->full_name,
                    "email" => $data->email,
                    "role" => $role,
                    "department" => $department
                )
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("error" => "Failed to register user"));
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "Registration error", "message" => $e->getMessage()));
    }
}

function login($db) {
    try {
        $data = json_decode(file_get_contents("php://input"));

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("error" => "Invalid JSON data"));
            return;
        }

        if(empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(array("message" => "Username/email and password are required"));
            return;
        }

        $identifier = trim($data->username);

        $query = "SELECT id, username, password, full_name, email, role, department, status
                  FROM users
                  WHERE (username = ? OR email = ?)
                  AND status = 'active' LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$identifier, $identifier]);

        if($stmt->rowCount() === 0) {
            http_response_code(401);
            echo json_encode(array("message" => "User not found"));
            return;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $demo_passwords = [
            'admin' => 'admin123',
            'custodian' => 'custodian123',
            'staff' => 'staff123'
        ];

        $passwordValid = false;

        if(password_verify($data->password, $row['password'])) {
            $passwordValid = true;
        } elseif(isset($demo_passwords[$row['username']]) && $demo_passwords[$row['username']] === $data->password) {
            $passwordValid = true;
        }

        if(!$passwordValid) {
            http_response_code(401);
            echo json_encode(array("message" => "Invalid credentials"));
            return;
        }

        if((!defined('OTP_DEV_MODE') || !OTP_DEV_MODE) && (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL))) {
            http_response_code(400);
            echo json_encode(array("message" => "Two-factor authentication requires a valid email address on file. Please contact an administrator."));
            return;
        }

        $otpData = createOtpRecord($db, $row['id']);

        if(!$otpData) {
            http_response_code(500);
            echo json_encode(array("error" => "Failed to generate verification code"));
            return;
        }

        $emailSent = sendOtpEmail($row['email'], $row['full_name'] ?: $row['username'], $otpData['code']);

        if(!$emailSent && (!defined('OTP_DEV_MODE') || !OTP_DEV_MODE)) {
            removeOtpByToken($db, $otpData['token']);
            http_response_code(500);
            echo json_encode(array("error" => "Failed to send verification email. Please try again later."));
            return;
        }

        http_response_code(200);
        echo json_encode(array(
            "message" => "A verification code has been sent to your email.",
            "status" => "otp_required",
            "otp_token" => $otpData['token'],
            "email_hint" => maskEmail($row['email']),
            "otp_dev_code" => (defined('OTP_DEV_MODE') && OTP_DEV_MODE) ? $otpData['code'] : null
        ));

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "Login error", "message" => $e->getMessage()));
    }
}

function logout() {
    session_start();
    session_destroy();
    http_response_code(200);
    echo json_encode(array("message" => "Logout successful"));
}

function verifyOtp($db) {
    try {
        $data = json_decode(file_get_contents("php://input"));

        if(!$data || empty($data->otp_token) || empty($data->otp_code)) {
            http_response_code(400);
            echo json_encode(array("message" => "OTP token and code are required"));
            return;
        }

        ensureOtpTable($db);

        $query = "SELECT t.id as token_id, t.user_id, t.otp_hash, t.expires_at, t.attempts, t.created_at, u.username, u.full_name, u.email, u.role, u.department, u.status
                  FROM user_otp_tokens t
                  INNER JOIN users u ON t.user_id = u.id
                  WHERE t.token = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data->otp_token]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$record) {
            http_response_code(404);
            echo json_encode(array("message" => "Verification session not found"));
            return;
        }

        if($record['status'] !== 'active') {
            removeOtpByToken($db, $data->otp_token);
            http_response_code(403);
            echo json_encode(array("message" => "Account is not active"));
            return;
        }

        if(strtotime($record['expires_at']) < time()) {
            removeOtpByToken($db, $data->otp_token);
            http_response_code(410);
            echo json_encode(array("message" => "Verification code has expired. Please sign in again."));
            return;
        }

        if(!password_verify((string)$data->otp_code, $record['otp_hash'])) {
            $attempts = (int)$record['attempts'] + 1;
            $update = $db->prepare("UPDATE user_otp_tokens SET attempts = ? WHERE id = ?");
            $update->execute([$attempts, $record['token_id']]);

            if($attempts >= 5) {
                removeOtpByToken($db, $data->otp_token);
                http_response_code(429);
                echo json_encode(array("message" => "Too many incorrect attempts. Please sign in again."));
                return;
            }

            http_response_code(401);
            echo json_encode(array(
                "message" => "Invalid verification code",
                "attempts_remaining" => max(0, 5 - $attempts)
            ));
            return;
        }

        removeOtpByToken($db, $data->otp_token);

        session_start();
        $_SESSION['user_id'] = $record['user_id'];
        $_SESSION['username'] = $record['username'];
        $_SESSION['full_name'] = $record['full_name'];
        $_SESSION['email'] = $record['email'];
        $_SESSION['role'] = $record['role'];
        $_SESSION['department'] = $record['department'];

        logActivity($db, $record['user_id'], 'login', 'users', $record['user_id']);

        http_response_code(200);
        echo json_encode(array(
            "message" => "Login successful",
            "user" => array(
                "id" => $record['user_id'],
                "username" => $record['username'],
                "full_name" => $record['full_name'],
                "email" => $record['email'],
                "role" => $record['role'],
                "department" => $record['department']
            )
        ));

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "OTP verification error", "message" => $e->getMessage()));
    }
}

function resendOtp($db) {
    try {
        $data = json_decode(file_get_contents("php://input"));

        if(!$data || empty($data->otp_token)) {
            http_response_code(400);
            echo json_encode(array("message" => "OTP token is required"));
            return;
        }

        ensureOtpTable($db);

        $query = "SELECT t.user_id, t.created_at, u.email, u.full_name, u.username, u.status
                  FROM user_otp_tokens t
                  INNER JOIN users u ON t.user_id = u.id
                  WHERE t.token = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data->otp_token]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$record) {
            http_response_code(404);
            echo json_encode(array("message" => "Verification session not found"));
            return;
        }

        if($record['status'] !== 'active') {
            removeOtpByToken($db, $data->otp_token);
            http_response_code(403);
            echo json_encode(array("message" => "Account is not active"));
            return;
        }

        if(strtotime($record['created_at']) > time() - 60) {
            http_response_code(429);
            echo json_encode(array("message" => "Please wait a moment before requesting another code."));
            return;
        }

        $otpData = createOtpRecord($db, $record['user_id']);

        if(!$otpData) {
            http_response_code(500);
            echo json_encode(array("error" => "Failed to generate verification code"));
            return;
        }

        $emailSent = sendOtpEmail($record['email'], $record['full_name'] ?: $record['username'], $otpData['code']);

        if(!$emailSent && (!defined('OTP_DEV_MODE') || !OTP_DEV_MODE)) {
            removeOtpByToken($db, $otpData['token']);
            http_response_code(500);
            echo json_encode(array("error" => "Failed to send verification email. Please try again later."));
            return;
        }

        http_response_code(200);
        echo json_encode(array(
            "message" => "A new verification code has been sent.",
            "status" => "otp_required",
            "otp_token" => $otpData['token'],
            "otp_dev_code" => (defined('OTP_DEV_MODE') && OTP_DEV_MODE) ? $otpData['code'] : null
        ));

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "OTP resend error", "message" => $e->getMessage()));
    }
}

function logActivity($db, $user_id, $action, $table_name, $record_id) {
    $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $action, $table_name, $record_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

function ensureOtpTable($db) {
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;
    $stmt = $db->query("SHOW TABLES LIKE 'user_otp_tokens'");

    if($stmt->rowCount() === 0) {
        $createTableSql = "CREATE TABLE IF NOT EXISTS user_otp_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            otp_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            attempts TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($createTableSql);
    }
}

function createOtpRecord($db, $userId) {
    try {
        ensureOtpTable($db);

        $delete = $db->prepare("DELETE FROM user_otp_tokens WHERE user_id = ?");
        $delete->execute([$userId]);

        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpToken = bin2hex(random_bytes(16));
        $otpHash = password_hash($otpCode, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 300);

        $insert = $db->prepare("INSERT INTO user_otp_tokens (user_id, token, otp_hash, expires_at) VALUES (?, ?, ?, ?)");
        $insert->execute([$userId, $otpToken, $otpHash, $expiresAt]);

        return array(
            'token' => $otpToken,
            'code' => $otpCode,
            'expires_at' => $expiresAt
        );
    } catch (Exception $e) {
        error_log('Failed to create OTP record: ' . $e->getMessage());
        return null;
    }
}

function removeOtpByToken($db, $token) {
    try {
        $stmt = $db->prepare("DELETE FROM user_otp_tokens WHERE token = ?");
        $stmt->execute([$token]);
    } catch (Exception $e) {
        error_log('Failed to remove OTP token: ' . $e->getMessage());
    }
}

function sendOtpEmail($toEmail, $recipientName, $otpCode) {
    if((defined('OTP_DEV_MODE') && OTP_DEV_MODE)) {
        return true;
    }

    if(empty($toEmail)) {
        return false;
    }

    $fromEmail = FROM_EMAIL ?: SMTP_USERNAME;
    $fromName = FROM_NAME ?: 'Property Custodian System';

    $subject = 'Your verification code';
    $displayName = $recipientName ?: 'there';

    $textBody = "Hello {$displayName},\n\n" .
        "Use the verification code below to finish signing in:\n\n" .
        $otpCode . "\n\n" .
        "This code will expire in 5 minutes. If you didn't request this, please let an administrator know." . "\n\n" .
        $fromName;

    $htmlBody = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#f2f4f8;font-family:\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f2f4f8;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="520" style="background:#ffffff;border-radius:18px;box-shadow:0 20px 45px rgba(15,23,42,0.12);overflow:hidden;">
                    <tr>
                        <td style="padding:32px 40px 24px;background:linear-gradient(135deg,#2563eb,#6366f1,#9333ea);color:#ffffff;">
                            <h1 style="margin:0;font-size:24px;font-weight:600;">Your verification code</h1>
                            <p style="margin:8px 0 0;font-size:14px;opacity:0.9;">Property Custodian Management</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px;color:#1f2937;">
                            <p style="margin:0 0 14px;font-size:16px;">Hello <span style="font-weight:600;">' . htmlspecialchars($displayName) . '</span>,</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;">Use the secure code below to finish signing in. For your protection this code expires in <strong>5 minutes</strong>.</p>
                            <div style="margin:0 auto 28px;width:100%;max-width:280px;background:#1d4ed8;color:#ffffff;border-radius:16px;padding:18px 24px;text-align:center;font-size:32px;font-weight:700;letter-spacing:10px;">
                                ' . htmlspecialchars($otpCode) . '
                            </div>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#4b5563;">If you did not request this code, please let an administrator know immediately so we can secure your account.</p>
                            <p style="margin:0;font-size:14px;color:#9ca3af;">Thank you,<br><strong>' . htmlspecialchars($fromName) . '</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 40px 28px;background:#f8fafc;color:#94a3b8;font-size:12px;text-align:center;">This email was sent automatically by the Property Custodian Management System. Please do not reply directly to this message.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return smtpSendMail($toEmail, $subject, $textBody, $htmlBody, $fromEmail, $fromName);
}

function maskEmail($email) {
    if(strpos($email, '@') === false) {
        return $email;
    }

    list($name, $domain) = explode('@', $email, 2);
    $visible = substr($name, 0, 2);
    $maskedLength = max(1, strlen($name) - strlen($visible));
    return $visible . str_repeat('*', $maskedLength) . '@' . $domain;
}

function smtpSendMail($toEmail, $subject, $textBody, $htmlBody, $fromEmail, $fromName) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USERNAME;
    $password = SMTP_PASSWORD;

    if (empty($host) || empty($username) || empty($password)) {
        error_log('SMTP configuration incomplete.');
        return false;
    }

    $encryption = defined('SMTP_ENCRYPTION') ? strtolower(SMTP_ENCRYPTION) : 'tls';
    $useImplicitTls = ($port == 465) || $encryption === 'ssl';
    $transport = ($useImplicitTls ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}");

    $socket = @stream_socket_client($transport, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log("SMTP connection failed: {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, 20);

    if (!smtpExpect($socket, [220])) {
        fclose($socket);
        return false;
    }

    $hostname = gethostname() ?: 'localhost';
    if (!smtpExpect($socket, [250], "EHLO {$hostname}")) {
        fclose($socket);
        return false;
    }

    if (!$useImplicitTls) {
        if (!smtpExpect($socket, [220], 'STARTTLS')) {
            fclose($socket);
            return false;
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('Unable to start TLS for SMTP connection.');
            fclose($socket);
            return false;
        }

        if (!smtpExpect($socket, [250], "EHLO {$hostname}")) {
            fclose($socket);
            return false;
        }
    }

    if (!smtpExpect($socket, [334], 'AUTH LOGIN')) {
        fclose($socket);
        return false;
    }

    if (!smtpExpect($socket, [334], base64_encode($username))) {
        fclose($socket);
        return false;
    }

    if (!smtpExpect($socket, [235], base64_encode($password))) {
        fclose($socket);
        return false;
    }

    if (!smtpExpect($socket, [250], "MAIL FROM:<{$fromEmail}>")) {
        fclose($socket);
        return false;
    }

    if (!smtpExpect($socket, [250], "RCPT TO:<{$toEmail}>")) {
        fclose($socket);
        return false;
    }

    if (!smtpExpect($socket, [354], 'DATA')) {
        fclose($socket);
        return false;
    }

    $boundary = 'b' . bin2hex(random_bytes(15));
    $hasHtml = !empty($htmlBody);

    $headers = [];
    $headers[] = "From: {$fromName} <{$fromEmail}>";
    $headers[] = "To: {$toEmail}";
    $headers[] = "Subject: {$subject}";
    $headers[] = 'MIME-Version: 1.0';
    if ($hasHtml) {
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
    }

    if ($hasHtml) {
        $parts = [];
        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: text/plain; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: 8bit';
        $parts[] = '';
        $parts[] = $textBody;
        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: text/html; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: 8bit';
        $parts[] = '';
        $parts[] = $htmlBody;
        $parts[] = '--' . $boundary . '--';
        $messageBody = implode("\r\n", $parts);
    } else {
        $messageBody = $textBody;
    }

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $messageBody . "\r\n";

    fwrite($socket, $message . ".\r\n");
    if (!smtpExpect($socket, [250])) {
        fclose($socket);
        return false;
    }

    smtpExpect($socket, [221], 'QUIT');
    fclose($socket);
    return true;
}

function smtpExpect($socket, $expectedCodes, $command = null) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }

    $response = smtpReadResponse($socket);
    if ($response === false) {
        return false;
    }

    foreach ((array)$expectedCodes as $code) {
        if (strpos($response, (string)$code) === 0) {
            return true;
        }
    }

    error_log('Unexpected SMTP response: ' . trim($response));
    return false;
}

function smtpReadResponse($socket) {
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }

    return $response === '' ? false : $response;
}
?>