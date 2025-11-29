import { create } from 'zustand'
import { devtools } from 'zustand/middleware'
import type { CaseWithRelations, CaseFilters, CaseStatus, Department } from '@/types'

interface CaseState {
  // 케이스 목록
  cases: CaseWithRelations[]
  // 로딩 상태
  isLoading: boolean
  // 에러 메시지
  error: string | null
  // 필터
  filters: CaseFilters
  // 선택된 케이스 ID
  selectedCaseId: string | null
  // 최근 업데이트된 케이스 ID (하이라이트용)
  recentlyUpdatedIds: Set<string>

  // Actions
  setCases: (cases: CaseWithRelations[]) => void
  addCase: (caseData: CaseWithRelations) => void
  updateCase: (id: string, data: Partial<CaseWithRelations>) => void
  removeCase: (id: string) => void
  setLoading: (isLoading: boolean) => void
  setError: (error: string | null) => void
  setFilters: (filters: Partial<CaseFilters>) => void
  resetFilters: () => void
  selectCase: (id: string | null) => void
  markAsRecentlyUpdated: (id: string) => void
  clearRecentlyUpdated: (id: string) => void
}

const initialFilters: CaseFilters = {
  status: undefined,
  department: undefined,
  search: '',
  fromDate: undefined,
  toDate: undefined,
  isDelayed: undefined,
  assigneeId: undefined,
}

export const useCaseStore = create<CaseState>()(
  devtools(
    (set, get) => ({
      cases: [],
      isLoading: false,
      error: null,
      filters: initialFilters,
      selectedCaseId: null,
      recentlyUpdatedIds: new Set(),

      setCases: (cases) => {
        set({ cases, isLoading: false, error: null })
      },

      addCase: (caseData) => {
        set((state) => ({
          cases: [caseData, ...state.cases],
        }))
        // 하이라이트 효과
        get().markAsRecentlyUpdated(caseData.id)
      },

      updateCase: (id, data) => {
        set((state) => ({
          cases: state.cases.map((c) =>
            c.id === id ? { ...c, ...data } : c
          ),
        }))
        // 하이라이트 효과
        get().markAsRecentlyUpdated(id)
      },

      removeCase: (id) => {
        set((state) => ({
          cases: state.cases.filter((c) => c.id !== id),
          selectedCaseId:
            state.selectedCaseId === id ? null : state.selectedCaseId,
        }))
      },

      setLoading: (isLoading) => {
        set({ isLoading })
      },

      setError: (error) => {
        set({ error, isLoading: false })
      },

      setFilters: (filters) => {
        set((state) => ({
          filters: { ...state.filters, ...filters },
        }))
      },

      resetFilters: () => {
        set({ filters: initialFilters })
      },

      selectCase: (id) => {
        set({ selectedCaseId: id })
      },

      markAsRecentlyUpdated: (id) => {
        set((state) => {
          const newSet = new Set(state.recentlyUpdatedIds)
          newSet.add(id)
          return { recentlyUpdatedIds: newSet }
        })
        // 2초 후 하이라이트 제거
        setTimeout(() => {
          get().clearRecentlyUpdated(id)
        }, 2000)
      },

      clearRecentlyUpdated: (id) => {
        set((state) => {
          const newSet = new Set(state.recentlyUpdatedIds)
          newSet.delete(id)
          return { recentlyUpdatedIds: newSet }
        })
      },
    }),
    { name: 'case-store' }
  )
)

// 필터링된 케이스 선택자
export const useFilteredCases = () => {
  return useCaseStore((state) => {
    let filtered = [...state.cases]
    const { filters } = state

    // 상태 필터
    if (filters.status) {
      filtered = filtered.filter((c) => c.status === filters.status)
    }

    // 부서 필터
    if (filters.department) {
      filtered = filtered.filter(
        (c) => c.currentDepartment === filters.department
      )
    }

    // 검색어 필터
    if (filters.search) {
      const search = filters.search.toLowerCase()
      filtered = filtered.filter(
        (c) =>
          c.caseNumber.toLowerCase().includes(search) ||
          c.patientName.toLowerCase().includes(search) ||
          c.labDoctorName.toLowerCase().includes(search)
      )
    }

    // 날짜 범위 필터
    if (filters.fromDate) {
      const from = new Date(filters.fromDate)
      filtered = filtered.filter((c) => new Date(c.createdAt) >= from)
    }

    if (filters.toDate) {
      const to = new Date(filters.toDate)
      filtered = filtered.filter((c) => new Date(c.createdAt) <= to)
    }

    // 지연 건 필터
    if (filters.isDelayed) {
      const now = new Date()
      filtered = filtered.filter(
        (c) =>
          new Date(c.dueDate) < now &&
          c.status !== 'COMPLETED' &&
          c.status !== 'CANCELLED'
      )
    }

    // 담당자 필터
    if (filters.assigneeId) {
      filtered = filtered.filter((c) =>
        c.assignments?.some((a) => a.assigneeId === filters.assigneeId)
      )
    }

    return filtered
  })
}

// 케이스 통계 선택자
export const useCaseStats = () => {
  return useCaseStore((state) => {
    const now = new Date()
    const cases = state.cases

    return {
      total: cases.length,
      pending: cases.filter((c) => c.status === 'PENDING').length,
      inProgress: cases.filter((c) => c.status === 'IN_PROGRESS').length,
      completed: cases.filter((c) => c.status === 'COMPLETED').length,
      onHold: cases.filter((c) => c.status === 'ON_HOLD').length,
      cancelled: cases.filter((c) => c.status === 'CANCELLED').length,
      delayed: cases.filter(
        (c) =>
          new Date(c.dueDate) < now &&
          c.status !== 'COMPLETED' &&
          c.status !== 'CANCELLED'
      ).length,
    }
  })
}
