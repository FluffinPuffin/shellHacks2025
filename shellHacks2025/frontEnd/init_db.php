<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Initialization</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Database Initialization</h1>
    
    <?php
    // Database initialization script
    $db_path = __DIR__ . '/budget_app.db';
    
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
        
        echo "<p class='success'>✅ Database tables created successfully!</p>";
        
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
            echo "<p class='success'>✅ Demo user created successfully!</p>";
        } else {
            echo "<p class='info'>ℹ️ Demo user already exists.</p>";
        }
        
        echo "<h2>Database Status</h2>";
        echo "<p class='success'>✅ All tables created: users, sessions, user_profiles</p>";
        echo "<p class='info'>📝 Demo user: username='demo', password='demo'</p>";
        echo "<p class='info'>🗂️ Database file: " . $db_path . "</p>";
        
        // Show table counts
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $stmt->fetchColumn();
        echo "<p class='info'>👥 Users in database: " . $user_count . "</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
        $session_count = $stmt->fetchColumn();
        echo "<p class='info'>📊 Sessions in database: " . $session_count . "</p>";
        
        echo "<hr>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
        echo "<p><a href='index.php'>Go to Home Page</a></p>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error initializing database: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='error'>Database path: " . $db_path . "</p>";
    }
    ?>
</body>
</html>
