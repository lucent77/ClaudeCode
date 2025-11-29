import NextAuth from 'next-auth'
import { authConfig } from '@/lib/auth.config'

/**
 * Next.js 미들웨어
 * Auth.js를 사용하여 인증이 필요한 라우트를 보호
 */

export default NextAuth(authConfig).auth

export const config = {
  // 인증이 필요한 경로 패턴
  matcher: [
    /*
     * 다음 경로를 제외한 모든 경로에 매칭:
     * - api/auth (NextAuth.js API 라우트)
     * - _next/static (정적 파일)
     * - _next/image (이미지 최적화 파일)
     * - favicon.ico, sitemap.xml, robots.txt (SEO 파일)
     * - 공개 이미지/에셋
     */
    '/((?!api/auth|_next/static|_next/image|favicon.ico|sitemap.xml|robots.txt|.*\\.png$|.*\\.jpg$|.*\\.svg$).*)',
  ],
}
