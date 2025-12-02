/**
 * Notification Store - DentalFlow
 *
 * Manages notification state including:
 * - Unread notifications
 * - Real-time notification updates
 * - Notification preferences
 */

import { create } from 'zustand'
import type { Notification, NotificationType } from '@/types'

interface NotificationState {
  // Notifications list
  notifications: Notification[]
  unreadCount: number

  // Loading state
  isLoading: boolean

  // Preferences
  soundEnabled: boolean
  desktopEnabled: boolean

  // Actions
  setNotifications: (notifications: Notification[]) => void
  addNotification: (notification: Notification) => void
  markAsRead: (id: string) => void
  markAllAsRead: () => void
  removeNotification: (id: string) => void
  clearAll: () => void
  setLoading: (loading: boolean) => void
  toggleSound: () => void
  toggleDesktop: () => void
}

export const useNotificationStore = create<NotificationState>((set) => ({
  // Initial state
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  soundEnabled: true,
  desktopEnabled: false,

  // Actions
  setNotifications: (notifications) =>
    set({
      notifications,
      unreadCount: notifications.filter((n) => !n.isRead).length,
    }),

  addNotification: (notification) =>
    set((state) => ({
      notifications: [notification, ...state.notifications],
      unreadCount: notification.isRead ? state.unreadCount : state.unreadCount + 1,
    })),

  markAsRead: (id) =>
    set((state) => {
      const notification = state.notifications.find((n) => n.id === id)
      if (!notification || notification.isRead) return state

      return {
        notifications: state.notifications.map((n) =>
          n.id === id ? { ...n, isRead: true } : n
        ),
        unreadCount: Math.max(0, state.unreadCount - 1),
      }
    }),

  markAllAsRead: () =>
    set((state) => ({
      notifications: state.notifications.map((n) => ({ ...n, isRead: true })),
      unreadCount: 0,
    })),

  removeNotification: (id) =>
    set((state) => {
      const notification = state.notifications.find((n) => n.id === id)
      return {
        notifications: state.notifications.filter((n) => n.id !== id),
        unreadCount:
          notification && !notification.isRead
            ? Math.max(0, state.unreadCount - 1)
            : state.unreadCount,
      }
    }),

  clearAll: () =>
    set({ notifications: [], unreadCount: 0 }),

  setLoading: (loading) =>
    set({ isLoading: loading }),

  toggleSound: () =>
    set((state) => ({ soundEnabled: !state.soundEnabled })),

  toggleDesktop: () =>
    set((state) => ({ desktopEnabled: !state.desktopEnabled })),
}))

// Selectors
export const selectNotifications = (state: NotificationState) => state.notifications
export const selectUnreadCount = (state: NotificationState) => state.unreadCount
export const selectUnreadNotifications = (state: NotificationState) =>
  state.notifications.filter((n) => !n.isRead)

// Helper to get notification icon based on type
export function getNotificationIcon(type: NotificationType): string {
  const icons: Record<NotificationType, string> = {
    TASK_ASSIGNED: 'UserPlus',
    TASK_UPDATED: 'RefreshCw',
    TASK_COMPLETED: 'CheckCircle',
    CASE_UPDATED: 'FileText',
    COMMENT_MENTION: 'MessageCircle',
    DEADLINE_APPROACHING: 'Clock',
    SYSTEM_ALERT: 'AlertTriangle',
  }
  return icons[type] || 'Bell'
}

// Helper to get notification color based on type
export function getNotificationColor(type: NotificationType): string {
  const colors: Record<NotificationType, string> = {
    TASK_ASSIGNED: 'text-blue-500',
    TASK_UPDATED: 'text-amber-500',
    TASK_COMPLETED: 'text-emerald-500',
    CASE_UPDATED: 'text-purple-500',
    COMMENT_MENTION: 'text-cyan-500',
    DEADLINE_APPROACHING: 'text-orange-500',
    SYSTEM_ALERT: 'text-red-500',
  }
  return colors[type] || 'text-gray-500'
}
