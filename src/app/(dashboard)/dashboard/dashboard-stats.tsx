'use client'

import { useEffect, useState } from 'react'
import {
  ClipboardList,
  Clock,
  CheckCircle,
  AlertTriangle,
  TrendingUp,
  TrendingDown,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'

interface StatsSummary {
  totalCases: number
  pendingCases: number
  inProgressCases: number
  completedCases: number
  delayedCases: number
  completionRate: number
  todayCreated: number
  todayCompleted: number
}

export function DashboardStats() {
  const [stats, setStats] = useState<StatsSummary | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    async function fetchStats() {
      try {
        const response = await fetch('/api/stats')
        const result = await response.json()
        if (result.data?.summary) {
          setStats(result.data.summary)
        }
      } catch (error) {
        console.error('Failed to fetch stats:', error)
      } finally {
        setIsLoading(false)
      }
    }

    fetchStats()
    // 30초마다 자동 새로고침
    const interval = setInterval(fetchStats, 30000)
    return () => clearInterval(interval)
  }, [])

  if (isLoading || !stats) {
    return <StatsCardsSkeleton />
  }

  const cards = [
    {
      title: '전체 케이스',
      value: stats.totalCases,
      icon: ClipboardList,
      description: `오늘 ${stats.todayCreated}건 생성`,
      trend: stats.todayCreated > 0 ? 'up' : 'neutral',
      color: 'text-blue-600',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      title: '진행중',
      value: stats.inProgressCases,
      icon: Clock,
      description: `대기 ${stats.pendingCases}건`,
      trend: 'neutral',
      color: 'text-yellow-600',
      bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
    },
    {
      title: '완료율',
      value: `${stats.completionRate}%`,
      icon: CheckCircle,
      description: `오늘 ${stats.todayCompleted}건 완료`,
      trend: stats.completionRate >= 80 ? 'up' : 'down',
      color: 'text-green-600',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      title: '지연 건',
      value: stats.delayedCases,
      icon: AlertTriangle,
      description: '마감일 초과',
      trend: stats.delayedCases > 0 ? 'down' : 'up',
      color: 'text-red-600',
      bgColor: 'bg-red-100 dark:bg-red-900/30',
    },
  ]

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {cards.map((card) => (
        <Card key={card.title}>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              {card.title}
            </CardTitle>
            <div className={cn('p-2 rounded-lg', card.bgColor)}>
              <card.icon className={cn('h-4 w-4', card.color)} />
            </div>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{card.value}</div>
            <div className="flex items-center text-xs text-muted-foreground mt-1">
              {card.trend === 'up' && (
                <TrendingUp className="mr-1 h-3 w-3 text-green-500" />
              )}
              {card.trend === 'down' && (
                <TrendingDown className="mr-1 h-3 w-3 text-red-500" />
              )}
              {card.description}
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

function StatsCardsSkeleton() {
  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {[1, 2, 3, 4].map((i) => (
        <Card key={i}>
          <CardHeader className="pb-2">
            <div className="h-4 w-24 bg-muted animate-pulse rounded" />
          </CardHeader>
          <CardContent>
            <div className="h-8 w-16 bg-muted animate-pulse rounded" />
            <div className="mt-2 h-3 w-32 bg-muted animate-pulse rounded" />
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
