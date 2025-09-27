<?php
// Database initialization script
// This creates the necessary tables in the SQLite database

$db_path = __DIR__ . '/../../budget_app.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            email TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP
        )
    ");
    
    // Create sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT UNIQUE NOT NULL,
            user_id INTEGER,
            user_data TEXT NOT NULL,
            budget_analysis TEXT,
            app_recommendations TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )
    ");
    
    // Create user profiles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            profile_id TEXT UNIQUE NOT NULL,
            profile_name TEXT NOT NULL,
            profile_data TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create indexes for faster queries
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_created_at ON sessions(created_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_profiles_created_at ON user_profiles(created_at DESC)");
    
    // Create a demo user for testing
    $demo_username = 'demo';
    $demo_password = 'demo';
    $demo_password_hash = hash('sha256', $demo_password);
    
    // Check if demo user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$demo_username]);
    
    if (!$stmt->fetch()) {
        // Create demo user
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([$demo_username, $demo_password_hash, 'demo@example.com']);
        echo "Demo user created successfully!\n";
    } else {
        echo "Demo user already exists.\n";
    }
    
    echo "Database initialized successfully!\n";
    echo "Tables created: users, sessions, user_profiles\n";
    echo "Demo user: username='demo', password='demo'\n";
    
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?>
