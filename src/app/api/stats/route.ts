import { NextRequest, NextResponse } from 'next/server'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { CaseStatus, Department } from '@prisma/client'

/**
 * GET /api/stats
 * 대시보드 통계 조회 (관리자 전용)
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

    const now = new Date()
    const startOfToday = new Date(now.setHours(0, 0, 0, 0))
    const startOfWeek = new Date(now)
    startOfWeek.setDate(startOfWeek.getDate() - 7)

    // 전체 통계
    const [
      totalCases,
      pendingCases,
      inProgressCases,
      completedCases,
      onHoldCases,
      cancelledCases,
      delayedCases,
      todayCreated,
      todayCompleted,
    ] = await Promise.all([
      prisma.case.count(),
      prisma.case.count({ where: { status: CaseStatus.PENDING } }),
      prisma.case.count({ where: { status: CaseStatus.IN_PROGRESS } }),
      prisma.case.count({ where: { status: CaseStatus.COMPLETED } }),
      prisma.case.count({ where: { status: CaseStatus.ON_HOLD } }),
      prisma.case.count({ where: { status: CaseStatus.CANCELLED } }),
      prisma.case.count({
        where: {
          dueDate: { lt: new Date() },
          status: { notIn: [CaseStatus.COMPLETED, CaseStatus.CANCELLED] },
        },
      }),
      prisma.case.count({
        where: { createdAt: { gte: startOfToday } },
      }),
      prisma.case.count({
        where: {
          status: CaseStatus.COMPLETED,
          updatedAt: { gte: startOfToday },
        },
      }),
    ])

    // 부서별 통계
    const departments = Object.values(Department)
    const departmentStats = await Promise.all(
      departments.map(async (dept) => {
        const [total, pending, inProgress, completed] = await Promise.all([
          prisma.case.count({ where: { currentDepartment: dept } }),
          prisma.case.count({
            where: { currentDepartment: dept, status: CaseStatus.PENDING },
          }),
          prisma.case.count({
            where: { currentDepartment: dept, status: CaseStatus.IN_PROGRESS },
          }),
          prisma.case.count({
            where: { currentDepartment: dept, status: CaseStatus.COMPLETED },
          }),
        ])

        return {
          department: dept,
          totalCases: total,
          pendingCases: pending,
          inProgressCases: inProgress,
          completedCases: completed,
        }
      })
    )

    // 최근 7일 일별 통계
    const dailyStats = []
    for (let i = 6; i >= 0; i--) {
      const date = new Date()
      date.setDate(date.getDate() - i)
      const startOfDay = new Date(date.setHours(0, 0, 0, 0))
      const endOfDay = new Date(date.setHours(23, 59, 59, 999))

      const [created, completed] = await Promise.all([
        prisma.case.count({
          where: {
            createdAt: { gte: startOfDay, lte: endOfDay },
          },
        }),
        prisma.case.count({
          where: {
            status: CaseStatus.COMPLETED,
            updatedAt: { gte: startOfDay, lte: endOfDay },
          },
        }),
      ])

      dailyStats.push({
        date: startOfDay.toISOString().split('T')[0],
        created,
        completed,
      })
    }

    // 지연 케이스 목록
    const delayedCasesList = await prisma.case.findMany({
      where: {
        dueDate: { lt: new Date() },
        status: { notIn: [CaseStatus.COMPLETED, CaseStatus.CANCELLED] },
      },
      select: {
        id: true,
        caseNumber: true,
        patientName: true,
        labDoctorName: true,
        dueDate: true,
        currentDepartment: true,
        status: true,
      },
      orderBy: { dueDate: 'asc' },
      take: 10,
    })

    const completionRate = totalCases > 0
      ? Math.round((completedCases / totalCases) * 100)
      : 0

    return NextResponse.json({
      data: {
        summary: {
          totalCases,
          pendingCases,
          inProgressCases,
          completedCases,
          onHoldCases,
          cancelledCases,
          delayedCases,
          completionRate,
          todayCreated,
          todayCompleted,
        },
        departmentStats,
        dailyStats,
        delayedCasesList,
      },
    })
  } catch (error) {
    console.error('GET /api/stats error:', error)
    return NextResponse.json(
      { error: '통계를 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}
