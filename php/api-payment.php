<?php
// ===== LOTUS HOTEL - PAYMENT API =====
// Handles payment processing for bookings

require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'process';

// Rate limiting - stricter for payments
$identifier = $_SERVER['REMOTE_ADDR'] . '_payment_api';
if (!checkRateLimit($identifier, 20, 3600)) { // 20 payment attempts per hour
    sendErrorResponse('Rate limit exceeded. Please try again later.', null, 429);
}

try {
    switch ($method) {
        case 'GET':
            handlePaymentGetRequest($action);
            break;
        case 'POST':
            handlePaymentPostRequest($action);
            break;
        default:
            sendErrorResponse('Method not allowed', null, 405);
    }
} catch (Exception $e) {
    error_log("Payment API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error', null, 500);
}

function handlePaymentGetRequest($action) {
    switch ($action) {
        case 'status':
            getPaymentStatus();
            break;
        default:
            sendErrorResponse('Invalid action', null, 400);
    }
}

function handlePaymentPostRequest($action) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    switch ($action) {
        case 'process':
            processPayment($input);
            break;
        default:
            sendErrorResponse('Invalid action', null, 400);
    }
}

function processPayment($data) {
    try {
        // Validate required fields
        $required = ['booking_reference', 'payment_method', 'amount'];
        $errors = validateRequired($data, $required);

        if (!empty($errors)) {
            sendErrorResponse('Validation failed', $errors, 400);
        }

        $bookingRef = sanitizeInput($data['booking_reference']);
        $paymentMethod = sanitizeInput($data['payment_method']);
        $amount = (float)$data['amount'];

        // Validate payment method
        $validMethods = ['credit-card', 'debit-card', 'net-banking', 'digital-wallet', 'upi'];
        if (!in_array($paymentMethod, $validMethods)) {
            sendErrorResponse('Invalid payment method', null, 400);
        }

        // Validate amount
        if ($amount <= 0 || $amount > 50000) { // Max $50,000
            sendErrorResponse('Invalid payment amount', null, 400);
        }

        $conn = getDBConnection();

        // Get booking details
        $stmt = $conn->prepare("
            SELECT b.*, r.name as room_name, u.email, u.first_name, u.last_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN users u ON b.user_id = u.id
            WHERE b.booking_reference = ? AND b.status = 'pending'
        ");
        $stmt->execute([$bookingRef]);
        $booking = $stmt->fetch();

        if (!$booking) {
            sendErrorResponse('Booking not found or already processed', null, 404);
        }

        // Verify amount matches booking
        if (abs($booking['total_amount'] - $amount) > 0.01) {
            sendErrorResponse('Payment amount does not match booking total', null, 400);
        }

        // Begin transaction
        $conn->beginTransaction();

        // Process payment based on method
        $paymentProcessor = new PaymentProcessor(getGatewayFromMethod($paymentMethod));
        $paymentResult = $paymentProcessor->processPayment($data);

        if (!$paymentResult['success']) {
            $conn->rollBack();
            sendErrorResponse($paymentResult['message'], null, 400);
        }

        // Create payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (
                booking_id, payment_method, transaction_id, amount, currency,
                status, gateway_response, created_at
            ) VALUES (?, ?, ?, ?, 'USD', 'completed', ?, NOW())
        ");

        $stmt->execute([
            $booking['id'],
            $paymentMethod,
            $paymentResult['transaction_id'],
            $amount,
            json_encode($paymentResult)
        ]);

        $paymentId = $conn->lastInsertId();

        // Update booking status
        $stmt = $conn->prepare("
            UPDATE bookings
            SET status = 'confirmed', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$booking['id']]);

        // Log activity
        logActivity('payment_completed', $booking['user_id'],
            "Payment of $" . $amount . " for booking " . $bookingRef);

        $conn->commit();

        // Send confirmation email
        sendBookingConfirmation($booking, $paymentResult['transaction_id']);

        $response = [
            'payment_id' => $paymentId,
            'transaction_id' => $paymentResult['transaction_id'],
            'booking_reference' => $bookingRef,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'completed',
            'payment_method' => $paymentMethod
        ];

        sendSuccessResponse($response, 'Payment processed successfully');

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Process payment error: " . $e->getMessage());
        sendErrorResponse('Payment processing failed', null, 500);
    }
}

function getPaymentStatus() {
    try {
        $bookingRef = $_GET['booking_reference'] ?? null;

        if (!$bookingRef) {
            sendErrorResponse('Booking reference is required', null, 400);
        }

        $conn = getDBConnection();

        $stmt = $conn->prepare("
            SELECT p.*, b.booking_reference, b.status as booking_status
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE b.booking_reference = ?
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$bookingRef]);
        $payment = $stmt->fetch();

        if (!$payment) {
            sendErrorResponse('Payment not found', null, 404);
        }

        $response = [
            'payment_id' => $payment['id'],
            'booking_reference' => $payment['booking_reference'],
            'payment_method' => $payment['payment_method'],
            'transaction_id' => $payment['transaction_id'],
            'amount' => (float)$payment['amount'],
            'currency' => $payment['currency'],
            'status' => $payment['status'],
            'booking_status' => $payment['booking_status'],
            'created_at' => $payment['created_at']
        ];

        sendSuccessResponse($response, 'Payment status retrieved successfully');

    } catch (Exception $e) {
        error_log("Get payment status error: " . $e->getMessage());
        sendErrorResponse('Failed to retrieve payment status', null, 500);
    }
}

