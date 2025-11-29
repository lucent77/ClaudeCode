import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import bcrypt from 'bcryptjs'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { Role, Department } from '@prisma/client'

/**
 * 사용자 생성 스키마
 */
const createUserSchema = z.object({
  email: z.string().email('유효한 이메일을 입력하세요'),
  password: z.string().min(6, '비밀번호는 6자 이상이어야 합니다'),
  name: z.string().min(1, '이름을 입력하세요'),
  role: z.nativeEnum(Role),
  department: z.nativeEnum(Department),
})

/**
 * GET /api/users
 * 사용자 목록 조회 (관리자 전용)
 */
export async function GET(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    if (session.user.role !== 'ADMIN') {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    const { searchParams } = new URL(request.url)
    const department = searchParams.get('department') as Department | null
    const role = searchParams.get('role') as Role | null
    const search = searchParams.get('search')

    const where: Record<string, unknown> = {}

    if (department) {
      where.department = department
    }

    if (role) {
      where.role = role
    }

    if (search) {
      where.OR = [
        { name: { contains: search } },
        { email: { contains: search } },
      ]
    }

    const users = await prisma.user.findMany({
      where,
      select: {
        id: true,
        email: true,
        name: true,
        role: true,
        department: true,
        isActive: true,
        createdAt: true,
        updatedAt: true,
        _count: {
          select: {
            assignedTasks: true,
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    })

    return NextResponse.json({ data: users })
  } catch (error) {
    console.error('GET /api/users error:', error)
    return NextResponse.json(
      { error: '사용자 목록을 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * POST /api/users
 * 사용자 생성 (관리자 전용)
 */
export async function POST(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    if (session.user.role !== 'ADMIN') {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    const body = await request.json()
    const parsed = createUserSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    const { email, password, name, role, department } = parsed.data

    // 이메일 중복 확인
    const existingUser = await prisma.user.findUnique({
      where: { email },
    })

    if (existingUser) {
      return NextResponse.json(
        { error: '이미 등록된 이메일입니다' },
        { status: 400 }
      )
    }

    // 비밀번호 해싱
    const hashedPassword = await bcrypt.hash(password, 10)

    // 사용자 생성
    const user = await prisma.user.create({
      data: {
        email,
        password: hashedPassword,
        name,
        role,
        department,
      },
      select: {
        id: true,
        email: true,
        name: true,
        role: true,
        department: true,
        isActive: true,
        createdAt: true,
      },
    })

    return NextResponse.json({ data: user }, { status: 201 })
  } catch (error) {
    console.error('POST /api/users error:', error)
    return NextResponse.json(
      { error: '사용자 생성에 실패했습니다' },
      { status: 500 }
    )
  }
}
