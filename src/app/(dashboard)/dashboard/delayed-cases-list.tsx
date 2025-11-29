'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { AlertTriangle } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { CaseStatusBadge, DepartmentBadge } from '@/components/cases/case-status-badge'
import { formatDate, calculateDelayDays } from '@/lib/utils'
import type { CaseStatus, Department } from '@/types'

interface DelayedCase {
  id: string
  caseNumber: string
  patientName: string
  labDoctorName: string
  dueDate: string
  currentDepartment: Department
  status: CaseStatus
}

export function DelayedCasesList() {
  const [cases, setCases] = useState<DelayedCase[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    async function fetchDelayedCases() {
      try {
        const response = await fetch('/api/stats')
        const result = await response.json()
        if (result.data?.delayedCasesList) {
          setCases(result.data.delayedCasesList)
        }
      } catch (error) {
        console.error('Failed to fetch delayed cases:', error)
      } finally {
        setIsLoading(false)
      }
    }

    fetchDelayedCases()
  }, [])

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-base flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-red-500" />
            지연 케이스
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-12 bg-muted animate-pulse rounded-md" />
            ))}
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base flex items-center gap-2">
          <AlertTriangle className="h-5 w-5 text-red-500" />
          지연 케이스
          {cases.length > 0 && (
            <span className="ml-2 text-sm font-normal text-muted-foreground">
              ({cases.length}건)
            </span>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent>
        {cases.length === 0 ? (
          <div className="text-center py-8 text-muted-foreground">
            <p>지연된 케이스가 없습니다.</p>
          </div>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>케이스 번호</TableHead>
                <TableHead>환자명</TableHead>
                <TableHead>LAB/DOCTOR</TableHead>
                <TableHead>마감일</TableHead>
                <TableHead>지연 일수</TableHead>
                <TableHead>현재 부서</TableHead>
                <TableHead>상태</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {cases.map((caseData) => {
                const delayDays = calculateDelayDays(caseData.dueDate)

                return (
                  <TableRow
                    key={caseData.id}
                    className="bg-red-50/50 dark:bg-red-950/20"
                  >
                    <TableCell>
                      <Link
                        href={`/cases/${caseData.id}`}
                        className="font-mono text-sm hover:underline text-blue-600"
                      >
                        {caseData.caseNumber}
                      </Link>
                    </TableCell>
                    <TableCell className="font-medium">
                      {caseData.patientName}
                    </TableCell>
                    <TableCell>{caseData.labDoctorName}</TableCell>
                    <TableCell>{formatDate(caseData.dueDate)}</TableCell>
                    <TableCell>
                      <span className="text-red-600 font-medium">
                        {delayDays}일 지연
                      </span>
                    </TableCell>
                    <TableCell>
                      <DepartmentBadge department={caseData.currentDepartment} />
                    </TableCell>
                    <TableCell>
                      <CaseStatusBadge status={caseData.status} />
                    </TableCell>
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  )
}
