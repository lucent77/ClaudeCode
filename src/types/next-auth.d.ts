import { DefaultSession, DefaultUser } from 'next-auth'
import { JWT } from 'next-auth/jwt'
import { Role, Department } from '@/types'

/**
 * NextAuth.js 타입 확장
 * role과 department 필드를 Session과 JWT에 추가
 */

declare module 'next-auth' {
  interface Session {
    user: {
      id: string
      role: Role
      department: Department
    } & DefaultSession['user']
  }

  interface User extends DefaultUser {
    role: Role
    department: Department
  }
}

declare module 'next-auth/jwt' {
  interface JWT {
    id: string
    role: Role
    department: Department
  }
}
