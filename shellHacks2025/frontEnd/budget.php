<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get current session data
$current_session = null;
$budget_data = null;
$destination_data = null;
$message = '';

// Check if data was just loaded
if (isset($_GET['loaded'])) {
    $message = "Budget data loaded successfully!";
}

if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
    if ($current_session && isset($current_session['user_data']['household_data'])) {
        $budget_data = $current_session['user_data']['household_data'];
    }
    if ($current_session && isset($current_session['user_data']['destination_data'])) {
        $destination_data = $current_session['user_data']['destination_data'];
    }
}

// Get all sessions for this user
$all_sessions = $db->getRecentSessions(10);

// Function to generate cost breakdown using the specified prompt
function generateCostBreakdown($location, $household_size) {
    // Create the prompt as specified
    $prompt = "Give me a cost break down average cost of the monthly payments for {$household_size} people paying for the average phone bill (no MVNO), cost of owning a car, health insurance in {$location} based on recent sources only a single number per category in the format of the text below with no other text or notes \n 'Phone: number,Car: number,Health Insurance: number'";
    
    // Try to call the AI service first
    $ai_response = callGeminiAPI($prompt);
    
    
    if ($ai_response && !strpos($ai_response, 'Error')) {
        // Parse the AI response
        $costs = parseAIResponse($ai_response);
        if ($costs) {
            return $costs;
        }
    }
    
    // Fallback to realistic estimates if AI fails
    $location_multiplier = 1.0;
    if (stripos($location, 'new york') !== false || stripos($location, 'san francisco') !== false || stripos($location, 'los angeles') !== false) {
        $location_multiplier = 1.4;
    } elseif (stripos($location, 'chicago') !== false || stripos($location, 'boston') !== false || stripos($location, 'seattle') !== false) {
        $location_multiplier = 1.2;
    } elseif (stripos($location, 'texas') !== false || stripos($location, 'florida') !== false || stripos($location, 'arizona') !== false) {
        $location_multiplier = 0.8;
    }
    
    // Generate realistic estimates
    $phone_cost = round(80 * $household_size * $location_multiplier, 2);
    $car_cost = round(600 * $location_multiplier, 2);
    $health_insurance_cost = round(400 * $household_size * $location_multiplier, 2);
    
    return [
        'phone' => $phone_cost,
        'car' => $car_cost,
        'health_insurance' => $health_insurance_cost
    ];
}

// Function to call Gemini API via Python script
function callGeminiAPI($prompt) {
    try {
        // Create a temporary file with the prompt
        $temp_file = tempnam(sys_get_temp_dir(), 'gemini_prompt_');
        file_put_contents($temp_file, $prompt);
        
        // Call the Python script
        $python_script = __DIR__ . '/../call_gemini.py';
        $command = "python \"$python_script\" \"$temp_file\" 2>&1";
        
        $output = shell_exec($command);
        
        // Clean up temp file
        unlink($temp_file);
        
        return $output;
    } catch (Exception $e) {
        return "Error calling AI: " . $e->getMessage();
    }
}

// Function to parse AI response
function parseAIResponse($response) {
    // Look for the specific format: Phone: number,Car: number,Health Insurance: number
    if (preg_match('/Phone:\s*([0-9.]+).*Car:\s*([0-9.]+).*Health Insurance:\s*([0-9.]+)/i', $response, $matches)) {
        return [
            'phone' => (float)$matches[1],
            'car' => (float)$matches[2],
            'health_insurance' => (float)$matches[3]
        ];
    }
    
    // Try alternative parsing if the format is slightly different
    if (preg_match('/([0-9.]+).*?([0-9.]+).*?([0-9.]+)/', $response, $matches)) {
        return [
            'phone' => (float)$matches[1],
            'car' => (float)$matches[2],
            'health_insurance' => (float)$matches[3]
        ];
    }
    
    return false;
}

