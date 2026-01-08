<?php
/**
 * Authentication Functions
 * Centralized authentication for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/rate_limit.php';

/**
 * Authenticate user with email and password
 * @param string $email
 * @param string $password
 * @return array Result with status and user data or error
 */
function authenticateUser(string $email, string $password): array {
    try {
        $pdo = getDB();
        
        // Get user by email
        $stmt = $pdo->prepare("
            SELECT Customer_ID, Name, Email, Phone, Birth_Date, PasswordHash 
            FROM Customers 
            WHERE Email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['PasswordHash'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Remove password hash from user data
        unset($user['PasswordHash']);
        
        return [
            'success' => true,
            'user' => $user
        ];
        
    } catch (Exception $e) {
        logError('Authentication error: ' . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'An error occurred during authentication. Please try again.'
        ];
    }
}

/**
 * Register a new user
 * @param array $data User registration data
 * @return array Result with status
 */
function registerUser(array $data): array {
    try {
        $pdo = getDB();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT Customer_ID FROM Customers WHERE Email = ?");
        $stmt->execute([$data['email']]);
        
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'This email address is already registered.'
            ];
        }
        
        // Check if phone exists (if provided)
        if (!empty($data['phone'])) {
            $stmt = $pdo->prepare("SELECT Customer_ID FROM Customers WHERE Phone = ?");
            $stmt->execute([$data['phone']]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'This phone number is already registered.'
                ];
            }
        }
        
        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO Customers (Name, Email, Phone, Birth_Date, PasswordHash) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['birth_date'] ?? null,
            $passwordHash
        ]);
        
        $userId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration successful! You can now log in.'
        ];
        
    } catch (Exception $e) {
        logError('Registration error: ' . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.'
        ];
    }
}

/**
 * Validate password strength
 * @param string $password
 * @return array Result with valid status and messages
 */
function validatePasswordStrength(string $password): array {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = sprintf('Password must be at least %d characters long.', MIN_PASSWORD_LENGTH);
    }
    
    if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    
    if (REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    
    if (REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    
    if (REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone format
 * @param string $phone
 * @return bool
 */
function isValidPhone(string $phone): bool {
    return preg_match('/^\+[0-9]{10,15}$/', $phone) === 1;
}

/**
 * Sanitize user input
 * @param string $input
 * @return string
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
