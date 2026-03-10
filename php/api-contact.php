<?php
// ===== LOTUS HOTEL - CONTACT API =====
// Handles contact form submissions and inquiries

require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'submit';

// Rate limiting
$identifier = $_SERVER['REMOTE_ADDR'] . '_contact_api';
if (!checkRateLimit($identifier, 50, 3600)) { // 50 contact submissions per hour
    sendErrorResponse('Rate limit exceeded. Please try again later.', null, 429);
}

try {
    switch ($method) {
        case 'GET':
            handleContactGetRequest($action);
            break;
        case 'POST':
            handleContactPostRequest($action);
            break;
        default:
            sendErrorResponse('Method not allowed', null, 405);
    }
} catch (Exception $e) {
    error_log("Contact API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error', null, 500);
}

function handleContactGetRequest($action) {
    switch ($action) {
        case 'list':
            getContactSubmissions();
            break;
        default:
            sendErrorResponse('Invalid action', null, 400);
    }
}

function handleContactPostRequest($action) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    switch ($action) {
        case 'submit':
            submitContactForm($input);
            break;
        default:
            sendErrorResponse('Invalid action', null, 400);
    }
}

function submitContactForm($data) {
    try {
        // Validate required fields
        $required = ['firstName', 'lastName', 'email', 'subject', 'message'];
        $errors = validateRequired($data, $required);

        if (!empty($errors)) {
            sendErrorResponse('Validation failed', $errors, 400);
        }

        // Additional validation
        if (!validateEmail($data['email'])) {
            sendErrorResponse('Invalid email address', null, 400);
        }

        if (isset($data['phone']) && !empty($data['phone']) && !validatePhone($data['phone'])) {
            sendErrorResponse('Invalid phone number', null, 400);
        }

        if (strlen($data['message']) < 10) {
            sendErrorResponse('Message must be at least 10 characters long', null, 400);
        }

        if (strlen($data['message']) > 2000) {
            sendErrorResponse('Message must not exceed 2000 characters', null, 400);
        }

        $conn = getDBConnection();

        // Begin transaction
        $conn->beginTransaction();

        // Create or get user
        $userId = getOrCreateContactUser($data);

        // Insert contact inquiry
        $stmt = $conn->prepare("
            INSERT INTO contact_inquiries (
                user_id, subject, message, phone, newsletter_subscription,
                status, priority, created_at
            ) VALUES (?, ?, ?, ?, ?, 'open', ?, NOW())
        ");

        $newsletter = isset($data['newsletter']) && $data['newsletter'] === 'true' ? 1 : 0;
        $priority = determinePriority($data['subject']);

        $stmt->execute([
            $userId,
            sanitizeInput($data['subject']),
            sanitizeInput($data['message']),
            sanitizeInput($data['phone'] ?? null),
            $newsletter,
            $priority
        ]);

        $inquiryId = $conn->lastInsertId();

        // Log activity
        logActivity('contact_inquiry_submitted', $userId, "Contact inquiry submitted: " . $data['subject']);

        $conn->commit();

        // Send confirmation email
        sendContactConfirmation($data, $inquiryId);

        // Send notification to hotel staff
        sendStaffNotification($data, $inquiryId);

        $response = [
            'inquiry_id' => $inquiryId,
            'status' => 'submitted',
            'priority' => $priority,
            'estimated_response' => getEstimatedResponseTime($priority)
        ];

        sendSuccessResponse($response, 'Thank you for your message. We\'ll get back to you within ' . getEstimatedResponseTime($priority) . '.');

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Submit contact form error: " . $e->getMessage());
        sendErrorResponse('Failed to submit contact form', null, 500);
    }
}

function getContactSubmissions() {
    try {
        // This would typically require admin authentication
        // For now, return a message
        sendErrorResponse('Admin access required', null, 403);

    } catch (Exception $e) {
        error_log("Get contact submissions error: " . $e->getMessage());
        sendErrorResponse('Failed to retrieve submissions', null, 500);
    }
}

function getOrCreateContactUser($data) {
    $conn = getDBConnection();

    // Check if user exists by email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if ($user) {
        return $user['id'];
    }

    // Create new user
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, email, phone, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        sanitizeInput($data['firstName']),
        sanitizeInput($data['lastName']),
        sanitizeInput($data['email']),
        sanitizeInput($data['phone'] ?? null)
    ]);

    return $conn->lastInsertId();
}

function determinePriority($subject) {
    $urgentKeywords = ['emergency', 'urgent', 'asap', 'immediate', 'complaint', 'problem'];
    $highKeywords = ['reservation', 'booking', 'cancel', 'change', 'refund'];

    $subjectLower = strtolower($subject);

    foreach ($urgentKeywords as $keyword) {
        if (strpos($subjectLower, $keyword) !== false) {
            return 'urgent';
        }
    }

    foreach ($highKeywords as $keyword) {
        if (strpos($subjectLower, $keyword) !== false) {
            return 'high';
        }
    }

    return 'normal';
}

function getEstimatedResponseTime($priority) {
    switch ($priority) {
        case 'urgent':
            return '2 hours';
        case 'high':
            return '4 hours';
        case 'normal':
        default:
            return '24 hours';
    }
}

