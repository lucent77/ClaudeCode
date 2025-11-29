'use client'

import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { CaseStatus } from '@/types'

interface CaseStatusBadgeProps {
  status: CaseStatus
  className?: string
}

const statusConfig: Record<
  CaseStatus,
  { label: string; variant: 'pending' | 'inProgress' | 'completed' | 'onHold' | 'cancelled' }
> = {
  PENDING: { label: '대기중', variant: 'pending' },
  IN_PROGRESS: { label: '진행중', variant: 'inProgress' },
  COMPLETED: { label: '완료', variant: 'completed' },
  ON_HOLD: { label: '보류', variant: 'onHold' },
  CANCELLED: { label: '취소', variant: 'cancelled' },
}

export function CaseStatusBadge({ status, className }: CaseStatusBadgeProps) {
  const config = statusConfig[status]

  return (
    <Badge variant={config.variant} className={cn(className)}>
      {config.label}
    </Badge>
  )
}

export function DepartmentBadge({
  department,
  className,
}: {
  department: string
  className?: string
}) {
  const departmentLabels: Record<string, string> = {
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

  return (
    <Badge variant="outline" className={cn(className)}>
      {departmentLabels[department] || department}
    </Badge>
  )
}
