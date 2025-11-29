import { handlers } from '@/lib/auth'

/**
 * NextAuth.js API 라우트 핸들러
 * /api/auth/* 경로의 모든 요청을 처리
 */

export const { GET, POST } = handlers
