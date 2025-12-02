/**
 * Socket.io Client Hook - DentalFlow
 *
 * React hook for managing Socket.io connection and events
 * Provides real-time communication capabilities to components
 */

'use client'

import { useEffect, useRef, useCallback, useState } from 'react'
import { io, Socket } from 'socket.io-client'

// Socket server URL (configured for CyberPanel reverse proxy)
const SOCKET_URL = process.env.NEXT_PUBLIC_SOCKET_URL || 'http://localhost:3001'

interface UseSocketOptions {
  userId?: string
  rooms?: string[]
  autoConnect?: boolean
}

interface SocketState {
  isConnected: boolean
  isConnecting: boolean
  error: Error | null
}

export function useSocket(options: UseSocketOptions = {}) {
  const { userId, rooms = [], autoConnect = true } = options
  const socketRef = useRef<Socket | null>(null)
  const [state, setState] = useState<SocketState>({
    isConnected: false,
    isConnecting: false,
    error: null,
  })

  // Initialize socket connection
  useEffect(() => {
    if (!autoConnect) return

    setState((prev) => ({ ...prev, isConnecting: true }))

    const socket = io(SOCKET_URL, {
      transports: ['websocket', 'polling'],
      reconnection: true,
      reconnectionAttempts: 5,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      timeout: 20000,
    })

    socketRef.current = socket

    // Connection event handlers
    socket.on('connect', () => {
      console.log('[Socket] Connected:', socket.id)
      setState({ isConnected: true, isConnecting: false, error: null })

      // Identify user if userId is provided
      if (userId) {
        socket.emit('user:identify', userId)
      }

      // Join specified rooms
      rooms.forEach((room) => {
        socket.emit('room:join', room)
      })
    })

    socket.on('disconnect', (reason) => {
      console.log('[Socket] Disconnected:', reason)
      setState((prev) => ({ ...prev, isConnected: false }))
    })

    socket.on('connect_error', (error) => {
      console.error('[Socket] Connection error:', error.message)
      setState({ isConnected: false, isConnecting: false, error })
    })

    // Cleanup on unmount
    return () => {
      if (socket.connected) {
        rooms.forEach((room) => {
          socket.emit('room:leave', room)
        })
        socket.disconnect()
      }
      socketRef.current = null
    }
  }, [autoConnect, userId, rooms.join(',')])

  // Join a room
  const joinRoom = useCallback((room: string) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('room:join', room)
    }
  }, [])

  // Leave a room
  const leaveRoom = useCallback((room: string) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('room:leave', room)
    }
  }, [])

  // Emit an event
  const emit = useCallback(<T>(event: string, data?: T) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit(event, data)
    }
  }, [])

  // Subscribe to an event
  const on = useCallback(<T>(event: string, callback: (data: T) => void) => {
    if (socketRef.current) {
      socketRef.current.on(event, callback)
    }
  }, [])

  // Unsubscribe from an event
  const off = useCallback((event: string, callback?: (...args: unknown[]) => void) => {
    if (socketRef.current) {
      socketRef.current.off(event, callback)
    }
  }, [])

  // Manual connect
  const connect = useCallback(() => {
    if (socketRef.current && !socketRef.current.connected) {
      socketRef.current.connect()
    }
  }, [])

  // Manual disconnect
  const disconnect = useCallback(() => {
    if (socketRef.current?.connected) {
      socketRef.current.disconnect()
    }
  }, [])

  return {
    socket: socketRef.current,
    ...state,
    joinRoom,
    leaveRoom,
    emit,
    on,
    off,
    connect,
    disconnect,
  }
}

// Typed event hooks for specific use cases
export function useCaseUpdates(caseId: string) {
  const { on, off, joinRoom, leaveRoom, isConnected } = useSocket({
    rooms: [`case:${caseId}`],
  })

  useEffect(() => {
    if (isConnected) {
      joinRoom(`case:${caseId}`)
    }
    return () => {
      leaveRoom(`case:${caseId}`)
    }
  }, [caseId, isConnected, joinRoom, leaveRoom])

  return { on, off, isConnected }
}

export function useDepartmentUpdates(departmentId: string) {
  const { on, off, joinRoom, leaveRoom, isConnected } = useSocket({
    rooms: [`department:${departmentId}`],
  })

  useEffect(() => {
    if (isConnected) {
      joinRoom(`department:${departmentId}`)
    }
    return () => {
      leaveRoom(`department:${departmentId}`)
    }
  }, [departmentId, isConnected, joinRoom, leaveRoom])

  return { on, off, isConnected }
}

export function useNotifications(userId: string) {
  const { on, off, isConnected } = useSocket({
    userId,
    rooms: [`user:${userId}`],
  })

  return { on, off, isConnected }
}
