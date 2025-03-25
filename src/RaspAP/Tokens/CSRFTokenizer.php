<?php

/**
 * CSRF tokenizer class
 *
 * @description CSRF tokenizer class for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @author      Martin Glaß <mail@glasz.org>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Tokens;

class CSRFTokenizer {

    // Constructor
    public function __construct()
    {
        $this->ensureSession();

        // ensure a CSRF token exists in the session
        if (empty($_SESSION['csrf_token'])) {
            $this->ensureCSRFSessionToken;
        }

        if ($this->csrfValidateRequest() && !$this->CSRFValidate($_SESSION['csrf_token'])) {
            $this->handleInvalidCSRFToken();
        }
    }

    /**
     * Saves a CSRF token in the session
     */
    public function ensureCSRFSessionToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Adds a CSRF Token to form
     */
    public function CSRFTokenFieldTag(): string
    {
        $token = htmlspecialchars($_SESSION['csrf_token']);
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Returns a CSRF meta tag (for use with xhr, for example)
     */
    public function CSRFMetaTag(): string
    {
        // if session has expired or user has logged out,
        // create a new session and token
        if (empty($_SESSION['csrf_token'])) {
            $this->ensureSession();
            $this->ensureCSRFSessionToken();
            return $_SESSION['csrf_token'];
        } else {
            $token = htmlspecialchars($_SESSION['csrf_token']);
            return '<meta name="csrf_token" content="' . $token . '">';
        }
    }

    /**
     * Validates a CSRF Token
     *
     * @param string $token
     */
    public function CSRFValidate(string $token): bool
    {
        if(isset($token)) {
            $header_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if (empty($token) && empty($header_token)) {
                return false;
            }
            $request_token = $token;
            if (empty($token)) {
                $request_token = $header_token;
            }
            if (hash_equals($_SESSION['csrf_token'], $request_token)) {
                return true;
            } else {
                error_log('CSRF violation');
                return false;
            }
        }
    }

    /**
     * Should the request be CSRF-validated?
     */
    public function csrfValidateRequest(): bool
    {
        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        return in_array($request_method, [ "post", "put", "patch", "delete" ]);
    }

    /**
     * Handle invalid CSRF
     */
    public function handleInvalidCSRFToken(): string
    {
        if (function_exists('http_response_code')) { 
            http_response_code(500);
            echo 'Invalid CSRF token';
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain');
            echo 'Invalid CSRF token';
        }
        exit;
    }
    
    protected function ensureSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            session_regenerate_id(true);
        }
    } 
}

