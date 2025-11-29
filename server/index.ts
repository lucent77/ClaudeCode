/**
 * Socket.io 서버
 * 실시간 케이스 업데이트를 위한 WebSocket 서버
 */

import { createServer } from 'http'
import { Server } from 'socket.io'
import type { Department } from '../src/types'

const PORT = process.env.PORT || 3001

// HTTP 서버 생성
const httpServer = createServer()

// Socket.io 서버 설정
const io = new Server(httpServer, {
  cors: {
    origin: process.env.NEXTAUTH_URL || 'http://localhost:3000',
    methods: ['GET', 'POST'],
    credentials: true,
  },
  transports: ['websocket', 'polling'],
})

// 연결된 사용자 추적
const connectedUsers = new Map<string, { userId?: string; department?: Department }>()

io.on('connection', (socket) => {
  console.log(`[Socket.io] Client connected: ${socket.id}`)
  connectedUsers.set(socket.id, {})

  // 부서 room 참가
  socket.on('department:join', (department: Department) => {
    socket.join(`department:${department}`)
    const userData = connectedUsers.get(socket.id)
    if (userData) {
      userData.department = department
    }
    console.log(`[Socket.io] ${socket.id} joined department: ${department}`)

    // 다른 사용자에게 알림
    socket.to(`department:${department}`).emit('user:joined', {
      userId: socket.id,
      department,
    })
  })

  // 부서 room 떠나기
  socket.on('department:leave', (department: Department) => {
    socket.leave(`department:${department}`)
    console.log(`[Socket.io] ${socket.id} left department: ${department}`)

    // 다른 사용자에게 알림
    socket.to(`department:${department}`).emit('user:left', {
      userId: socket.id,
      department,
    })
  })

  // 케이스 구독
  socket.on('case:subscribe', (caseId: string) => {
    socket.join(`case:${caseId}`)
    console.log(`[Socket.io] ${socket.id} subscribed to case: ${caseId}`)
  })

  // 케이스 구독 해제
  socket.on('case:unsubscribe', (caseId: string) => {
    socket.leave(`case:${caseId}`)
    console.log(`[Socket.io] ${socket.id} unsubscribed from case: ${caseId}`)
  })

  // 연결 해제
  socket.on('disconnect', (reason) => {
    const userData = connectedUsers.get(socket.id)
    if (userData?.department) {
      io.to(`department:${userData.department}`).emit('user:left', {
        userId: socket.id,
        department: userData.department,
      })
    }
    connectedUsers.delete(socket.id)
    console.log(`[Socket.io] Client disconnected: ${socket.id}, reason: ${reason}`)
  })
})

// 서버에서 이벤트를 브로드캐스트하기 위한 헬퍼 함수들
export function broadcastCaseCreated(caseData: unknown) {
  io.emit('case:created', caseData)
}

export function broadcastCaseUpdated(caseId: string, caseData: unknown) {
  io.to(`case:${caseId}`).emit('case:updated', caseData)
  io.emit('case:updated', caseData)
}

export function broadcastCaseDeleted(caseId: string) {
  io.to(`case:${caseId}`).emit('case:deleted', caseId)
  io.emit('case:deleted', caseId)
}

export function broadcastToDepartment(
  department: Department,
  event: string,
  data: unknown
) {
  io.to(`department:${department}`).emit(event, data)
}

// 서버 시작
httpServer.listen(PORT, () => {
  console.log(`[Socket.io] Server running on port ${PORT}`)
})

export { io }
