import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { CaseStatus, Department } from '@prisma/client'
import { generateCaseNumber } from '@/lib/utils'

/**
 * 케이스 생성 스키마
 */
const createCaseSchema = z.object({
  panNumber: z.string().optional(),
  labDoctorName: z.string().min(1, 'LAB/DOCTOR 이름을 입력하세요'),
  patientName: z.string().min(1, '환자명을 입력하세요'),
  quantity: z.number().min(1, '개수는 1 이상이어야 합니다').default(1),
  toothNumbers: z.array(z.string()).default([]),
  toothColor: z.string().optional(),
  implantType: z.string().optional(),
  dueDate: z.string().or(z.date()),
  cocrType: z.string().optional(),
  solidexType: z.string().optional(),
  print3dType: z.string().optional(),
  noteOptions: z.array(z.string()).default([]),
  noteText: z.string().optional(),
  workflow: z.array(z.nativeEnum(Department)).min(1, '워크플로우를 선택하세요'),
})

/**
 * GET /api/cases
 * 케이스 목록 조회
 */
export async function GET(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const { searchParams } = new URL(request.url)
    const status = searchParams.get('status') as CaseStatus | null
    const department = searchParams.get('department') as Department | null
    const search = searchParams.get('search')
    const isDelayed = searchParams.get('isDelayed') === 'true'
    const page = parseInt(searchParams.get('page') || '1')
    const pageSize = parseInt(searchParams.get('pageSize') || '20')

    const where: Record<string, unknown> = {}

    // 작업자는 본인 부서 케이스만 조회
    if (session.user.role !== 'ADMIN') {
      where.currentDepartment = session.user.department
    } else if (department) {
      where.currentDepartment = department
    }

    if (status) {
      where.status = status
    }

    if (search) {
      where.OR = [
        { caseNumber: { contains: search } },
        { patientName: { contains: search } },
        { labDoctorName: { contains: search } },
      ]
    }

    if (isDelayed) {
      where.dueDate = { lt: new Date() }
      where.status = { notIn: ['COMPLETED', 'CANCELLED'] }
    }

    const [cases, total] = await Promise.all([
      prisma.case.findMany({
        where,
        include: {
          createdBy: {
            select: {
              id: true,
              name: true,
              email: true,
            },
          },
          departmentStatuses: true,
          assignments: {
            include: {
              assignee: {
                select: {
                  id: true,
                  name: true,
                },
              },
            },
          },
        },
        orderBy: [{ dueDate: 'asc' }, { createdAt: 'desc' }],
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.case.count({ where }),
    ])

    return NextResponse.json({
      data: cases,
      total,
      page,
      pageSize,
      totalPages: Math.ceil(total / pageSize),
    })
  } catch (error) {
    console.error('GET /api/cases error:', error)
    return NextResponse.json(
      { error: '케이스 목록을 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * POST /api/cases
 * 케이스 생성 (관리자 전용)
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
    const parsed = createCaseSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    const data = parsed.data

    // 케이스 번호 생성
    const today = new Date()
    const startOfDay = new Date(today.setHours(0, 0, 0, 0))
    const endOfDay = new Date(today.setHours(23, 59, 59, 999))

    const todayCount = await prisma.case.count({
      where: {
        createdAt: {
          gte: startOfDay,
          lte: endOfDay,
        },
      },
    })

    const caseNumber = generateCaseNumber(todayCount + 1)

    // 케이스 생성
    const newCase = await prisma.case.create({
      data: {
        caseNumber,
        panNumber: data.panNumber,
        labDoctorName: data.labDoctorName,
        patientName: data.patientName,
        quantity: data.quantity,
        toothNumbers: data.toothNumbers,
        toothColor: data.toothColor,
        implantType: data.implantType,
        dueDate: new Date(data.dueDate),
        cocrType: data.cocrType,
        solidexType: data.solidexType,
        print3dType: data.print3dType,
        noteOptions: data.noteOptions,
        noteText: data.noteText,
        workflow: data.workflow,
        currentDepartment: data.workflow[0],
        status: CaseStatus.PENDING,
        createdById: session.user.id,
      },
      include: {
        createdBy: {
          select: {
            id: true,
            name: true,
            email: true,
          },
        },
      },
    })

    // 워크플로우의 각 부서에 대해 DepartmentStatus 생성
    await prisma.departmentStatus.createMany({
      data: data.workflow.map((dept) => ({
        caseId: newCase.id,
        department: dept,
        status: dept === data.workflow[0] ? CaseStatus.PENDING : CaseStatus.PENDING,
      })),
    })

    // 케이스 이력 기록
    await prisma.caseHistory.create({
      data: {
        caseId: newCase.id,
        userId: session.user.id,
        action: 'CREATE',
        changes: { created: true },
      },
    })

    return NextResponse.json({ data: newCase }, { status: 201 })
  } catch (error) {
    console.error('POST /api/cases error:', error)
    return NextResponse.json(
      { error: '케이스 생성에 실패했습니다' },
      { status: 500 }
    )
  }
}
