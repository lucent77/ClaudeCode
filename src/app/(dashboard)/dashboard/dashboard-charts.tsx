'use client'

import { useEffect, useState } from 'react'
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  LineChart,
  Line,
  Legend,
} from 'recharts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DEPARTMENT_LABELS } from '@/lib/utils'

interface DepartmentStat {
  department: string
  totalCases: number
  pendingCases: number
  inProgressCases: number
  completedCases: number
}

interface DailyStat {
  date: string
  created: number
  completed: number
}

export function DashboardCharts() {
  const [departmentStats, setDepartmentStats] = useState<DepartmentStat[]>([])
  const [dailyStats, setDailyStats] = useState<DailyStat[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    async function fetchStats() {
      try {
        const response = await fetch('/api/stats')
        const result = await response.json()
        if (result.data) {
          setDepartmentStats(result.data.departmentStats || [])
          setDailyStats(result.data.dailyStats || [])
        }
      } catch (error) {
        console.error('Failed to fetch stats:', error)
      } finally {
        setIsLoading(false)
      }
    }

    fetchStats()
  }, [])

  // 부서별 데이터 변환
  const deptChartData = departmentStats
    .filter((d) => d.totalCases > 0)
    .map((d) => ({
      name: DEPARTMENT_LABELS[d.department] || d.department,
      대기: d.pendingCases,
      진행중: d.inProgressCases,
      완료: d.completedCases,
    }))

  // 일별 데이터 변환
  const dailyChartData = dailyStats.map((d) => ({
    name: d.date.slice(5), // MM-DD 형식
    생성: d.created,
    완료: d.completed,
  }))

  if (isLoading) {
    return (
      <>
        <ChartSkeleton title="부서별 작업 현황" />
        <ChartSkeleton title="일별 추이 (최근 7일)" />
      </>
    )
  }

  return (
    <>
      {/* 부서별 작업 현황 */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">부서별 작업 현황</CardTitle>
        </CardHeader>
        <CardContent>
          {deptChartData.length > 0 ? (
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={deptChartData}>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis
                  dataKey="name"
                  tick={{ fontSize: 11 }}
                  angle={-45}
                  textAnchor="end"
                  height={80}
                />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: '8px',
                  }}
                />
                <Legend />
                <Bar dataKey="대기" fill="#f59e0b" stackId="a" />
                <Bar dataKey="진행중" fill="#3b82f6" stackId="a" />
                <Bar dataKey="완료" fill="#22c55e" stackId="a" />
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-[300px] flex items-center justify-center text-muted-foreground">
              데이터가 없습니다
            </div>
          )}
        </CardContent>
      </Card>

      {/* 일별 추이 */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">일별 추이 (최근 7일)</CardTitle>
        </CardHeader>
        <CardContent>
          {dailyChartData.length > 0 ? (
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={dailyChartData}>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: '8px',
                  }}
                />
                <Legend />
                <Line
                  type="monotone"
                  dataKey="생성"
                  stroke="#3b82f6"
                  strokeWidth={2}
                  dot={{ fill: '#3b82f6' }}
                />
                <Line
                  type="monotone"
                  dataKey="완료"
                  stroke="#22c55e"
                  strokeWidth={2}
                  dot={{ fill: '#22c55e' }}
                />
              </LineChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-[300px] flex items-center justify-center text-muted-foreground">
              데이터가 없습니다
            </div>
          )}
        </CardContent>
      </Card>
    </>
  )
}

function ChartSkeleton({ title }: { title: string }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="h-[300px] flex items-center justify-center bg-muted/20 rounded animate-pulse">
          <span className="text-muted-foreground">로딩 중...</span>
        </div>
      </CardContent>
    </Card>
  )
}
