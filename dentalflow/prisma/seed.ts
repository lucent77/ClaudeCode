/**
 * Database Seed Script - DentalFlow
 *
 * Creates initial data for:
 * - Departments (CAD Design, Milling, Sintering, Finishing, QC)
 * - Task types per department
 * - Admin user
 * - System settings
 */

import { PrismaClient, UserRole, CasePriority } from '@prisma/client'
import bcrypt from 'bcryptjs'

const prisma = new PrismaClient()

async function main() {
  console.log('Starting database seed...')

  // Create departments
  const departments = await Promise.all([
    prisma.department.upsert({
      where: { code: 'CAD' },
      update: {},
      create: {
        name: 'CAD Design',
        code: 'CAD',
        description: 'Computer-Aided Design for dental prosthetics',
        color: '#8b5cf6',
        icon: 'Pencil',
        sortOrder: 1,
      },
    }),
    prisma.department.upsert({
      where: { code: 'MILL' },
      update: {},
      create: {
        name: 'Milling',
        code: 'MILL',
        description: 'CNC milling operations for zirconia, PMMA, and other materials',
        color: '#f59e0b',
        icon: 'Cog',
        sortOrder: 2,
      },
    }),
    prisma.department.upsert({
      where: { code: 'SINT' },
      update: {},
      create: {
        name: 'Sintering',
        code: 'SINT',
        description: 'High-temperature sintering for zirconia and ceramic materials',
        color: '#ef4444',
        icon: 'Flame',
        sortOrder: 3,
      },
    }),
    prisma.department.upsert({
      where: { code: 'FIN' },
      update: {},
      create: {
        name: 'Finishing',
        code: 'FIN',
        description: 'Final finishing, staining, and glazing operations',
        color: '#10b981',
        icon: 'Sparkles',
        sortOrder: 4,
      },
    }),
    prisma.department.upsert({
      where: { code: 'QC' },
      update: {},
      create: {
        name: 'Quality Control',
        code: 'QC',
        description: 'Quality inspection and approval',
        color: '#3b82f6',
        icon: 'CheckCircle',
        sortOrder: 5,
      },
    }),
  ])

  console.log(`Created ${departments.length} departments`)

  // Create task types for each department
  const taskTypes = [
    // CAD Design
    { departmentCode: 'CAD', name: 'Scan Import', defaultTime: 15 },
    { departmentCode: 'CAD', name: 'Crown Design', defaultTime: 30 },
    { departmentCode: 'CAD', name: 'Bridge Design', defaultTime: 45 },
    { departmentCode: 'CAD', name: 'Implant Abutment Design', defaultTime: 40 },
    { departmentCode: 'CAD', name: 'Veneer Design', defaultTime: 35 },
    { departmentCode: 'CAD', name: 'Inlay/Onlay Design', defaultTime: 25 },
    { departmentCode: 'CAD', name: 'Full Denture Design', defaultTime: 60 },
    { departmentCode: 'CAD', name: 'Design Review', defaultTime: 15 },

    // Milling
    { departmentCode: 'MILL', name: 'Zirconia Milling', defaultTime: 120 },
    { departmentCode: 'MILL', name: 'PMMA Milling', defaultTime: 60 },
    { departmentCode: 'MILL', name: 'Wax Milling', defaultTime: 45 },
    { departmentCode: 'MILL', name: 'Titanium Milling', defaultTime: 90 },
    { departmentCode: 'MILL', name: 'E.max Milling', defaultTime: 75 },
    { departmentCode: 'MILL', name: 'Machine Setup', defaultTime: 15 },
    { departmentCode: 'MILL', name: 'Tool Change', defaultTime: 10 },

    // Sintering
    { departmentCode: 'SINT', name: 'Zirconia Sintering', defaultTime: 480 },
    { departmentCode: 'SINT', name: 'Speed Sintering', defaultTime: 120 },
    { departmentCode: 'SINT', name: 'Glaze Firing', defaultTime: 60 },
    { departmentCode: 'SINT', name: 'Stain Firing', defaultTime: 45 },
    { departmentCode: 'SINT', name: 'Crystallization', defaultTime: 30 },

    // Finishing
    { departmentCode: 'FIN', name: 'Sprue Removal', defaultTime: 10 },
    { departmentCode: 'FIN', name: 'Contouring', defaultTime: 20 },
    { departmentCode: 'FIN', name: 'Polishing', defaultTime: 25 },
    { departmentCode: 'FIN', name: 'Staining', defaultTime: 30 },
    { departmentCode: 'FIN', name: 'Glazing', defaultTime: 15 },
    { departmentCode: 'FIN', name: 'Final Inspection', defaultTime: 10 },

    // Quality Control
    { departmentCode: 'QC', name: 'Fit Check', defaultTime: 15 },
    { departmentCode: 'QC', name: 'Color Match', defaultTime: 10 },
    { departmentCode: 'QC', name: 'Margin Inspection', defaultTime: 15 },
    { departmentCode: 'QC', name: 'Bite Check', defaultTime: 10 },
    { departmentCode: 'QC', name: 'Documentation', defaultTime: 5 },
    { departmentCode: 'QC', name: 'Final Approval', defaultTime: 10 },
  ]

  for (const [index, taskType] of taskTypes.entries()) {
    const dept = departments.find((d) => d.code === taskType.departmentCode)
    if (dept) {
      await prisma.taskType.upsert({
        where: {
          departmentId_name: {
            departmentId: dept.id,
            name: taskType.name,
          },
        },
        update: {},
        create: {
          departmentId: dept.id,
          name: taskType.name,
          defaultTime: taskType.defaultTime,
          sortOrder: index,
        },
      })
    }
  }

  console.log(`Created ${taskTypes.length} task types`)

  // Create custom statuses for each department
  const customStatuses = [
    // CAD
    { departmentCode: 'CAD', name: 'designing', label: 'Designing', color: '#3b82f6' },
    { departmentCode: 'CAD', name: 'awaiting_approval', label: 'Awaiting Approval', color: '#f59e0b' },
    { departmentCode: 'CAD', name: 'revision_needed', label: 'Revision Needed', color: '#ef4444' },
    { departmentCode: 'CAD', name: 'approved', label: 'Approved', color: '#10b981', isFinal: true },

    // Milling
    { departmentCode: 'MILL', name: 'queued', label: 'Queued', color: '#6b7280' },
    { departmentCode: 'MILL', name: 'milling', label: 'Milling', color: '#3b82f6' },
    { departmentCode: 'MILL', name: 'milled', label: 'Milled', color: '#10b981', isFinal: true },

    // Sintering
    { departmentCode: 'SINT', name: 'loading', label: 'Loading Furnace', color: '#f59e0b' },
    { departmentCode: 'SINT', name: 'firing', label: 'Firing', color: '#ef4444' },
    { departmentCode: 'SINT', name: 'cooling', label: 'Cooling', color: '#3b82f6' },
    { departmentCode: 'SINT', name: 'sintered', label: 'Sintered', color: '#10b981', isFinal: true },

    // Finishing
    { departmentCode: 'FIN', name: 'finishing', label: 'Finishing', color: '#3b82f6' },
    { departmentCode: 'FIN', name: 'staining', label: 'Staining', color: '#8b5cf6' },
    { departmentCode: 'FIN', name: 'glazing', label: 'Glazing', color: '#f59e0b' },
    { departmentCode: 'FIN', name: 'finished', label: 'Finished', color: '#10b981', isFinal: true },

    // QC
    { departmentCode: 'QC', name: 'inspecting', label: 'Inspecting', color: '#3b82f6' },
    { departmentCode: 'QC', name: 'issues_found', label: 'Issues Found', color: '#ef4444' },
    { departmentCode: 'QC', name: 'passed', label: 'Passed', color: '#10b981', isFinal: true },
  ]

  for (const [index, status] of customStatuses.entries()) {
    const dept = departments.find((d) => d.code === status.departmentCode)
    if (dept) {
      await prisma.customStatus.upsert({
        where: {
          departmentId_name: {
            departmentId: dept.id,
            name: status.name,
          },
        },
        update: {},
        create: {
          departmentId: dept.id,
          name: status.name,
          label: status.label,
          color: status.color,
          isFinal: status.isFinal || false,
          sortOrder: index,
        },
      })
    }
  }

  console.log(`Created ${customStatuses.length} custom statuses`)

  // Create admin user
  const hashedPassword = await bcrypt.hash('admin123', 12)

  const adminUser = await prisma.user.upsert({
    where: { email: 'admin@dentalflow.local' },
    update: {},
    create: {
      email: 'admin@dentalflow.local',
      name: 'System Administrator',
      password: hashedPassword,
      role: UserRole.ADMIN,
      emailVerified: new Date(),
    },
  })

  console.log(`Created admin user: ${adminUser.email}`)

  // Create system settings
  const settings = [
    { key: 'app.name', value: 'DentalFlow', group: 'general' },
    { key: 'app.company', value: 'Your Dental Lab', group: 'general' },
    { key: 'app.timezone', value: 'Asia/Seoul', group: 'general' },
    { key: 'app.dateFormat', value: 'yyyy-MM-dd', group: 'general' },
    { key: 'app.timeFormat', value: 'HH:mm', group: 'general' },
    { key: 'case.autoNumber', value: 'true', group: 'case' },
    { key: 'case.numberPrefix', value: 'DF', group: 'case' },
    { key: 'notification.email', value: 'true', group: 'notification' },
    { key: 'notification.realtime', value: 'true', group: 'notification' },
  ]

  for (const setting of settings) {
    await prisma.setting.upsert({
      where: { key: setting.key },
      update: {},
      create: setting,
    })
  }

  console.log(`Created ${settings.length} system settings`)

  console.log('Database seed completed successfully!')
}

main()
  .catch((e) => {
    console.error('Seed error:', e)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
  })
