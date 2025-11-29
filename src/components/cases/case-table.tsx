'use client'

import { useCallback } from 'react'
import { useRouter } from 'next/navigation'
import { MoreHorizontal, Eye, Edit, Trash2, UserPlus } from 'lucide-react'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { CaseStatusBadge, DepartmentBadge } from './case-status-badge'
import { useCaseStore, useFilteredCases } from '@/stores/case-store'
import { formatDate, daysUntilDue, cn } from '@/lib/utils'
import type { CaseWithRelations } from '@/types'

interface CaseTableProps {
  onViewCase?: (caseData: CaseWithRelations) => void
  onEditCase?: (caseData: CaseWithRelations) => void
  onDeleteCase?: (caseData: CaseWithRelations) => void
  onAssignCase?: (caseData: CaseWithRelations) => void
  isAdmin?: boolean
}

export function CaseTable({
  onViewCase,
  onEditCase,
  onDeleteCase,
  onAssignCase,
  isAdmin = false,
}: CaseTableProps) {
  const router = useRouter()
  const cases = useFilteredCases()
  const { isLoading, recentlyUpdatedIds } = useCaseStore()

  const handleRowClick = useCallback(
    (caseData: CaseWithRelations) => {
      if (onViewCase) {
        onViewCase(caseData)
      } else {
        router.push(`/cases/${caseData.id}`)
      }
    },
    [onViewCase, router]
  )

  if (isLoading) {
    return <CaseTableSkeleton />
  }

  if (cases.length === 0) {
    return (
      <div className="text-center py-12 text-muted-foreground">
        <p>케이스가 없습니다.</p>
      </div>
    )
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead className="w-[120px]">케이스 번호</TableHead>
          <TableHead>환자명</TableHead>
          <TableHead>LAB/DOCTOR</TableHead>
          <TableHead className="w-[100px]">개수</TableHead>
          <TableHead className="w-[120px]">마감일</TableHead>
          <TableHead className="w-[140px]">현재 부서</TableHead>
          <TableHead className="w-[100px]">상태</TableHead>
          <TableHead className="w-[50px]"></TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {cases.map((caseData) => {
          const isRecent = recentlyUpdatedIds.has(caseData.id)
          const daysLeft = daysUntilDue(caseData.dueDate)
          const isDelayed = daysLeft < 0 && caseData.status !== 'COMPLETED' && caseData.status !== 'CANCELLED'
          const isUrgent = daysLeft <= 1 && daysLeft >= 0

          return (
            <TableRow
              key={caseData.id}
              className={cn(
                'cursor-pointer',
                isRecent && 'pulse-highlight',
                isDelayed && 'bg-red-50 dark:bg-red-950/20'
              )}
              onClick={() => handleRowClick(caseData)}
            >
              <TableCell className="font-mono text-sm">
                {caseData.caseNumber}
              </TableCell>
              <TableCell className="font-medium">
                {caseData.patientName}
              </TableCell>
              <TableCell>{caseData.labDoctorName}</TableCell>
              <TableCell>{caseData.quantity}</TableCell>
              <TableCell>
                <span
                  className={cn(
                    'text-sm',
                    isDelayed && 'text-red-600 font-medium',
                    isUrgent && 'text-orange-600 font-medium'
                  )}
                >
                  {formatDate(caseData.dueDate)}
                  {isDelayed && (
                    <span className="ml-1 text-xs">
                      ({Math.abs(daysLeft)}일 지연)
                    </span>
                  )}
                  {isUrgent && daysLeft === 0 && (
                    <span className="ml-1 text-xs">(오늘)</span>
                  )}
                  {isUrgent && daysLeft === 1 && (
                    <span className="ml-1 text-xs">(내일)</span>
                  )}
                </span>
              </TableCell>
              <TableCell>
                <DepartmentBadge department={caseData.currentDepartment} />
              </TableCell>
              <TableCell>
                <CaseStatusBadge status={caseData.status} />
              </TableCell>
              <TableCell>
                <DropdownMenu>
                  <DropdownMenuTrigger asChild onClick={(e) => e.stopPropagation()}>
                    <Button variant="ghost" size="icon" className="h-8 w-8">
                      <MoreHorizontal className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem
                      onClick={(e) => {
                        e.stopPropagation()
                        onViewCase?.(caseData)
                      }}
                    >
                      <Eye className="mr-2 h-4 w-4" />
                      상세 보기
                    </DropdownMenuItem>
                    {isAdmin && (
                      <>
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation()
                            onEditCase?.(caseData)
                          }}
                        >
                          <Edit className="mr-2 h-4 w-4" />
                          수정
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation()
                            onAssignCase?.(caseData)
                          }}
                        >
                          <UserPlus className="mr-2 h-4 w-4" />
                          작업자 할당
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation()
                            onDeleteCase?.(caseData)
                          }}
                          className="text-red-600 focus:text-red-600"
                        >
                          <Trash2 className="mr-2 h-4 w-4" />
                          삭제
                        </DropdownMenuItem>
                      </>
                    )}
                  </DropdownMenuContent>
                </DropdownMenu>
              </TableCell>
            </TableRow>
          )
        })}
      </TableBody>
    </Table>
  )
}

function CaseTableSkeleton() {
  return (
    <div className="space-y-3">
      {[1, 2, 3, 4, 5].map((i) => (
        <div
          key={i}
          className="h-12 bg-muted animate-pulse rounded-md"
        />
      ))}
    </div>
  )
}
