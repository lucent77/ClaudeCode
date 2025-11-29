import { Suspense } from 'react'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'
import { MyTasksClient } from './my-tasks-client'

/**
 * 마이 태스크 페이지
 * 본인에게 할당된 작업 목록을 표시
 */
export default async function MyTasksPage() {
  const session = await auth()

  return (
    <div className="flex flex-col">
      <Header title="마이 태스크" />

      <div className="flex-1 p-6">
        <Suspense fallback={<MyTasksSkeleton />}>
          <MyTasksClient
            userName={session?.user?.name || ''}
            userDepartment={session?.user?.department}
          />
        </Suspense>
      </div>
    </div>
  )
}

function MyTasksSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-3">
        {[1, 2, 3].map((i) => (
          <div key={i} className="rounded-xl border bg-card p-6 animate-pulse">
            <div className="h-4 w-24 bg-muted rounded" />
            <div className="mt-2 h-10 w-16 bg-muted rounded" />
          </div>
        ))}
      </div>
      <div className="rounded-xl border bg-card p-4 space-y-3">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="h-20 bg-muted animate-pulse rounded-md" />
        ))}
      </div>
    </div>
  )
}
