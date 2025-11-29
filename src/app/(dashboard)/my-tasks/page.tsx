import { Suspense } from 'react'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'

/**
 * 마이 태스크 페이지
 * 본인에게 할당된 작업 목록을 표시
 */
export default async function MyTasksPage() {
  const session = await auth()

  return (
    <div className="flex flex-col">
      <Header title="마이 태스크" />

      <div className="flex-1 p-6 space-y-6">
        {/* 오늘의 요약 */}
        <div className="grid gap-4 md:grid-cols-3">
          <SummaryCard
            title="오늘 할 일"
            value="-"
            description="오늘 마감 또는 긴급 작업"
            color="blue"
          />
          <SummaryCard
            title="진행 중"
            value="-"
            description="현재 담당 중인 작업"
            color="yellow"
          />
          <SummaryCard
            title="오늘 완료"
            value="-"
            description="오늘 완료한 작업"
            color="green"
          />
        </div>

        {/* 작업 목록 */}
        <div className="rounded-xl border bg-card">
          <div className="p-4 border-b">
            <h3 className="font-semibold">내 작업 목록</h3>
          </div>
          <div className="p-4">
            <div className="text-center py-8 text-muted-foreground">
              할당된 작업이 없습니다.
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

function SummaryCard({
  title,
  value,
  description,
  color,
}: {
  title: string
  value: string
  description: string
  color: 'blue' | 'yellow' | 'green' | 'red'
}) {
  const colorClasses = {
    blue: 'border-l-blue-500',
    yellow: 'border-l-yellow-500',
    green: 'border-l-green-500',
    red: 'border-l-red-500',
  }

  return (
    <div
      className={`rounded-xl border bg-card p-6 border-l-4 ${colorClasses[color]}`}
    >
      <h3 className="text-sm font-medium text-muted-foreground">{title}</h3>
      <div className="mt-2">
        <span className="text-3xl font-bold">{value}</span>
      </div>
      <p className="mt-1 text-xs text-muted-foreground">{description}</p>
    </div>
  )
}
