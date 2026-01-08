<?php
/**
 * Validation Helper Functions
 * Common validation utilities for Cobra Shop
 * 
 * @package CobraShop
 * @version 1.0.0
 */

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number format
 * Expects format: +[country code][number] (10-15 digits total)
 * 
 * @param string $phone Phone number to validate
 * @return bool
 */
function isValidPhone(string $phone): bool {
    return preg_match('/^\+[0-9]{10,15}$/', $phone) === 1;
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @param array $options Validation options
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword(string $password, array $options = []): array {
    $defaults = [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => false
    ];
    
    $options = array_merge($defaults, $options);
    $errors = [];
    
    if (strlen($password) < $options['min_length']) {
        $errors[] = "Password must be at least {$options['min_length']} characters long.";
    }
    
    if ($options['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    
    if ($options['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    
    if ($options['require_number'] && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    
    if ($options['require_special'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate date format
 * 
 * @param string $date Date string to validate
 * @param string $format Expected format (default: Y-m-d)
 * @return bool
 */
function isValidDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate date is in the past
 * 
 * @param string $date Date string (Y-m-d format)
 * @return bool
 */
function isDateInPast(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d) return false;
    return $d < new DateTime();
}

/**
 * Validate date is in the future
 * 
 * @param string $date Date string (Y-m-d format)
 * @return bool
 */
function isDateInFuture(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d) return false;
    return $d > new DateTime();
}

/**
 * Validate positive integer
 * 
 * @param mixed $value Value to validate
 * @return bool
 */
function isPositiveInt($value): bool {
    return is_numeric($value) && intval($value) > 0 && intval($value) == $value;
}

/**
 * Validate non-negative number
 * 
 * @param mixed $value Value to validate
 * @return bool
 */
function isNonNegative($value): bool {
    return is_numeric($value) && floatval($value) >= 0;
}

/**
 * Validate string length
 * 
 * @param string $string String to validate
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool
 */
function isValidLength(string $string, int $min, int $max): bool {
    $length = mb_strlen($string);
    return $length >= $min && $length <= $max;
}

/**
 * Validate array contains only allowed values
 * 
 * @param mixed $value Value to check
 * @param array $allowed Allowed values
 * @return bool
 */
function isInAllowedValues($value, array $allowed): bool {
    return in_array($value, $allowed, true);
}

/**
 * Sanitize string input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized string
 */
function sanitizeString(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize integer input
 * 
 * @param mixed $input Input to sanitize
 * @return int
 */
function sanitizeInt($input): int {
    return intval($input);
}

/**
 * Sanitize float input
 * 
 * @param mixed $input Input to sanitize
 * @return float
 */
function sanitizeFloat($input): float {
    return floatval($input);
}

/**
 * Validate and sanitize input array based on rules
 * 
 * @param array $input Input data
 * @param array $rules Validation rules
 * @return array ['valid' => bool, 'data' => array, 'errors' => array]
 */
function validateInput(array $input, array $rules): array {
    $errors = [];
    $sanitized = [];
    
    foreach ($rules as $field => $rule) {
        $value = $input[$field] ?? null;
        $isRequired = $rule['required'] ?? false;
        $type = $rule['type'] ?? 'string';
        
        // Check required
        if ($isRequired && ($value === null || $value === '')) {
            $errors[$field] = $rule['message'] ?? "The {$field} field is required.";
            continue;
        }
        
        // Skip if not required and empty
        if (!$isRequired && ($value === null || $value === '')) {
            $sanitized[$field] = null;
            continue;
        }
        
        // Type validation and sanitization
        switch ($type) {
            case 'email':
                if (!isValidEmail($value)) {
                    $errors[$field] = 'Invalid email format.';
                } else {
                    $sanitized[$field] = strtolower(trim($value));
                }
                break;
                
            case 'phone':
                if (!isValidPhone($value)) {
                    $errors[$field] = 'Invalid phone format. Use +[country code][number].';
                } else {
                    $sanitized[$field] = trim($value);
                }
                break;
                
            case 'int':
                if (!is_numeric($value)) {
                    $errors[$field] = 'Must be a valid number.';
                } else {
                    $sanitized[$field] = intval($value);
                    if (isset($rule['min']) && $sanitized[$field] < $rule['min']) {
                        $errors[$field] = "Must be at least {$rule['min']}.";
                    }
                    if (isset($rule['max']) && $sanitized[$field] > $rule['max']) {
                        $errors[$field] = "Must be at most {$rule['max']}.";
                    }
                }
                break;
                
            case 'float':
                if (!is_numeric($value)) {
                    $errors[$field] = 'Must be a valid number.';
                } else {
                    $sanitized[$field] = floatval($value);
                }
                break;
                
            case 'date':
                if (!isValidDate($value)) {
                    $errors[$field] = 'Invalid date format. Use YYYY-MM-DD.';
                } else {
                    $sanitized[$field] = $value;
                }
                break;
                
            case 'bool':
                $sanitized[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
                
            case 'array':
                if (!is_array($value)) {
                    $errors[$field] = 'Must be an array.';
                } else {
                    $sanitized[$field] = $value;
                }
                break;
                
            default: // string
                $sanitized[$field] = sanitizeString($value);
                if (isset($rule['min_length']) && mb_strlen($sanitized[$field]) < $rule['min_length']) {
                    $errors[$field] = "Must be at least {$rule['min_length']} characters.";
                }
                if (isset($rule['max_length']) && mb_strlen($sanitized[$field]) > $rule['max_length']) {
                    $errors[$field] = "Must be at most {$rule['max_length']} characters.";
                }
                break;
        }
    }
    
    return [
        'valid' => empty($errors),
        'data' => $sanitized,
        'errors' => $errors
    ];
}
?>