// Function to generate rent estimate based on location and household size
function generateRentEstimate($location, $household_size, $bedrooms) {
    $base_rent = 800;
    
    // Location multipliers
    $location_multipliers = [
        'new york' => 1.8, 'nyc' => 1.8, 'manhattan' => 2.2,
        'san francisco' => 2.0, 'sf' => 2.0,
        'los angeles' => 1.6, 'la' => 1.6,
        'chicago' => 1.2, 'boston' => 1.4, 'seattle' => 1.3,
        'austin' => 1.1, 'denver' => 1.2, 'portland' => 1.1,
        'houston' => 0.9, 'dallas' => 0.9, 'phoenix' => 0.8,
        'atlanta' => 0.9, 'miami' => 1.0, 'las vegas' => 0.8
    ];
    
    $multiplier = 1.0;
    $location_lower = strtolower(trim($location));
    
    // More specific matching to avoid conflicts and ensure consistency
    if (stripos($location_lower, 'manhattan') !== false || stripos($location_lower, 'brooklyn') !== false) {
        $multiplier = 2.2; // Highest cost areas
    } elseif (stripos($location_lower, 'new york') !== false || stripos($location_lower, 'nyc') !== false) {
        $multiplier = 1.8;
    } elseif (stripos($location_lower, 'san francisco') !== false || stripos($location_lower, 'sf') !== false) {
        $multiplier = 2.0;
    } elseif (stripos($location_lower, 'los angeles') !== false || stripos($location_lower, 'la') !== false) {
        $multiplier = 1.6;
    } elseif (stripos($location_lower, 'boston') !== false) {
        $multiplier = 1.4;
    } elseif (stripos($location_lower, 'seattle') !== false) {
        $multiplier = 1.3;
    } elseif (stripos($location_lower, 'chicago') !== false) {
        $multiplier = 1.2;
    } elseif (stripos($location_lower, 'denver') !== false) {
        $multiplier = 1.2;
    } elseif (stripos($location_lower, 'austin') !== false) {
        $multiplier = 1.1;
    } elseif (stripos($location_lower, 'portland') !== false) {
        $multiplier = 1.1;
    } elseif (stripos($location_lower, 'miami') !== false) {
        $multiplier = 1.0;
    } elseif (stripos($location_lower, 'houston') !== false || stripos($location_lower, 'dallas') !== false) {
        $multiplier = 0.9;
    } elseif (stripos($location_lower, 'atlanta') !== false) {
        $multiplier = 0.9;
    } elseif (stripos($location_lower, 'phoenix') !== false || stripos($location_lower, 'las vegas') !== false) {
        $multiplier = 0.8;
    }
    
    // Adjust for household size and bedrooms
    $size_factor = 1 + (($household_size - 1) * 0.3);
    $bedroom_factor = 1 + (($bedrooms - 1) * 0.4);
    
    $result = round($base_rent * $multiplier * $size_factor * $bedroom_factor);
    return $result;
}

// Function to generate water estimate
function generateWaterEstimate($household_size, $bathrooms) {
    $base_water = 40;
    $size_factor = 1 + (($household_size - 1) * 0.4);
    $bathroom_factor = 1 + (($bathrooms - 1) * 0.2);
    return round($base_water * $size_factor * $bathroom_factor);
}

// Function to generate phone estimate
function generatePhoneEstimate($household_size) {
    $base_phone = 60;
    $size_factor = 1 + (($household_size - 1) * 0.3);
    return round($base_phone * $size_factor);
}

// Function to generate electricity estimate
function generateElectricityEstimate($household_size, $bedrooms) {
    $base_electricity = 80;
    $size_factor = 1 + (($household_size - 1) * 0.3);
    $bedroom_factor = 1 + (($bedrooms - 1) * 0.15);
    return round($base_electricity * $size_factor * $bedroom_factor);
}

// Function to generate other utilities estimate
function generateOtherUtilitiesEstimate($household_size) {
    $base_other = 25;
    $size_factor = 1 + (($household_size - 1) * 0.2);
    return round($base_other * $size_factor);
}

// Function to generate groceries estimate
function generateGroceriesEstimate($household_size, $age) {
    $base_groceries = 300;
    $size_factor = 1 + (($household_size - 1) * 0.6);
    $age_factor = $age > 50 ? 1.1 : 1.0; // Slightly higher for older adults
    return round($base_groceries * $size_factor * $age_factor);
}

// Function to generate savings estimate
function generateSavingsEstimate($age, $household_size) {
    $base_savings = 200;
    $age_factor = $age > 30 ? 1.3 : 1.0; // Higher savings for older adults
    $size_factor = 1 + (($household_size - 1) * 0.2);
    return round($base_savings * $age_factor * $size_factor);
}

// Function to generate car cost estimate
function generateCarCostEstimate($location, $age) {
    $base_car_cost = 400;
    
    // Location adjustments
    $location_multipliers = [
        'new york' => 0.3, 'nyc' => 0.3, 'manhattan' => 0.2, // Lower car costs in cities with good transit
        'san francisco' => 0.4, 'sf' => 0.4,
        'chicago' => 0.6, 'boston' => 0.5, 'seattle' => 0.5,
        'houston' => 1.2, 'dallas' => 1.2, 'phoenix' => 1.1, // Higher car costs in car-dependent cities
        'atlanta' => 1.1, 'miami' => 1.0, 'las vegas' => 1.1
    ];
    
    $multiplier = 1.0;
    $location_lower = strtolower(trim($location));
    
    // More specific matching to avoid conflicts and ensure consistency
    if (stripos($location_lower, 'manhattan') !== false || stripos($location_lower, 'brooklyn') !== false) {
        $multiplier = 0.2; // Lowest car costs in areas with excellent transit
    } elseif (stripos($location_lower, 'new york') !== false || stripos($location_lower, 'nyc') !== false) {
        $multiplier = 0.3;
    } elseif (stripos($location_lower, 'san francisco') !== false || stripos($location_lower, 'sf') !== false) {
        $multiplier = 0.4;
    } elseif (stripos($location_lower, 'boston') !== false || stripos($location_lower, 'seattle') !== false) {
        $multiplier = 0.5;
    } elseif (stripos($location_lower, 'chicago') !== false) {
        $multiplier = 0.6;
    } elseif (stripos($location_lower, 'miami') !== false) {
        $multiplier = 1.0;
    } elseif (stripos($location_lower, 'houston') !== false || stripos($location_lower, 'dallas') !== false) {
        $multiplier = 1.2;
    } elseif (stripos($location_lower, 'atlanta') !== false || stripos($location_lower, 'las vegas') !== false) {
        $multiplier = 1.1;
    } elseif (stripos($location_lower, 'phoenix') !== false) {
        $multiplier = 1.1;
    }
    
    $age_factor = $age > 25 ? 1.1 : 0.9; // Slightly higher for older drivers
    $result = round($base_car_cost * $multiplier * $age_factor);
    return $result;
}

