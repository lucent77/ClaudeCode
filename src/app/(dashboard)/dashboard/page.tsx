import { Suspense } from 'react'
import { redirect } from 'next/navigation'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'
import { DashboardStats } from './dashboard-stats'
import { DashboardCharts } from './dashboard-charts'
import { DelayedCasesList } from './delayed-cases-list'

/**
 * 관리자 대시보드 페이지
 * 전체 케이스 현황, 부서별 통계, 지연 건 등을 표시
 */
export default async function DashboardPage() {
  const session = await auth()

  // 관리자만 접근 가능
  if (session?.user?.role !== 'ADMIN') {
    redirect('/my-tasks')
  }

  return (
    <div className="flex flex-col">
      <Header title="대시보드" />

      <div className="flex-1 p-6 space-y-6">
        {/* KPI 카드 */}
        <Suspense fallback={<StatsCardsSkeleton />}>
          <DashboardStats />
        </Suspense>

        {/* 차트 영역 */}
        <div className="grid gap-6 md:grid-cols-2">
          <Suspense fallback={<ChartSkeleton title="부서별 작업 현황" />}>
            <DashboardCharts />
          </Suspense>
        </div>

        {/* 지연 케이스 목록 */}
        <Suspense fallback={<DelayedListSkeleton />}>
          <DelayedCasesList />
        </Suspense>
      </div>
    </div>
  )
}

function StatsCardsSkeleton() {
  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="rounded-xl border bg-card p-6 animate-pulse">
          <div className="h-4 w-24 bg-muted rounded" />
          <div className="mt-2 h-8 w-16 bg-muted rounded" />
        </div>
      ))}
    </div>
  )
}

function ChartSkeleton({ title }: { title: string }) {
  return (
    <div className="rounded-xl border bg-card p-6">
      <h3 className="font-semibold mb-4">{title}</h3>
      <div className="h-[300px] flex items-center justify-center bg-muted/20 rounded animate-pulse">
        <span className="text-muted-foreground">로딩 중...</span>
      </div>
    </div>
  )
}

function DelayedListSkeleton() {
  return (
    <div className="rounded-xl border bg-card p-6">
      <h3 className="font-semibold mb-4">지연 케이스</h3>
      <div className="space-y-3">
        {[1, 2, 3].map((i) => (
          <div key={i} className="h-12 bg-muted animate-pulse rounded-md" />
        ))}
      </div>
    </div>
  )
}
