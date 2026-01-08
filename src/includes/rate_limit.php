<?php
/**
 * Rate Limiting
 * Brute force protection for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Check if login is rate limited
 * @param string $identifier Email or IP address
 * @return array ['limited' => bool, 'remaining_time' => int, 'attempts' => int]
 */
function checkRateLimit(string $identifier): array {
    try {
        $pdo = getDB();
        
        // Clean up old attempts
        cleanupOldAttempts($pdo);
        
        // Get recent failed attempts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempt_count, 
                   MAX(attempt_time) as last_attempt
            FROM Login_Attempts 
            WHERE identifier = ? 
              AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
              AND success = 0
        ");
        $stmt->execute([$identifier, RATE_LIMIT_WINDOW]);
        $result = $stmt->fetch();
        
        $attemptCount = (int)($result['attempt_count'] ?? 0);
        $lastAttempt = $result['last_attempt'] ? strtotime($result['last_attempt']) : 0;
        
        // Check if currently locked out
        if ($attemptCount >= MAX_LOGIN_ATTEMPTS) {
            $lockoutEnd = $lastAttempt + LOGIN_LOCKOUT_TIME;
            $remainingTime = $lockoutEnd - time();
            
            if ($remainingTime > 0) {
                return [
                    'limited' => true,
                    'remaining_time' => $remainingTime,
                    'attempts' => $attemptCount,
                    'message' => sprintf(
                        'Too many failed attempts. Please try again in %d minutes.',
                        ceil($remainingTime / 60)
                    )
                ];
            }
        }
        
        return [
            'limited' => false,
            'remaining_time' => 0,
            'attempts' => $attemptCount,
            'remaining_attempts' => MAX_LOGIN_ATTEMPTS - $attemptCount
        ];
        
    } catch (Exception $e) {
        logError('Rate limit check failed: ' . $e->getMessage(), 'ERROR');
        // On error, allow the attempt but log it
        return [
            'limited' => false,
            'remaining_time' => 0,
            'attempts' => 0
        ];
    }
}

/**
 * Record a login attempt
 * @param string $identifier Email or IP address
 * @param bool $success Whether the attempt was successful
 * @param int|null $userId User ID if successful
 */
function recordLoginAttempt(string $identifier, bool $success, ?int $userId = null): void {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            INSERT INTO Login_Attempts (identifier, ip_address, user_agent, success, user_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $identifier,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $success ? 1 : 0,
            $userId
        ]);
        
        // On successful login, clear failed attempts for this identifier
        if ($success) {
            clearFailedAttempts($identifier);
        }
        
    } catch (Exception $e) {
        logError('Failed to record login attempt: ' . $e->getMessage(), 'ERROR');
    }
}

/**
 * Clear failed login attempts for an identifier
 * @param string $identifier
 */
function clearFailedAttempts(string $identifier): void {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM Login_Attempts WHERE identifier = ? AND success = 0");
        $stmt->execute([$identifier]);
    } catch (Exception $e) {
        logError('Failed to clear login attempts: ' . $e->getMessage(), 'ERROR');
    }
}

/**
 * Clean up old login attempts
 * @param PDO $pdo
 */
function cleanupOldAttempts(PDO $pdo): void {
    try {
        // Delete attempts older than the rate limit window (keep some for audit)
        $stmt = $pdo->prepare("
            DELETE FROM Login_Attempts 
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail - this is just cleanup
        logError('Failed to cleanup login attempts: ' . $e->getMessage(), 'WARNING');
    }
}

/**
 * Check rate limit and respond if limited
 * @param string $identifier
 */
function enforceRateLimit(string $identifier): void {
    $rateCheck = checkRateLimit($identifier);
    
    if ($rateCheck['limited']) {
        logError('Rate limit exceeded', 'SECURITY', [
            'identifier' => $identifier,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        apiResponse(
            API_RATE_LIMITED, 
            $rateCheck['message'], 
            ['retry_after' => $rateCheck['remaining_time']], 
            429
        );
    }
}

/**
 * Get rate limit status for display
 * @param string $identifier
 * @return array
 */
function getRateLimitStatus(string $identifier): array {
    $check = checkRateLimit($identifier);
    
    return [
        'is_limited' => $check['limited'],
        'attempts_remaining' => $check['remaining_attempts'] ?? MAX_LOGIN_ATTEMPTS,
        'lockout_remaining' => $check['remaining_time'] ?? 0
    ];
}
?>
