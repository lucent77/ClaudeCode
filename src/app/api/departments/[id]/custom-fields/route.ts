import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { Department, FieldType } from '@prisma/client'

const customFieldSchema = z.object({
  name: z.string().min(1, '필드 이름을 입력하세요'),
  label: z.string().min(1, '표시 레이블을 입력하세요'),
  fieldType: z.nativeEnum(FieldType),
  options: z.array(z.string()).optional(),
  defaultValue: z.string().optional(),
  placeholder: z.string().optional(),
  isRequired: z.boolean().default(false),
})

interface RouteParams {
  params: Promise<{ id: string }>
}

/**
 * GET /api/departments/[id]/custom-fields
 * 부서별 커스텀 필드 목록 조회
 */
export async function GET(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id: department } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    if (!Object.values(Department).includes(department as Department)) {
      return NextResponse.json({ error: '유효하지 않은 부서입니다' }, { status: 400 })
    }

    const customFields = await prisma.customField.findMany({
      where: { department: department as Department },
      orderBy: { sortOrder: 'asc' },
    })

    return NextResponse.json({ data: customFields })
  } catch (error) {
    console.error('GET /api/departments/[id]/custom-fields error:', error)
    return NextResponse.json(
      { error: '커스텀 필드를 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * POST /api/departments/[id]/custom-fields
 * 커스텀 필드 생성 (관리자 전용)
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
    const parsed = customFieldSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    // SELECT/MULTI_SELECT는 options 필수
    if (
      (parsed.data.fieldType === 'SELECT' || parsed.data.fieldType === 'MULTI_SELECT') &&
      (!parsed.data.options || parsed.data.options.length === 0)
    ) {
      return NextResponse.json(
        { error: '선택 필드는 옵션을 입력해야 합니다' },
        { status: 400 }
      )
    }

    const maxOrder = await prisma.customField.aggregate({
      where: { department: department as Department },
      _max: { sortOrder: true },
    })

    const customField = await prisma.customField.create({
      data: {
        department: department as Department,
        name: parsed.data.name,
        label: parsed.data.label,
        fieldType: parsed.data.fieldType,
        options: parsed.data.options || null,
        defaultValue: parsed.data.defaultValue,
        placeholder: parsed.data.placeholder,
        isRequired: parsed.data.isRequired,
        sortOrder: (maxOrder._max.sortOrder || 0) + 1,
      },
    })

    return NextResponse.json({ data: customField }, { status: 201 })
  } catch (error) {
    console.error('POST /api/departments/[id]/custom-fields error:', error)
    return NextResponse.json(
      { error: '커스텀 필드 생성에 실패했습니다' },
      { status: 500 }
    )
  }
}
