<?php
// Centralized Input Validator class for strict schema verification

class ValidationException extends Exception {
    private $errors;

    public function __construct(array $errors) {
        parent::__construct("Validation failed");
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
}

class Validator {
    // Disposable domains list to prevent disposable email registration
    private static $disposable_domains = [
        'mailinator.com', '10minutemail.com', 'tempmail.com', 'temp-mail.org',
        'yopmail.com', 'guerrillamail.com', 'sharklasers.com', 'dispostable.com',
        'getairmail.com', 'maildrop.cc', 'tempmailaddress.com', 'throwawaymail.com',
        'tempmail.net', 'fakeinbox.com', 'trashmail.com'
    ];

    /**
     * Detects if the current request is an API request.
     */
    public static function isApiRequest() {
        if (defined('API_REQUEST') && API_REQUEST === true) {
            return true;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') !== false && 
            strpos($uri, 'restaurant_router') === false && 
            strpos($uri, 'admin_router') === false && 
            strpos($uri, 'delivery_router') === false) {
            return true;
        }
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $accept = isset($headers['Accept']) ? $headers['Accept'] : ($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (strpos($accept, 'application/json') !== false || strpos($contentType, 'application/json') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Validates data against a schema.
     * 
     * @param array $data The input array (e.g., $_POST, $_GET, or JSON data).
     * @param array $schema The validation schema.
     * @param bool $rejectOnError Whether to automatically reject the request on failure.
     * @return array The validated, casted, and filtered data.
     * @throws ValidationException if validation fails and $rejectOnError is false.
     */
    public static function validate(array $data, array $schema, $rejectOnError = true) {
        $errors = [];
        $validated = [];

        foreach ($schema as $field => $rules) {
            $exists = array_key_exists($field, $data);
            $val = $exists ? $data[$field] : null;

            // Handle default value if field is missing
            if (!$exists && isset($rules['default'])) {
                $val = $rules['default'];
                $exists = true;
            }

            // Required check
            $required = $rules['required'] ?? false;
            if ($required) {
                if (!$exists || $val === null || $val === '' || (is_array($val) && empty($val))) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
                    continue;
                }
            } else {
                // If not required and not set/empty, skip further validation
                if (!$exists || $val === null || $val === '' || (is_array($val) && empty($val))) {
                    $validated[$field] = $val;
                    continue;
                }
            }

            // Type and Format Checks
            $type = $rules['type'] ?? 'string';
            $castVal = $val;

            switch ($type) {
                case 'int':
                case 'integer':
                    if (filter_var($val, FILTER_VALIDATE_INT) === false) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be an integer.";
                    } else {
                        $castVal = intval($val);
                    }
                    break;

                case 'float':
                case 'numeric':
                case 'double':
                    if (!is_numeric($val)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a numeric value.";
                    } else {
                        $castVal = floatval($val);
                    }
                    break;

                case 'bool':
                case 'boolean':
                    if (is_bool($val)) {
                        $castVal = $val;
                    } elseif ($val === '1' || $val === 1 || strtolower($val) === 'true') {
                        $castVal = true;
                    } elseif ($val === '0' || $val === 0 || strtolower($val) === 'false') {
                        $castVal = false;
                    } else {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a boolean.";
                    }
                    break;

                case 'array':
                    if (!is_array($val)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be an array.";
                    }
                    break;

                case 'email':
                    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "Please enter a valid email address.";
                    } else {
                        // Check disposable email
                        $parts = explode('@', $val);
                        $domain = strtolower(end($parts));
                        if (in_array($domain, self::$disposable_domains)) {
                            $errors[$field] = "Disposable email addresses are not allowed.";
                        }
                    }
                    break;

                case 'phone':
                    // Normalize phone (strip spaces/dashes if any, but strict schema check suggests rejecting malformed ones)
                    $cleanPhone = preg_replace('/\s+|-/', '', $val);
                    if (!preg_match('/^[6-9]\d{9}$/', $cleanPhone)) {
                        $errors[$field] = "Please enter a valid 10-digit mobile number (starting with 6-9).";
                    } else {
                        $castVal = $cleanPhone;
                    }
                    break;

                case 'pincode':
                    $cleanPin = preg_replace('/\s+/', '', $val);
                    if (!preg_match('/^[1-9]\d{5}$/', $cleanPin)) {
                        $errors[$field] = "Please enter a valid 6-digit PIN code.";
                    } else {
                        $castVal = $cleanPin;
                    }
                    break;

                case 'date':
                    $d = DateTime::createFromFormat('Y-m-d', $val);
                    if (!$d || $d->format('Y-m-d') !== $val) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a valid date in YYYY-MM-DD format.";
                    }
                    break;

                case 'datetime':
                    $d = DateTime::createFromFormat('Y-m-d H:i:s', $val);
                    if (!$d && strpos($val, 'T') !== false) {
                        // try ISO 8601
                        $d = DateTime::createFromFormat(DateTime::ATOM, $val);
                    }
                    if (!$d) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a valid date and time.";
                    }
                    break;

                case 'json':
                    if (is_string($val)) {
                        json_decode($val);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a valid JSON string.";
                        }
                    } else {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a JSON string.";
                    }
                    break;

