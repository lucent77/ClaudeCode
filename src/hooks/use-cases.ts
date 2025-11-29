'use client'

import { useEffect, useCallback } from 'react'
import { useCaseStore } from '@/stores/case-store'
import type { CaseFilters, CaseWithRelations } from '@/types'

/**
 * 케이스 데이터 페칭 및 관리 훅
 */
export function useCases(initialFilters?: Partial<CaseFilters>) {
  const {
    cases,
    isLoading,
    error,
    filters,
    setCases,
    setLoading,
    setError,
    setFilters,
  } = useCaseStore()

  // 초기 필터 설정
  useEffect(() => {
    if (initialFilters) {
      setFilters(initialFilters)
    }
  }, [])

  // 케이스 목록 조회
  const fetchCases = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const params = new URLSearchParams()
      if (filters.status) params.set('status', filters.status)
      if (filters.department) params.set('department', filters.department)
      if (filters.search) params.set('search', filters.search)
      if (filters.isDelayed) params.set('isDelayed', 'true')

      const response = await fetch(`/api/cases?${params.toString()}`)
      const result = await response.json()

      if (!response.ok) {
        throw new Error(result.error || '케이스를 불러오는데 실패했습니다')
      }

      setCases(result.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : '알 수 없는 오류')
    }
  }, [filters, setCases, setLoading, setError])

  // 초기 로드 및 필터 변경 시 재조회
  useEffect(() => {
    fetchCases()
  }, [fetchCases])

  // 케이스 상태 변경
  const updateCaseStatus = useCallback(
    async (caseId: string, status: string, version: number) => {
      try {
        const response = await fetch(`/api/cases/${caseId}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ status, version }),
        })

        const result = await response.json()

        if (!response.ok) {
          throw new Error(result.error)
        }

        // 로컬 상태 업데이트
        useCaseStore.getState().updateCase(caseId, result.data)
        return { success: true, data: result.data }
      } catch (err) {
        return {
          success: false,
          error: err instanceof Error ? err.message : '상태 변경 실패',
        }
      }
    },
    []
  )

  // 케이스 삭제
  const deleteCase = useCallback(async (caseId: string) => {
    try {
      const response = await fetch(`/api/cases/${caseId}`, {
        method: 'DELETE',
      })

      if (!response.ok) {
        const result = await response.json()
        throw new Error(result.error)
      }

      useCaseStore.getState().removeCase(caseId)
      return { success: true }
    } catch (err) {
      return {
        success: false,
        error: err instanceof Error ? err.message : '삭제 실패',
      }
    }
  }, [])

  return {
    cases,
    isLoading,
    error,
    filters,
    fetchCases,
    updateCaseStatus,
    deleteCase,
  }
}

/**
 * 단일 케이스 조회 훅
 */
export function useCase(caseId: string | null) {
  const { selectCase, selectedCaseId } = useCaseStore()

  useEffect(() => {
    if (caseId) {
      selectCase(caseId)
    }
    return () => selectCase(null)
  }, [caseId, selectCase])

  const fetchCase = useCallback(async () => {
    if (!caseId) return null

    try {
      const response = await fetch(`/api/cases/${caseId}`)
      const result = await response.json()

      if (!response.ok) {
        throw new Error(result.error)
      }

      return result.data as CaseWithRelations
    } catch (err) {
      console.error('Failed to fetch case:', err)
      return null
    }
  }, [caseId])

  return { fetchCase, selectedCaseId }
}