function getGatewayFromMethod($method) {
    $gatewayMap = [
        'credit-card' => 'stripe',
        'debit-card' => 'stripe',
        'net-banking' => 'razorpay',
        'digital-wallet' => 'razorpay',
        'upi' => 'razorpay'
    ];

    return $gatewayMap[$method] ?? 'stripe';
}

function sendBookingConfirmation($booking, $transactionId) {
    try {
        $subject = "LOTUS Hotel - Booking Confirmation #" . $booking['booking_reference'];

        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .header { background: linear-gradient(135deg, #D4AF37, #2C323E); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .booking-details { background: #f9f9f9; padding: 15px; margin: 20px 0; border-radius: 8px; }
                .footer { background: #2C323E; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>LOTUS Hotel</h1>
                <p>Booking Confirmation</p>
            </div>

            <div class='content'>
                <h2>Dear {$booking['first_name']} {$booking['last_name']},</h2>

                <p>Thank you for choosing LOTUS Hotel. Your booking has been confirmed!</p>

                <div class='booking-details'>
                    <h3>Booking Details</h3>
                    <p><strong>Booking Reference:</strong> {$booking['booking_reference']}</p>
                    <p><strong>Room:</strong> {$booking['room_name']}</p>
                    <p><strong>Check-in:</strong> " . date('F j, Y', strtotime($booking['check_in'])) . "</p>
                    <p><strong>Check-out:</strong> " . date('F j, Y', strtotime($booking['check_out'])) . "</p>
                    <p><strong>Guests:</strong> {$booking['guests']}</p>
                    <p><strong>Total Amount:</strong> $" . number_format($booking['total_amount'], 2) . "</p>
                    <p><strong>Transaction ID:</strong> {$transactionId}</p>
                </div>

                <p>We look forward to welcoming you to LOTUS Hotel. If you have any questions, please don't hesitate to contact us.</p>

                <p>Best regards,<br>LOTUS Hotel Team</p>
            </div>

            <div class='footer'>
                <p>LOTUS Hotel | Luxury Redefined</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>
        ";

        sendEmail($booking['email'], $subject, $message);

    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        // Don't fail the payment if email fails
    }
}

// ===== PAYMENT VALIDATION FUNCTIONS =====

function validateCreditCardData($data) {
    $errors = [];

    // Cardholder name
    if (empty($data['cardholder']) || strlen($data['cardholder']) < 2) {
        $errors[] = 'Valid cardholder name is required';
    }

    // Card number
    $cardNumber = preg_replace('/\s+/', '', $data['card_number'] ?? '');
    if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
        $errors[] = 'Valid card number is required';
    }

    // Expiry date
    if (empty($data['expiry']) || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $data['expiry'])) {
        $errors[] = 'Valid expiry date (MM/YY) is required';
    }

    // CVV
    if (empty($data['cvv']) || !preg_match('/^\d{3,4}$/', $data['cvv'])) {
        $errors[] = 'Valid CVV is required';
    }

    return $errors;
}

function validateUpiData($data) {
    $errors = [];

    if (empty($data['upi_id']) || !preg_match('/^[\w\.\-]+@[\w\-]+\.[\w\-]+$/', $data['upi_id'])) {
        $errors[] = 'Valid UPI ID is required';
    }

    return $errors;
}

function validateNetBankingData($data) {
    $errors = [];

    if (empty($data['bank'])) {
        $errors[] = 'Bank selection is required';
    }

    if (empty($data['user_id'])) {
        $errors[] = 'User ID is required';
    }

    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    }

    return $errors;
}

function validateWalletData($data) {
    $errors = [];

    if (empty($data['wallet'])) {
        $errors[] = 'Wallet selection is required';
    }

    if (empty($data['mobile'])) {
        $errors[] = 'Registered mobile number is required';
    }

    return $errors;
}

// ===== ADDITIONAL PAYMENT METHODS =====

function processUPIPayment($data) {
    // UPI payment processing
    // This would integrate with UPI gateway
    try {
        // Simulate UPI processing
        $success = rand(0, 1);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'UPI' . generateToken(12),
                'message' => 'UPI payment processed successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'UPI payment failed - invalid UPI ID or insufficient balance'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'UPI payment processing error: ' . $e->getMessage()
        ];
    }
}

function processNetBankingPayment($data) {
    // Net banking payment processing
    try {
        // Simulate net banking processing
        $success = rand(0, 1);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'NB' . generateToken(12),
                'message' => 'Net banking payment processed successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Net banking payment failed - authentication failed'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Net banking payment processing error: ' . $e->getMessage()
        ];
    }
}

function processWalletPayment($data) {
    // Digital wallet payment processing
    try {
        // Simulate wallet processing
        $success = rand(0, 1);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'WALLET' . generateToken(12),
                'message' => 'Digital wallet payment processed successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Digital wallet payment failed - insufficient balance'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Digital wallet payment processing error: ' . $e->getMessage()
        ];
    }
}
?>
