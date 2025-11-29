import type { Metadata } from 'next'
import { SessionProvider } from 'next-auth/react'
import './globals.css'

export const metadata: Metadata = {
  title: {
    default: 'DentalFlow - 치과 기공소 작업 관리 시스템',
    template: '%s | DentalFlow',
  },
  description: '치과 기공소의 CADCAM 작업을 부서별로 실시간 추적하고 관리하는 웹 기반 시스템',
  keywords: ['치과', '기공소', 'CADCAM', '작업관리', '덴탈', '보철물'],
  authors: [{ name: 'DentalFlow Team' }],
  robots: {
    index: false,
    follow: false,
  },
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html lang="ko" suppressHydrationWarning>
      <body className="min-h-screen bg-background font-sans antialiased">
        <SessionProvider>{children}</SessionProvider>
      </body>
    </html>
  )
}
