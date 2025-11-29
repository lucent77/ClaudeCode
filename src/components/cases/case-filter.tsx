'use client'

import { useCallback } from 'react'
import { Search, X } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useCaseStore } from '@/stores/case-store'
import { DEPARTMENT_LABELS, STATUS_LABELS } from '@/lib/utils'
import type { CaseStatus, Department } from '@/types'

interface CaseFilterProps {
  showDepartmentFilter?: boolean
}

export function CaseFilter({ showDepartmentFilter = true }: CaseFilterProps) {
  const { filters, setFilters, resetFilters } = useCaseStore()

  const handleSearchChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      setFilters({ search: e.target.value })
    },
    [setFilters]
  )

  const handleStatusChange = useCallback(
    (value: string) => {
      setFilters({ status: value === 'all' ? undefined : (value as CaseStatus) })
    },
    [setFilters]
  )

  const handleDepartmentChange = useCallback(
    (value: string) => {
      setFilters({
        department: value === 'all' ? undefined : (value as Department),
      })
    },
    [setFilters]
  )

  const handleDelayedChange = useCallback(
    (value: string) => {
      setFilters({ isDelayed: value === 'delayed' ? true : undefined })
    },
    [setFilters]
  )

  const hasActiveFilters =
    filters.search ||
    filters.status ||
    filters.department ||
    filters.isDelayed

  return (
    <div className="flex flex-col sm:flex-row gap-3">
      {/* 검색 */}
      <div className="relative flex-1 max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          placeholder="케이스번호, 환자명, LAB/DOCTOR 검색..."
          value={filters.search || ''}
          onChange={handleSearchChange}
          className="pl-9"
        />
      </div>

      {/* 상태 필터 */}
      <Select
        value={filters.status || 'all'}
        onValueChange={handleStatusChange}
      >
        <SelectTrigger className="w-[140px]">
          <SelectValue placeholder="상태" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">전체 상태</SelectItem>
          {Object.entries(STATUS_LABELS).map(([value, label]) => (
            <SelectItem key={value} value={value}>
              {label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* 부서 필터 */}
      {showDepartmentFilter && (
        <Select
          value={filters.department || 'all'}
          onValueChange={handleDepartmentChange}
        >
          <SelectTrigger className="w-[160px]">
            <SelectValue placeholder="부서" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">전체 부서</SelectItem>
            {Object.entries(DEPARTMENT_LABELS).map(([value, label]) => (
              <SelectItem key={value} value={value}>
                {label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      )}

      {/* 지연 필터 */}
      <Select
        value={filters.isDelayed ? 'delayed' : 'all'}
        onValueChange={handleDelayedChange}
      >
        <SelectTrigger className="w-[120px]">
          <SelectValue placeholder="필터" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">전체</SelectItem>
          <SelectItem value="delayed">지연 건</SelectItem>
        </SelectContent>
      </Select>

      {/* 필터 초기화 */}
      {hasActiveFilters && (
        <Button variant="ghost" size="icon" onClick={resetFilters}>
          <X className="h-4 w-4" />
        </Button>
      )}
    </div>
  )
}