// Function to generate health insurance estimate
function generateHealthInsuranceEstimate($age, $location) {
    $base_insurance = 250;
    
    // Age adjustments
    $age_factor = 1.0;
    if ($age < 26) $age_factor = 0.8;
    elseif ($age > 50) $age_factor = 1.4;
    elseif ($age > 60) $age_factor = 1.8;
    
    // Location adjustments
    $location_multipliers = [
        'california' => 1.2, 'new york' => 1.3, 'massachusetts' => 1.2,
        'texas' => 0.9, 'florida' => 0.9, 'arizona' => 0.8
    ];
    
    $multiplier = 1.0;
    $location_lower = strtolower(trim($location));
    
    // More specific matching to avoid conflicts and ensure consistency
    if (stripos($location_lower, 'california') !== false || stripos($location_lower, 'san francisco') !== false || stripos($location_lower, 'los angeles') !== false) {
        $multiplier = 1.2;
    } elseif (stripos($location_lower, 'new york') !== false || stripos($location_lower, 'nyc') !== false || stripos($location_lower, 'manhattan') !== false) {
        $multiplier = 1.3;
    } elseif (stripos($location_lower, 'massachusetts') !== false || stripos($location_lower, 'boston') !== false) {
        $multiplier = 1.2;
    } elseif (stripos($location_lower, 'texas') !== false || stripos($location_lower, 'houston') !== false || stripos($location_lower, 'dallas') !== false) {
        $multiplier = 0.9;
    } elseif (stripos($location_lower, 'florida') !== false || stripos($location_lower, 'miami') !== false) {
        $multiplier = 0.9;
    } elseif (stripos($location_lower, 'arizona') !== false || stripos($location_lower, 'phoenix') !== false) {
        $multiplier = 0.8;
    }
    
    $result = round($base_insurance * $age_factor * $multiplier);
    return $result;
}




// Test Save button
if (isset($_POST['Save'])) {
}

// Handle form submissions
if (isset($_POST['Load'])) {
    // Load selected session data
    if (isset($_POST['load_session_id']) && !empty($_POST['load_session_id'])) {
        $selected_session_id = $_POST['load_session_id'];
        $selected_session = $db->getSession($selected_session_id);
        
        if ($selected_session && isset($selected_session['user_data']['household_data'])) {
            $budget_data = $selected_session['user_data']['household_data'];
            $destination_data = $selected_session['user_data']['destination_data'] ?? null;
            $current_session = $selected_session; // Update current session
            $_SESSION['current_session_id'] = $selected_session_id; // Update session ID
            
            $message = "Budget data loaded successfully!";
            
            // Redirect to refresh the page and show the loaded data
            header("Location: budget.php?loaded=1");
            exit();
        } else {
            $message = "Selected budget data not found.";
        }
    } else {
        $message = "Please select a budget to load.";
    }
}

