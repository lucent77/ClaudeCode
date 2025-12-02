const express = require('express');
const router = express.Router();
const sanitizeHtml = require('sanitize-html');
const {
  userOps,
  sessionOps,
  createUser,
  verifyPassword,
  createSession
} = require('../db/database');
const { authMiddleware } = require('../middleware/auth');

// Sanitize input
const sanitize = (str) => {
  if (!str) return '';
  return sanitizeHtml(str.trim(), {
    allowedTags: [],
    allowedAttributes: {}
  });
};

// Validate email format
const isValidEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
};

// Validate username (alphanumeric, underscores, 3-20 chars)
const isValidUsername = (username) => {
  const re = /^[a-zA-Z0-9_]{3,20}$/;
  return re.test(username);
};

// Register new user
router.post('/register', async (req, res) => {
  try {
    const { username, email, password, displayName } = req.body;

    // Validate inputs
    const cleanUsername = sanitize(username).toLowerCase();
    const cleanEmail = sanitize(email).toLowerCase();
    const cleanDisplayName = sanitize(displayName);

    if (!cleanUsername || !cleanEmail || !password) {
      return res.status(400).json({ error: 'Username, email, and password are required' });
    }

    if (!isValidUsername(cleanUsername)) {
      return res.status(400).json({
        error: 'Username must be 3-20 characters, alphanumeric and underscores only'
      });
    }

    if (!isValidEmail(cleanEmail)) {
      return res.status(400).json({ error: 'Invalid email format' });
    }

    if (password.length < 6) {
      return res.status(400).json({ error: 'Password must be at least 6 characters' });
    }

    // Check if user exists
    const existingUsername = userOps.findByUsername.get(cleanUsername);
    if (existingUsername) {
      return res.status(409).json({ error: 'Username already taken' });
    }

    const existingEmail = userOps.findByEmail.get(cleanEmail);
    if (existingEmail) {
      return res.status(409).json({ error: 'Email already registered' });
    }

    // Create user
    const user = await createUser(cleanUsername, cleanEmail, password, cleanDisplayName || cleanUsername);

    // Create session
    const session = createSession(user.id);

    res.status(201).json({
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        displayName: user.displayName
      },
      token: session.token,
      expiresAt: session.expiresAt
    });
  } catch (err) {
    console.error('Registration error:', err);
    res.status(500).json({ error: 'Failed to register user' });
  }
});

// Login
router.post('/login', async (req, res) => {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password are required' });
    }

    const cleanUsername = sanitize(username).toLowerCase();

    // Find user by username or email
    let user = userOps.findByUsername.get(cleanUsername);
    if (!user) {
      user = userOps.findByEmail.get(cleanUsername);
    }

    if (!user) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Verify password
    const isValid = await verifyPassword(password, user.password_hash);
    if (!isValid) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Create session
    const session = createSession(user.id);

    // Update user status to online
    userOps.updateStatus.run('online', user.id);

    res.json({
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        displayName: user.display_name,
        avatarUrl: user.avatar_url,
        status: 'online'
      },
      token: session.token,
      expiresAt: session.expiresAt
    });
  } catch (err) {
    console.error('Login error:', err);
    res.status(500).json({ error: 'Failed to login' });
  }
});

// Logout
router.post('/logout', authMiddleware, (req, res) => {
  try {
    const token = req.headers.authorization?.replace('Bearer ', '');

    if (token) {
      sessionOps.delete.run(token);
    }

    // Update user status to offline
    userOps.updateStatus.run('offline', req.user.id);

    res.json({ message: 'Logged out successfully' });
  } catch (err) {
    console.error('Logout error:', err);
    res.status(500).json({ error: 'Failed to logout' });
  }
});

// Get current user
router.get('/me', authMiddleware, (req, res) => {
  try {
    const user = userOps.findById.get(req.user.id);

    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({
      id: user.id,
      username: user.username,
      email: user.email,
      displayName: user.display_name,
      avatarUrl: user.avatar_url,
      status: user.status,
      statusMessage: user.status_message,
      createdAt: user.created_at
    });
  } catch (err) {
    console.error('Get user error:', err);
    res.status(500).json({ error: 'Failed to get user' });
  }
});

// Update profile
router.patch('/me', authMiddleware, (req, res) => {
  try {
    const { displayName, statusMessage } = req.body;

    const cleanDisplayName = sanitize(displayName);
    const cleanStatusMessage = sanitize(statusMessage);

    userOps.updateProfile.run(cleanDisplayName, cleanStatusMessage, req.user.id);

    const user = userOps.findById.get(req.user.id);

    res.json({
      id: user.id,
      username: user.username,
      displayName: user.display_name,
      statusMessage: user.status_message
    });
  } catch (err) {
    console.error('Update profile error:', err);
    res.status(500).json({ error: 'Failed to update profile' });
  }
});

// Validate token
router.get('/validate', authMiddleware, (req, res) => {
  res.json({ valid: true, user: req.user });
});

module.exports = router;
