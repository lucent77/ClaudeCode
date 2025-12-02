const express = require('express');
const router = express.Router();
const { v4: uuidv4 } = require('uuid');
const sanitizeHtml = require('sanitize-html');
const { userOps, dmOps } = require('../db/database');
const { authMiddleware } = require('../middleware/auth');

// Sanitize input
const sanitize = (str) => {
  if (!str) return '';
  return sanitizeHtml(str.trim(), {
    allowedTags: [],
    allowedAttributes: {}
  });
};

// Get all users
router.get('/', authMiddleware, (req, res) => {
  try {
    const users = userOps.getAll.all();
    res.json(users.map(user => ({
      id: user.id,
      username: user.username,
      displayName: user.display_name,
      avatarUrl: user.avatar_url,
      status: user.status,
      statusMessage: user.status_message
    })));
  } catch (err) {
    console.error('Get users error:', err);
    res.status(500).json({ error: 'Failed to get users' });
  }
});

// Get online users
router.get('/online', authMiddleware, (req, res) => {
  try {
    const users = userOps.getAllOnline.all();
    res.json(users.map(user => ({
      id: user.id,
      username: user.username,
      displayName: user.display_name,
      avatarUrl: user.avatar_url,
      status: user.status,
      statusMessage: user.status_message
    })));
  } catch (err) {
    console.error('Get online users error:', err);
    res.status(500).json({ error: 'Failed to get online users' });
  }
});

// Get user by ID
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const user = userOps.findById.get(req.params.id);

    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({
      id: user.id,
      username: user.username,
      displayName: user.display_name,
      avatarUrl: user.avatar_url,
      status: user.status,
      statusMessage: user.status_message,
      createdAt: user.created_at,
      lastSeen: user.last_seen
    });
  } catch (err) {
    console.error('Get user error:', err);
    res.status(500).json({ error: 'Failed to get user' });
  }
});

// Get direct messages with a user
router.get('/:id/messages', authMiddleware, (req, res) => {
  try {
    const otherUser = userOps.findById.get(req.params.id);

    if (!otherUser) {
      return res.status(404).json({ error: 'User not found' });
    }

    const limit = Math.min(parseInt(req.query.limit) || 50, 100);
    const offset = parseInt(req.query.offset) || 0;

    const messages = dmOps.getConversation.all(
      req.user.id,
      req.params.id,
      req.params.id,
      req.user.id,
      limit,
      offset
    );

    // Mark messages as read
    dmOps.markAsRead.run(req.user.id, req.params.id);

    res.json({
      messages: messages.map(msg => ({
        id: msg.id,
        content: msg.content,
        type: msg.type,
        isRead: Boolean(msg.is_read),
        createdAt: msg.created_at,
        sender: {
          id: msg.sender_id,
          username: msg.sender_username,
          displayName: msg.sender_display_name,
          avatarUrl: msg.sender_avatar
        },
        receiver: {
          id: msg.receiver_id,
          username: msg.receiver_username,
          displayName: msg.receiver_display_name
        }
      })).reverse(),
      hasMore: messages.length === limit
    });
  } catch (err) {
    console.error('Get DMs error:', err);
    res.status(500).json({ error: 'Failed to get messages' });
  }
});

// Get unread message counts
router.get('/me/unread', authMiddleware, (req, res) => {
  try {
    const unread = dmOps.getUnreadCount.all(req.user.id);
    res.json({
      unread: unread.map(u => ({
        userId: u.sender_id,
        count: u.count
      }))
    });
  } catch (err) {
    console.error('Get unread error:', err);
    res.status(500).json({ error: 'Failed to get unread count' });
  }
});

// Get recent conversations
router.get('/me/conversations', authMiddleware, (req, res) => {
  try {
    const conversations = dmOps.getRecentConversations.all(
      req.user.id,
      req.user.id,
      req.user.id
    );

    // Get user details for each conversation
    const result = conversations.map(conv => {
      const user = userOps.findById.get(conv.other_user_id);
      return {
        user: {
          id: user.id,
          username: user.username,
          displayName: user.display_name,
          avatarUrl: user.avatar_url,
          status: user.status
        },
        lastMessageAt: conv.last_message_at
      };
    });

    res.json({ conversations: result });
  } catch (err) {
    console.error('Get conversations error:', err);
    res.status(500).json({ error: 'Failed to get conversations' });
  }
});

module.exports = router;
