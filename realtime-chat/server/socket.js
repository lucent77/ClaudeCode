const { v4: uuidv4 } = require('uuid');
const sanitizeHtml = require('sanitize-html');
const {
  userOps,
  channelOps,
  messageOps,
  dmOps,
  reactionOps
} = require('./db/database');

// Store online users and their socket connections
const onlineUsers = new Map(); // userId -> Set of socketIds
const socketToUser = new Map(); // socketId -> userId
const userTyping = new Map(); // channelId -> Set of userIds

// Sanitize message content
const sanitizeMessage = (content) => {
  if (!content) return '';
  return sanitizeHtml(content.trim(), {
    allowedTags: ['b', 'i', 'code', 'pre', 'a', 'br'],
    allowedAttributes: {
      'a': ['href', 'target']
    }
  }).substring(0, 4000); // Max 4000 characters
};

// Setup socket handlers
const setupSocket = (io) => {
  io.on('connection', (socket) => {
    const user = socket.user;
    console.log(`User connected: ${user.username} (${socket.id})`);

    // Track user connection
    if (!onlineUsers.has(user.id)) {
      onlineUsers.set(user.id, new Set());
    }
    onlineUsers.get(user.id).add(socket.id);
    socketToUser.set(socket.id, user.id);

    // Update user status to online
    userOps.updateStatus.run('online', user.id);

    // Broadcast user online status
    socket.broadcast.emit('user:online', {
      userId: user.id,
      username: user.username,
      displayName: user.displayName
    });

    // Send online users list to the newly connected user
    const onlineUsersList = [];
    for (const [userId] of onlineUsers) {
      const userData = userOps.findById.get(userId);
      if (userData) {
        onlineUsersList.push({
          id: userData.id,
          username: userData.username,
          displayName: userData.display_name,
          avatarUrl: userData.avatar_url,
          status: 'online'
        });
      }
    }
    socket.emit('users:online', onlineUsersList);

    // Join user's channels
    const channels = channelOps.getAll.all();
    channels.forEach(channel => {
      socket.join(`channel:${channel.id}`);
    });

    // ==================== Channel Events ====================

    // Join channel room
    socket.on('channel:join', (channelId) => {
      socket.join(`channel:${channelId}`);
      channelOps.addMember.run(channelId, user.id);

      socket.to(`channel:${channelId}`).emit('channel:user_joined', {
        channelId,
        user: {
          id: user.id,
          username: user.username,
          displayName: user.displayName
        }
      });
    });

    // Leave channel room
    socket.on('channel:leave', (channelId) => {
      socket.leave(`channel:${channelId}`);

      socket.to(`channel:${channelId}`).emit('channel:user_left', {
        channelId,
        userId: user.id
      });
    });

    // Send message to channel
    socket.on('message:send', (data, callback) => {
      try {
        const { channelId, content, parentId } = data;
        const cleanContent = sanitizeMessage(content);

        if (!cleanContent) {
          return callback?.({ error: 'Message content is required' });
        }

        if (!channelId) {
          return callback?.({ error: 'Channel ID is required' });
        }

        const channel = channelOps.getById.get(channelId);
        if (!channel) {
          return callback?.({ error: 'Channel not found' });
        }

        const messageId = uuidv4();
        messageOps.create.run(messageId, channelId, user.id, cleanContent, 'text', parentId || null);

        const message = messageOps.getById.get(messageId);

        const messageData = {
          id: message.id,
          channelId: message.channel_id,
          content: message.content,
          type: message.type,
          parentId: message.parent_id,
          isEdited: false,
          createdAt: message.created_at,
          user: {
            id: user.id,
            username: user.username,
            displayName: user.displayName,
            avatarUrl: user.avatarUrl
          },
          reactions: [],
          replyCount: 0
        };

        // Broadcast to channel (including sender)
        io.to(`channel:${channelId}`).emit('message:new', messageData);

        // Clear typing indicator
        const typingUsers = userTyping.get(channelId);
        if (typingUsers) {
          typingUsers.delete(user.id);
        }

        callback?.({ success: true, message: messageData });
      } catch (err) {
        console.error('Send message error:', err);
        callback?.({ error: 'Failed to send message' });
      }
    });

    // Edit message
    socket.on('message:edit', (data, callback) => {
      try {
        const { messageId, content } = data;
        const cleanContent = sanitizeMessage(content);

        if (!cleanContent) {
          return callback?.({ error: 'Message content is required' });
        }

        const message = messageOps.getById.get(messageId);
        if (!message) {
          return callback?.({ error: 'Message not found' });
        }

        if (message.user_id !== user.id) {
          return callback?.({ error: 'Not authorized to edit this message' });
        }

        messageOps.update.run(cleanContent, messageId, user.id);

        const updatedMessage = messageOps.getById.get(messageId);

        io.to(`channel:${message.channel_id}`).emit('message:edited', {
          id: updatedMessage.id,
          channelId: updatedMessage.channel_id,
          content: updatedMessage.content,
          isEdited: true,
          updatedAt: updatedMessage.updated_at
        });

        callback?.({ success: true });
      } catch (err) {
        console.error('Edit message error:', err);
        callback?.({ error: 'Failed to edit message' });
      }
    });

    // Delete message
    socket.on('message:delete', (data, callback) => {
      try {
        const { messageId } = data;

        const message = messageOps.getById.get(messageId);
        if (!message) {
          return callback?.({ error: 'Message not found' });
        }

        if (message.user_id !== user.id) {
          return callback?.({ error: 'Not authorized to delete this message' });
        }

        messageOps.delete.run(messageId, user.id);

        io.to(`channel:${message.channel_id}`).emit('message:deleted', {
          id: messageId,
          channelId: message.channel_id
        });

        callback?.({ success: true });
      } catch (err) {
        console.error('Delete message error:', err);
        callback?.({ error: 'Failed to delete message' });
      }
    });

    // Add reaction
    socket.on('reaction:add', (data, callback) => {
      try {
        const { messageId, emoji } = data;

        const message = messageOps.getById.get(messageId);
        if (!message) {
          return callback?.({ error: 'Message not found' });
        }

        const reactionId = uuidv4();
        reactionOps.add.run(reactionId, messageId, user.id, emoji);

        const reactions = reactionOps.getByMessage.all(messageId);

        io.to(`channel:${message.channel_id}`).emit('reaction:updated', {
          messageId,
          reactions: reactions.map(r => ({
            emoji: r.emoji,
            count: r.count,
            users: r.users ? r.users.split(',') : []
          }))
        });

        callback?.({ success: true });
      } catch (err) {
        console.error('Add reaction error:', err);
        callback?.({ error: 'Failed to add reaction' });
      }
    });

    // Remove reaction
    socket.on('reaction:remove', (data, callback) => {
      try {
        const { messageId, emoji } = data;

        const message = messageOps.getById.get(messageId);
        if (!message) {
          return callback?.({ error: 'Message not found' });
        }

        reactionOps.remove.run(messageId, user.id, emoji);

        const reactions = reactionOps.getByMessage.all(messageId);

        io.to(`channel:${message.channel_id}`).emit('reaction:updated', {
          messageId,
          reactions: reactions.map(r => ({
            emoji: r.emoji,
            count: r.count,
            users: r.users ? r.users.split(',') : []
          }))
        });

        callback?.({ success: true });
      } catch (err) {
        console.error('Remove reaction error:', err);
        callback?.({ error: 'Failed to remove reaction' });
      }
    });

    // Typing indicator
    socket.on('typing:start', (channelId) => {
      if (!userTyping.has(channelId)) {
        userTyping.set(channelId, new Set());
      }
      userTyping.get(channelId).add(user.id);

      socket.to(`channel:${channelId}`).emit('typing:update', {
        channelId,
        users: Array.from(userTyping.get(channelId)).map(userId => {
          const userData = userOps.findById.get(userId);
          return {
            id: userId,
            username: userData?.username,
            displayName: userData?.display_name
          };
        })
      });
    });

    socket.on('typing:stop', (channelId) => {
      const typingUsers = userTyping.get(channelId);
      if (typingUsers) {
        typingUsers.delete(user.id);

        socket.to(`channel:${channelId}`).emit('typing:update', {
          channelId,
          users: Array.from(typingUsers).map(userId => {
            const userData = userOps.findById.get(userId);
            return {
              id: userId,
              username: userData?.username,
              displayName: userData?.display_name
            };
          })
        });
      }
    });

    // ==================== Direct Message Events ====================

    // Join DM room
    socket.on('dm:join', (otherUserId) => {
      const roomId = [user.id, otherUserId].sort().join(':');
      socket.join(`dm:${roomId}`);
    });

    // Send direct message
    socket.on('dm:send', (data, callback) => {
      try {
        const { receiverId, content } = data;
        const cleanContent = sanitizeMessage(content);

        if (!cleanContent) {
          return callback?.({ error: 'Message content is required' });
        }

        const receiver = userOps.findById.get(receiverId);
        if (!receiver) {
          return callback?.({ error: 'Recipient not found' });
        }

        const messageId = uuidv4();
        dmOps.create.run(messageId, user.id, receiverId, cleanContent, 'text');

        const roomId = [user.id, receiverId].sort().join(':');

        const messageData = {
          id: messageId,
          content: cleanContent,
          type: 'text',
          isRead: false,
          createdAt: new Date().toISOString(),
          sender: {
            id: user.id,
            username: user.username,
            displayName: user.displayName,
            avatarUrl: user.avatarUrl
          },
          receiver: {
            id: receiverId,
            username: receiver.username,
            displayName: receiver.display_name
          }
        };

        // Send to DM room
        io.to(`dm:${roomId}`).emit('dm:new', messageData);

        // Also notify receiver directly if they're online
        const receiverSockets = onlineUsers.get(receiverId);
        if (receiverSockets) {
          for (const socketId of receiverSockets) {
            io.to(socketId).emit('dm:notification', {
              from: {
                id: user.id,
                username: user.username,
                displayName: user.displayName
              },
              preview: cleanContent.substring(0, 100)
            });
          }
        }

        callback?.({ success: true, message: messageData });
      } catch (err) {
        console.error('Send DM error:', err);
        callback?.({ error: 'Failed to send message' });
      }
    });

    // Mark DMs as read
    socket.on('dm:read', (senderId) => {
      dmOps.markAsRead.run(user.id, senderId);

      // Notify sender that messages were read
      const senderSockets = onlineUsers.get(senderId);
      if (senderSockets) {
        for (const socketId of senderSockets) {
          io.to(socketId).emit('dm:read_receipt', {
            readBy: user.id
          });
        }
      }
    });

    // DM typing indicator
    socket.on('dm:typing:start', (otherUserId) => {
      const otherSockets = onlineUsers.get(otherUserId);
      if (otherSockets) {
        for (const socketId of otherSockets) {
          io.to(socketId).emit('dm:typing', {
            userId: user.id,
            username: user.username,
            isTyping: true
          });
        }
      }
    });

    socket.on('dm:typing:stop', (otherUserId) => {
      const otherSockets = onlineUsers.get(otherUserId);
      if (otherSockets) {
        for (const socketId of otherSockets) {
          io.to(socketId).emit('dm:typing', {
            userId: user.id,
            username: user.username,
            isTyping: false
          });
        }
      }
    });

    // ==================== Channel Management ====================

    socket.on('channel:create', (data, callback) => {
      try {
        const { name, description, isPrivate } = data;
        const cleanName = sanitizeHtml(name?.trim().toLowerCase(), {
          allowedTags: [],
          allowedAttributes: {}
        });
        const cleanDescription = sanitizeHtml(description?.trim() || '', {
          allowedTags: [],
          allowedAttributes: {}
        });

        if (!cleanName || cleanName.length < 2 || cleanName.length > 50) {
          return callback?.({ error: 'Channel name must be 2-50 characters' });
        }

        if (!/^[a-z0-9_-]+$/.test(cleanName)) {
          return callback?.({ error: 'Channel name can only contain lowercase letters, numbers, hyphens and underscores' });
        }

        const existing = channelOps.getByName.get(cleanName);
        if (existing) {
          return callback?.({ error: 'Channel name already exists' });
        }

        const channelId = uuidv4();
        channelOps.create.run(channelId, cleanName, cleanDescription, isPrivate ? 1 : 0, user.id);
        channelOps.addMember.run(channelId, user.id);

        const channel = channelOps.getById.get(channelId);

        const channelData = {
          id: channel.id,
          name: channel.name,
          description: channel.description,
          isPrivate: Boolean(channel.is_private),
          createdBy: channel.created_by,
          createdAt: channel.created_at
        };

        // Broadcast new channel to all users
        io.emit('channel:created', channelData);

        // Join creator to the channel room
        socket.join(`channel:${channelId}`);

        callback?.({ success: true, channel: channelData });
      } catch (err) {
        console.error('Create channel error:', err);
        callback?.({ error: 'Failed to create channel' });
      }
    });

    // ==================== User Status ====================

    socket.on('status:update', (status) => {
      const validStatuses = ['online', 'away', 'dnd', 'offline'];
      if (!validStatuses.includes(status)) return;

      userOps.updateStatus.run(status, user.id);

      io.emit('user:status', {
        userId: user.id,
        status
      });
    });

    // ==================== Disconnect ====================

    socket.on('disconnect', () => {
      console.log(`User disconnected: ${user.username} (${socket.id})`);

      // Remove socket from tracking
      const userSockets = onlineUsers.get(user.id);
      if (userSockets) {
        userSockets.delete(socket.id);

        // If no more sockets, user is offline
        if (userSockets.size === 0) {
          onlineUsers.delete(user.id);
          userOps.updateStatus.run('offline', user.id);

          socket.broadcast.emit('user:offline', {
            userId: user.id
          });
        }
      }

      socketToUser.delete(socket.id);

      // Clear typing indicators for this user
      for (const [channelId, users] of userTyping) {
        if (users.has(user.id)) {
          users.delete(user.id);
          socket.to(`channel:${channelId}`).emit('typing:update', {
            channelId,
            users: Array.from(users).map(userId => {
              const userData = userOps.findById.get(userId);
              return {
                id: userId,
                username: userData?.username,
                displayName: userData?.display_name
              };
            })
          });
        }
      }
    });
  });
};

module.exports = { setupSocket };
