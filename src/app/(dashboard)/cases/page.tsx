import { Suspense } from 'react'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'
import { CaseListClient } from './case-list-client'

/**
 * 케이스 목록 페이지
 * 작업자: 본인 부서 케이스만 표시
 * 관리자: 전체 케이스 + 부서 필터
 */
export default async function CasesPage() {
  const session = await auth()
  const isAdmin = session?.user?.role === 'ADMIN'
  const userDepartment = session?.user?.department

  return (
    <div className="flex flex-col">
      <Header title="케이스 관리" />

      <div className="flex-1 p-6">
        <Suspense fallback={<CaseListSkeleton />}>
          <CaseListClient
            isAdmin={isAdmin}
            userDepartment={userDepartment}
          />
        </Suspense>
      </div>
    </div>
  )
}

function CaseListSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="h-10 w-96 bg-muted animate-pulse rounded-md" />
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