function sendContactConfirmation($data, $inquiryId) {
    try {
        $subject = "LOTUS Hotel - Contact Inquiry Received";

        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .header { background: linear-gradient(135deg, #D4AF37, #2C323E); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .inquiry-details { background: #f9f9f9; padding: 15px; margin: 20px 0; border-radius: 8px; }
                .footer { background: #2C323E; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>LOTUS Hotel</h1>
                <p>Contact Inquiry Confirmation</p>
            </div>

            <div class='content'>
                <h2>Dear {$data['firstName']} {$data['lastName']},</h2>

                <p>Thank you for contacting LOTUS Hotel. We have received your inquiry and will respond as soon as possible.</p>

                <div class='inquiry-details'>
                    <h3>Your Inquiry Details</h3>
                    <p><strong>Reference ID:</strong> {$inquiryId}</p>
                    <p><strong>Subject:</strong> {$data['subject']}</p>
                    <p><strong>Message:</strong></p>
                    <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
                    " . (isset($data['phone']) && !empty($data['phone']) ? "<p><strong>Phone:</strong> {$data['phone']}</p>" : "") . "
                </div>

                <p>Our team will review your message and get back to you within " . getEstimatedResponseTime(determinePriority($data['subject'])) . ". For urgent matters, please call us directly at +1 (555) 123-4567.</p>

                <p>Best regards,<br>LOTUS Hotel Guest Services Team</p>
            </div>

            <div class='footer'>
                <p>LOTUS Hotel | Luxury Redefined</p>
                <p>Phone: +1 (555) 123-4567 | Email: info@lotushotel.com</p>
            </div>
        </body>
        </html>
        ";

        sendEmail($data['email'], $subject, $message);

    } catch (Exception $e) {
        error_log("Contact confirmation email failed: " . $e->getMessage());
    }
}

function sendStaffNotification($data, $inquiryId) {
    try {
        $priority = determinePriority($data['subject']);
        $subject = "New Contact Inquiry - " . ucfirst($priority) . " Priority";

        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .header { background: " . ($priority === 'urgent' ? '#dc3545' : ($priority === 'high' ? '#ffc107' : '#D4AF37')) . "; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .inquiry-details { background: #f9f9f9; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid " . ($priority === 'urgent' ? '#dc3545' : ($priority === 'high' ? '#ffc107' : '#D4AF37')) . "; }
                .footer { background: #2C323E; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>New Contact Inquiry</h1>
                <p>Priority: " . ucfirst($priority) . "</p>
            </div>

            <div class='content'>
                <h2>Inquiry Details</h2>

                <div class='inquiry-details'>
                    <p><strong>Inquiry ID:</strong> {$inquiryId}</p>
                    <p><strong>Name:</strong> {$data['firstName']} {$data['lastName']}</p>
                    <p><strong>Email:</strong> {$data['email']}</p>
                    " . (isset($data['phone']) && !empty($data['phone']) ? "<p><strong>Phone:</strong> {$data['phone']}</p>" : "") . "
                    <p><strong>Subject:</strong> {$data['subject']}</p>
                    <p><strong>Message:</strong></p>
                    <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
                    <p><strong>Newsletter Subscription:</strong> " . (isset($data['newsletter']) && $data['newsletter'] === 'true' ? 'Yes' : 'No') . "</p>
                    <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>

                <p><strong>Estimated Response Time:</strong> " . getEstimatedResponseTime($priority) . "</p>

                <p>Please respond to this inquiry promptly.</p>
            </div>

            <div class='footer'>
                <p>LOTUS Hotel Management System</p>
                <p>This is an automated notification for staff.</p>
            </div>
        </body>
        </html>
        ";

        // Send to staff email (you should configure this)
        sendEmail('staff@lotushotel.com', $subject, $message);

    } catch (Exception $e) {
        error_log("Staff notification email failed: " . $e->getMessage());
    }
}

// ===== ADDITIONAL CONTACT FEATURES =====

function getFAQData() {
    // This would typically come from a database
    return [
        [
            'question' => 'What are your check-in and check-out times?',
            'answer' => 'Check-in time is 3:00 PM and check-out time is 11:00 AM. Early check-in or late check-out may be available upon request.'
        ],
        [
            'question' => 'Do you offer airport transportation?',
            'answer' => 'Yes, we offer airport transportation services. Please contact our concierge at least 24 hours in advance to arrange pickup.'
        ],
        [
            'question' => 'Is parking available at the hotel?',
            'answer' => 'Yes, we offer valet parking services. The cost is $45 per day for self-parking and $65 per day for valet service.'
        ],
        [
            'question' => 'Do you have a fitness center?',
            'answer' => 'Yes, our state-of-the-art fitness center is open 24/7 and includes cardio equipment, free weights, and yoga classes.'
        ],
        [
            'question' => 'What dining options are available?',
            'answer' => 'We offer multiple dining venues including our signature restaurant, lounge bar, and 24-hour room service.'
        ]
    ];
}

function validateContactData($data) {
    $errors = [];

    // Name validation
    if (strlen($data['firstName']) < 2 || strlen($data['lastName']) < 2) {
        $errors[] = 'First and last name must be at least 2 characters';
    }

    // Email validation
    if (!validateEmail($data['email'])) {
        $errors[] = 'Valid email address is required';
    }

    // Subject validation
    if (strlen($data['subject']) < 5) {
        $errors[] = 'Subject must be at least 5 characters';
    }

    // Message validation
    if (strlen($data['message']) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    }

    return $errors;
}

// ===== CONTACT FORM ENHANCEMENTS =====

function getContactInfo() {
    return [
        'phone' => '+1 (555) 123-4567',
        'email' => 'info@lotushotel.com',
        'address' => '123 Luxury Avenue, Downtown District, City, State 12345',
        'hours' => [
            'monday_friday' => '24/7',
            'saturday_sunday' => '24/7'
        ]
    ];
}

function getSocialMediaLinks() {
    return [
        'facebook' => 'https://facebook.com/lotushotel',
        'instagram' => 'https://instagram.com/lotushotel',
        'twitter' => 'https://twitter.com/lotushotel',
        'linkedin' => 'https://linkedin.com/company/lotushotel'
    ];
}
?>
