const express = require('express');
const router = express.Router();
const { v4: uuidv4 } = require('uuid');
const sanitizeHtml = require('sanitize-html');
const { channelOps, messageOps } = require('../db/database');
const { authMiddleware } = require('../middleware/auth');

// Sanitize input
const sanitize = (str) => {
  if (!str) return '';
  return sanitizeHtml(str.trim(), {
    allowedTags: [],
    allowedAttributes: {}
  });
};

// Validate channel name (lowercase, alphanumeric, hyphens, underscores, 2-50 chars)
const isValidChannelName = (name) => {
  const re = /^[a-z0-9_-]{2,50}$/;
  return re.test(name);
};

// Get all channels
router.get('/', authMiddleware, (req, res) => {
  try {
    const channels = channelOps.getAll.all();
    res.json(channels.map(ch => ({
      id: ch.id,
      name: ch.name,
      description: ch.description,
      isPrivate: Boolean(ch.is_private),
      messageCount: ch.message_count,
      createdAt: ch.created_at
    })));
  } catch (err) {
    console.error('Get channels error:', err);
    res.status(500).json({ error: 'Failed to get channels' });
  }
});

// Get channel by ID
router.get('/:id', authMiddleware, (req, res) => {
  try {
    const channel = channelOps.getById.get(req.params.id);

    if (!channel) {
      return res.status(404).json({ error: 'Channel not found' });
    }

    const members = channelOps.getMembers.all(channel.id);

    res.json({
      id: channel.id,
      name: channel.name,
      description: channel.description,
      isPrivate: Boolean(channel.is_private),
      createdBy: channel.created_by,
      createdAt: channel.created_at,
      members
    });
  } catch (err) {
    console.error('Get channel error:', err);
    res.status(500).json({ error: 'Failed to get channel' });
  }
});

// Create new channel
router.post('/', authMiddleware, (req, res) => {
  try {
    const { name, description, isPrivate } = req.body;

    const cleanName = sanitize(name).toLowerCase();
    const cleanDescription = sanitize(description);

    if (!cleanName) {
      return res.status(400).json({ error: 'Channel name is required' });
    }

    if (!isValidChannelName(cleanName)) {
      return res.status(400).json({
        error: 'Channel name must be 2-50 characters, lowercase alphanumeric, hyphens and underscores only'
      });
    }

    // Check if channel exists
    const existing = channelOps.getByName.get(cleanName);
    if (existing) {
      return res.status(409).json({ error: 'Channel name already exists' });
    }

    const id = uuidv4();
    channelOps.create.run(id, cleanName, cleanDescription, isPrivate ? 1 : 0, req.user.id);

    // Add creator as member
    channelOps.addMember.run(id, req.user.id);

    const channel = channelOps.getById.get(id);

    res.status(201).json({
      id: channel.id,
      name: channel.name,
      description: channel.description,
      isPrivate: Boolean(channel.is_private),
      createdBy: channel.created_by,
      createdAt: channel.created_at
    });
  } catch (err) {
    console.error('Create channel error:', err);
    res.status(500).json({ error: 'Failed to create channel' });
  }
});

// Delete channel
router.delete('/:id', authMiddleware, (req, res) => {
  try {
    const channel = channelOps.getById.get(req.params.id);

    if (!channel) {
      return res.status(404).json({ error: 'Channel not found' });
    }

    // Only creator can delete (or we could add admin logic)
    if (channel.created_by !== req.user.id) {
      return res.status(403).json({ error: 'Not authorized to delete this channel' });
    }

    // Prevent deleting default channels
    const defaultChannels = ['general', 'random', 'announcements'];
    if (defaultChannels.includes(channel.name)) {
      return res.status(403).json({ error: 'Cannot delete default channels' });
    }

    channelOps.delete.run(req.params.id);

    res.json({ message: 'Channel deleted successfully' });
  } catch (err) {
    console.error('Delete channel error:', err);
    res.status(500).json({ error: 'Failed to delete channel' });
  }
});

// Join channel
router.post('/:id/join', authMiddleware, (req, res) => {
  try {
    const channel = channelOps.getById.get(req.params.id);

    if (!channel) {
      return res.status(404).json({ error: 'Channel not found' });
    }

    channelOps.addMember.run(channel.id, req.user.id);

    res.json({ message: 'Joined channel successfully' });
  } catch (err) {
    console.error('Join channel error:', err);
    res.status(500).json({ error: 'Failed to join channel' });
  }
});

// Leave channel
router.post('/:id/leave', authMiddleware, (req, res) => {
  try {
    const channel = channelOps.getById.get(req.params.id);

    if (!channel) {
      return res.status(404).json({ error: 'Channel not found' });
    }

    channelOps.removeMember.run(channel.id, req.user.id);

    res.json({ message: 'Left channel successfully' });
  } catch (err) {
    console.error('Leave channel error:', err);
    res.status(500).json({ error: 'Failed to leave channel' });
  }
});

// Get channel messages
router.get('/:id/messages', authMiddleware, (req, res) => {
  try {
    const channel = channelOps.getById.get(req.params.id);

    if (!channel) {
      return res.status(404).json({ error: 'Channel not found' });
    }

    const limit = Math.min(parseInt(req.query.limit) || 50, 100);
    const offset = parseInt(req.query.offset) || 0;

    const messages = messageOps.getByChannel.all(channel.id, limit, offset);

    // Get reactions for each message
    const messagesWithReactions = messages.map(msg => {
      const reactions = require('../db/database').reactionOps.getByMessage.all(msg.id);
      const replyCount = messageOps.getReplyCount.get(msg.id).count;
      return {
        id: msg.id,
        content: msg.content,
        type: msg.type,
        parentId: msg.parent_id,
        isEdited: Boolean(msg.is_edited),
        createdAt: msg.created_at,
        updatedAt: msg.updated_at,
        user: {
          id: msg.user_id,
          username: msg.username,
          displayName: msg.display_name,
          avatarUrl: msg.avatar_url
        },
        reactions: reactions.map(r => ({
          emoji: r.emoji,
          count: r.count,
          users: r.users ? r.users.split(',') : []
        })),
        replyCount
      };
    });

    res.json({
      messages: messagesWithReactions.reverse(), // Oldest first
      hasMore: messages.length === limit
    });
  } catch (err) {
    console.error('Get messages error:', err);
    res.status(500).json({ error: 'Failed to get messages' });
  }
});

// Get message thread
router.get('/:channelId/messages/:messageId/thread', authMiddleware, (req, res) => {
  try {
    const parentMessage = messageOps.getById.get(req.params.messageId);

    if (!parentMessage) {
      return res.status(404).json({ error: 'Message not found' });
    }

    const replies = messageOps.getThread.all(req.params.messageId);

    const allMessages = [parentMessage, ...replies].map(msg => ({
      id: msg.id,
      content: msg.content,
      type: msg.type,
      parentId: msg.parent_id,
      isEdited: Boolean(msg.is_edited),
      createdAt: msg.created_at,
      user: {
        id: msg.user_id,
        username: msg.username,
        displayName: msg.display_name,
        avatarUrl: msg.avatar_url
      }
    }));

    res.json({ messages: allMessages });
  } catch (err) {
    console.error('Get thread error:', err);
    res.status(500).json({ error: 'Failed to get thread' });
  }
});

module.exports = router;
