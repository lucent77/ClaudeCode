/**
 * Type Definitions - DentalFlow
 *
 * Central type definitions for the application
 */

import type {
  User,
  Case,
  Task,
  Department,
  TaskType,
  CustomField,
  CustomStatus,
  Comment,
  Attachment,
  Activity,
  Notification,
  Client,
  UserRole,
  CaseStatus,
  CasePriority,
  TaskStatus,
  ActivityType,
  NotificationType,
  CustomFieldType,
} from '@prisma/client'

// Re-export Prisma types
export type {
  User,
  Case,
  Task,
  Department,
  TaskType,
  CustomField,
  CustomStatus,
  Comment,
  Attachment,
  Activity,
  Notification,
  Client,
  UserRole,
  CaseStatus,
  CasePriority,
  TaskStatus,
  ActivityType,
  NotificationType,
  CustomFieldType,
}

// Extended types with relations
export interface UserWithDepartment extends User {
  department: Department | null
}

export interface CaseWithRelations extends Case {
  createdBy: User
  tasks: TaskWithRelations[]
  comments: CommentWithUser[]
  attachments: Attachment[]
  _count?: {
    tasks: number
    comments: number
    attachments: number
  }
}

export interface TaskWithRelations extends Task {
  case: Case
  department: Department
  taskType: TaskType | null
  customStatus: CustomStatus | null
  assignee: User | null
  comments: CommentWithUser[]
  attachments: Attachment[]
}

export interface CommentWithUser extends Comment {
  user: User
  replies?: CommentWithUser[]
}

export interface DepartmentWithStats extends Department {
  _count: {
    users: number
    tasks: number
    taskTypes: number
  }
}

// Dashboard statistics
export interface DashboardStats {
  totalCases: number
  activeCases: number
  completedToday: number
  pendingTasks: number
  urgentCases: number
  overdueCount: number
  departmentStats: DepartmentStat[]
  recentActivity: ActivityWithUser[]
  weeklyTrend: WeeklyTrend[]
}

export interface DepartmentStat {
  departmentId: string
  departmentName: string
  departmentCode: string
  departmentColor: string
  pendingCount: number
  inProgressCount: number
  completedCount: number
  totalCount: number
}

export interface WeeklyTrend {
  date: string
  received: number
  completed: number
}

export interface ActivityWithUser extends Activity {
  user: User
  case?: Case | null
  task?: Task | null
}

// Form types
export interface CreateCaseInput {
  patientName?: string
  patientId?: string
  clientName: string
  clientId?: string
  description?: string
  priority: CasePriority
  dueDate?: Date
  notes?: string
}

export interface UpdateCaseInput extends Partial<CreateCaseInput> {
  status?: CaseStatus
}

export interface CreateTaskInput {
  caseId: string
  departmentId: string
  taskTypeId?: string
  assigneeId?: string
  title: string
  description?: string
  priority?: CasePriority
  estimatedTime?: number
  dueDate?: Date
}

export interface UpdateTaskInput extends Partial<CreateTaskInput> {
  status?: TaskStatus
  customStatusId?: string
  actualTime?: number
}

// Socket.io event types
export interface SocketEvents {
  // Case events
  'case:created': (data: CaseWithRelations) => void
  'case:updated': (data: CaseWithRelations) => void
  'case:deleted': (data: { id: string }) => void

  // Task events
  'task:created': (data: TaskWithRelations) => void
  'task:updated': (data: TaskWithRelations) => void
  'task:deleted': (data: { id: string }) => void
  'task:assigned': (data: { taskId: string; assigneeId: string }) => void

  // Comment events
  'comment:created': (data: CommentWithUser) => void
  'comment:deleted': (data: { id: string }) => void

  // Notification events
  'notification:new': (data: Notification) => void
  'notification:read': (data: { id: string }) => void

  // Presence events
  'user:online': (data: { userId: string }) => void
  'user:offline': (data: { userId: string }) => void

  // Room events
  'room:join': (data: { room: string }) => void
  'room:leave': (data: { room: string }) => void
}

// API response types
export interface ApiResponse<T> {
  success: boolean
  data?: T
  error?: string
  message?: string
}

export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  limit: number
  totalPages: number
}

// Filter types
export interface CaseFilters {
  status?: CaseStatus[]
  priority?: CasePriority[]
  clientName?: string
  dateFrom?: Date
  dateTo?: Date
  search?: string
}

export interface TaskFilters {
  status?: TaskStatus[]
  departmentId?: string
  assigneeId?: string
  priority?: CasePriority[]
  search?: string
}

// Session/Auth types
export interface SessionUser {
  id: string
  email: string
  name: string | null
  image: string | null
  role: UserRole
  departmentId: string | null
}

// Navigation types
export interface NavItem {
  title: string
  href: string
  icon: React.ComponentType<{ className?: string }>
  badge?: number
  children?: NavItem[]
}

// Table column definition
export interface TableColumn<T> {
  key: keyof T | string
  title: string
  sortable?: boolean
  width?: string
  render?: (value: unknown, row: T) => React.ReactNode
}

// Select option type
export interface SelectOption {
  value: string
  label: string
  disabled?: boolean
}

// Theme types
export type Theme = 'light' | 'dark' | 'system'
