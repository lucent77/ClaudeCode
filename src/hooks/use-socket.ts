'use client'

import { useEffect, useState, useCallback } from 'react'
import { useSession } from 'next-auth/react'
import {
  getSocket,
  connectSocket,
  disconnectSocket,
  isSocketConnected,
  type TypedSocket,
} from '@/lib/socket'
import type { Department } from '@/types'

/**
 * Socket.io 연결 관리 훅
 * 인증된 사용자만 연결을 시작하고, 로그아웃 시 연결을 해제
 */
export function useSocket() {
  const { data: session, status } = useSession()
  const [socket, setSocket] = useState<TypedSocket | null>(null)
  const [isConnected, setIsConnected] = useState(false)

  useEffect(() => {
    // 인증 상태가 로딩 중이거나 미인증 상태면 연결하지 않음
    if (status === 'loading') return
    if (status === 'unauthenticated') {
      disconnectSocket()
      setSocket(null)
      setIsConnected(false)
      return
    }

    // 인증된 경우 연결 시작
    const s = connectSocket()
    setSocket(s)

    const handleConnect = () => {
      setIsConnected(true)
      // 사용자의 부서 room에 자동 참가
      if (session?.user?.department) {
        s.emit('department:join', session.user.department as Department)
      }
    }

    const handleDisconnect = () => {
      setIsConnected(false)
    }

    s.on('connect', handleConnect)
    s.on('disconnect', handleDisconnect)

    // 이미 연결되어 있다면 상태 업데이트
    if (s.connected) {
      setIsConnected(true)
    }

    return () => {
      s.off('connect', handleConnect)
      s.off('disconnect', handleDisconnect)
    }
  }, [status, session?.user?.department])

  // 부서 room 참가
  const joinDepartment = useCallback(
    (department: Department) => {
      if (socket?.connected) {
        socket.emit('department:join', department)
      }
    },
    [socket]
  )

  // 부서 room 떠나기
  const leaveDepartment = useCallback(
    (department: Department) => {
      if (socket?.connected) {
        socket.emit('department:leave', department)
      }
    },
    [socket]
  )

  // 케이스 구독
  const subscribeToCase = useCallback(
    (caseId: string) => {
      if (socket?.connected) {
        socket.emit('case:subscribe', caseId)
      }
    },
    [socket]
  )

  // 케이스 구독 해제
  const unsubscribeFromCase = useCallback(
    (caseId: string) => {
      if (socket?.connected) {
        socket.emit('case:unsubscribe', caseId)
      }
    },
    [socket]
  )

  return {
    socket,
    isConnected,
    joinDepartment,
    leaveDepartment,
    subscribeToCase,
    unsubscribeFromCase,
  }
}

/**
 * 간단한 연결 상태 확인 훅
 */
export function useSocketStatus() {
  const [isConnected, setIsConnected] = useState(false)

  useEffect(() => {
    setIsConnected(isSocketConnected())

    const socket = getSocket()
    const handleConnect = () => setIsConnected(true)
    const handleDisconnect = () => setIsConnected(false)

    socket.on('connect', handleConnect)
    socket.on('disconnect', handleDisconnect)

    return () => {
      socket.off('connect', handleConnect)
      socket.off('disconnect', handleDisconnect)
    }
  }, [])

  return isConnected
}
