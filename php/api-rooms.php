<?php
// ===== LOTUS HOTEL - ROOMS API =====
// Handles room listings, availability, and booking operations

require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Rate limiting
$identifier = $_SERVER['REMOTE_ADDR'] . '_rooms_api';
if (!checkRateLimit($identifier, 100, 3600)) { // 100 requests per hour
    sendErrorResponse('Rate limit exceeded. Please try again later.', null, 429);
}

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            sendErrorResponse('Method not allowed', null, 405);
    }
} catch (Exception $e) {
    error_log("Rooms API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error', null, 500);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'list':
            getRoomsList();
            break;
        case 'details':
            getRoomDetails();
            break;
        case 'availability':
            checkAvailability();
            break;
        default:
            sendErrorResponse('Invalid action', null, 400);
    }
}

function handlePostRequest($action) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input && $action !== 'book') {
        $input = $_POST;
    }

    switch ($action) {
        case 'book':
            createBooking($input);
            break;
        case 'availability':
            checkAvailability($input);
            break;
        default:
            sendErrorResponse('Invalid action', null, 400);
    }
}

function getRoomsList() {
    try {
        $conn = getDBConnection();

        $filter = $_GET['filter'] ?? 'all';
        $minPrice = $_GET['min_price'] ?? 0;
        $maxPrice = $_GET['max_price'] ?? 9999;
        $capacity = $_GET['capacity'] ?? null;

        $query = "
            SELECT r.*,
                   AVG(rv.rating) as avg_rating,
                   COUNT(rv.id) as review_count
            FROM rooms r
            LEFT JOIN reviews rv ON r.id = rv.room_id
            WHERE r.is_active = 1
            AND r.price BETWEEN ? AND ?
        ";

        $params = [$minPrice, $maxPrice];

        if ($filter !== 'all') {
            $query .= " AND r.type = ?";
            $params[] = $filter;
        }

        if ($capacity) {
            $query .= " AND r.capacity >= ?";
            $params[] = $capacity;
        }

        $query .= " GROUP BY r.id ORDER BY r.price ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        $rooms = $stmt->fetchAll();

        // Format response
        $formattedRooms = array_map(function($room) {
            return [
                'id' => $room['id'],
                'name' => $room['name'],
                'type' => $room['type'],
                'description' => $room['description'],
                'price' => (float)$room['price'],
                'capacity' => (int)$room['capacity'],
                'bed_type' => $room['bed_type'],
                'size' => $room['size'],
                'view' => $room['view'],
                'amenities' => json_decode($room['amenities'], true),
                'images' => json_decode($room['images'], true),
                'rating' => round((float)$room['avg_rating'], 1),
                'review_count' => (int)$room['review_count'],
                'is_available' => (bool)$room['is_available']
            ];
        }, $rooms);

        sendSuccessResponse($formattedRooms, 'Rooms retrieved successfully');

    } catch (Exception $e) {
        error_log("Get rooms list error: " . $e->getMessage());
        sendErrorResponse('Failed to retrieve rooms', null, 500);
    }
}

function getRoomDetails() {
    try {
        $roomId = $_GET['id'] ?? null;

        if (!$roomId) {
            sendErrorResponse('Room ID is required', null, 400);
        }

        $conn = getDBConnection();

        // Get room details
        $stmt = $conn->prepare("
            SELECT r.*,
                   AVG(rv.rating) as avg_rating,
                   COUNT(rv.id) as review_count
            FROM rooms r
            LEFT JOIN reviews rv ON r.id = rv.room_id
            WHERE r.id = ? AND r.is_active = 1
            GROUP BY r.id
        ");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            sendErrorResponse('Room not found', null, 404);
        }

        // Get reviews
        $stmt = $conn->prepare("
            SELECT rv.*,
                   u.first_name, u.last_name
            FROM reviews rv
            LEFT JOIN users u ON rv.user_id = u.id
            WHERE rv.room_id = ?
            ORDER BY rv.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$roomId]);
        $reviews = $stmt->fetchAll();

        $response = [
            'id' => $room['id'],
            'name' => $room['name'],
            'type' => $room['type'],
            'description' => $room['description'],
            'price' => (float)$room['price'],
            'capacity' => (int)$room['capacity'],
            'bed_type' => $room['bed_type'],
            'size' => $room['size'],
            'view' => $room['view'],
            'amenities' => json_decode($room['amenities'], true),
            'images' => json_decode($room['images'], true),
            'rating' => round((float)$room['avg_rating'], 1),
            'review_count' => (int)$room['review_count'],
            'is_available' => (bool)$room['is_available'],
            'reviews' => array_map(function($review) {
                return [
                    'id' => $review['id'],
                    'rating' => (int)$review['rating'],
                    'comment' => $review['comment'],
                    'guest_name' => $review['first_name'] . ' ' . $review['last_name'],
                    'created_at' => $review['created_at']
                ];
            }, $reviews)
        ];

        sendSuccessResponse($response, 'Room details retrieved successfully');

    } catch (Exception $e) {
        error_log("Get room details error: " . $e->getMessage());
        sendErrorResponse('Failed to retrieve room details', null, 500);
    }
}

