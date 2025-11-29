'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useSession } from 'next-auth/react'
import {
  LayoutDashboard,
  ClipboardList,
  Users,
  Settings,
  ListTodo,
  Building2,
  Tags,
  FormInput,
} from 'lucide-react'

import { cn } from '@/lib/utils'
import { DEPARTMENT_LABELS, ROLE_LABELS } from '@/lib/utils'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'

interface NavItem {
  title: string
  href: string
  icon: React.ReactNode
  adminOnly?: boolean
}

const mainNavItems: NavItem[] = [
  {
    title: '대시보드',
    href: '/dashboard',
    icon: <LayoutDashboard className="h-5 w-5" />,
    adminOnly: true,
  },
  {
    title: '마이 태스크',
    href: '/my-tasks',
    icon: <ListTodo className="h-5 w-5" />,
  },
  {
    title: '케이스 관리',
    href: '/cases',
    icon: <ClipboardList className="h-5 w-5" />,
  },
]

const adminNavItems: NavItem[] = [
  {
    title: '사용자 관리',
    href: '/users',
    icon: <Users className="h-5 w-5" />,
    adminOnly: true,
  },
]

const settingsNavItems: NavItem[] = [
  {
    title: '설정',
    href: '/settings',
    icon: <Settings className="h-5 w-5" />,
    adminOnly: true,
  },
  {
    title: '부서 설정',
    href: '/settings/departments',
    icon: <Building2 className="h-5 w-5" />,
    adminOnly: true,
  },
  {
    title: '작업 유형',
    href: '/settings/task-types',
    icon: <Tags className="h-5 w-5" />,
    adminOnly: true,
  },
  {
    title: '커스텀 필드',
    href: '/settings/custom-fields',
    icon: <FormInput className="h-5 w-5" />,
    adminOnly: true,
  },
]

export function Sidebar() {
  const pathname = usePathname()
  const { data: session } = useSession()

  const isAdmin = session?.user?.role === 'ADMIN'

  const renderNavItems = (items: NavItem[]) => {
    return items
      .filter((item) => !item.adminOnly || isAdmin)
      .map((item) => {
        const isActive =
          pathname === item.href ||
          (item.href !== '/dashboard' && pathname.startsWith(item.href))

        return (
          <Link
            key={item.href}
            href={item.href}
            className={cn(
              'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
              isActive
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
            )}
          >
            {item.icon}
            <span>{item.title}</span>
          </Link>
        )
      })
  }

  return (
    <aside className="fixed left-0 top-0 z-40 h-screen w-64 border-r bg-background">
      <div className="flex h-full flex-col">
        {/* 로고 */}
        <div className="flex h-16 items-center border-b px-6">
          <Link href="/" className="flex items-center gap-2">
            <div className="h-8 w-8 rounded-lg bg-primary flex items-center justify-center">
              <span className="text-lg font-bold text-primary-foreground">D</span>
            </div>
            <span className="text-xl font-bold">DentalFlow</span>
          </Link>
        </div>

        {/* 네비게이션 */}
        <nav className="flex-1 overflow-y-auto p-4 scrollbar-thin">
          {/* 메인 메뉴 */}
          <div className="space-y-1">
            {renderNavItems(mainNavItems)}
          </div>

          {/* 관리자 메뉴 */}
          {isAdmin && (
            <>
              <div className="my-4 border-t" />
              <div className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                관리자
              </div>
              <div className="space-y-1">
                {renderNavItems(adminNavItems)}
              </div>
            </>
          )}

          {/* 설정 메뉴 */}
          {isAdmin && (
            <>
              <div className="my-4 border-t" />
              <div className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                설정
              </div>
              <div className="space-y-1">
                {renderNavItems(settingsNavItems)}
              </div>
            </>
          )}
        </nav>

        {/* 사용자 정보 */}
        <div className="border-t p-4">
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9">
              <AvatarFallback className="bg-primary/10 text-primary">
                {session?.user?.name?.charAt(0).toUpperCase() || 'U'}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium truncate">
                {session?.user?.name || '사용자'}
              </p>
              <p className="text-xs text-muted-foreground truncate">
                {session?.user?.department
                  ? DEPARTMENT_LABELS[session.user.department]
                  : ''}{' '}
                {session?.user?.role
                  ? `(${ROLE_LABELS[session.user.role]})`
                  : ''}
              </p>
            </div>
          </div>
        </div>
      </div>
    </aside>
  )
}
