/**
 * DentalFlow 공통 타입 정의
 */

// ===== 열거형 =====

export type Role = 'ADMIN' | 'WORKER'

export type Department =
  | 'FRONT_DESK'
  | 'PRE_CAD'
  | 'SOLIDEX_DESIGN'
  | 'COCR_DESIGN'
  | 'PRINT_3D_DESIGN'
  | 'PRE_CAM'
  | 'SOLIDEX'
  | 'COCR'
  | 'PRINT_3D'

export type CaseStatus =
  | 'PENDING'
  | 'IN_PROGRESS'
  | 'COMPLETED'
  | 'ON_HOLD'
  | 'CANCELLED'

export type FieldType =
  | 'TEXT'
  | 'NUMBER'
  | 'SELECT'
  | 'MULTI_SELECT'
  | 'DATE'
  | 'BOOLEAN'

// ===== 사용자 =====

export interface User {
  id: string
  email: string
  name: string
  role: Role
  department: Department
  isActive: boolean
  createdAt: Date
  updatedAt: Date
}

export interface UserWithStats extends User {
  assignedTasksCount: number
  completedTasksCount: number
  inProgressTasksCount: number
}

// ===== 케이스 =====

export interface Case {
  id: string
  caseNumber: string
  panNumber: string | null
  labDoctorName: string
  patientName: string
  quantity: number
  toothNumbers: string[]
  toothColor: string | null
  implantType: string | null
  dueDate: Date
  cocrType: string | null
  solidexType: string | null
  print3dType: string | null
  noteOptions: string[]
  noteText: string | null
  status: CaseStatus
  currentDepartment: Department
  workflow: Department[]
  version: number
  createdAt: Date
  updatedAt: Date
  createdById: string
}

export interface CaseWithRelations extends Case {
  createdBy: User
  departmentStatuses?: DepartmentStatus[]
  assignments?: CaseAssignment[]
}

export interface CaseCreateInput {
  panNumber?: string
  labDoctorName: string
  patientName: string
  quantity: number
  toothNumbers: string[]
  toothColor?: string
  implantType?: string
  dueDate: Date | string
  cocrType?: string
  solidexType?: string
  print3dType?: string
  noteOptions: string[]
  noteText?: string
  workflow: Department[]
}

export interface CaseUpdateInput extends Partial<CaseCreateInput> {
  status?: CaseStatus
  currentDepartment?: Department
  version: number // 낙관적 잠금을 위한 필수 필드
}

// ===== 부서별 상태 =====

export interface DepartmentStatus {
  id: string
  caseId: string
  department: Department
  status: CaseStatus
  startedAt: Date | null
  completedAt: Date | null
}

// ===== 케이스 이력 =====

export type CaseHistoryAction =
  | 'CREATE'
  | 'UPDATE'
  | 'STATUS_CHANGE'
  | 'DEPARTMENT_CHANGE'
  | 'ASSIGN'
  | 'TRANSFER'

export interface CaseHistory {
  id: string
  caseId: string
  userId: string
  action: CaseHistoryAction
  changes: Record<string, unknown>
  createdAt: Date
  user?: User
}

// ===== NOTE 옵션 =====

export interface NoteOption {
  id: string
  label: string
  isActive: boolean
  sortOrder: number
}

// ===== 작업 유형 =====

export interface TaskType {
  id: string
  department: Department
  name: string
  description: string | null
  color: string | null
  isActive: boolean
  sortOrder: number
  createdAt: Date
  updatedAt: Date
}

export interface TaskTypeCreateInput {
  department: Department
  name: string
  description?: string
  color?: string
}

// ===== 커스텀 필드 =====

export interface CustomField {
  id: string
  department: Department
  name: string
  label: string
  fieldType: FieldType
  options: string[] | null
  defaultValue: string | null
  placeholder: string | null
  isRequired: boolean
  isActive: boolean
  sortOrder: number
  createdAt: Date
  updatedAt: Date
}

export interface CustomFieldCreateInput {
  department: Department
  name: string
  label: string
  fieldType: FieldType
  options?: string[]
  defaultValue?: string
  placeholder?: string
  isRequired?: boolean
}

// ===== 커스텀 상태 =====

export interface CustomStatus {
  id: string
  department: Department
  name: string
  color: string | null
  description: string | null
  isActive: boolean
  sortOrder: number
  createdAt: Date
  updatedAt: Date
}

// ===== 작업 할당 =====

export interface CaseAssignment {
  id: string
  caseId: string
  department: Department
  assigneeId: string
  assignedById: string
  assignedAt: Date
  status: CaseStatus
  startedAt: Date | null
  completedAt: Date | null
  customFieldValues: Record<string, unknown> | null
  notes: string | null
  assignee?: User
  assignedBy?: User
  case?: Case
}

export interface AssignmentCreateInput {
  caseId: string
  department: Department
  assigneeId: string
}

export interface AssignmentTransfer {
  id: string
  caseId: string
  department: Department
  fromUserId: string
  toUserId: string
  reason: string
  transferredAt: Date
  fromUser?: User
  toUser?: User
}

// ===== 통계 =====

export interface DashboardStats {
  totalCases: number
  pendingCases: number
  inProgressCases: number
  completedCases: number
  delayedCases: number
  completionRate: number
  todayCreated: number
  todayCompleted: number
}

export interface DepartmentStats {
  department: Department
  totalCases: number
  pendingCases: number
  inProgressCases: number
  completedCases: number
}

export interface WorkerStats {
  userId: string
  userName: string
  department: Department
  assignedTasks: number
  completedTasks: number
  inProgressTasks: number
  avgCompletionTime: number // 시간 단위
  delayRate: number // 백분율
}

// ===== API 응답 =====

export interface ApiResponse<T> {
  success: boolean
  data?: T
  error?: string
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// ===== 필터/정렬 =====

export interface CaseFilters {
  status?: CaseStatus
  department?: Department
  search?: string
  fromDate?: Date | string
  toDate?: Date | string
  isDelayed?: boolean
  assigneeId?: string
}

export interface SortOption {
  field: string
  direction: 'asc' | 'desc'
}

export interface PaginationOption {
  page: number
  pageSize: number
}

// ===== Socket.io 이벤트 =====

export interface SocketEvents {
  // 클라이언트 -> 서버
  'case:subscribe': (caseId: string) => void
  'case:unsubscribe': (caseId: string) => void
  'department:join': (department: Department) => void
  'department:leave': (department: Department) => void

  // 서버 -> 클라이언트
  'case:created': (data: CaseWithRelations) => void
  'case:updated': (data: CaseWithRelations) => void
  'case:deleted': (caseId: string) => void
  'case:statusChanged': (data: { caseId: string; status: CaseStatus; department: Department }) => void
  'assignment:changed': (data: CaseAssignment) => void
}
