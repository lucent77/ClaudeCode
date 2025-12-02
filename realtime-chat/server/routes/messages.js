const express = require('express');
const router = express.Router();
const { v4: uuidv4 } = require('uuid');
const sanitizeHtml = require('sanitize-html');
const { messageOps, reactionOps } = require('../db/database');
const { authMiddleware } = require('../middleware/auth');

// Sanitize message content - allow some formatting
const sanitizeMessage = (content) => {
  if (!content) return '';
  return sanitizeHtml(content.trim(), {
    allowedTags: ['b', 'i', 'code', 'pre', 'a', 'br'],
    allowedAttributes: {
      'a': ['href', 'target']
    },
    transformTags: {
      'a': (tagName, attribs) => ({
        tagName: 'a',
        attribs: {
          ...attribs,
          target: '_blank',
          rel: 'noopener noreferrer'
        }
      })
    }
  });
};

// Search messages
router.get('/search', authMiddleware, (req, res) => {
  try {
    const { q } = req.query;

    if (!q || q.length < 2) {
      return res.status(400).json({ error: 'Search query must be at least 2 characters' });
    }

    const results = messageOps.search.all(`%${q}%`);

    res.json({
      results: results.map(msg => ({
        id: msg.id,
        content: msg.content,
        channelName: msg.channel_name,
        createdAt: msg.created_at,
        user: {
          id: msg.user_id,
          username: msg.username,
          displayName: msg.display_name
        }
      }))
    });
  } catch (err) {
    console.error('Search error:', err);
    res.status(500).json({ error: 'Failed to search messages' });
  }
});

// Get message by ID
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const message = messageOps.getById.get(req.params.id);

    if (!message) {
      return res.status(404).json({ error: 'Message not found' });
    }

    const reactions = reactionOps.getByMessage.all(message.id);
    const replyCount = messageOps.getReplyCount.get(message.id).count;

    res.json({
      id: message.id,
      content: message.content,
      channelId: message.channel_id,
      type: message.type,
      parentId: message.parent_id,
      isEdited: Boolean(message.is_edited),
      createdAt: message.created_at,
      updatedAt: message.updated_at,
      user: {
        id: message.user_id,
        username: message.username,
        displayName: message.display_name,
        avatarUrl: message.avatar_url
      },
      reactions: reactions.map(r => ({
        emoji: r.emoji,
        count: r.count,
        users: r.users ? r.users.split(',') : []
      })),
      replyCount
    });
  } catch (err) {
    console.error('Get message error:', err);
    res.status(500).json({ error: 'Failed to get message' });
  }
});

// Update message
router.patch('/:id', authMiddleware, (req, res) => {
  try {
    const message = messageOps.getById.get(req.params.id);

    if (!message) {
      return res.status(404).json({ error: 'Message not found' });
    }

    if (message.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Not authorized to edit this message' });
    }

    const { content } = req.body;
    const cleanContent = sanitizeMessage(content);

    if (!cleanContent) {
      return res.status(400).json({ error: 'Message content is required' });
    }

    const result = messageOps.update.run(cleanContent, req.params.id, req.user.id);

    if (result.changes === 0) {
      return res.status(400).json({ error: 'Failed to update message' });
    }

    const updated = messageOps.getById.get(req.params.id);

    res.json({
      id: updated.id,
      content: updated.content,
      isEdited: true,
      updatedAt: updated.updated_at
    });
  } catch (err) {
    console.error('Update message error:', err);
    res.status(500).json({ error: 'Failed to update message' });
  }
});

// Delete message
router.delete('/:id', authMiddleware, (req, res) => {
  try {
    const message = messageOps.getById.get(req.params.id);

    if (!message) {
      return res.status(404).json({ error: 'Message not found' });
    }

    if (message.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Not authorized to delete this message' });
    }

    const result = messageOps.delete.run(req.params.id, req.user.id);

    if (result.changes === 0) {
      return res.status(400).json({ error: 'Failed to delete message' });
    }

    res.json({ message: 'Message deleted successfully' });
  } catch (err) {
    console.error('Delete message error:', err);
    res.status(500).json({ error: 'Failed to delete message' });
  }
});

// Add reaction
router.post('/:id/reactions', authMiddleware, (req, res) => {
  try {
    const message = messageOps.getById.get(req.params.id);

    if (!message) {
      return res.status(404).json({ error: 'Message not found' });
    }

    const { emoji } = req.body;

    if (!emoji) {
      return res.status(400).json({ error: 'Emoji is required' });
    }

    // Validate emoji (basic check)
    const emojiRegex = /^[\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]|[\u{1F600}-\u{1F64F}]|[\u{1F680}-\u{1F6FF}]$/u;
    if (!emojiRegex.test(emoji) && emoji.length > 2) {
      return res.status(400).json({ error: 'Invalid emoji' });
    }

    const id = uuidv4();
    reactionOps.add.run(id, req.params.id, req.user.id, emoji);

    const reactions = reactionOps.getByMessage.all(req.params.id);

    res.json({
      reactions: reactions.map(r => ({
        emoji: r.emoji,
        count: r.count,
        users: r.users ? r.users.split(',') : []
      }))
    });
  } catch (err) {
    console.error('Add reaction error:', err);
    res.status(500).json({ error: 'Failed to add reaction' });
  }
});

// Remove reaction
router.delete('/:id/reactions/:emoji', authMiddleware, (req, res) => {
  try {
    const message = messageOps.getById.get(req.params.id);

    if (!message) {
      return res.status(404).json({ error: 'Message not found' });
    }

    reactionOps.remove.run(req.params.id, req.user.id, decodeURIComponent(req.params.emoji));

    const reactions = reactionOps.getByMessage.all(req.params.id);

    res.json({
      reactions: reactions.map(r => ({
        emoji: r.emoji,
        count: r.count,
        users: r.users ? r.users.split(',') : []
      }))
    });
  } catch (err) {
    console.error('Remove reaction error:', err);
    res.status(500).json({ error: 'Failed to remove reaction' });
  }
});

module.exports = router;
