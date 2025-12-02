/**
 * Case Store - DentalFlow
 *
 * Manages case and task state including:
 * - Current case/task selection
 * - Filters and sorting
 * - Real-time updates integration
 */

import { create } from 'zustand'
import type { CaseStatus, CasePriority, TaskStatus } from '@/types'

interface CaseFilters {
  status: CaseStatus[]
  priority: CasePriority[]
  clientName: string
  dateFrom: Date | null
  dateTo: Date | null
  search: string
}

interface TaskFilters {
  status: TaskStatus[]
  departmentId: string | null
  assigneeId: string | null
  priority: CasePriority[]
  search: string
}

type SortDirection = 'asc' | 'desc'

interface SortConfig {
  field: string
  direction: SortDirection
}

interface CaseState {
  // Current selections
  selectedCaseId: string | null
  selectedTaskId: string | null

  // Filters
  caseFilters: CaseFilters
  taskFilters: TaskFilters

  // Sorting
  caseSort: SortConfig
  taskSort: SortConfig

  // View mode
  viewMode: 'list' | 'board' | 'calendar'

  // Pagination
  casePage: number
  caseLimit: number
  taskPage: number
  taskLimit: number

  // Actions
  setSelectedCase: (id: string | null) => void
  setSelectedTask: (id: string | null) => void
  setCaseFilters: (filters: Partial<CaseFilters>) => void
  setTaskFilters: (filters: Partial<TaskFilters>) => void
  resetCaseFilters: () => void
  resetTaskFilters: () => void
  setCaseSort: (sort: SortConfig) => void
  setTaskSort: (sort: SortConfig) => void
  setViewMode: (mode: 'list' | 'board' | 'calendar') => void
  setCasePage: (page: number) => void
  setTaskPage: (page: number) => void
}

const defaultCaseFilters: CaseFilters = {
  status: [],
  priority: [],
  clientName: '',
  dateFrom: null,
  dateTo: null,
  search: '',
}

const defaultTaskFilters: TaskFilters = {
  status: [],
  departmentId: null,
  assigneeId: null,
  priority: [],
  search: '',
}

export const useCaseStore = create<CaseState>((set) => ({
  // Initial state
  selectedCaseId: null,
  selectedTaskId: null,
  caseFilters: defaultCaseFilters,
  taskFilters: defaultTaskFilters,
  caseSort: { field: 'createdAt', direction: 'desc' },
  taskSort: { field: 'createdAt', direction: 'desc' },
  viewMode: 'list',
  casePage: 1,
  caseLimit: 20,
  taskPage: 1,
  taskLimit: 20,

  // Actions
  setSelectedCase: (id) =>
    set({ selectedCaseId: id }),

  setSelectedTask: (id) =>
    set({ selectedTaskId: id }),

  setCaseFilters: (filters) =>
    set((state) => ({
      caseFilters: { ...state.caseFilters, ...filters },
      casePage: 1, // Reset to first page when filters change
    })),

  setTaskFilters: (filters) =>
    set((state) => ({
      taskFilters: { ...state.taskFilters, ...filters },
      taskPage: 1,
    })),

  resetCaseFilters: () =>
    set({ caseFilters: defaultCaseFilters, casePage: 1 }),

  resetTaskFilters: () =>
    set({ taskFilters: defaultTaskFilters, taskPage: 1 }),

  setCaseSort: (sort) =>
    set({ caseSort: sort }),

  setTaskSort: (sort) =>
    set({ taskSort: sort }),

  setViewMode: (mode) =>
    set({ viewMode: mode }),

  setCasePage: (page) =>
    set({ casePage: page }),

  setTaskPage: (page) =>
    set({ taskPage: page }),
}))

// Selectors
export const selectSelectedCase = (state: CaseState) => state.selectedCaseId
export const selectSelectedTask = (state: CaseState) => state.selectedTaskId
export const selectCaseFilters = (state: CaseState) => state.caseFilters
export const selectTaskFilters = (state: CaseState) => state.taskFilters
export const selectViewMode = (state: CaseState) => state.viewMode
