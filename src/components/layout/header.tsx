'use client'

import { useSession, signOut } from 'next-auth/react'
import {
  LogOut,
  User,
  Settings,
  Bell,
  Wifi,
  WifiOff,
} from 'lucide-react'

import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { useSocketStatus } from '@/hooks/use-socket'
import { DEPARTMENT_LABELS, ROLE_LABELS } from '@/lib/utils'

interface HeaderProps {
  title?: string
}

export function Header({ title }: HeaderProps) {
  const { data: session } = useSession()
  const isConnected = useSocketStatus()

  const handleSignOut = async () => {
    await signOut({ callbackUrl: '/login' })
  }

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b bg-background px-6">
      {/* 페이지 제목 */}
      <div>
        <h1 className="text-xl font-semibold">{title || 'DentalFlow'}</h1>
      </div>

      {/* 오른쪽 영역 */}
      <div className="flex items-center gap-4">
        {/* 연결 상태 표시 */}
        <div
          className="flex items-center gap-1.5 text-xs"
          title={isConnected ? '실시간 연결됨' : '연결 끊김'}
        >
          {isConnected ? (
            <>
              <Wifi className="h-4 w-4 text-green-500" />
              <span className="text-muted-foreground hidden sm:inline">
                실시간
              </span>
            </>
          ) : (
            <>
              <WifiOff className="h-4 w-4 text-red-500" />
              <span className="text-muted-foreground hidden sm:inline">
                오프라인
              </span>
            </>
          )}
        </div>

        {/* 알림 버튼 (추후 구현) */}
        <Button variant="ghost" size="icon" className="relative">
          <Bell className="h-5 w-5" />
          {/* 알림 배지 (추후 구현) */}
          {/* <span className="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-[10px] text-white flex items-center justify-center">
            3
          </span> */}
        </Button>

        {/* 사용자 메뉴 */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              className="relative h-9 w-9 rounded-full"
            >
              <Avatar className="h-9 w-9">
                <AvatarFallback className="bg-primary text-primary-foreground">
                  {session?.user?.name?.charAt(0).toUpperCase() || 'U'}
                </AvatarFallback>
              </Avatar>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-56">
            <DropdownMenuLabel>
              <div className="flex flex-col space-y-1">
                <p className="text-sm font-medium">
                  {session?.user?.name || '사용자'}
                </p>
                <p className="text-xs text-muted-foreground">
                  {session?.user?.email}
                </p>
                <p className="text-xs text-muted-foreground">
                  {session?.user?.department
                    ? DEPARTMENT_LABELS[session.user.department]
                    : ''}{' '}
                  ({session?.user?.role ? ROLE_LABELS[session.user.role] : ''})
                </p>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem>
              <User className="mr-2 h-4 w-4" />
              <span>프로필</span>
            </DropdownMenuItem>
            <DropdownMenuItem>
              <Settings className="mr-2 h-4 w-4" />
              <span>설정</span>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={handleSignOut}
              className="text-red-600 focus:text-red-600"
            >
              <LogOut className="mr-2 h-4 w-4" />
              <span>로그아웃</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  )
}
