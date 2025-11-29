'use client'

import { useEffect, useState, useCallback } from 'react'
import Link from 'next/link'
import {
  Clock,
  Play,
  CheckCircle,
  Calendar,
  AlertTriangle,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CaseStatusBadge } from '@/components/cases/case-status-badge'
import { useToast } from '@/hooks/use-toast'
import { formatDate, daysUntilDue, cn, DEPARTMENT_LABELS } from '@/lib/utils'
import type { CaseStatus, Department } from '@/types'

interface Assignment {
  id: string
  caseId: string
  department: Department
  status: CaseStatus
  assignedAt: string
  startedAt: string | null
  completedAt: string | null
  case: {
    id: string
    caseNumber: string
    patientName: string
    labDoctorName: string
    dueDate: string
    status: CaseStatus
    currentDepartment: Department
    quantity: number
    noteText: string | null
  }
  assignedBy: {
    id: string
    name: string
  }
}

interface Summary {
  today: number
  inProgress: number
  pending: number
  completed: number
  todayCompleted: number
}

interface MyTasksClientProps {
  userName: string
  userDepartment?: Department
}

export function MyTasksClient({ userName, userDepartment }: MyTasksClientProps) {
  const { toast } = useToast()
  const [assignments, setAssignments] = useState<Assignment[]>([])
  const [summary, setSummary] = useState<Summary>({
    today: 0,
    inProgress: 0,
    pending: 0,
    completed: 0,
    todayCompleted: 0,
  })
  const [filter, setFilter] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  const fetchTasks = useCallback(async () => {
    try {
      const params = new URLSearchParams()
      if (filter) params.set('filter', filter)

      const response = await fetch(`/api/my-tasks?${params.toString()}`)
      const result = await response.json()

      if (result.data) {
        setAssignments(result.data)
      }
      if (result.summary) {
        setSummary(result.summary)
      }
    } catch (error) {
      console.error('Failed to fetch tasks:', error)
    } finally {
      setIsLoading(false)
    }
  }, [filter])

  useEffect(() => {
    fetchTasks()
  }, [fetchTasks])

  const handleStartTask = async (assignment: Assignment) => {
    try {
      const response = await fetch(`/api/cases/${assignment.caseId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          status: 'IN_PROGRESS',
          version: 1, // TODO: 실제 버전 사용
        }),
      })

      if (response.ok) {
        toast({ title: '작업을 시작했습니다' })
        fetchTasks()
      }
    } catch (error) {
      toast({
        title: '오류',
        description: '작업 시작에 실패했습니다',
        variant: 'destructive',
      })
    }
  }

  const handleCompleteTask = async (assignment: Assignment) => {
    try {
      const response = await fetch(`/api/cases/${assignment.caseId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          status: 'COMPLETED',
          version: 1,
        }),
      })

      if (response.ok) {
        toast({ title: '작업을 완료했습니다' })
        fetchTasks()
      }
    } catch (error) {
      toast({
        title: '오류',
        description: '작업 완료에 실패했습니다',
        variant: 'destructive',
      })
    }
  }

  const summaryCards = [
    {
      title: '오늘 할 일',
      value: summary.today,
      icon: Calendar,
      color: 'border-l-blue-500',
      filter: 'today',
    },
    {
      title: '진행 중',
      value: summary.inProgress,
      icon: Play,
      color: 'border-l-yellow-500',
      filter: 'inProgress',
    },
    {
      title: '오늘 완료',
      value: summary.todayCompleted,
      icon: CheckCircle,
      color: 'border-l-green-500',
      filter: 'completed',
    },
  ]

  return (
    <div className="space-y-6">
      {/* 환영 메시지 */}
      <div>
        <h2 className="text-lg font-medium">
          안녕하세요, {userName}님!
        </h2>
        <p className="text-sm text-muted-foreground">
          {userDepartment && DEPARTMENT_LABELS[userDepartment]} 부서
        </p>
      </div>

      {/* 요약 카드 */}
      <div className="grid gap-4 md:grid-cols-3">
        {summaryCards.map((card) => (
          <Card
            key={card.title}
            className={cn(
              'border-l-4 cursor-pointer transition-shadow hover:shadow-md',
              card.color,
              filter === card.filter && 'ring-2 ring-primary'
            )}
            onClick={() => setFilter(filter === card.filter ? null : card.filter)}
          >
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                {card.title}
              </CardTitle>
              <card.icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-3xl font-bold">{card.value}</div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* 작업 목록 */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-base">
              {filter === 'today' && '오늘 할 일'}
              {filter === 'inProgress' && '진행 중인 작업'}
              {filter === 'completed' && '완료한 작업'}
              {!filter && '내 작업 목록'}
            </CardTitle>
            {filter && (
              <Button variant="ghost" size="sm" onClick={() => setFilter(null)}>
                전체 보기
              </Button>
            )}
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-3">
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-20 bg-muted animate-pulse rounded-md" />
              ))}
            </div>
          ) : assignments.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <Clock className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>할당된 작업이 없습니다.</p>
            </div>
          ) : (
            <div className="space-y-3">
              {assignments.map((assignment) => {
                const daysLeft = daysUntilDue(assignment.case.dueDate)
                const isDelayed = daysLeft < 0
                const isUrgent = daysLeft <= 1 && daysLeft >= 0

                return (
                  <div
                    key={assignment.id}
                    className={cn(
                      'p-4 rounded-lg border transition-colors',
                      isDelayed && 'border-red-200 bg-red-50 dark:bg-red-950/20',
                      isUrgent && 'border-orange-200 bg-orange-50 dark:bg-orange-950/20'
                    )}
                  >
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                          <Link
                            href={`/cases/${assignment.case.id}`}
                            className="font-mono text-sm text-blue-600 hover:underline"
                          >
                            {assignment.case.caseNumber}
                          </Link>
                          <CaseStatusBadge status={assignment.status} />
                          {isDelayed && (
                            <Badge variant="destructive" className="text-xs">
                              <AlertTriangle className="h-3 w-3 mr-1" />
                              {Math.abs(daysLeft)}일 지연
                            </Badge>
                          )}
                          {isUrgent && !isDelayed && (
                            <Badge variant="outline" className="text-xs text-orange-600">
                              {daysLeft === 0 ? '오늘 마감' : '내일 마감'}
                            </Badge>
                          )}
                        </div>
                        <p className="font-medium truncate">
                          {assignment.case.patientName}
                        </p>
                        <p className="text-sm text-muted-foreground">
                          {assignment.case.labDoctorName} | 수량: {assignment.case.quantity}
                        </p>
                        <p className="text-xs text-muted-foreground mt-1">
                          마감: {formatDate(assignment.case.dueDate)}
                        </p>
                      </div>
                      <div className="flex gap-2">
                        {assignment.status === 'PENDING' && (
                          <Button
                            size="sm"
                            onClick={() => handleStartTask(assignment)}
                          >
                            <Play className="h-4 w-4 mr-1" />
                            시작
                          </Button>
                        )}
                        {assignment.status === 'IN_PROGRESS' && (
                          <Button
                            size="sm"
                            variant="default"
                            onClick={() => handleCompleteTask(assignment)}
                          >
                            <CheckCircle className="h-4 w-4 mr-1" />
                            완료
                          </Button>
                        )}
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
