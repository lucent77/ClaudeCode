import { Suspense } from 'react'
import { redirect } from 'next/navigation'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'
import { UsersClient } from './users-client'

/**
 * 사용자 관리 페이지
 * 관리자만 접근 가능
 */
export default async function UsersPage() {
  const session = await auth()

  if (session?.user?.role !== 'ADMIN') {
    redirect('/my-tasks')
  }

  return (
    <div className="flex flex-col">
      <Header title="사용자 관리" />

      <div className="flex-1 p-6">
        <Suspense fallback={<UsersSkeleton />}>
          <UsersClient />
        </Suspense>
      </div>
    </div>
  )
}

function UsersSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="h-10 w-64 bg-muted animate-pulse rounded-md" />
        <div className="h-10 w-32 bg-muted animate-pulse rounded-md" />
      </div>
      <div className="rounded-xl border bg-card p-4 space-y-3">
        {[1, 2, 3, 4, 5].map((i) => (
          <div key={i} className="h-12 bg-muted animate-pulse rounded-md" />
        ))}
      </div>
    </div>
  )
}
