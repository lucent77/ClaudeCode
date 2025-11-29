import { PrismaClient } from '@prisma/client'

/**
 * Prisma 클라이언트 싱글톤
 *
 * Next.js 개발 모드에서 핫 리로드로 인한 다중 인스턴스 생성을 방지
 * 프로덕션에서는 단일 인스턴스만 생성
 */

const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined
}

export const prisma =
  globalForPrisma.prisma ??
  new PrismaClient({
    log:
      process.env.NODE_ENV === 'development'
        ? ['query', 'error', 'warn']
        : ['error'],
  })

if (process.env.NODE_ENV !== 'production') {
  globalForPrisma.prisma = prisma
}

export default prisma
