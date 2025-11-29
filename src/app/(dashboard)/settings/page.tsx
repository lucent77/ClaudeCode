import { redirect } from 'next/navigation'
import { auth } from '@/lib/auth'
import { Header } from '@/components/layout/header'
import Link from 'next/link'
import {
  Building2,
  Tags,
  FormInput,
  ChevronRight,
} from 'lucide-react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

/**
 * 설정 메인 페이지
 * 부서 설정, 작업 유형, 커스텀 필드 등으로 이동
 */
export default async function SettingsPage() {
  const session = await auth()

  if (session?.user?.role !== 'ADMIN') {
    redirect('/my-tasks')
  }

  const settingsItems = [
    {
      title: '부서 설정',
      description: '부서별 작업 유형, 커스텀 필드, 상태를 관리합니다.',
      href: '/settings/departments',
      icon: Building2,
    },
    {
      title: '작업 유형 관리',
      description: '부서별 작업 유형을 추가하고 관리합니다.',
      href: '/settings/task-types',
      icon: Tags,
    },
    {
      title: '커스텀 필드 관리',
      description: '부서별 추가 입력 필드를 정의합니다.',
      href: '/settings/custom-fields',
      icon: FormInput,
    },
  ]

  return (
    <div className="flex flex-col">
      <Header title="설정" />

      <div className="flex-1 p-6">
        <div className="max-w-3xl space-y-4">
          {settingsItems.map((item) => (
            <Link key={item.href} href={item.href}>
              <Card className="cursor-pointer transition-shadow hover:shadow-md">
                <CardHeader className="flex flex-row items-center gap-4">
                  <div className="p-2 rounded-lg bg-primary/10">
                    <item.icon className="h-6 w-6 text-primary" />
                  </div>
                  <div className="flex-1">
                    <CardTitle className="text-base">{item.title}</CardTitle>
                    <CardDescription>{item.description}</CardDescription>
                  </div>
                  <ChevronRight className="h-5 w-5 text-muted-foreground" />
                </CardHeader>
              </Card>
            </Link>
          ))}
        </div>
      </div>
    </div>
  )
}