                case 'string':
                default:
                    if (!is_string($val)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be a string.";
                    }
                    break;
            }

            // If there's already an error for this field, skip further checks
            if (isset($errors[$field])) {
                continue;
            }

            // Length Check for strings and arrays
            if (is_string($castVal)) {
                $len = mb_strlen($castVal);
                if (isset($rules['min_len']) && $len < $rules['min_len']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$rules['min_len']} characters.";
                }
                if (isset($rules['max_len']) && $len > $rules['max_len']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$rules['max_len']} characters.";
                }
            } elseif (is_array($castVal)) {
                $count = count($castVal);
                if (isset($rules['min_len']) && $count < $rules['min_len']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must have at least {$rules['min_len']} items.";
                }
                if (isset($rules['max_len']) && $count > $rules['max_len']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$rules['max_len']} items.";
                }
            }

            // Numeric Range Checks
            if (is_numeric($castVal)) {
                if (isset($rules['min']) && $castVal < $rules['min']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$rules['min']}.";
                }
                if (isset($rules['max']) && $castVal > $rules['max']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$rules['max']}.";
                }
            }

            // Regex Check
            if (isset($rules['regex']) && is_string($castVal)) {
                if (!preg_match($rules['regex'], $castVal)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " has an invalid format.";
                }
            }

            // Enum Check
            if (isset($rules['enum']) && is_array($rules['enum'])) {
                if (!in_array($castVal, $rules['enum'], true)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is invalid.";
                }
            }

            // Custom Callback Check
            if (isset($rules['custom']) && is_callable($rules['custom'])) {
                $customRes = call_user_func($rules['custom'], $castVal);
                if ($customRes !== true) {
                    $errors[$field] = is_string($customRes) ? $customRes : ucfirst(str_replace('_', ' ', $field)) . " is invalid.";
                }
            }

            // If no errors, add to validated array
            if (!isset($errors[$field])) {
                $validated[$field] = $castVal;
            }
        }

        if (!empty($errors)) {
            if ($rejectOnError) {
                self::reject($errors);
            } else {
                throw new ValidationException($errors);
            }
        }

        return $validated;
    }

    /**
     * Rejects the request, logging the error and presenting appropriate error representation.
     */
    public static function reject(array $errors) {
        // Log validation failures for security auditing
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $errStr = json_encode($errors);
        error_log("[Validation Failure] IP: $ip | URI: $uri | Errors: $errStr");

        if (self::isApiRequest()) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Input validation failed: ' . reset($errors),
                'errors' => $errors
            ]);
            exit;
        } else {
            // Save to session to show error on the redirected page
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['validation_errors'] = $errors;
            $_SESSION['error'] = reset($errors);

            // Redirect back if referrer exists and is on the same domain
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (!empty($referer) && strpos($referer, $host) !== false) {
                header("Location: " . $referer);
                exit;
            }

            // Safe fallback secure error page if no referer is available
            http_response_code(400);
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Bad Request</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f7f9fc; color: #333; text-align: center; padding: 50px; }
                    .error-card { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-top: 4px solid #e64a19; }
                    h1 { font-size: 24px; color: #111; margin-bottom: 20px; }
                    p { color: #666; font-size: 16px; line-height: 1.5; }
                    .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #e64a19; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; }
                </style>
            </head>
            <body>
                <div class="error-card">
                    <h1>Invalid Request Parameters</h1>
                    <p><?php echo htmlspecialchars(reset($errors)); ?></p>
                    <a href="javascript:history.back()" class="btn">Go Back</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}
