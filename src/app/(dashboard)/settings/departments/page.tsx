'use client'

import { useEffect, useState } from 'react'
import { Header } from '@/components/layout/header'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { DEPARTMENT_LABELS } from '@/lib/utils'
import type { Department } from '@/types'

interface DepartmentStat {
  department: Department
  taskTypeCount: number
  customFieldCount: number
  customStatusCount: number
  userCount: number
}

export default function DepartmentsSettingsPage() {
  const [stats, setStats] = useState<DepartmentStat[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    async function fetchStats() {
      try {
        const response = await fetch('/api/departments')
        const result = await response.json()
        if (result.data) {
          setStats(result.data)
        }
      } catch (error) {
        console.error('Failed to fetch department stats:', error)
      } finally {
        setIsLoading(false)
      }
    }

    fetchStats()
  }, [])

  return (
    <div className="flex flex-col">
      <Header title="부서 설정" />

      <div className="flex-1 p-6">
        <div className="mb-6">
          <p className="text-muted-foreground">
            각 부서의 작업 유형, 커스텀 필드, 상태를 관리합니다.
          </p>
        </div>

        {isLoading ? (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <Card key={i} className="animate-pulse">
                <CardHeader>
                  <div className="h-5 w-32 bg-muted rounded" />
                </CardHeader>
                <CardContent>
                  <div className="h-4 w-full bg-muted rounded" />
                </CardContent>
              </Card>
            ))}
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {stats.map((stat) => (
              <Card key={stat.department} className="hover:shadow-md transition-shadow">
                <CardHeader>
                  <CardTitle className="text-base flex items-center justify-between">
                    {DEPARTMENT_LABELS[stat.department]}
                    <Badge variant="secondary">{stat.userCount}명</Badge>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">작업 유형</span>
                      <span>{stat.taskTypeCount}개</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">커스텀 필드</span>
                      <span>{stat.customFieldCount}개</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">커스텀 상태</span>
                      <span>{stat.customStatusCount}개</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
