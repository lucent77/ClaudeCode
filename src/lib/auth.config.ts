import type { NextAuthConfig } from 'next-auth'
import type { Role, Department } from '@/types'

/**
 * Auth.js 콜백 설정
 * 미들웨어에서 사용할 수 있도록 분리
 */

export const authConfig: NextAuthConfig = {
  pages: {
    signIn: '/login',
    error: '/login',
  },
  callbacks: {
    authorized({ auth, request: { nextUrl } }) {
      const isLoggedIn = !!auth?.user
      const isOnDashboard =
        nextUrl.pathname.startsWith('/dashboard') ||
        nextUrl.pathname.startsWith('/cases') ||
        nextUrl.pathname.startsWith('/my-tasks') ||
        nextUrl.pathname.startsWith('/users') ||
        nextUrl.pathname.startsWith('/settings')

      if (isOnDashboard) {
        if (isLoggedIn) return true
        return false // 로그인 페이지로 리다이렉트
      } else if (isLoggedIn) {
        // 로그인된 상태에서 로그인 페이지 접근 시 대시보드로 리다이렉트
        if (nextUrl.pathname === '/login') {
          return Response.redirect(new URL('/dashboard', nextUrl))
        }
      }
      return true
    },
    jwt({ token, user }) {
      // 로그인 시 사용자 정보를 JWT에 추가
      if (user) {
        token.id = user.id as string
        token.role = user.role as Role
        token.department = user.department as Department
      }
      return token
    },
    session({ session, token }) {
      // JWT의 정보를 세션에 추가
      if (token) {
        session.user.id = token.id
        session.user.role = token.role
        session.user.department = token.department
      }
      return session
    },
  },
  providers: [], // 실제 프로바이더는 auth.ts에서 설정
}
