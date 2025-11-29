import NextAuth from 'next-auth'
import Credentials from 'next-auth/providers/credentials'
import { PrismaAdapter } from '@auth/prisma-adapter'
import bcrypt from 'bcryptjs'
import { z } from 'zod'

import { prisma } from '@/lib/prisma'
import { authConfig } from '@/lib/auth.config'

/**
 * 로그인 입력 스키마
 */
const loginSchema = z.object({
  email: z.string().email('유효한 이메일을 입력하세요'),
  password: z.string().min(6, '비밀번호는 6자 이상이어야 합니다'),
})

/**
 * Auth.js 설정
 */
export const { handlers, auth, signIn, signOut } = NextAuth({
  ...authConfig,
  adapter: PrismaAdapter(prisma),
  session: {
    strategy: 'jwt',
    maxAge: 30 * 24 * 60 * 60, // 30일
  },
  providers: [
    Credentials({
      name: 'credentials',
      credentials: {
        email: { label: '이메일', type: 'email' },
        password: { label: '비밀번호', type: 'password' },
      },
      async authorize(credentials) {
        // 입력값 검증
        const parsed = loginSchema.safeParse(credentials)
        if (!parsed.success) {
          return null
        }

        const { email, password } = parsed.data

        // 사용자 조회
        const user = await prisma.user.findUnique({
          where: { email },
          select: {
            id: true,
            email: true,
            name: true,
            password: true,
            role: true,
            department: true,
            isActive: true,
          },
        })

        if (!user) {
          return null
        }

        // 비활성 사용자 체크
        if (!user.isActive) {
          throw new Error('비활성화된 계정입니다. 관리자에게 문의하세요.')
        }

        // 비밀번호 검증
        const isValidPassword = await bcrypt.compare(password, user.password)
        if (!isValidPassword) {
          return null
        }

        // 인증 성공
        return {
          id: user.id,
          email: user.email,
          name: user.name,
          role: user.role,
          department: user.department,
        }
      },
    }),
  ],
})
