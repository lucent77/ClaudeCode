const Database = require('better-sqlite3');
const path = require('path');
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');

// Database file path
const dbPath = path.join(__dirname, '../../data/chat.db');

// Ensure data directory exists
const fs = require('fs');
const dataDir = path.dirname(dbPath);
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

// Initialize database with WAL mode for better concurrent performance
const db = new Database(dbPath);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');
db.pragma('busy_timeout = 5000');

// Create tables
const initDatabase = () => {
  // Users table
  db.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id TEXT PRIMARY KEY,
      username TEXT UNIQUE NOT NULL,
      email TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      display_name TEXT,
      avatar_url TEXT,
      status TEXT DEFAULT 'offline',
      status_message TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      last_seen DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  // Channels table
  db.exec(`
    CREATE TABLE IF NOT EXISTS channels (
      id TEXT PRIMARY KEY,
      name TEXT UNIQUE NOT NULL,
      description TEXT,
      is_private INTEGER DEFAULT 0,
      created_by TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (created_by) REFERENCES users(id)
    )
  `);

  // Channel members table
  db.exec(`
    CREATE TABLE IF NOT EXISTS channel_members (
      channel_id TEXT,
      user_id TEXT,
      joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      last_read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (channel_id, user_id),
      FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
  `);

  // Messages table
  db.exec(`
    CREATE TABLE IF NOT EXISTS messages (
      id TEXT PRIMARY KEY,
      channel_id TEXT,
      user_id TEXT NOT NULL,
      content TEXT NOT NULL,
      type TEXT DEFAULT 'text',
      parent_id TEXT,
      is_edited INTEGER DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE SET NULL
    )
  `);

  // Direct messages table
  db.exec(`
    CREATE TABLE IF NOT EXISTS direct_messages (
      id TEXT PRIMARY KEY,
      sender_id TEXT NOT NULL,
      receiver_id TEXT NOT NULL,
      content TEXT NOT NULL,
      type TEXT DEFAULT 'text',
      is_read INTEGER DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )
  `);

  // Reactions table
  db.exec(`
    CREATE TABLE IF NOT EXISTS reactions (
      id TEXT PRIMARY KEY,
      message_id TEXT NOT NULL,
      user_id TEXT NOT NULL,
      emoji TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE(message_id, user_id, emoji),
      FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
  `);

  // Sessions table for authentication
  db.exec(`
    CREATE TABLE IF NOT EXISTS sessions (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      token TEXT UNIQUE NOT NULL,
      expires_at DATETIME NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
  `);

  // Create indexes for better query performance
  db.exec(`
    CREATE INDEX IF NOT EXISTS idx_messages_channel ON messages(channel_id);
    CREATE INDEX IF NOT EXISTS idx_messages_created ON messages(created_at);
    CREATE INDEX IF NOT EXISTS idx_messages_parent ON messages(parent_id);
    CREATE INDEX IF NOT EXISTS idx_dm_sender ON direct_messages(sender_id);
    CREATE INDEX IF NOT EXISTS idx_dm_receiver ON direct_messages(receiver_id);
    CREATE INDEX IF NOT EXISTS idx_dm_created ON direct_messages(created_at);
    CREATE INDEX IF NOT EXISTS idx_reactions_message ON reactions(message_id);
    CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token);
    CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at);
  `);

  // Create default channels if they don't exist
  const defaultChannels = ['general', 'random', 'announcements'];
  const insertChannel = db.prepare(`
    INSERT OR IGNORE INTO channels (id, name, description, created_by)
    VALUES (?, ?, ?, NULL)
  `);

  defaultChannels.forEach(name => {
    const id = uuidv4();
    const description = name === 'general'
      ? 'General discussion for the team'
      : name === 'random'
      ? 'Random stuff and fun'
      : 'Important announcements';
    insertChannel.run(id, name, description);
  });
};

// User operations
const userOps = {
  create: db.prepare(`
    INSERT INTO users (id, username, email, password_hash, display_name)
    VALUES (?, ?, ?, ?, ?)
  `),

  findByUsername: db.prepare(`
    SELECT * FROM users WHERE username = ?
  `),

  findByEmail: db.prepare(`
    SELECT * FROM users WHERE email = ?
  `),

  findById: db.prepare(`
    SELECT id, username, email, display_name, avatar_url, status, status_message, created_at, last_seen
    FROM users WHERE id = ?
  `),

  updateStatus: db.prepare(`
    UPDATE users SET status = ?, last_seen = CURRENT_TIMESTAMP WHERE id = ?
  `),

  updateProfile: db.prepare(`
    UPDATE users SET display_name = ?, status_message = ? WHERE id = ?
  `),

  getAllOnline: db.prepare(`
    SELECT id, username, display_name, avatar_url, status, status_message
    FROM users WHERE status != 'offline'
  `),

  getAll: db.prepare(`
    SELECT id, username, display_name, avatar_url, status, status_message
    FROM users ORDER BY username
  `)
};

// Channel operations
const channelOps = {
  getAll: db.prepare(`
    SELECT c.*, COUNT(m.id) as message_count
    FROM channels c
    LEFT JOIN messages m ON c.id = m.channel_id
    GROUP BY c.id
    ORDER BY c.name
  `),

  getById: db.prepare(`
    SELECT * FROM channels WHERE id = ?
  `),

  getByName: db.prepare(`
    SELECT * FROM channels WHERE name = ?
  `),

  create: db.prepare(`
    INSERT INTO channels (id, name, description, is_private, created_by)
    VALUES (?, ?, ?, ?, ?)
  `),

  delete: db.prepare(`
    DELETE FROM channels WHERE id = ?
  `),

  addMember: db.prepare(`
    INSERT OR IGNORE INTO channel_members (channel_id, user_id) VALUES (?, ?)
  `),

  removeMember: db.prepare(`
    DELETE FROM channel_members WHERE channel_id = ? AND user_id = ?
  `),

  getMembers: db.prepare(`
    SELECT u.id, u.username, u.display_name, u.avatar_url, u.status
    FROM channel_members cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.channel_id = ?
  `)
};

