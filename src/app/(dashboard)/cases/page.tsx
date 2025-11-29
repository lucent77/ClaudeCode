import { Suspense } from 'react'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'
import { Button } from '@/components/ui/button'
import { Plus } from 'lucide-react'
import Link from 'next/link'

/**
 * 케이스 목록 페이지
 * 작업자: 본인 부서 케이스만 표시
 * 관리자: 전체 케이스 + 부서 필터
 */
export default async function CasesPage() {
  const session = await auth()
  const isAdmin = session?.user?.role === 'ADMIN'

  return (
    <div className="flex flex-col">
      <Header title="케이스 관리" />

      <div className="flex-1 p-6 space-y-6">
        {/* 액션 바 */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            {/* 필터 (추후 구현) */}
            <div className="text-sm text-muted-foreground">
              {isAdmin ? '전체 케이스' : `${session?.user?.department} 케이스`}
            </div>
          </div>

          {isAdmin && (
            <Link href="/cases/new">
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                새 케이스
              </Button>
            </Link>
          )}
        </div>

        {/* 케이스 테이블 */}
        <div className="rounded-xl border bg-card">
          <div className="p-4">
            <div className="text-center py-8 text-muted-foreground">
              케이스가 없습니다.
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
