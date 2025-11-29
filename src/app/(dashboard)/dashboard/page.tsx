import { Suspense } from 'react'
import { redirect } from 'next/navigation'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'

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
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Suspense fallback={<StatsCardSkeleton />}>
            <StatsCards />
          </Suspense>
        </div>

        {/* 차트 영역 */}
        <div className="grid gap-6 md:grid-cols-2">
          {/* 부서별 작업 현황 */}
          <div className="rounded-xl border bg-card p-6">
            <h3 className="font-semibold mb-4">부서별 작업 현황</h3>
            <div className="h-[300px] flex items-center justify-center text-muted-foreground">
              차트 컴포넌트 (Recharts)
            </div>
          </div>

          {/* 일별 완료 추이 */}
          <div className="rounded-xl border bg-card p-6">
            <h3 className="font-semibold mb-4">일별 완료 추이</h3>
            <div className="h-[300px] flex items-center justify-center text-muted-foreground">
              차트 컴포넌트 (Recharts)
            </div>
          </div>
        </div>

        {/* 지연 케이스 목록 */}
        <div className="rounded-xl border bg-card p-6">
          <h3 className="font-semibold mb-4">지연 케이스</h3>
          <div className="text-muted-foreground">
            지연 케이스 테이블
          </div>
        </div>
      </div>
    </div>
  )
}

// 임시 통계 카드 컴포넌트
function StatsCards() {
  const stats = [
    { title: '전체 케이스', value: '-', change: '', color: 'blue' },
    { title: '진행중', value: '-', change: '', color: 'yellow' },
    { title: '완료율', value: '-', change: '', color: 'green' },
    { title: '지연 건', value: '-', change: '', color: 'red' },
  ]

  return (
    <>
      {stats.map((stat) => (
        <div
          key={stat.title}
          className="rounded-xl border bg-card p-6"
        >
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-medium text-muted-foreground">
              {stat.title}
            </h3>
          </div>
          <div className="mt-2">
            <span className="text-2xl font-bold">{stat.value}</span>
            {stat.change && (
              <span className="ml-2 text-sm text-muted-foreground">
                {stat.change}
              </span>
            )}
          </div>
        </div>
      ))}
    </>
  )
}

function StatsCardSkeleton() {
  return (
    <>
      {[1, 2, 3, 4].map((i) => (
        <div
          key={i}
          className="rounded-xl border bg-card p-6 animate-pulse"
        >
          <div className="h-4 w-24 bg-muted rounded" />
          <div className="mt-2 h-8 w-16 bg-muted rounded" />
        </div>
      ))}
    </>
  )
}