// Message operations
const messageOps = {
  create: db.prepare(`
    INSERT INTO messages (id, channel_id, user_id, content, type, parent_id)
    VALUES (?, ?, ?, ?, ?, ?)
  `),

  getByChannel: db.prepare(`
    SELECT m.*, u.username, u.display_name, u.avatar_url
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.channel_id = ?
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
  `),

  getById: db.prepare(`
    SELECT m.*, u.username, u.display_name, u.avatar_url
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = ?
  `),

  update: db.prepare(`
    UPDATE messages SET content = ?, is_edited = 1, updated_at = CURRENT_TIMESTAMP
    WHERE id = ? AND user_id = ?
  `),

  delete: db.prepare(`
    DELETE FROM messages WHERE id = ? AND user_id = ?
  `),

  getThread: db.prepare(`
    SELECT m.*, u.username, u.display_name, u.avatar_url
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.parent_id = ?
    ORDER BY m.created_at ASC
  `),

  getReplyCount: db.prepare(`
    SELECT COUNT(*) as count FROM messages WHERE parent_id = ?
  `),

  search: db.prepare(`
    SELECT m.*, u.username, u.display_name, c.name as channel_name
    FROM messages m
    JOIN users u ON m.user_id = u.id
    JOIN channels c ON m.channel_id = c.id
    WHERE m.content LIKE ?
    ORDER BY m.created_at DESC
    LIMIT 50
  `)
};

// Direct message operations
const dmOps = {
  create: db.prepare(`
    INSERT INTO direct_messages (id, sender_id, receiver_id, content, type)
    VALUES (?, ?, ?, ?, ?)
  `),

  getConversation: db.prepare(`
    SELECT dm.*,
           s.username as sender_username, s.display_name as sender_display_name, s.avatar_url as sender_avatar,
           r.username as receiver_username, r.display_name as receiver_display_name
    FROM direct_messages dm
    JOIN users s ON dm.sender_id = s.id
    JOIN users r ON dm.receiver_id = r.id
    WHERE (dm.sender_id = ? AND dm.receiver_id = ?)
       OR (dm.sender_id = ? AND dm.receiver_id = ?)
    ORDER BY dm.created_at DESC
    LIMIT ? OFFSET ?
  `),

  markAsRead: db.prepare(`
    UPDATE direct_messages SET is_read = 1
    WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
  `),

  getUnreadCount: db.prepare(`
    SELECT sender_id, COUNT(*) as count
    FROM direct_messages
    WHERE receiver_id = ? AND is_read = 0
    GROUP BY sender_id
  `),

  getRecentConversations: db.prepare(`
    SELECT DISTINCT
      CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id,
      MAX(created_at) as last_message_at
    FROM direct_messages
    WHERE sender_id = ? OR receiver_id = ?
    GROUP BY other_user_id
    ORDER BY last_message_at DESC
  `)
};

// Reaction operations
const reactionOps = {
  add: db.prepare(`
    INSERT OR IGNORE INTO reactions (id, message_id, user_id, emoji)
    VALUES (?, ?, ?, ?)
  `),

  remove: db.prepare(`
    DELETE FROM reactions WHERE message_id = ? AND user_id = ? AND emoji = ?
  `),

  getByMessage: db.prepare(`
    SELECT r.emoji, COUNT(*) as count, GROUP_CONCAT(u.username) as users
    FROM reactions r
    JOIN users u ON r.user_id = u.id
    WHERE r.message_id = ?
    GROUP BY r.emoji
  `)
};

// Session operations
const sessionOps = {
  create: db.prepare(`
    INSERT INTO sessions (id, user_id, token, expires_at)
    VALUES (?, ?, ?, ?)
  `),

  findByToken: db.prepare(`
    SELECT s.*, u.username, u.display_name, u.avatar_url, u.status
    FROM sessions s
    JOIN users u ON s.user_id = u.id
    WHERE s.token = ? AND s.expires_at > datetime('now')
  `),

  delete: db.prepare(`
    DELETE FROM sessions WHERE token = ?
  `),

  deleteExpired: db.prepare(`
    DELETE FROM sessions WHERE expires_at <= datetime('now')
  `),

  deleteByUser: db.prepare(`
    DELETE FROM sessions WHERE user_id = ?
  `)
};

// Helper functions
const createUser = async (username, email, password, displayName = null) => {
  const id = uuidv4();
  const passwordHash = await bcrypt.hash(password, 12);
  try {
    userOps.create.run(id, username, email, passwordHash, displayName || username);
    return { id, username, email, displayName: displayName || username };
  } catch (err) {
    if (err.code === 'SQLITE_CONSTRAINT_UNIQUE') {
      throw new Error('Username or email already exists');
    }
    throw err;
  }
};

const verifyPassword = async (password, hash) => {
  return bcrypt.compare(password, hash);
};

const createSession = (userId) => {
  const id = uuidv4();
  const token = uuidv4();
  const expiresAt = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString();
  sessionOps.create.run(id, userId, token, expiresAt);
  return { token, expiresAt };
};

// Cleanup expired sessions periodically
setInterval(() => {
  sessionOps.deleteExpired.run();
}, 60 * 60 * 1000); // Every hour

// Initialize database on module load
initDatabase();

module.exports = {
  db,
  userOps,
  channelOps,
  messageOps,
  dmOps,
  reactionOps,
  sessionOps,
  createUser,
  verifyPassword,
  createSession
};
