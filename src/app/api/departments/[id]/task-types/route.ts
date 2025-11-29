import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { Department } from '@prisma/client'

const taskTypeSchema = z.object({
  name: z.string().min(1, '이름을 입력하세요'),
  description: z.string().optional(),
  color: z.string().regex(/^#[0-9A-Fa-f]{6}$/, '유효한 HEX 색상을 입력하세요').optional(),
})

const reorderSchema = z.object({
  items: z.array(z.object({
    id: z.string(),
    sortOrder: z.number(),
  })),
})

interface RouteParams {
  params: Promise<{ id: string }>
}

/**
 * GET /api/departments/[id]/task-types
 * 부서별 작업 유형 목록 조회
 */
export async function GET(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id: department } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    // 유효한 부서인지 확인
    if (!Object.values(Department).includes(department as Department)) {
      return NextResponse.json({ error: '유효하지 않은 부서입니다' }, { status: 400 })
    }

    const taskTypes = await prisma.taskType.findMany({
      where: { department: department as Department },
      orderBy: { sortOrder: 'asc' },
    })

    return NextResponse.json({ data: taskTypes })
  } catch (error) {
    console.error('GET /api/departments/[id]/task-types error:', error)
    return NextResponse.json(
      { error: '작업 유형을 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * POST /api/departments/[id]/task-types
 * 작업 유형 생성 (관리자 전용)
 */
export async function POST(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id: department } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    if (session.user.role !== 'ADMIN') {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    if (!Object.values(Department).includes(department as Department)) {
      return NextResponse.json({ error: '유효하지 않은 부서입니다' }, { status: 400 })
    }

    const body = await request.json()
    const parsed = taskTypeSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    // 최대 sortOrder 조회
    const maxOrder = await prisma.taskType.aggregate({
      where: { department: department as Department },
      _max: { sortOrder: true },
    })

    const taskType = await prisma.taskType.create({
      data: {
        department: department as Department,
        name: parsed.data.name,
        description: parsed.data.description,
        color: parsed.data.color,
        sortOrder: (maxOrder._max.sortOrder || 0) + 1,
      },
    })

    return NextResponse.json({ data: taskType }, { status: 201 })
  } catch (error) {
    console.error('POST /api/departments/[id]/task-types error:', error)
    return NextResponse.json(
      { error: '작업 유형 생성에 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * PUT /api/departments/[id]/task-types
 * 작업 유형 순서 변경 (관리자 전용)
 */
export async function PUT(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id: department } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    if (session.user.role !== 'ADMIN') {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    const body = await request.json()
    const parsed = reorderSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다' },
        { status: 400 }
      )
    }

    // 트랜잭션으로 순서 업데이트
    await prisma.$transaction(
      parsed.data.items.map((item) =>
        prisma.taskType.update({
          where: { id: item.id },
          data: { sortOrder: item.sortOrder },
        })
      )
    )

    return NextResponse.json({ success: true })
  } catch (error) {
    console.error('PUT /api/departments/[id]/task-types error:', error)
    return NextResponse.json(
      { error: '순서 변경에 실패했습니다' },
      { status: 500 }
    )
  }
}