if (isset($_POST['Save'])) {
    // Save current budget data by updating the current session
    
    $current_budget_data = [
        'name' => $_POST['name'] ?? $budget_data['name'] ?? '',
        'age' => (int)($_POST['age'] ?? $budget_data['age'] ?? 0),
        'location' => $_POST['location'] ?? $budget_data['location'] ?? '',
        'household_size' => (int)($_POST['household_size'] ?? $budget_data['household_size'] ?? 0),
        'bedrooms' => (int)($_POST['bedrooms'] ?? $budget_data['bedrooms'] ?? 0),
        'bathrooms' => (float)($_POST['bathrooms'] ?? $budget_data['bathrooms'] ?? 0),
        'rent' => (float)($_POST['rent'] ?? $budget_data['rent'] ?? 0),
        'utilities' => [
            'water' => (float)($_POST['water'] ?? $budget_data['utilities']['water'] ?? 0),
            'phone' => (float)($_POST['phone'] ?? $budget_data['utilities']['phone'] ?? 0),
            'electricity' => (float)($_POST['electricity'] ?? $budget_data['utilities']['electricity'] ?? 0),
            'other' => (float)($_POST['other_utilities'] ?? $budget_data['utilities']['other'] ?? 0)
        ],
        'groceries' => (float)($_POST['groceries'] ?? $budget_data['groceries'] ?? 0),
        'savings' => (float)($_POST['savings'] ?? $budget_data['savings'] ?? 0),
        'car_cost' => (float)($_POST['car_cost'] ?? $budget_data['car_cost'] ?? 0),
        'health_insurance' => (float)($_POST['health_insurance'] ?? $budget_data['health_insurance'] ?? 0),
        'debt' => [
            'total_debt' => (float)($_POST['total_debt'] ?? $budget_data['debt']['total_debt'] ?? 0),
            'monthly_payment' => (float)($_POST['monthly_debt'] ?? $budget_data['debt']['monthly_payment'] ?? 0),
            'debt_type' => $_POST['debt_type'] ?? $budget_data['debt']['debt_type'] ?? '',
            'interest_rate' => (float)($_POST['interest_rate'] ?? $budget_data['debt']['interest_rate'] ?? 0)
        ],
        'monthly_payments' => $budget_data['monthly_payments'] ?? []
    ];
    
    // Update the current session instead of creating a new one
    if ($current_session) {
        $update_data = [
            'user_data' => [
                'household_data' => $current_budget_data,
                'destination_data' => $current_session['user_data']['destination_data'] ?? null,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
            ]
        ];
        
        if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
            $budget_data = $current_budget_data; // Update the current budget_data for display
            $current_session = $db->getSession($_SESSION['current_session_id']); // Refresh current session
            $message = "Budget data saved successfully! (Updated current session)";
        } else {
            $message = "Failed to save budget data.";
        }
    } else {
        // If no current session, create a new one
        $new_session_data = [
            'user_data' => [
                'household_data' => $current_budget_data,
                'destination_data' => null,
                'app_requirements' => null,
            ]
        ];
        
        $new_session_id = uniqid('budget_', true);
        
        if ($db->createSession($new_session_id, $new_session_data)) {
            $budget_data = $current_budget_data; // Update the current budget_data for display
            $current_session = $db->getSession($new_session_id);
            $_SESSION['current_session_id'] = $new_session_id;
            $message = "Budget data saved successfully! (Created new session)";
        } else {
            $message = "Failed to save budget data.";
        }
    }
}

