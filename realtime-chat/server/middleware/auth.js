const { sessionOps } = require('../db/database');

// Authentication middleware for HTTP routes
const authMiddleware = (req, res, next) => {
  const token = req.headers.authorization?.replace('Bearer ', '') ||
                req.cookies?.token;

  if (!token) {
    return res.status(401).json({ error: 'Authentication required' });
  }

  const session = sessionOps.findByToken.get(token);

  if (!session) {
    return res.status(401).json({ error: 'Invalid or expired session' });
  }

  req.user = {
    id: session.user_id,
    username: session.username,
    displayName: session.display_name,
    avatarUrl: session.avatar_url,
    status: session.status
  };

  next();
};

// Authentication middleware for Socket.io
const socketAuthMiddleware = (socket, next) => {
  const token = socket.handshake.auth.token ||
                socket.handshake.query.token;

  if (!token) {
    return next(new Error('Authentication required'));
  }

  const session = sessionOps.findByToken.get(token);

  if (!session) {
    return next(new Error('Invalid or expired session'));
  }

  socket.user = {
    id: session.user_id,
    username: session.username,
    displayName: session.display_name,
    avatarUrl: session.avatar_url
  };

  next();
};

// Optional auth - allows access but attaches user if authenticated
const optionalAuth = (req, res, next) => {
  const token = req.headers.authorization?.replace('Bearer ', '') ||
                req.cookies?.token;

  if (token) {
    const session = sessionOps.findByToken.get(token);
    if (session) {
      req.user = {
        id: session.user_id,
        username: session.username,
        displayName: session.display_name,
        avatarUrl: session.avatar_url
      };
    }
  }

  next();
};

module.exports = {
  authMiddleware,
  socketAuthMiddleware,
  optionalAuth
};
