import sqlite3
import json
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
            
            # Create sessions table
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id TEXT UNIQUE NOT NULL,
                    user_data TEXT NOT NULL,
                    budget_analysis TEXT,
                    app_recommendations TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            """)
            
            # Create index for faster queries
            cursor.execute("""
                CREATE INDEX IF NOT EXISTS idx_sessions_created_at 
                ON sessions(created_at DESC)
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
                return True
        except sqlite3.IntegrityError:
            # Session ID already exists
            return False
        except Exception as e:
            print(f"Error creating session: {e}")
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
                
                if not set_clauses:
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
                conn.commit()
                
                return cursor.rowcount > 0
        except Exception as e:
            print(f"Error updating session: {e}")
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
        if count >= 3:
            cursor.execute("""
                DELETE FROM sessions 
                WHERE id NOT IN (
                    SELECT id FROM sessions 
                    ORDER BY created_at DESC 
                    LIMIT 3
                )
            """)
    
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

# Global database instance
db = DatabaseManager()