/**
 * UI Store - DentalFlow
 *
 * Manages UI state including:
 * - Sidebar visibility
 * - Modal states
 * - Theme preferences
 * - Loading states
 */

import { create } from 'zustand'
import { persist } from 'zustand/middleware'

type Theme = 'light' | 'dark' | 'system'

interface Modal {
  id: string
  isOpen: boolean
  data?: unknown
}

interface UIState {
  // Sidebar
  sidebarOpen: boolean
  sidebarCollapsed: boolean

  // Theme
  theme: Theme

  // Modals
  modals: Record<string, Modal>

  // Loading
  isLoading: boolean
  loadingMessage: string | null

  // Search
  searchOpen: boolean
  searchQuery: string

  // Actions
  toggleSidebar: () => void
  setSidebarOpen: (open: boolean) => void
  setSidebarCollapsed: (collapsed: boolean) => void
  setTheme: (theme: Theme) => void
  openModal: (id: string, data?: unknown) => void
  closeModal: (id: string) => void
  setLoading: (loading: boolean, message?: string) => void
  toggleSearch: () => void
  setSearchQuery: (query: string) => void
  closeSearch: () => void
}

export const useUIStore = create<UIState>()(
  persist(
    (set) => ({
      // Initial state
      sidebarOpen: true,
      sidebarCollapsed: false,
      theme: 'system',
      modals: {},
      isLoading: false,
      loadingMessage: null,
      searchOpen: false,
      searchQuery: '',

      // Actions
      toggleSidebar: () =>
        set((state) => ({ sidebarOpen: !state.sidebarOpen })),

      setSidebarOpen: (open) =>
        set({ sidebarOpen: open }),

      setSidebarCollapsed: (collapsed) =>
        set({ sidebarCollapsed: collapsed }),

      setTheme: (theme) =>
        set({ theme }),

      openModal: (id, data) =>
        set((state) => ({
          modals: {
            ...state.modals,
            [id]: { id, isOpen: true, data },
          },
        })),

      closeModal: (id) =>
        set((state) => ({
          modals: {
            ...state.modals,
            [id]: { ...state.modals[id], isOpen: false },
          },
        })),

      setLoading: (loading, message) =>
        set({ isLoading: loading, loadingMessage: message || null }),

      toggleSearch: () =>
        set((state) => ({ searchOpen: !state.searchOpen })),

      setSearchQuery: (query) =>
        set({ searchQuery: query }),

      closeSearch: () =>
        set({ searchOpen: false, searchQuery: '' }),
    }),
    {
      name: 'dentalflow-ui',
      partialize: (state) => ({
        sidebarCollapsed: state.sidebarCollapsed,
        theme: state.theme,
      }),
    }
  )
)

// Selectors for common use cases
export const selectSidebarOpen = (state: UIState) => state.sidebarOpen
export const selectTheme = (state: UIState) => state.theme
export const selectIsLoading = (state: UIState) => state.isLoading
export const selectModal = (id: string) => (state: UIState) => state.modals[id]
