'use client'

import { useEffect, useCallback } from 'react'
import { useSocket } from '@/hooks/use-socket'
import { useCaseStore } from '@/stores/case-store'
import type { CaseWithRelations, CaseAssignment, CaseStatus, Department } from '@/types'

/**
 * 케이스 실시간 업데이트 훅
 * Socket.io를 통해 케이스 변경 사항을 실시간으로 수신
 */
export function useRealtimeCases() {
  const { socket, isConnected } = useSocket()
  const { addCase, updateCase, removeCase } = useCaseStore()

  useEffect(() => {
    if (!socket || !isConnected) return

    // 케이스 생성 이벤트
    const handleCaseCreated = (data: CaseWithRelations) => {
      addCase(data)
    }

    // 케이스 업데이트 이벤트
    const handleCaseUpdated = (data: CaseWithRelations) => {
      updateCase(data.id, data)
    }

    // 케이스 삭제 이벤트
    const handleCaseDeleted = (caseId: string) => {
      removeCase(caseId)
    }

    // 상태 변경 이벤트
    const handleStatusChanged = (data: {
      caseId: string
      status: CaseStatus
      department: Department
    }) => {
      updateCase(data.caseId, {
        status: data.status,
        currentDepartment: data.department,
      } as Partial<CaseWithRelations>)
    }

    socket.on('case:created', handleCaseCreated)
    socket.on('case:updated', handleCaseUpdated)
    socket.on('case:deleted', handleCaseDeleted)
    socket.on('case:statusChanged', handleStatusChanged)

    return () => {
      socket.off('case:created', handleCaseCreated)
      socket.off('case:updated', handleCaseUpdated)
      socket.off('case:deleted', handleCaseDeleted)
      socket.off('case:statusChanged', handleStatusChanged)
    }
  }, [socket, isConnected, addCase, updateCase, removeCase])

  return { isConnected }
}

/**
 * 작업 할당 실시간 업데이트 훅
 */
export function useRealtimeAssignments(
  onAssignmentChanged?: (assignment: CaseAssignment) => void
) {
  const { socket, isConnected } = useSocket()

  useEffect(() => {
    if (!socket || !isConnected) return

    const handleAssignmentChanged = (data: CaseAssignment) => {
      onAssignmentChanged?.(data)
    }

    socket.on('assignment:changed', handleAssignmentChanged)

    return () => {
      socket.off('assignment:changed', handleAssignmentChanged)
    }
  }, [socket, isConnected, onAssignmentChanged])

  return { isConnected }
}

/**
 * 특정 케이스 구독 훅
 */
export function useCaseSubscription(caseId: string | null) {
  const { socket, isConnected, subscribeToCase, unsubscribeFromCase } = useSocket()

  useEffect(() => {
    if (!caseId || !isConnected) return

    subscribeToCase(caseId)

    return () => {
      unsubscribeFromCase(caseId)
    }
  }, [caseId, isConnected, subscribeToCase, unsubscribeFromCase])

  return { isConnected }
}

/**
 * 부서별 실시간 업데이트 훅
 */
export function useDepartmentRealtime(department: Department | null) {
  const { socket, isConnected, joinDepartment, leaveDepartment } = useSocket()

  useEffect(() => {
    if (!department || !isConnected) return

    joinDepartment(department)

    return () => {
      leaveDepartment(department)
    }
  }, [department, isConnected, joinDepartment, leaveDepartment])

  return { isConnected }
}
