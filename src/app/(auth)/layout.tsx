/**
 * 인증 페이지 레이아웃
 * 로그인, 비밀번호 찾기 등 인증 관련 페이지에 사용
 */

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-900 dark:to-gray-800">
      <div className="w-full max-w-md px-4">
        {/* 로고 및 브랜딩 */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-primary-600 dark:text-primary-400">
            DentalFlow
          </h1>
          <p className="text-muted-foreground mt-2">
            치과 기공소 작업 관리 시스템
          </p>
        </div>

        {/* 인증 폼 컨테이너 */}
        {children}

        {/* 푸터 */}
        <div className="text-center mt-8 text-sm text-muted-foreground">
          <p>&copy; {new Date().getFullYear()} DentalFlow. All rights reserved.</p>
        </div>
      </div>
    </div>
  )
}
