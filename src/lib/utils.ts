import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

/**
 * Tailwind CSS 클래스 병합 유틸리티
 * clsx로 조건부 클래스를 처리하고 tailwind-merge로 충돌을 해결
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * 케이스 번호 생성
 * 형식: YYYYMMDD-XXXX (예: 20251129-0001)
 */
export function generateCaseNumber(sequence: number): string {
  const today = new Date()
  const dateStr = today.toISOString().slice(0, 10).replace(/-/g, '')
  const seqStr = sequence.toString().padStart(4, '0')
  return `${dateStr}-${seqStr}`
}

/**
 * 날짜 포맷팅 (한국어)
 */
export function formatDate(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  return new Intl.DateTimeFormat('ko-KR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(d)
}

/**
 * 날짜/시간 포맷팅 (한국어)
 */
export function formatDateTime(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  return new Intl.DateTimeFormat('ko-KR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(d)
}

/**
 * 상대적 시간 표시 (예: "2시간 전")
 */
export function formatRelativeTime(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  const now = new Date()
  const diffMs = now.getTime() - d.getTime()
  const diffSec = Math.floor(diffMs / 1000)
  const diffMin = Math.floor(diffSec / 60)
  const diffHour = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHour / 24)

  if (diffSec < 60) return '방금 전'
  if (diffMin < 60) return `${diffMin}분 전`
  if (diffHour < 24) return `${diffHour}시간 전`
  if (diffDay < 7) return `${diffDay}일 전`
  return formatDate(d)
}

/**
 * 지연 일수 계산
 */
export function calculateDelayDays(dueDate: Date | string): number {
  const due = typeof dueDate === 'string' ? new Date(dueDate) : dueDate
  const now = new Date()
  const diffMs = now.getTime() - due.getTime()
  return Math.max(0, Math.floor(diffMs / (1000 * 60 * 60 * 24)))
}

/**
 * 마감일까지 남은 일수
 */
export function daysUntilDue(dueDate: Date | string): number {
  const due = typeof dueDate === 'string' ? new Date(dueDate) : dueDate
  const now = new Date()
  const diffMs = due.getTime() - now.getTime()
  return Math.ceil(diffMs / (1000 * 60 * 60 * 24))
}

/**
 * 부서 한글명 매핑
 */
export const DEPARTMENT_LABELS: Record<string, string> = {
  FRONT_DESK: 'Front Desk',
  PRE_CAD: 'PreCAD',
  SOLIDEX_DESIGN: 'Solidex Design',
  COCR_DESIGN: 'Cocr Design',
  PRINT_3D_DESIGN: '3D Print Design',
  PRE_CAM: 'PreCAM',
  SOLIDEX: 'Solidex',
  COCR: 'Cocr',
  PRINT_3D: '3D Print',
}

/**
 * 상태 한글명 매핑
 */
export const STATUS_LABELS: Record<string, string> = {
  PENDING: '대기중',
  IN_PROGRESS: '진행중',
  COMPLETED: '완료',
  ON_HOLD: '보류',
  CANCELLED: '취소',
}

/**
 * 상태별 색상 클래스
 */
export const STATUS_COLORS: Record<string, string> = {
  PENDING: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  IN_PROGRESS: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  COMPLETED: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  ON_HOLD: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
  CANCELLED: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
}

/**
 * 역할 한글명 매핑
 */
export const ROLE_LABELS: Record<string, string> = {
  ADMIN: '관리자',
  WORKER: '작업자',
}

/**
 * 슬러그 생성
 */
export function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9가-힣]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

/**
 * 값이 비어있는지 확인
 */
export function isEmpty(value: unknown): boolean {
  if (value === null || value === undefined) return true
  if (typeof value === 'string') return value.trim() === ''
  if (Array.isArray(value)) return value.length === 0
  if (typeof value === 'object') return Object.keys(value).length === 0
  return false
}

/**
 * 배열을 지정된 크기로 분할
 */
export function chunk<T>(array: T[], size: number): T[][] {
  const chunks: T[][] = []
  for (let i = 0; i < array.length; i += size) {
    chunks.push(array.slice(i, i + size))
  }
  return chunks
}

/**
 * 딜레이 유틸리티
 */
export function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

/**
 * 안전한 JSON 파싱
 */
export function safeJsonParse<T>(json: string, fallback: T): T {
  try {
    return JSON.parse(json) as T
  } catch {
    return fallback
  }
}
