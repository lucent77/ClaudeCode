import { io, Socket } from 'socket.io-client'
import type { CaseWithRelations, CaseAssignment, CaseStatus, Department } from '@/types'

/**
 * Socket.io 클라이언트 이벤트 타입 정의
 */
export interface ServerToClientEvents {
  'case:created': (data: CaseWithRelations) => void
  'case:updated': (data: CaseWithRelations) => void
  'case:deleted': (caseId: string) => void
  'case:statusChanged': (data: {
    caseId: string
    status: CaseStatus
    department: Department
  }) => void
  'assignment:changed': (data: CaseAssignment) => void
  'user:joined': (data: { userId: string; department: Department }) => void
  'user:left': (data: { userId: string; department: Department }) => void
  'error': (message: string) => void
}

export interface ClientToServerEvents {
  'case:subscribe': (caseId: string) => void
  'case:unsubscribe': (caseId: string) => void
  'department:join': (department: Department) => void
  'department:leave': (department: Department) => void
}

export type TypedSocket = Socket<ServerToClientEvents, ClientToServerEvents>

/**
 * Socket.io 클라이언트 싱글톤
 * 앱 전체에서 하나의 연결만 유지
 */
let socket: TypedSocket | null = null

export function getSocket(): TypedSocket {
  if (!socket) {
    const socketUrl = process.env.NEXT_PUBLIC_SOCKET_URL || 'http://localhost:3001'

    socket = io(socketUrl, {
      autoConnect: false,
      reconnection: true,
      reconnectionAttempts: 5,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      timeout: 20000,
      transports: ['websocket', 'polling'],
    })

    // 연결 이벤트 핸들러
    socket.on('connect', () => {
      console.log('[Socket.io] Connected:', socket?.id)
    })

    socket.on('disconnect', (reason) => {
      console.log('[Socket.io] Disconnected:', reason)
    })

    socket.on('connect_error', (error) => {
      console.error('[Socket.io] Connection error:', error.message)
    })

    socket.on('error', (message) => {
      console.error('[Socket.io] Server error:', message)
    })
  }

  return socket
}

/**
 * Socket.io 연결 시작
 */
export function connectSocket(): TypedSocket {
  const s = getSocket()
  if (!s.connected) {
    s.connect()
  }
  return s
}

/**
 * Socket.io 연결 해제
 */
export function disconnectSocket(): void {
  if (socket?.connected) {
    socket.disconnect()
  }
}

/**
 * 연결 상태 확인
 */
export function isSocketConnected(): boolean {
  return socket?.connected ?? false
}

export default getSocket
