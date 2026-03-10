<?php
// ===== LOTUS HOTEL - CONFIGURATION =====
// Database configuration, security settings, and utility functions

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'lotus_hotel');
define('DB_USER', 'root'); // Change this to your database username
define('DB_PASS', ''); // Change this to your database password

// ===== PAYMENT GATEWAY CONFIGURATION =====
// Add your actual payment gateway credentials here
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key_here');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_stripe_publishable_key_here');

define('RAZORPAY_KEY_ID', 'rzp_test_your_razorpay_key_id_here');
define('RAZORPAY_KEY_SECRET', 'your_razorpay_key_secret_here');

define('PAYPAL_CLIENT_ID', 'your_paypal_client_id_here');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret_here');

// ===== EMAIL CONFIGURATION =====
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('FROM_EMAIL', 'noreply@lotushotel.com');
define('FROM_NAME', 'LOTUS Hotel');

// ===== SECURITY SETTINGS =====
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');
define('JWT_SECRET', 'your-jwt-secret-key-here');
define('SESSION_LIFETIME', 3600); // 1 hour

// ===== APPLICATION SETTINGS =====
define('APP_NAME', 'LOTUS Hotel');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://lotushotel.com');
define('TIMEZONE', 'Asia/Kolkata');

// ===== CORS HEADERS =====
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===== DATABASE CONNECTION =====
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }
    }

    return $conn;
}

// ===== UTILITY FUNCTIONS =====

// Sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number
function validatePhone($phone) {
    $phone = preg_replace('/[^\d]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

// Generate secure token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate booking reference
function generateBookingReference() {
    return 'LOTUS-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹'
    ];

    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2);
}

// Calculate nights between dates
function calculateNights($checkIn, $checkOut) {
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);

    $interval = $checkInDate->diff($checkOutDate);
    return $interval->days;
}

// Validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Send email
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];

    $allHeaders = array_merge($defaultHeaders, $headers);

    return mail($to, $subject, $message, implode("\r\n", $allHeaders));
}

// Log activity
function logActivity($action, $userId = null, $details = null) {
    try {
        $conn = getDBConnection();

        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Rate limiting
function checkRateLimit($identifier, $limit = 10, $window = 3600) {
    try {
        $conn = getDBConnection();

        // Clean old entries
        $stmt = $conn->prepare("
            DELETE FROM rate_limits
            WHERE identifier = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $window]);

        // Count current requests
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM rate_limits
            WHERE identifier = ?
        ");
        $stmt->execute([$identifier]);
        $result = $stmt->fetch();

        if ($result['count'] >= $limit) {
            return false;
        }

        // Add new request
        $stmt = $conn->prepare("
            INSERT INTO rate_limits (identifier, created_at)
            VALUES (?, NOW())
        ");
        $stmt->execute([$identifier]);

        return true;

    } catch (Exception $e) {
        error_log("Rate limiting failed: " . $e->getMessage());
        return true; // Allow request if rate limiting fails
    }
}

// ===== PAYMENT PROCESSOR CLASS =====
class PaymentProcessor {
    private $gateway;

    public function __construct($gateway = 'stripe') {
        $this->gateway = $gateway;
    }

    public function processPayment($paymentData) {
        switch ($this->gateway) {
            case 'stripe':
                return $this->processStripePayment($paymentData);
            case 'razorpay':
                return $this->processRazorpayPayment($paymentData);
            case 'paypal':
                return $this->processPayPalPayment($paymentData);
            default:
                return ['success' => false, 'message' => 'Unsupported payment gateway'];
        }
    }

    private function processStripePayment($data) {
        // Stripe payment processing logic
        // This is a placeholder - implement actual Stripe API calls
        try {
            // Simulate payment processing
            $success = rand(0, 1); // Random success/failure for demo

            if ($success) {
                return [
                    'success' => true,
                    'transaction_id' => 'stripe_' . generateToken(16),
                    'message' => 'Payment processed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Payment failed - insufficient funds'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }

    private function processRazorpayPayment($data) {
        // Razorpay payment processing logic
        try {
            $success = rand(0, 1);

            if ($success) {
                return [
                    'success' => true,
                    'transaction_id' => 'razorpay_' . generateToken(16),
                    'message' => 'Payment processed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Payment failed'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }

    private function processPayPalPayment($data) {
        // PayPal payment processing logic
        try {
            $success = rand(0, 1);

            if ($success) {
                return [
                    'success' => true,
                    'transaction_id' => 'paypal_' . generateToken(16),
                    'message' => 'Payment processed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Payment failed'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }
}

// ===== ERROR HANDLING =====
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    error_log(json_encode($error));

    // Don't show detailed errors in production
    if (ini_get('display_errors')) {
        return false;
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit();
}

set_error_handler('handleError');

// ===== SESSION MANAGEMENT =====
function startSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        session_start();

        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > SESSION_LIFETIME) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// ===== INPUT VALIDATION =====
function validateRequired($data, $fields) {
    $errors = [];

    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    return $errors;
}

function validateBookingData($data) {
    $errors = [];

    // Required fields
    $required = ['firstName', 'lastName', 'email', 'phone', 'checkIn', 'checkOut', 'roomType'];
    $errors = array_merge($errors, validateRequired($data, $required));

    // Email validation
    if (isset($data['email']) && !validateEmail($data['email'])) {
        $errors[] = 'Invalid email address';
    }

    // Phone validation
    if (isset($data['phone']) && !validatePhone($data['phone'])) {
        $errors[] = 'Invalid phone number';
    }

    // Date validation
    if (isset($data['checkIn']) && !validateDate($data['checkIn'])) {
        $errors[] = 'Invalid check-in date';
    }

    if (isset($data['checkOut']) && !validateDate($data['checkOut'])) {
        $errors[] = 'Invalid check-out date';
    }

    // Date logic validation
    if (isset($data['checkIn']) && isset($data['checkOut']) &&
        validateDate($data['checkIn']) && validateDate($data['checkOut'])) {
        if (strtotime($data['checkOut']) <= strtotime($data['checkIn'])) {
            $errors[] = 'Check-out date must be after check-in date';
        }
    }

    // Room type validation
    $validRoomTypes = ['deluxe', 'executive', 'presidential'];
    if (isset($data['roomType']) && !in_array($data['roomType'], $validRoomTypes)) {
        $errors[] = 'Invalid room type';
    }

    return $errors;
}

// ===== RESPONSE HELPERS =====
function sendSuccessResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit();
}

function sendErrorResponse($message = 'An error occurred', $errors = null, $code = 400) {
    http_response_code($code);

    $response = ['success' => false, 'message' => $message];

    if ($errors !== null) {
        $response['errors'] = $errors;
    }

    echo json_encode($response);
    exit();
}

// ===== INITIALIZATION =====
// Set timezone
date_default_timezone_set(TIMEZONE);

// Start secure session if needed
// startSecureSession(); // Uncomment if using sessions

?>
