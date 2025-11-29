import { redirect } from 'next/navigation'
import { auth } from '@/lib/auth'
import { Sidebar } from '@/components/layout/sidebar'
import { Toaster } from '@/components/ui/toaster'

/**
 * 대시보드 레이아웃
 * 인증이 필요한 모든 페이지의 공통 레이아웃
 */
export default async function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const session = await auth()

  // 미인증 사용자는 로그인 페이지로 리다이렉트
  if (!session?.user) {
    redirect('/login')
  }

  return (
    <div className="min-h-screen bg-background">
      {/* 사이드바 */}
      <Sidebar />

      {/* 메인 콘텐츠 */}
      <div className="pl-64">
        <main className="min-h-screen">{children}</main>
      </div>

      {/* 토스트 알림 */}
      <Toaster />
    </div>
  )
}
