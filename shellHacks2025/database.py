import sqlite3
import json
import hashlib
from datetime import datetime
from typing import List, Dict, Optional
from contextlib import contextmanager

class DatabaseManager:
    """Database manager for budget app sessions"""
    
    def __init__(self, db_path: str = "budget_app.db"):
        self.db_path = db_path
        self.init_database()
    
    def init_database(self):
        """Initialize the database with required tables"""
        with self.get_connection() as conn:
            cursor = conn.cursor()
            
            # Create users table
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    email TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP
                )
            """)
            
            # Create sessions table (updated with user_id)
            cursor.execute("""
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
            """)
            
            # Create user profiles table
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS user_profiles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    profile_id TEXT UNIQUE NOT NULL,
                    profile_name TEXT NOT NULL,
                    profile_data TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            """)
            
            # Create indexes for faster queries
            cursor.execute("""
                CREATE INDEX IF NOT EXISTS idx_sessions_created_at 
                ON sessions(created_at DESC)
            """)
            
            cursor.execute("""
                CREATE INDEX IF NOT EXISTS idx_sessions_user_id 
                ON sessions(user_id)
            """)
            
            cursor.execute("""
                CREATE INDEX IF NOT EXISTS idx_users_username 
                ON users(username)
            """)
            
            cursor.execute("""
                CREATE INDEX IF NOT EXISTS idx_profiles_created_at 
                ON user_profiles(created_at DESC)
            """)
            
            conn.commit()
    
    @contextmanager
    def get_connection(self):
        """Context manager for database connections"""
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row  # Enable column access by name
        try:
            yield conn
        finally:
            conn.close()
    
    def create_session(self, session_id: str, user_data: Dict) -> bool:
        """Create a new session"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                
                # Clean up old sessions (keep only 3 most recent)
                self._cleanup_old_sessions(conn)
                
                # Insert new session
                cursor.execute("""
                    INSERT INTO sessions (session_id, user_data, created_at, updated_at)
                    VALUES (?, ?, ?, ?)
                """, (
                    session_id,
                    json.dumps(user_data),
                    datetime.now().isoformat(),
                    datetime.now().isoformat()
                ))
                
                conn.commit()
                
                # Ensure cleanup happens after commit
                self._cleanup_old_sessions(conn)
                conn.commit()
                
                return True
        except sqlite3.IntegrityError as e:
            # Session ID already exists
            print(f"Session {session_id} already exists: {e}")
            return False
        except Exception as e:
            print(f"Error creating session: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    def get_session(self, session_id: str) -> Optional[Dict]:
        """Get a specific session by ID"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    SELECT * FROM sessions WHERE session_id = ?
                """, (session_id,))
                
                row = cursor.fetchone()
                if row:
                    return {
                        'id': row['id'],
                        'session_id': row['session_id'],
                        'user_data': json.loads(row['user_data']),
                        'budget_analysis': json.loads(row['budget_analysis']) if row['budget_analysis'] else None,
                        'app_recommendations': json.loads(row['app_recommendations']) if row['app_recommendations'] else None,
                        'created_at': row['created_at'],
                        'updated_at': row['updated_at']
                    }
                return None
        except Exception as e:
            print(f"Error getting session: {e}")
            return None
    
    def get_recent_sessions(self, limit: int = 3) -> List[Dict]:
        """Get the most recent sessions (up to limit)"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    SELECT * FROM sessions 
                    ORDER BY created_at DESC 
                    LIMIT ?
                """, (limit,))
                
                sessions = []
                for row in cursor.fetchall():
                    sessions.append({
                        'id': row['id'],
                        'session_id': row['session_id'],
                        'user_data': json.loads(row['user_data']),
                        'budget_analysis': json.loads(row['budget_analysis']) if row['budget_analysis'] else None,
                        'app_recommendations': json.loads(row['app_recommendations']) if row['app_recommendations'] else None,
                        'created_at': row['created_at'],
                        'updated_at': row['updated_at']
                    })
                
                return sessions
        except Exception as e:
            print(f"Error getting recent sessions: {e}")
            return []
    
    def update_session(self, session_id: str, updates: Dict) -> bool:
        """Update a session with new data"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                
                # First check if session exists
                cursor.execute("SELECT session_id FROM sessions WHERE session_id = ?", (session_id,))
                if not cursor.fetchone():
                    print(f"Session {session_id} not found in database")
                    return False
                
                # Build dynamic update query
                set_clauses = []
                values = []
                
                for key, value in updates.items():
                    if key in ['budget_analysis', 'app_recommendations']:
                        set_clauses.append(f"{key} = ?")
                        values.append(json.dumps(value) if value else None)
                    elif key == 'user_data':
                        set_clauses.append(f"{key} = ?")
                        values.append(json.dumps(value))
                    elif key == 'household_data':
                        # Map household_data to user_data in the database
                        set_clauses.append(f"user_data = ?")
                        values.append(json.dumps(value))
                    else:
                        # Handle any other fields that might be passed
                        set_clauses.append(f"{key} = ?")
                        values.append(json.dumps(value) if isinstance(value, (dict, list)) else value)
                
                if not set_clauses:
                    print("No valid update fields found")
                    return False
                
                # Add updated_at timestamp
                set_clauses.append("updated_at = ?")
                values.append(datetime.now().isoformat())
                values.append(session_id)
                
                query = f"""
                    UPDATE sessions 
                    SET {', '.join(set_clauses)}
                    WHERE session_id = ?
                """
                
                cursor.execute(query, values)
                
                # Clean up old sessions after update too
                self._cleanup_old_sessions(conn)
                
                conn.commit()
                
                rowcount = cursor.rowcount
                
                return rowcount > 0
        except Exception as e:
            print(f"Error updating session: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    def delete_session(self, session_id: str) -> bool:
        """Delete a specific session"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("DELETE FROM sessions WHERE session_id = ?", (session_id,))
                conn.commit()
                return cursor.rowcount > 0
        except Exception as e:
            print(f"Error deleting session: {e}")
            return False
    
    def _cleanup_old_sessions(self, conn):
        """Keep only the 3 most recent sessions"""
        cursor = conn.cursor()
        
        # Get count of sessions
        cursor.execute("SELECT COUNT(*) FROM sessions")
        count = cursor.fetchone()[0]
        
        # If more than 3 sessions, delete the oldest ones
        if count > 3:
            # Get the IDs of the 3 most recent sessions
            cursor.execute("""
                SELECT id FROM sessions 
                ORDER BY created_at DESC 
                LIMIT 3
            """)
            recent_ids = [row[0] for row in cursor.fetchall()]
            
            # Delete all sessions that are not in the recent 3
            if recent_ids:
                placeholders = ','.join('?' for _ in recent_ids)
                cursor.execute(f"""
                    DELETE FROM sessions 
                    WHERE id NOT IN ({placeholders})
                """, recent_ids)
                
                deleted_count = cursor.rowcount
                print(f"Auto-cleanup: Kept 3 most recent sessions (deleted {deleted_count} old sessions)")
    
    def cleanup_old_sessions(self):
        """Public method to clean up old sessions"""
        try:
            with self.get_connection() as conn:
                self._cleanup_old_sessions(conn)
                conn.commit()
                return True
        except Exception as e:
            print(f"Error cleaning up sessions: {e}")
            return False
    
    def get_session_count(self) -> int:
        """Get total number of sessions"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("SELECT COUNT(*) FROM sessions")
                return cursor.fetchone()[0]
        except Exception as e:
            print(f"Error getting session count: {e}")
            return 0
    
    def clear_all_sessions(self) -> bool:
        """Clear all sessions from the database"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("DELETE FROM sessions")
                conn.commit()
                print("All sessions cleared from database")
                return True
        except Exception as e:
            print(f"Error clearing all sessions: {e}")
            return False
    
    # Profile Management Methods
    
    def create_profile(self, profile_id: str, profile_data: dict) -> bool:
        """Create a new user profile"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    INSERT INTO user_profiles (profile_id, profile_name, profile_data, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                """, (
                    profile_id,
                    profile_data.get('profile_name', 'Unnamed Profile'),
                    json.dumps(profile_data),
                    datetime.now().isoformat(),
                    datetime.now().isoformat()
                ))
                conn.commit()
                print(f"Profile created: {profile_id}")
                return True
        except Exception as e:
            print(f"Error creating profile: {e}")
            return False
    
    def get_profile(self, profile_id: str) -> dict:
        """Get a specific profile by ID"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    SELECT profile_data, created_at, updated_at 
                    FROM user_profiles 
                    WHERE profile_id = ?
                """, (profile_id,))
                result = cursor.fetchone()
                
                if result:
                    profile_data = json.loads(result[0])
                    profile_data['created_at'] = result[1]
                    profile_data['updated_at'] = result[2]
                    return profile_data
                return None
        except Exception as e:
            print(f"Error getting profile: {e}")
            return None
    
    def get_all_profiles(self) -> list:
        """Get all user profiles"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    SELECT profile_id, profile_name, profile_data, created_at, updated_at
                    FROM user_profiles 
                    ORDER BY created_at DESC
                """)
                results = cursor.fetchall()
                
                profiles = []
                for row in results:
                    profile_data = json.loads(row[2])
                    profile_data['profile_id'] = row[0]
                    profile_data['created_at'] = row[3]
                    profile_data['updated_at'] = row[4]
                    profiles.append(profile_data)
                
                return profiles
        except Exception as e:
            print(f"Error getting profiles: {e}")
            return []
    
    def update_profile(self, profile_id: str, profile_data: dict) -> bool:
        """Update an existing profile"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    UPDATE user_profiles 
                    SET profile_name = ?, profile_data = ?, updated_at = ?
                    WHERE profile_id = ?
                """, (
                    profile_data.get('profile_name', 'Unnamed Profile'),
                    json.dumps(profile_data),
                    datetime.now().isoformat(),
                    profile_id
                ))
                conn.commit()
                print(f"Profile updated: {profile_id}")
                return cursor.rowcount > 0
        except Exception as e:
            print(f"Error updating profile: {e}")
            return False
    
    def delete_profile(self, profile_id: str) -> bool:
        """Delete a profile"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("DELETE FROM user_profiles WHERE profile_id = ?", (profile_id,))
                conn.commit()
                print(f"Profile deleted: {profile_id}")
                return cursor.rowcount > 0
        except Exception as e:
            print(f"Error deleting profile: {e}")
            return False
    
    # User Management Methods
    
    def hash_password(self, password: str) -> str:
        """Hash a password using SHA-256"""
        return hashlib.sha256(password.encode()).hexdigest()
    
    def create_user(self, username: str, password: str, email: Optional[str] = None) -> bool:
        """Create a new user"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                
                # Check if username already exists
                cursor.execute("SELECT id FROM users WHERE username = ?", (username,))
                if cursor.fetchone():
                    return False
                
                # Hash password and create user
                password_hash = self.hash_password(password)
                cursor.execute("""
                    INSERT INTO users (username, password_hash, email, created_at)
                    VALUES (?, ?, ?, ?)
                """, (username, password_hash, email, datetime.now().isoformat()))
                
                conn.commit()
                return True
        except Exception as e:
            print(f"Error creating user: {e}")
            return False
    
    def authenticate_user(self, username: str, password: str) -> Optional[Dict]:
        """Authenticate a user and return user data"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                password_hash = self.hash_password(password)
                
                cursor.execute("""
                    SELECT id, username, email, created_at FROM users 
                    WHERE username = ? AND password_hash = ?
                """, (username, password_hash))
                
                row = cursor.fetchone()
                if row:
                    # Update last login
                    cursor.execute("""
                        UPDATE users SET last_login = ? WHERE id = ?
                    """, (datetime.now().isoformat(), row[0]))
                    conn.commit()
                    
                    return {
                        'id': row[0],
                        'username': row[1],
                        'email': row[2],
                        'created_at': row[3]
                    }
                return None
        except Exception as e:
            print(f"Error authenticating user: {e}")
            return None
    
    def get_user_by_id(self, user_id: int) -> Optional[Dict]:
        """Get user by ID"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    SELECT id, username, email, created_at, last_login FROM users 
                    WHERE id = ?
                """, (user_id,))
                
                row = cursor.fetchone()
                if row:
                    return {
                        'id': row[0],
                        'username': row[1],
                        'email': row[2],
                        'created_at': row[3],
                        'last_login': row[4]
                    }
                return None
        except Exception as e:
            print(f"Error getting user: {e}")
            return None
    
    def get_user_sessions(self, user_id: int, limit: int = 3) -> List[Dict]:
        """Get sessions for a specific user"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute("""
                    SELECT * FROM sessions 
                    WHERE user_id = ?
                    ORDER BY created_at DESC 
                    LIMIT ?
                """, (user_id, limit))
                
                sessions = []
                for row in cursor.fetchall():
                    sessions.append({
                        'id': row[0],
                        'session_id': row[1],
                        'user_id': row[2],
                        'user_data': json.loads(row[3]),
                        'budget_analysis': json.loads(row[4]) if row[4] else None,
                        'app_recommendations': json.loads(row[5]) if row[5] else None,
                        'created_at': row[6],
                        'updated_at': row[7]
                    })
                
                return sessions
        except Exception as e:
            print(f"Error getting user sessions: {e}")
            return []
    
    def create_session_with_user(self, session_id: str, user_id: int, user_data: Dict) -> bool:
        """Create a new session with user association"""
        try:
            with self.get_connection() as conn:
                cursor = conn.cursor()
                
                # Clean up old sessions for this user (keep only 3 most recent)
                self._cleanup_old_user_sessions(conn, user_id)
                
                # Insert new session
                cursor.execute("""
                    INSERT INTO sessions (session_id, user_id, user_data, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                """, (
                    session_id,
                    user_id,
                    json.dumps(user_data),
                    datetime.now().isoformat(),
                    datetime.now().isoformat()
                ))
                
                conn.commit()
                return True
        except sqlite3.IntegrityError:
            # Session ID already exists
            return False
        except Exception as e:
            print(f"Error creating session with user: {e}")
            return False
    
    def _cleanup_old_user_sessions(self, conn, user_id: int):
        """Keep only the 3 most recent sessions for a user"""
        cursor = conn.cursor()
        
        # Get count of sessions for this user
        cursor.execute("SELECT COUNT(*) FROM sessions WHERE user_id = ?", (user_id,))
        count = cursor.fetchone()[0]
        
        # If more than 3 sessions, delete the oldest ones
        if count >= 3:
            cursor.execute("""
                DELETE FROM sessions 
                WHERE user_id = ? AND id NOT IN (
                    SELECT id FROM sessions 
                    WHERE user_id = ?
                    ORDER BY created_at DESC 
                    LIMIT 3
                )
            """, (user_id, user_id))

# Global database instance
db = DatabaseManager()