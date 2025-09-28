<?php
// Database configuration for PHP frontend
class Database {
    private $db_path;
    private $connection;
    
    public function __construct() {
        // Path to the SQLite database file
        $this->db_path = __DIR__ . '/../../budget_app.db';
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $this->connection = new PDO('sqlite:' . $this->db_path);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return $this->connection;
    }
    
    // User authentication methods
    public function authenticateUser($username, $password) {
        $conn = $this->getConnection();
        $password_hash = hash('sha256', $password);
        
        $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE username = ? AND password_hash = ?");
        $stmt->execute([$username, $password_hash]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update last login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
            $update_stmt->execute([$user['id']]);
        }
        
        return $user;
    }
    
    public function createUser($username, $password, $email = null) {
        $conn = $this->getConnection();
        $password_hash = hash('sha256', $password);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, datetime('now'))");
            $stmt->execute([$username, $password_hash, $email]);
            return $conn->lastInsertId();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Session management methods
    public function createSession($session_id, $user_data) {
        $conn = $this->getConnection();
        
        try {
            // Clean up old sessions first
            $this->cleanupOldSessions($conn);
            
            $stmt = $conn->prepare("INSERT INTO sessions (session_id, user_data, created_at, updated_at) VALUES (?, ?, datetime('now'), datetime('now'))");
            $stmt->execute([$session_id, json_encode($user_data)]);
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function getSession($session_id) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            $session['user_data'] = json_decode($session['user_data'], true);
            $session['budget_analysis'] = $session['budget_analysis'] ? json_decode($session['budget_analysis'], true) : null;
            $session['app_recommendations'] = $session['app_recommendations'] ? json_decode($session['app_recommendations'], true) : null;
        }
        
        return $session;
    }
    
    public function getRecentSessions($limit = 3) {
        $conn = $this->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM sessions ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sessions as &$session) {
            $session['user_data'] = json_decode($session['user_data'], true);
            $session['budget_analysis'] = $session['budget_analysis'] ? json_decode($session['budget_analysis'], true) : null;
            $session['app_recommendations'] = $session['app_recommendations'] ? json_decode($session['app_recommendations'], true) : null;
        }
        
        return $sessions;
    }
    
    public function updateSession($session_id, $updates) {
        $conn = $this->getConnection();
        
        try {
            $set_clauses = [];
            $values = [];
            
            foreach ($updates as $key => $value) {
                if (in_array($key, ['budget_analysis', 'app_recommendations', 'user_data'])) {
                    $set_clauses[] = "$key = ?";
                    $values[] = json_encode($value);
                } else {
                    $set_clauses[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            $set_clauses[] = "updated_at = datetime('now')";
            $values[] = $session_id;
            
            $query = "UPDATE sessions SET " . implode(', ', $set_clauses) . " WHERE session_id = ?";
            $stmt = $conn->prepare($query);
            
            $result = $stmt->execute($values);
            
            // Check if any rows were affected
            if ($result && $stmt->rowCount() === 0) {
                // Session doesn't exist, create it
                if (isset($updates['user_data'])) {
                    return $this->createSession($session_id, $updates['user_data']);
                }
            }
            
            return $result;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    private function cleanupOldSessions($conn) {
        // Keep only the 3 most recent sessions
        $stmt = $conn->prepare("
            DELETE FROM sessions 
            WHERE id NOT IN (
                SELECT id FROM sessions 
                ORDER BY created_at DESC 
                LIMIT 3
            )
        ");
        $stmt->execute();
    }
    
    public function getSessionCount() {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sessions");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function getCurrentSession() {
        $conn = $this->getConnection();
        
        // Get the most recent session
        $stmt = $conn->prepare("SELECT * FROM sessions ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            $session['user_data'] = json_decode($session['user_data'], true);
            $session['budget_analysis'] = $session['budget_analysis'] ? json_decode($session['budget_analysis'], true) : null;
            $session['app_recommendations'] = $session['app_recommendations'] ? json_decode($session['app_recommendations'], true) : null;
        }
        
        return $session;
    }
    
    public function sessionExists($session_id) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        return $stmt->fetchColumn() > 0;
    }
}

// Global database instance
$db = new Database();
?>
