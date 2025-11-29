import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { CaseStatus, Department } from '@prisma/client'

/**
 * 케이스 업데이트 스키마
 */
const updateCaseSchema = z.object({
  panNumber: z.string().optional(),
  labDoctorName: z.string().min(1).optional(),
  patientName: z.string().min(1).optional(),
  quantity: z.number().min(1).optional(),
  toothNumbers: z.array(z.string()).optional(),
  toothColor: z.string().optional(),
  implantType: z.string().optional(),
  dueDate: z.string().or(z.date()).optional(),
  cocrType: z.string().optional(),
  solidexType: z.string().optional(),
  print3dType: z.string().optional(),
  noteOptions: z.array(z.string()).optional(),
  noteText: z.string().optional(),
  status: z.nativeEnum(CaseStatus).optional(),
  currentDepartment: z.nativeEnum(Department).optional(),
  version: z.number(), // 낙관적 잠금 필수
})

interface RouteParams {
  params: Promise<{ id: string }>
}

/**
 * GET /api/cases/[id]
 * 케이스 상세 조회
 */
export async function GET(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const caseData = await prisma.case.findUnique({
      where: { id },
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
                department: true,
              },
            },
            assignedBy: {
              select: {
                id: true,
                name: true,
              },
            },
          },
        },
        histories: {
          include: {
            user: {
              select: {
                id: true,
                name: true,
              },
            },
          },
          orderBy: { createdAt: 'desc' },
          take: 50,
        },
      },
    })

    if (!caseData) {
      return NextResponse.json({ error: '케이스를 찾을 수 없습니다' }, { status: 404 })
    }

    // 작업자는 본인 부서 케이스만 조회 가능
    if (
      session.user.role !== 'ADMIN' &&
      caseData.currentDepartment !== session.user.department
    ) {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    return NextResponse.json({ data: caseData })
  } catch (error) {
    console.error('GET /api/cases/[id] error:', error)
    return NextResponse.json(
      { error: '케이스를 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * PUT /api/cases/[id]
 * 케이스 수정 (낙관적 잠금 적용)
 */
export async function PUT(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const body = await request.json()
    const parsed = updateCaseSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    const { version, ...updateData } = parsed.data

    // 현재 케이스 조회
    const currentCase = await prisma.case.findUnique({
      where: { id },
    })

    if (!currentCase) {
      return NextResponse.json({ error: '케이스를 찾을 수 없습니다' }, { status: 404 })
    }

    // 작업자는 상태 변경만 가능
    if (session.user.role !== 'ADMIN') {
      if (currentCase.currentDepartment !== session.user.department) {
        return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
      }
      // 상태 변경 외 다른 필드 수정 불가
      const allowedFields = ['status']
      const attemptedFields = Object.keys(updateData)
      const disallowedFields = attemptedFields.filter(
        (f) => !allowedFields.includes(f)
      )
      if (disallowedFields.length > 0) {
        return NextResponse.json(
          { error: '작업자는 상태만 변경할 수 있습니다' },
          { status: 403 }
        )
      }
    }

    // 낙관적 잠금 - 버전 확인 및 업데이트
    const result = await prisma.case.updateMany({
      where: {
        id,
        version, // 버전이 일치해야 업데이트
      },
      data: {
        ...updateData,
        dueDate: updateData.dueDate ? new Date(updateData.dueDate) : undefined,
        version: { increment: 1 },
        updatedAt: new Date(),
      },
    })

    if (result.count === 0) {
      // 버전 불일치 - 다른 사용자가 수정함
      return NextResponse.json(
        {
          error: '다른 사용자가 이 케이스를 수정했습니다. 새로고침 후 다시 시도하세요.',
          code: 'CONFLICT',
        },
        { status: 409 }
      )
    }

    // 변경 이력 기록
    const changes: Record<string, unknown> = {}
    for (const [key, value] of Object.entries(updateData)) {
      if (value !== undefined) {
        changes[key] = {
          from: currentCase[key as keyof typeof currentCase],
          to: value,
        }
      }
    }

    // 상태 변경인 경우 특별 처리
    let action = 'UPDATE'
    if (updateData.status && updateData.status !== currentCase.status) {
      action = 'STATUS_CHANGE'

      // 부서별 상태도 업데이트
      await prisma.departmentStatus.updateMany({
        where: {
          caseId: id,
          department: currentCase.currentDepartment,
        },
        data: {
          status: updateData.status,
          startedAt:
            updateData.status === CaseStatus.IN_PROGRESS ? new Date() : undefined,
          completedAt:
            updateData.status === CaseStatus.COMPLETED ? new Date() : undefined,
        },
      })
    }

    if (updateData.currentDepartment && updateData.currentDepartment !== currentCase.currentDepartment) {
      action = 'DEPARTMENT_CHANGE'
    }

    await prisma.caseHistory.create({
      data: {
        caseId: id,
        userId: session.user.id,
        action,
        changes,
      },
    })

    // 업데이트된 케이스 반환
    const updatedCase = await prisma.case.findUnique({
      where: { id },
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
    })

    return NextResponse.json({ data: updatedCase })
  } catch (error) {
    console.error('PUT /api/cases/[id] error:', error)
    return NextResponse.json(
      { error: '케이스 수정에 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * DELETE /api/cases/[id]
 * 케이스 삭제 (관리자 전용)
 */
export async function DELETE(request: NextRequest, { params }: RouteParams) {
  try {
    const session = await auth()
    const { id } = await params

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    if (session.user.role !== 'ADMIN') {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    const caseData = await prisma.case.findUnique({
      where: { id },
    })

    if (!caseData) {
      return NextResponse.json({ error: '케이스를 찾을 수 없습니다' }, { status: 404 })
    }

    // 관련 데이터와 함께 삭제 (Cascade 설정되어 있음)
    await prisma.case.delete({
      where: { id },
    })

    return NextResponse.json({ success: true })
  } catch (error) {
    console.error('DELETE /api/cases/[id] error:', error)
    return NextResponse.json(
      { error: '케이스 삭제에 실패했습니다' },
      { status: 500 }
    )
  }
}
