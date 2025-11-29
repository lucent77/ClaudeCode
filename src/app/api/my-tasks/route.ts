import { NextRequest, NextResponse } from 'next/server'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { CaseStatus } from '@prisma/client'

/**
 * GET /api/my-tasks
 * 본인에게 할당된 작업 목록 조회
 */
export async function GET(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const { searchParams } = new URL(request.url)
    const filter = searchParams.get('filter') // today, inProgress, pending, completed

    const now = new Date()
    const startOfToday = new Date(now.setHours(0, 0, 0, 0))
    const endOfToday = new Date(now.setHours(23, 59, 59, 999))
    const sevenDaysAgo = new Date()
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)

    // 기본 where 조건
    const baseWhere = {
      assigneeId: session.user.id,
    }

    // 필터별 조건
    let where: Record<string, unknown> = { ...baseWhere }

    switch (filter) {
      case 'today':
        where = {
          ...baseWhere,
          case: {
            dueDate: { lte: endOfToday },
          },
          status: { notIn: [CaseStatus.COMPLETED, CaseStatus.CANCELLED] },
        }
        break
      case 'inProgress':
        where = {
          ...baseWhere,
          status: CaseStatus.IN_PROGRESS,
        }
        break
      case 'pending':
        where = {
          ...baseWhere,
          status: CaseStatus.PENDING,
        }
        break
      case 'completed':
        where = {
          ...baseWhere,
          status: CaseStatus.COMPLETED,
          completedAt: { gte: sevenDaysAgo },
        }
        break
    }

    const assignments = await prisma.caseAssignment.findMany({
      where,
      include: {
        case: {
          select: {
            id: true,
            caseNumber: true,
            patientName: true,
            labDoctorName: true,
            dueDate: true,
            status: true,
            currentDepartment: true,
            quantity: true,
            toothNumbers: true,
            noteOptions: true,
            noteText: true,
          },
        },
        assignedBy: {
          select: {
            id: true,
            name: true,
          },
        },
      },
      orderBy: [
        { case: { dueDate: 'asc' } },
        { assignedAt: 'desc' },
      ],
    })

    // 통계
    const stats = await prisma.caseAssignment.groupBy({
      by: ['status'],
      where: { assigneeId: session.user.id },
      _count: true,
    })

    const todayTasks = await prisma.caseAssignment.count({
      where: {
        assigneeId: session.user.id,
        case: {
          dueDate: { lte: endOfToday },
        },
        status: { notIn: [CaseStatus.COMPLETED, CaseStatus.CANCELLED] },
      },
    })

    const todayCompleted = await prisma.caseAssignment.count({
      where: {
        assigneeId: session.user.id,
        status: CaseStatus.COMPLETED,
        completedAt: { gte: startOfToday },
      },
    })

    const summary = {
      today: todayTasks,
      inProgress: stats.find((s) => s.status === CaseStatus.IN_PROGRESS)?._count || 0,
      pending: stats.find((s) => s.status === CaseStatus.PENDING)?._count || 0,
      completed: stats.find((s) => s.status === CaseStatus.COMPLETED)?._count || 0,
      todayCompleted,
    }

    return NextResponse.json({
      data: assignments,
      summary,
    })
  } catch (error) {
    console.error('GET /api/my-tasks error:', error)
    return NextResponse.json(
      { error: '작업 목록을 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}
