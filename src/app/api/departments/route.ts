import { NextRequest, NextResponse } from 'next/server'
import { auth } from '@/lib/auth'
import { prisma } from '@/lib/prisma'
import { Department } from '@prisma/client'

/**
 * GET /api/departments
 * 부서 목록 및 설정 조회
 */
export async function GET(request: NextRequest) {
  try {
    const session = await auth()

    if (!session?.user) {
      return NextResponse.json({ error: '인증이 필요합니다' }, { status: 401 })
    }

    const departments = Object.values(Department)

    // 각 부서별 작업 유형, 커스텀 필드, 커스텀 상태 수 조회
    const departmentStats = await Promise.all(
      departments.map(async (dept) => {
        const [taskTypeCount, customFieldCount, customStatusCount, userCount] =
          await Promise.all([
            prisma.taskType.count({ where: { department: dept, isActive: true } }),
            prisma.customField.count({ where: { department: dept, isActive: true } }),
            prisma.customStatus.count({ where: { department: dept, isActive: true } }),
            prisma.user.count({ where: { department: dept, isActive: true } }),
          ])

        return {
          department: dept,
          taskTypeCount,
          customFieldCount,
          customStatusCount,
          userCount,
        }
      })
    )

    return NextResponse.json({ data: departmentStats })
  } catch (error) {
    console.error('GET /api/departments error:', error)
    return NextResponse.json(
      { error: '부서 목록을 불러오는데 실패했습니다' },
      { status: 500 }
    )
  }
}
