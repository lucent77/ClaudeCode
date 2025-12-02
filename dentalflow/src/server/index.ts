/**
 * Socket.io Server - DentalFlow
 *
 * Real-time communication server for:
 * - Case status updates
 * - Task assignments and updates
 * - Notifications
 * - User presence
 *
 * Runs on port 3001 (separate from Next.js on port 3000)
 * Configured for CyberPanel/OpenLiteSpeed reverse proxy
 */

import { createServer } from 'http'
import { Server, Socket } from 'socket.io'

// Server configuration
const PORT = process.env.PORT || 3001
const CORS_ORIGIN = process.env.CORS_ORIGIN || 'http://localhost:3000'

// Create HTTP server
const httpServer = createServer((req, res) => {
  // Health check endpoint
  if (req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' })
    res.end(JSON.stringify({ status: 'ok', timestamp: new Date().toISOString() }))
    return
  }

  // Default response
  res.writeHead(200, { 'Content-Type': 'text/plain' })
  res.end('DentalFlow Socket.io Server')
})

// Initialize Socket.io with CORS configuration
const io = new Server(httpServer, {
  cors: {
    origin: CORS_ORIGIN.split(','),
    methods: ['GET', 'POST'],
    credentials: true,
  },
  pingTimeout: 60000,
  pingInterval: 25000,
  transports: ['websocket', 'polling'],
})

// Store online users
const onlineUsers = new Map<string, { socketId: string; lastSeen: Date }>()

// Socket event handlers
io.on('connection', (socket: Socket) => {
  console.log(`[Socket] Client connected: ${socket.id}`)

  // Handle user authentication/identification
  socket.on('user:identify', (userId: string) => {
    console.log(`[Socket] User identified: ${userId}`)

    // Store user in online users map
    onlineUsers.set(userId, { socketId: socket.id, lastSeen: new Date() })

    // Join user's personal room for direct notifications
    socket.join(`user:${userId}`)

    // Broadcast user online status
    socket.broadcast.emit('user:online', { userId })

    // Send current online users to the connected client
    socket.emit('users:online', Array.from(onlineUsers.keys()))
  })

  // Handle room subscriptions for real-time updates
  socket.on('room:join', (room: string) => {
    console.log(`[Socket] ${socket.id} joined room: ${room}`)
    socket.join(room)
  })

  socket.on('room:leave', (room: string) => {
    console.log(`[Socket] ${socket.id} left room: ${room}`)
    socket.leave(room)
  })

  // Handle case events
  socket.on('case:created', (data: unknown) => {
    console.log(`[Socket] Case created event`)
    io.emit('case:created', data)
  })

  socket.on('case:updated', (data: { caseId: string; [key: string]: unknown }) => {
    console.log(`[Socket] Case updated: ${data.caseId}`)
    io.to(`case:${data.caseId}`).emit('case:updated', data)
    io.emit('cases:refresh', { caseId: data.caseId })
  })

  socket.on('case:deleted', (data: { caseId: string }) => {
    console.log(`[Socket] Case deleted: ${data.caseId}`)
    io.emit('case:deleted', data)
  })

  // Handle task events
  socket.on('task:created', (data: { caseId: string; departmentId: string; [key: string]: unknown }) => {
    console.log(`[Socket] Task created`)
    io.to(`case:${data.caseId}`).emit('task:created', data)
    io.to(`department:${data.departmentId}`).emit('task:created', data)
    io.emit('tasks:refresh', { caseId: data.caseId })
  })

  socket.on('task:updated', (data: { taskId: string; caseId: string; departmentId: string; [key: string]: unknown }) => {
    console.log(`[Socket] Task updated: ${data.taskId}`)
    io.to(`case:${data.caseId}`).emit('task:updated', data)
    io.to(`department:${data.departmentId}`).emit('task:updated', data)
    io.emit('tasks:refresh', { taskId: data.taskId })
  })

  socket.on('task:assigned', (data: { taskId: string; assigneeId: string; [key: string]: unknown }) => {
    console.log(`[Socket] Task assigned: ${data.taskId} to ${data.assigneeId}`)
    // Notify the assigned user
    io.to(`user:${data.assigneeId}`).emit('task:assigned', data)
    io.emit('tasks:refresh', { taskId: data.taskId })
  })

  socket.on('task:deleted', (data: { taskId: string; caseId: string }) => {
    console.log(`[Socket] Task deleted: ${data.taskId}`)
    io.to(`case:${data.caseId}`).emit('task:deleted', data)
  })

  // Handle comment events
  socket.on('comment:created', (data: { caseId?: string; taskId?: string; [key: string]: unknown }) => {
    console.log(`[Socket] Comment created`)
    if (data.caseId) {
      io.to(`case:${data.caseId}`).emit('comment:created', data)
    }
    if (data.taskId) {
      io.to(`task:${data.taskId}`).emit('comment:created', data)
    }
  })

  // Handle notification events
  socket.on('notification:send', (data: { userId: string; [key: string]: unknown }) => {
    console.log(`[Socket] Sending notification to user: ${data.userId}`)
    io.to(`user:${data.userId}`).emit('notification:new', data)
  })

  socket.on('notification:broadcast', (data: { departmentId?: string; [key: string]: unknown }) => {
    console.log(`[Socket] Broadcasting notification`)
    if (data.departmentId) {
      io.to(`department:${data.departmentId}`).emit('notification:new', data)
    } else {
      io.emit('notification:new', data)
    }
  })

  // Handle typing indicators
  socket.on('typing:start', (data: { room: string; userId: string; userName: string }) => {
    socket.to(data.room).emit('typing:start', {
      userId: data.userId,
      userName: data.userName,
    })
  })

  socket.on('typing:stop', (data: { room: string; userId: string }) => {
    socket.to(data.room).emit('typing:stop', { userId: data.userId })
  })

  // Handle disconnection
  socket.on('disconnect', (reason: string) => {
    console.log(`[Socket] Client disconnected: ${socket.id}, reason: ${reason}`)

    // Find and remove user from online users
    for (const [userId, userData] of onlineUsers.entries()) {
      if (userData.socketId === socket.id) {
        onlineUsers.delete(userId)
        io.emit('user:offline', { userId })
        break
      }
    }
  })

  // Handle errors
  socket.on('error', (error: Error) => {
    console.error(`[Socket] Error: ${error.message}`)
  })
})

// Start server
httpServer.listen(PORT, () => {
  console.log(`
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║   DentalFlow Socket.io Server                              ║
║   Running on port ${PORT}                                      ║
║   CORS Origin: ${CORS_ORIGIN}                        ║
║                                                            ║
║   Health check: http://localhost:${PORT}/health                ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
  `)
})

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('[Socket] SIGTERM received, shutting down gracefully...')
  io.close(() => {
    console.log('[Socket] Server closed')
    process.exit(0)
  })
})

process.on('SIGINT', () => {
  console.log('[Socket] SIGINT received, shutting down gracefully...')
  io.close(() => {
    console.log('[Socket] Server closed')
    process.exit(0)
  })
})

export { io, httpServer }