if (isset($_POST['NewBudget'])) {
    // Create a new budget session
    $new_session_id = uniqid('budget_', true);
    $new_budget_data = [
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
    
    $new_session_data = [
        'user_data' => [
            'household_data' => $new_budget_data,
            'app_requirements' => null,
        ]
    ];
    
    if ($db->createSession($new_session_id, $new_session_data)) {
        $budget_data = $new_budget_data;
        $current_session = $db->getSession($new_session_id);
        $_SESSION['current_session_id'] = $new_session_id;
        $message = "New budget created successfully!";
    } else {
        $message = "Failed to create new budget.";
    }
}

if (isset($_POST['NewSession'])) {
    // Create a new session with profile data from current session
    $new_session_id = 'session_' . uniqid();
    
    // Get current profile data to use as template
    $current_profile_data = [
        'name' => $budget_data['name'] ?? '',
        'age' => $budget_data['age'] ?? 0,
        'location' => $budget_data['location'] ?? '',
        'household_size' => $budget_data['household_size'] ?? 1,
        'bedrooms' => $budget_data['bedrooms'] ?? 0,
        'bathrooms' => $budget_data['bathrooms'] ?? 0,
        'rent' => 0, // Reset financial data
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
    
    $new_session_data = [
        'user_data' => [
            'household_data' => $current_profile_data,
            'destination_data' => null,
            'app_requirements' => null,
        ]
    ];
    
    if ($db->createSession($new_session_id, $new_session_data)) {
        $_SESSION['current_session_id'] = $new_session_id;
        $budget_data = $current_profile_data;
        $current_session = $db->getSession($new_session_id);
        $message = "New session created successfully! Profile data copied from previous session. You can now modify the profile information and generate a new budget analysis.";
    } else {
        $message = "Failed to create new session.";
    }
}

if (isset($_POST['Generate'])) {
    
    // Generate new budget analysis with advanced data
    // If no current session, create one first
    if (!$current_session) {
        $new_session_id = 'session_' . uniqid();
        $new_session_data = [
            'user_data' => [
                'household_data' => null,
                'app_requirements' => null,
                'destination_data' => null,
            ]
        ];
        
        if ($db->createSession($new_session_id, $new_session_data)) {
            $_SESSION['current_session_id'] = $new_session_id;
            $current_session = $db->getSession($new_session_id);
        }
    }
    
    if ($current_session) {
        // Validate required profile fields
        $required_profile_fields = ['name', 'age', 'location', 'household_size', 'bedrooms', 'bathrooms'];
        $missing_fields = [];
        
        foreach ($required_profile_fields as $field) {
            $value = $_POST[$field] ?? '';
            if (empty($value)) {
                $missing_fields[] = str_replace('_', ' ', $field);
            }
        }
        
        
        // Proceed with generation regardless of missing fields
            // Use current form data or existing budget data, with smart defaults for missing financial data
        $current_data = [
                'name' => trim($_POST['name'] ?? ''),
                'age' => (int)($_POST['age'] ?? 25),
                'location' => trim($_POST['location'] ?? ''),
                'household_size' => (int)($_POST['household_size'] ?? 1),
                'bedrooms' => (int)($_POST['bedrooms'] ?? 1),
                'bathrooms' => (float)($_POST['bathrooms'] ?? 1),
            'rent' => (float)($_POST['rent'] ?? $budget_data['rent'] ?? 0),
            'utilities' => [
                'water' => (float)($_POST['water'] ?? $budget_data['utilities']['water'] ?? 0),
                'phone' => (float)($_POST['phone'] ?? $budget_data['utilities']['phone'] ?? 0),
                'electricity' => (float)($_POST['electricity'] ?? $budget_data['utilities']['electricity'] ?? 0),
                'other' => (float)($_POST['other_utilities'] ?? $budget_data['utilities']['other'] ?? 0)
            ],
            'groceries' => (float)($_POST['groceries'] ?? $budget_data['groceries'] ?? 0),
            'savings' => (float)($_POST['savings'] ?? $budget_data['savings'] ?? 0),
                'car_cost' => (float)($_POST['car_cost'] ?? $budget_data['car_cost'] ?? 0),
                'health_insurance' => (float)($_POST['health_insurance'] ?? $budget_data['health_insurance'] ?? 0),
            'debt' => [
                'total_debt' => (float)($_POST['total_debt'] ?? $budget_data['debt']['total_debt'] ?? 0),
                'monthly_payment' => (float)($_POST['monthly_debt'] ?? $budget_data['debt']['monthly_payment'] ?? 0),
                'debt_type' => $_POST['debt_type'] ?? $budget_data['debt']['debt_type'] ?? '',
                'interest_rate' => (float)($_POST['interest_rate'] ?? $budget_data['debt']['interest_rate'] ?? 0)
            ],
            'monthly_payments' => $budget_data['monthly_payments'] ?? []
        ];
        
        // Generate cost breakdown using AI
        $cost_breakdown = generateCostBreakdown($current_data['location'], $current_data['household_size']);
        
        // Generate fresh budget data based on current profile information
        // ALWAYS generate fresh estimates for all financial fields
        $optimized_budget_data = [
            'name' => $current_data['name'],
            'age' => $current_data['age'],
            'location' => $current_data['location'],
            'household_size' => $current_data['household_size'],
            'bedrooms' => $current_data['bedrooms'],
            'bathrooms' => $current_data['bathrooms'],
            // ALWAYS generate fresh rent estimate
            'rent' => generateRentEstimate($current_data['location'], $current_data['household_size'], $current_data['bedrooms']),
            'utilities' => [
                // ALWAYS generate fresh utility estimates
                'water' => generateWaterEstimate($current_data['household_size'], $current_data['bathrooms']),
                'phone' => $cost_breakdown['phone'] ?? generatePhoneEstimate($current_data['household_size']),
                'electricity' => generateElectricityEstimate($current_data['household_size'], $current_data['bedrooms']),
                'other' => generateOtherUtilitiesEstimate($current_data['household_size'])
            ],
            // ALWAYS generate fresh estimates for other expenses
            'groceries' => generateGroceriesEstimate($current_data['household_size'], $current_data['age']),
            'savings' => generateSavingsEstimate($current_data['age'], $current_data['household_size']),
            'car_cost' => $cost_breakdown['car'] ?? generateCarCostEstimate($current_data['location'], $current_data['age']),
            'health_insurance' => $cost_breakdown['health_insurance'] ?? generateHealthInsuranceEstimate($current_data['age'], $current_data['location']),
            'debt' => $current_data['debt'], // Keep current debt info
            'monthly_payments' => $current_data['monthly_payments']
        ];
        
        // Update the session with optimized budget data
        $update_data = [
            'user_data' => [
                'household_data' => $optimized_budget_data,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
            ]
        ];
        
        if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
            $budget_data = $optimized_budget_data; // Update the current budget_data for display
            $message = "Budget analysis generated successfully!";
            
            // Data has been generated and saved - form fields will show the updated values
        } else {
            $message = "Failed to save generated budget data.";
        }
    } else {
        $message = "Please create a budget session first.";
    }
}

if (isset($_POST['Update'])) {
    // Update budget data
    if ($current_session) {
        $updated_data = [
            'name' => $_POST['name'] ?? $budget_data['name'] ?? '',
            'age' => (int)($_POST['age'] ?? $budget_data['age'] ?? 0),
            'location' => $_POST['location'] ?? $budget_data['location'] ?? '',
            'household_size' => (int)($_POST['household_size'] ?? $budget_data['household_size'] ?? 0),
            'bedrooms' => (int)($_POST['bedrooms'] ?? $budget_data['bedrooms'] ?? 0),
            'bathrooms' => (float)($_POST['bathrooms'] ?? $budget_data['bathrooms'] ?? 0),
            'rent' => (float)($_POST['rent'] ?? $budget_data['rent'] ?? 0),
            'utilities' => [
                'water' => (float)($_POST['water'] ?? $budget_data['utilities']['water'] ?? 0),
                'phone' => (float)($_POST['phone'] ?? $budget_data['utilities']['phone'] ?? 0),
                'electricity' => (float)($_POST['electricity'] ?? $budget_data['utilities']['electricity'] ?? 0),
                'other' => (float)($_POST['other_utilities'] ?? $budget_data['utilities']['other'] ?? 0)
            ],
            'groceries' => (float)($_POST['groceries'] ?? $budget_data['groceries'] ?? 0),
            'savings' => (float)($_POST['savings'] ?? $budget_data['savings'] ?? 0),
            'car_cost' => (float)($_POST['car_cost'] ?? $budget_data['car_cost'] ?? 0),
            'health_insurance' => (float)($_POST['health_insurance'] ?? $budget_data['health_insurance'] ?? 0),
            'debt' => [
                'total_debt' => (float)($_POST['total_debt'] ?? $budget_data['debt']['total_debt'] ?? 0),
                'monthly_payment' => (float)($_POST['monthly_debt'] ?? $budget_data['debt']['monthly_payment'] ?? 0),
                'debt_type' => $_POST['debt_type'] ?? $budget_data['debt']['debt_type'] ?? '',
                'interest_rate' => (float)($_POST['interest_rate'] ?? $budget_data['debt']['interest_rate'] ?? 0)
            ],
            'monthly_payments' => $budget_data['monthly_payments'] ?? []
        ];
        
        $update_data = [
            'user_data' => [
                'household_data' => $updated_data,
                'destination_data' => $current_session['user_data']['destination_data'] ?? null,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null
            ]
        ];
        
        if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
            $budget_data = $updated_data;
            $message = "Budget data updated successfully!";
        } else {
            $message = "Failed to update budget data.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Builder - Budget App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
    <!-- <script src="./js/app.js"></script> -->
    <script>
        // Minimal JavaScript that doesn't interfere with form submission
        document.addEventListener('DOMContentLoaded', function() {
            // Add simple loading states without preventing form submission
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Don't prevent default - let the form submit normally
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        // Just add a simple loading state
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = 'Processing...';
                        submitBtn.disabled = true;
                        
                        // Re-enable after a short delay in case of errors
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            });
        });
    </script>
</head>

<body>
    <div class="budget-container">
        <?php include 'navigation.php'?>
        <h1>Budget Analysis</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        
        
        <div class="budget-actions">
            <form id="loadInformation" action="budget.php" method="post" class="budget-action-form">
                <div class="form-group">
                    <label for="load_session_id">Load Saved Budget</label>
                    <select name="load_session_id" id="load_session_id">
                        <option value="">Select a saved budget to load...</option>
                        <?php foreach ($all_sessions as $session): ?>
                            <?php if (isset($session['user_data']['household_data'])): ?>
                                <?php $data = $session['user_data']['household_data']; ?>
                                <option value="<?php echo $session['session_id']; ?>">
                                    <?php echo htmlspecialchars($data['name'] ?? 'Unknown User'); ?> - 
                                    <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="Load" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Load Selected Budget
                </button>
            </form>
            
            
            <form id="newBudget" action="budget.php" method="post" class="budget-action-form">
                <button type="submit" name="NewBudget" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Create New Budget
                </button>
            </form>
            
            <form id="newSession" action="budget.php" method="post" class="budget-action-form">
                <button type="submit" name="NewSession" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Create New Session
                </button>
            </form>
        </div>

        <form name="budgetForm" id="budgetForm" method="POST" action="budget.php">
            <input type="hidden" name="form_submitted" value="1">
            <div class="budget-actions">
                <input type="submit" name="Generate" value="Generate Analysis" class="btn btn-primary" style="padding: 10px 20px; border: none; border-radius: 5px; background-color: #007bff; color: white; cursor: pointer;">
                <button type="submit" name="Save" class="btn btn-success" style="margin-left: 10px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Save Current Budget
                </button>
            </div>
            
            <div class="compare-section">
                <div class="budget-forms-row">
                    <div class="location-form">
                        <h3 class="text-center">Personal Information <span class="required-indicator">*</span></h3>
                        <p class="form-description">These fields are required to generate your budget analysis.</p>
                        <div class="report-item">
                            <label for="name">Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($budget_data['name'] ?? ''); ?>" placeholder="Enter your full name">
                        </div>
                        <div class="report-item">
                            <label for="age">Age <span class="required">*</span></label>
                            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($budget_data['age'] ?? ''); ?>" min="18" max="120" placeholder="Enter your age">
                        </div>
                        <div class="report-item">
                            <label for="location">Location <span class="required">*</span></label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($budget_data['location'] ?? ''); ?>" placeholder="City, State">
                        </div>
                        <div class="report-item">
                            <label for="household_size">Household Size <span class="required">*</span></label>
                            <input type="number" id="household_size" name="household_size" value="<?php echo htmlspecialchars($budget_data['household_size'] ?? ''); ?>" min="1" max="20" placeholder="Number of people">
                        </div>
                        <div class="report-item">
                            <label for="bedrooms">Bedrooms <span class="required">*</span></label>
                            <input type="number" id="bedrooms" name="bedrooms" value="<?php echo htmlspecialchars($budget_data['bedrooms'] ?? ''); ?>" min="0" max="20" placeholder="Number of bedrooms">
                        </div>
                        <div class="report-item">
                            <label for="bathrooms">Bathrooms <span class="required">*</span></label>
                            <input type="number" id="bathrooms" name="bathrooms" value="<?php echo htmlspecialchars($budget_data['bathrooms'] ?? ''); ?>" min="0" max="20" step="0.5" placeholder="Number of bathrooms">
                        </div>
                    </div>
                    
                    <div class="location-form">
                        <h3 class="text-center">Financial Information <span class="optional-indicator">(Optional)</span></h3>
                        <p class="form-description">Enter your current expenses or leave blank to generate estimates based on your location and household size.</p>
                        <div class="cost-section">
                            <div class="report-item">
                                <label for="rent">Monthly Rent</label>
                                <input type="number" id="rent" name="rent" value="<?php echo htmlspecialchars($budget_data['rent'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="water">Water Bill</label>
                                <input type="number" id="water" name="water" value="<?php echo htmlspecialchars($budget_data['utilities']['water'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="phone">Phone Bill</label>
                                <input type="number" id="phone" name="phone" value="<?php echo htmlspecialchars($budget_data['utilities']['phone'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="electricity">Electricity</label>
                                <input type="number" id="electricity" name="electricity" value="<?php echo htmlspecialchars($budget_data['utilities']['electricity'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="other_utilities">Other Utilities</label>
                                <input type="number" id="other_utilities" name="other_utilities" value="<?php echo htmlspecialchars($budget_data['utilities']['other'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="groceries">Groceries</label>
                                <input type="number" id="groceries" name="groceries" value="<?php echo htmlspecialchars($budget_data['groceries'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="savings">Savings Goal</label>
                                <input type="number" id="savings" name="savings" value="<?php echo htmlspecialchars($budget_data['savings'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="car_cost">Car Ownership Cost</label>
                                <input type="number" id="car_cost" name="car_cost" value="<?php echo htmlspecialchars($budget_data['car_cost'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="health_insurance">Health Insurance</label>
                                <input type="number" id="health_insurance" name="health_insurance" value="<?php echo htmlspecialchars($budget_data['health_insurance'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="location-form">
                        <h3 class="text-center">Debt Information</h3>
                        <div class="cost-section">
                            <div class="report-item">
                                <label for="total_debt">Total Debt</label>
                                <input type="number" id="total_debt" name="total_debt" value="<?php echo htmlspecialchars($budget_data['debt']['total_debt'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="monthly_debt">Monthly Debt Payment</label>
                                <input type="number" id="monthly_debt" name="monthly_debt" value="<?php echo htmlspecialchars($budget_data['debt']['monthly_payment'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                            <div class="report-item">
                                <label for="debt_type">Debt Type</label>
                                <input type="text" id="debt_type" name="debt_type" value="<?php echo htmlspecialchars($budget_data['debt']['debt_type'] ?? ''); ?>" placeholder="e.g., Credit Card, Student Loan">
                            </div>
                            <div class="report-item">
                                <label for="interest_rate">Interest Rate (%)</label>
                                <input type="number" id="interest_rate" name="interest_rate" value="<?php echo htmlspecialchars($budget_data['debt']['interest_rate'] ?? ''); ?>" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="report-item total text-center">
                            <strong>Total Monthly Expenses:</strong>
                            <div class="total-amount">$<?php 
                                if ($budget_data) {
                                    $total = ($budget_data['rent'] ?? 0) + 
                                            (($budget_data['utilities']['water'] ?? 0) + 
                                             ($budget_data['utilities']['phone'] ?? 0) + 
                                             ($budget_data['utilities']['electricity'] ?? 0) + 
                                             ($budget_data['utilities']['other'] ?? 0)) + 
                                            ($budget_data['groceries'] ?? 0) + 
                                            ($budget_data['car_cost'] ?? 0) + 
                                            ($budget_data['health_insurance'] ?? 0) + 
                                            ($budget_data['debt']['monthly_payment'] ?? 0);
                                    echo number_format($total, 2);
                                } else {
                                    echo "0.00";
                                }
                            ?></div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="submit" name="Update" class="btn btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"></path>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                                Update Budget
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Location Comparison Section -->
        <?php if ($destination_data): ?>
        <div class="compare-section">
            <h2 class="text-center">Location Comparison</h2>
            <div class="reports-container">
                <div class="location-form">
                    <h3 class="text-center">Current Location</h3>
                    <div class="report-item">
                        <strong>Location:</strong> <?php echo htmlspecialchars($budget_data['location'] ?? 'N/A'); ?>
                    </div>
                    <div class="report-item">
                        <strong>Household Size:</strong> <?php echo htmlspecialchars($budget_data['household_size'] ?? 'N/A'); ?>
                    </div>
                    <div class="report-item">
                        <strong>Bedrooms/Bathrooms:</strong> <?php echo htmlspecialchars(($budget_data['bedrooms'] ?? 'N/A') . '/' . ($budget_data['bathrooms'] ?? 'N/A')); ?>
                    </div>
                    <div class="report-item">
                        <strong>Rent:</strong> $<?php echo number_format($budget_data['rent'] ?? 0, 2); ?>
                    </div>
                    <div class="report-item">
                        <strong>Utilities:</strong> $<?php echo number_format($budget_data['utilities']['water'] ?? 0, 2); ?>
                    </div>
                    <div class="report-item">
                        <strong>Groceries:</strong> $<?php echo number_format($budget_data['groceries'] ?? 0, 2); ?>
                    </div>
                    <div class="report-item total text-center">
                        <strong>Total Monthly:</strong>
                        <div class="total-amount">$<?php 
                            $current_total = ($budget_data['rent'] ?? 0) + 
                                           ($budget_data['utilities']['water'] ?? 0) + 
                                           ($budget_data['groceries'] ?? 0);
                            echo number_format($current_total, 2);
                        ?></div>
                    </div>
                </div>
                
                <div class="location-form">
                    <h3 class="text-center">Destination Location</h3>
                    <div class="report-item">
                        <strong>Location:</strong> <?php echo htmlspecialchars($destination_data['location'] ?? 'N/A'); ?>
                    </div>
                    <div class="report-item">
                        <strong>Household Size:</strong> <?php echo htmlspecialchars($destination_data['household_size'] ?? 'N/A'); ?>
                    </div>
                    <div class="report-item">
                        <strong>Bedrooms/Bathrooms:</strong> <?php echo htmlspecialchars(($destination_data['bedrooms'] ?? 'N/A') . '/' . ($destination_data['bathrooms'] ?? 'N/A')); ?>
                    </div>
                    <div class="report-item">
                        <strong>Rent:</strong> $<?php echo number_format($destination_data['rent'] ?? 0, 2); ?>
                    </div>
                    <div class="report-item">
                        <strong>Utilities:</strong> $<?php echo number_format($destination_data['utilities'] ?? 0, 2); ?>
                    </div>
                    <div class="report-item">
                        <strong>Groceries:</strong> $<?php echo number_format($destination_data['groceries'] ?? 0, 2); ?>
                    </div>
                    <div class="report-item total text-center">
                        <strong>Total Monthly:</strong>
                        <div class="total-amount">$<?php 
                            $destination_total = ($destination_data['rent'] ?? 0) + 
                                               ($destination_data['utilities'] ?? 0) + 
                                               ($destination_data['groceries'] ?? 0);
                            echo number_format($destination_total, 2);
                        ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Comparison Summary -->
            <div class="location-form">
                <h3 class="text-center">Comparison Summary</h3>
                <div class="cost-section">
                    <div class="report-item text-center">
                        <strong>Monthly Difference:</strong> 
                        <div class="total-amount <?php 
                            $difference = $destination_total - $current_total;
                            echo $difference >= 0 ? 'text-red-600' : 'text-green-600';
                        ?>">
                            <?php 
                                $difference_text = $difference >= 0 ? '+' : '';
                                echo $difference_text . '$' . number_format($difference, 2);
                            ?>
                        </div>
                    </div>
                    <div class="report-item text-center">
                        <strong>Annual Difference:</strong> 
                        <div class="total-amount <?php 
                            $annual_difference = $difference * 12;
                            echo $annual_difference >= 0 ? 'text-red-600' : 'text-green-600';
                        ?>">
                            <?php 
                                $annual_difference_text = $annual_difference >= 0 ? '+' : '';
                                echo $annual_difference_text . '$' . number_format($annual_difference, 2);
                            ?>
                        </div>
                    </div>
                    <div class="report-item text-center">
                        <strong>Percentage Change:</strong> 
                        <div class="total-amount <?php 
                            if ($current_total > 0) {
                                $percentage_change = (($destination_total - $current_total) / $current_total) * 100;
                                echo $percentage_change >= 0 ? 'text-red-600' : 'text-green-600';
                            }
                        ?>">
                            <?php 
                                if ($current_total > 0) {
                                    $percentage_text = $percentage_change >= 0 ? '+' : '';
                                    echo $percentage_text . number_format($percentage_change, 1) . '%';
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>