function checkAvailability($data = null) {
    try {
        $roomId = $data['room_id'] ?? $_GET['room_id'] ?? null;
        $checkIn = $data['check_in'] ?? $_GET['check_in'] ?? null;
        $checkOut = $data['check_out'] ?? $_GET['check_out'] ?? null;

        if (!$roomId || !$checkIn || !$checkOut) {
            sendErrorResponse('Room ID, check-in, and check-out dates are required', null, 400);
        }

        if (!validateDate($checkIn) || !validateDate($checkOut)) {
            sendErrorResponse('Invalid date format. Use YYYY-MM-DD', null, 400);
        }

        if (strtotime($checkOut) <= strtotime($checkIn)) {
            sendErrorResponse('Check-out date must be after check-in date', null, 400);
        }

        $conn = getDBConnection();

        // Check if room exists and is active
        $stmt = $conn->prepare("SELECT id, name FROM rooms WHERE id = ? AND is_active = 1");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            sendErrorResponse('Room not found', null, 404);
        }

        // Check for conflicting bookings
        $stmt = $conn->prepare("
            SELECT COUNT(*) as conflicts FROM bookings
            WHERE room_id = ?
            AND status IN ('confirmed', 'pending')
            AND (
                (check_in <= ? AND check_out > ?) OR
                (check_in < ? AND check_out >= ?) OR
                (check_in >= ? AND check_out <= ?)
            )
        ");
        $stmt->execute([$roomId, $checkIn, $checkIn, $checkOut, $checkOut, $checkIn, $checkOut]);
        $result = $stmt->fetch();

        $available = $result['conflicts'] == 0;

        $response = [
            'room_id' => $roomId,
            'room_name' => $room['name'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'available' => $available,
            'nights' => calculateNights($checkIn, $checkOut)
        ];

        sendSuccessResponse($response, $available ? 'Room is available' : 'Room is not available');

    } catch (Exception $e) {
        error_log("Check availability error: " . $e->getMessage());
        sendErrorResponse('Failed to check availability', null, 500);
    }
}

function createBooking($data) {
    try {
        // Validate input data
        $errors = validateBookingData($data);
        if (!empty($errors)) {
            sendErrorResponse('Validation failed', $errors, 400);
        }

        $conn = getDBConnection();

        // Check room availability
        $availabilityData = [
            'room_id' => $data['roomType'], // Note: This should be room ID, not type
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut']
        ];

        checkAvailability($availabilityData);

        // Get room details for pricing
        $stmt = $conn->prepare("SELECT id, name, price FROM rooms WHERE id = ? AND is_active = 1");
        $stmt->execute([$data['roomType']]);
        $room = $stmt->fetch();

        if (!$room) {
            sendErrorResponse('Selected room is not available', null, 400);
        }

        // Calculate total amount
        $nights = calculateNights($data['checkIn'], $data['checkOut']);
        $totalAmount = $room['price'] * $nights;

        // Begin transaction
        $conn->beginTransaction();

        // Create or get user
        $userId = getOrCreateUser($data);

        // Create booking
        $bookingRef = generateBookingReference();
        $stmt = $conn->prepare("
            INSERT INTO bookings (
                booking_reference, user_id, room_id, check_in, check_out,
                guests, total_amount, currency, status, special_requests,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'USD', 'pending', ?, NOW())
        ");

        $stmt->execute([
            $bookingRef,
            $userId,
            $room['id'],
            $data['checkIn'],
            $data['checkOut'],
            $data['guests'] ?? 1,
            $totalAmount,
            $data['specialRequests'] ?? null
        ]);

        $bookingId = $conn->lastInsertId();

        // Log activity
        logActivity('booking_created', $userId, "Booking $bookingRef created for room " . $room['name']);

        $conn->commit();

        $response = [
            'booking_id' => $bookingId,
            'booking_reference' => $bookingRef,
            'room_name' => $room['name'],
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut'],
            'nights' => $nights,
            'total_amount' => $totalAmount,
            'currency' => 'USD',
            'status' => 'pending'
        ];

        sendSuccessResponse($response, 'Booking created successfully. Proceed to payment.');

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Create booking error: " . $e->getMessage());
        sendErrorResponse('Failed to create booking', null, 500);
    }
}

function getOrCreateUser($data) {
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
        sanitizeInput($data['phone'])
    ]);

    return $conn->lastInsertId();
}
?>
