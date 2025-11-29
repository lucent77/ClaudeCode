import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { CaseStatus, Department } from '@prisma/client'

/**
 * 작업자 할당 스키마
 */
const assignSchema = z.object({
  caseId: z.string().min(1, '케이스 ID가 필요합니다'),
  department: z.nativeEnum(Department),
  assigneeId: z.string().min(1, '작업자 ID가 필요합니다'),
})

/**
 * 작업 인계 스키마
 */
const transferSchema = z.object({
  caseId: z.string().min(1, '케이스 ID가 필요합니다'),
  department: z.nativeEnum(Department),
  toUserId: z.string().min(1, '인계받을 작업자 ID가 필요합니다'),
  reason: z.string().min(1, '인계 사유를 입력하세요'),
})

/**
 * POST /api/cases/assign
 * 작업자 할당
 */
export async function POST(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const body = await request.json()
    const parsed = assignSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    const { caseId, department, assigneeId } = parsed.data

    // 케이스 확인
    const caseData = await prisma.case.findUnique({
      where: { id: caseId },
    })

    if (!caseData) {
      return NextResponse.json({ error: '케이스를 찾을 수 없습니다' }, { status: 404 })
    }

    // 작업자 확인
    const assignee = await prisma.user.findUnique({
      where: { id: assigneeId },
    })

    if (!assignee) {
      return NextResponse.json({ error: '작업자를 찾을 수 없습니다' }, { status: 404 })
    }

    // 작업자 부서 확인
    if (assignee.department !== department) {
      return NextResponse.json(
        { error: '해당 부서의 작업자만 할당할 수 있습니다' },
        { status: 400 }
      )
    }

    // 권한 확인 (관리자 또는 Self-Assign)
    const isSelfAssign = assigneeId === session.user.id
    if (session.user.role !== 'ADMIN' && !isSelfAssign) {
      return NextResponse.json({ error: '권한이 없습니다' }, { status: 403 })
    }

    // 이미 할당되어 있는지 확인
    const existingAssignment = await prisma.caseAssignment.findFirst({
      where: {
        caseId,
        department,
        assigneeId,
      },
    })

    if (existingAssignment) {
      return NextResponse.json(
        { error: '이미 해당 작업자에게 할당되어 있습니다' },
        { status: 400 }
      )
    }

    // 할당 생성
    const assignment = await prisma.caseAssignment.create({
      data: {
        caseId,
        department,
        assigneeId,
        assignedById: session.user.id,
        status: CaseStatus.PENDING,
      },
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
    })

    // 케이스 이력 기록
    await prisma.caseHistory.create({
      data: {
        caseId,
        userId: session.user.id,
        action: 'ASSIGN',
        changes: {
          department,
          assigneeId,
          assigneeName: assignee.name,
        },
      },
    })

    return NextResponse.json({ data: assignment }, { status: 201 })
  } catch (error) {
    console.error('POST /api/cases/assign error:', error)
    return NextResponse.json(
      { error: '작업자 할당에 실패했습니다' },
      { status: 500 }
    )
  }
}

/**
 * PUT /api/cases/assign
 * 작업 인계
 */
export async function PUT(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const body = await request.json()
    const parsed = transferSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: '입력값이 올바르지 않습니다', details: parsed.error.flatten() },
        { status: 400 }
      )
    }

    const { caseId, department, toUserId, reason } = parsed.data

    // 현재 할당 확인
    const currentAssignment = await prisma.caseAssignment.findFirst({
      where: {
        caseId,
        department,
        assigneeId: session.user.id,
      },
    })

    if (!currentAssignment) {
      return NextResponse.json(
        { error: '본인에게 할당된 작업만 인계할 수 있습니다' },
        { status: 400 }
      )
    }

    // 인계받을 작업자 확인
    const toUser = await prisma.user.findUnique({
      where: { id: toUserId },
    })

    if (!toUser) {
      return NextResponse.json({ error: '인계받을 작업자를 찾을 수 없습니다' }, { status: 404 })
    }

    if (toUser.department !== department) {
      return NextResponse.json(
        { error: '같은 부서의 작업자에게만 인계할 수 있습니다' },
        { status: 400 }
      )
    }

    // 트랜잭션으로 인계 처리
    const [, newAssignment] = await prisma.$transaction([
      // 기존 할당 삭제
      prisma.caseAssignment.delete({
        where: { id: currentAssignment.id },
      }),
      // 새 할당 생성
      prisma.caseAssignment.create({
        data: {
          caseId,
          department,
          assigneeId: toUserId,
          assignedById: session.user.id,
          status: currentAssignment.status,
          startedAt: currentAssignment.startedAt,
        },
        include: {
          assignee: {
            select: {
              id: true,
              name: true,
              department: true,
            },
          },
        },
      }),
      // 인계 이력 기록
      prisma.assignmentTransfer.create({
        data: {
          caseId,
          department,
          fromUserId: session.user.id,
          toUserId,
          reason,
        },
      }),
    ])

    // 케이스 이력 기록
    await prisma.caseHistory.create({
      data: {
        caseId,
        userId: session.user.id,
        action: 'TRANSFER',
        changes: {
          department,
          fromUserId: session.user.id,
          toUserId,
          reason,
        },
      },
    })

    return NextResponse.json({ data: newAssignment })
  } catch (error) {
    console.error('PUT /api/cases/assign error:', error)
    return NextResponse.json(
      { error: '작업 인계에 실패했습니다' },
      { status: 500 }
    )
  }
}
