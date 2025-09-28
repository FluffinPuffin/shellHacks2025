<?php
/**
 * Utility functions for the Budget App
 */

/**
 * Sanitize and validate input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $required_fields) {
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $missing_fields[] = str_replace('_', ' ', $field);
        }
    }
    return $missing_fields;
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2);
}

/**
 * Calculate total monthly expenses
 */
function calculateTotalExpenses($budget_data) {
    if (!$budget_data) return 0;
    
    $total = 0;
    $total += (float)($budget_data['rent'] ?? 0);
    $total += (float)($budget_data['groceries'] ?? 0);
    $total += (float)($budget_data['car_cost'] ?? 0);
    $total += (float)($budget_data['health_insurance'] ?? 0);
    $total += (float)($budget_data['debt']['monthly_payment'] ?? 0);
    
    if (isset($budget_data['utilities'])) {
        $total += (float)($budget_data['utilities']['water'] ?? 0);
        $total += (float)($budget_data['utilities']['phone'] ?? 0);
        $total += (float)($budget_data['utilities']['electricity'] ?? 0);
        $total += (float)($budget_data['utilities']['other'] ?? 0);
    }
    
    return $total;
}

/**
 * Get location cost multiplier
 */
function getLocationCostMultiplier($location) {
    $location = strtolower($location);
    
    if (strpos($location, 'new york') !== false || 
        strpos($location, 'san francisco') !== false || 
        strpos($location, 'los angeles') !== false) {
        return 1.4;
    } elseif (strpos($location, 'chicago') !== false || 
              strpos($location, 'boston') !== false || 
              strpos($location, 'seattle') !== false) {
        return 1.2;
    } elseif (strpos($location, 'texas') !== false || 
              strpos($location, 'florida') !== false || 
              strpos($location, 'arizona') !== false) {
        return 0.8;
    }
    
    return 1.0;
}

/**
 * Generate default household data structure
 */
function getDefaultHouseholdData() {
    return [
        'name' => '',
        'age' => 0,
        'location' => '',
        'household_size' => 1,
        'bedrooms' => 0,
        'bathrooms' => 0,
        'rent' => 0,
        'utilities' => [
            'water' => 0,
            'phone' => 0,
            'electricity' => 0,
            'other' => 0
        ],
        'groceries' => 0,
        'savings' => 0,
        'car_cost' => 0,
        'health_insurance' => 0,
        'debt' => [
            'total_debt' => 0,
            'monthly_payment' => 0,
            'debt_type' => '',
            'interest_rate' => 0
        ],
        'monthly_payments' => []
    ];
}

/**
 * Check if user is logged in
 */
function requireLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Display success message
 */
function displaySuccessMessage($message) {
    if ($message) {
        echo '<div class="success-message">' . htmlspecialchars($message) . '</div>';
    }
}

/**
 * Display error message
 */
function displayErrorMessage($message) {
    if ($message) {
        echo '<div class="error-message">' . htmlspecialchars($message) . '</div>';
    }
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        $class = $type === 'error' ? 'error-message' : 'success-message';
        
        echo '<div class="' . $class . '">' . htmlspecialchars($message) . '</div>';
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}
?>
