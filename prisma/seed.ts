import { PrismaClient, Role, Department } from '@prisma/client'
import bcrypt from 'bcryptjs'

const prisma = new PrismaClient()

async function main() {
  console.log('Seeding database...')

  // 1. 기본 관리자 계정 생성
  const adminPassword = await bcrypt.hash('admin123', 10)
  const admin = await prisma.user.upsert({
    where: { email: 'admin@dentalflow.com' },
    update: {},
    create: {
      email: 'admin@dentalflow.com',
      password: adminPassword,
      name: '관리자',
      role: Role.ADMIN,
      department: Department.FRONT_DESK,
    },
  })
  console.log('Created admin user:', admin.email)

  // 2. 부서별 샘플 작업자 생성
  const workerPassword = await bcrypt.hash('worker123', 10)
  const departments = Object.values(Department)

  for (const dept of departments) {
    const worker = await prisma.user.upsert({
      where: { email: `worker.${dept.toLowerCase()}@dentalflow.com` },
      update: {},
      create: {
        email: `worker.${dept.toLowerCase()}@dentalflow.com`,
        password: workerPassword,
        name: `${dept} 작업자`,
        role: Role.WORKER,
        department: dept,
      },
    })
    console.log('Created worker:', worker.email)
  }

  // 3. NOTE 옵션 생성
  const noteOptions = [
    'RUSH - 긴급',
    'REDO - 재작업',
    '색상 확인 필요',
    '담당의 확인 필요',
    '특수 재료 사용',
    '교정 케이스',
    '임플란트 케이스',
    '브릿지 케이스',
    '연조직 작업',
    '기타 특이사항',
  ]

  for (let i = 0; i < noteOptions.length; i++) {
    await prisma.noteOption.upsert({
      where: { label: noteOptions[i] },
      update: {},
      create: {
        label: noteOptions[i],
        isActive: true,
        sortOrder: i,
      },
    })
  }
  console.log('Created note options')

  // 4. 부서별 샘플 작업 유형 생성
  const taskTypes = [
    // Solidex 부서
    { department: Department.SOLIDEX, name: '풀 크라운', color: '#3B82F6' },
    { department: Department.SOLIDEX, name: '인레이', color: '#10B981' },
    { department: Department.SOLIDEX, name: '온레이', color: '#F59E0B' },
    { department: Department.SOLIDEX, name: '브릿지', color: '#8B5CF6' },
    { department: Department.SOLIDEX, name: '베니어', color: '#EC4899' },

    // Cocr 부서
    { department: Department.COCR, name: '코발트 크라운', color: '#6366F1' },
    { department: Department.COCR, name: '메탈 프레임', color: '#14B8A6' },
    { department: Department.COCR, name: '파샬 프레임', color: '#F97316' },

    // 3D Print 부서
    { department: Department.PRINT_3D, name: '모델', color: '#84CC16' },
    { department: Department.PRINT_3D, name: '서지컬 가이드', color: '#06B6D4' },
    { department: Department.PRINT_3D, name: '스플린트', color: '#A855F7' },
    { department: Department.PRINT_3D, name: '임시 크라운', color: '#F43F5E' },
  ]

  for (let i = 0; i < taskTypes.length; i++) {
    const tt = taskTypes[i]
    await prisma.taskType.upsert({
      where: {
        department_name: {
          department: tt.department,
          name: tt.name,
        },
      },
      update: {},
      create: {
        department: tt.department,
        name: tt.name,
        color: tt.color,
        isActive: true,
        sortOrder: i,
      },
    })
  }
  console.log('Created task types')

  // 5. 샘플 커스텀 필드 생성
  const customFields = [
    {
      department: Department.SOLIDEX,
      name: 'shade_guide',
      label: '쉐이드 가이드',
      fieldType: 'SELECT' as const,
      options: JSON.stringify(['Vita Classical', 'Vita 3D Master', 'Ivoclar']),
      isRequired: false,
    },
    {
      department: Department.SOLIDEX,
      name: 'stain_required',
      label: '스테인 필요',
      fieldType: 'BOOLEAN' as const,
      isRequired: false,
    },
    {
      department: Department.COCR,
      name: 'alloy_type',
      label: '합금 종류',
      fieldType: 'SELECT' as const,
      options: JSON.stringify(['CoCr', 'NiCr', 'Titanium']),
      isRequired: true,
    },
    {
      department: Department.PRINT_3D,
      name: 'resin_type',
      label: '레진 종류',
      fieldType: 'SELECT' as const,
      options: JSON.stringify(['Dental Model', 'Surgical Guide', 'Temp Crown']),
      isRequired: true,
    },
    {
      department: Department.PRINT_3D,
      name: 'layer_thickness',
      label: '레이어 두께 (um)',
      fieldType: 'NUMBER' as const,
      isRequired: false,
    },
  ]

  for (let i = 0; i < customFields.length; i++) {
    const cf = customFields[i]
    await prisma.customField.upsert({
      where: {
        department_name: {
          department: cf.department,
          name: cf.name,
        },
      },
      update: {},
      create: {
        ...cf,
        isActive: true,
        sortOrder: i,
      },
    })
  }
  console.log('Created custom fields')

  // 6. 부서별 커스텀 상태 생성
  const customStatuses = [
    { department: Department.SOLIDEX, name: '검수중', color: '#8B5CF6' },
    { department: Department.SOLIDEX, name: '수정요청', color: '#F97316' },
    { department: Department.COCR, name: '외주진행', color: '#06B6D4' },
    { department: Department.PRINT_3D, name: '프린팅중', color: '#84CC16' },
    { department: Department.PRINT_3D, name: '후처리중', color: '#A855F7' },
  ]

  for (let i = 0; i < customStatuses.length; i++) {
    const cs = customStatuses[i]
    await prisma.customStatus.upsert({
      where: {
        department_name: {
          department: cs.department,
          name: cs.name,
        },
      },
      update: {},
      create: {
        ...cs,
        isActive: true,
        sortOrder: i,
      },
    })
  }
  console.log('Created custom statuses')

  console.log('Seeding completed!')
}

main()
  .catch((e) => {
    console.error('Seeding failed:', e)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
  })
