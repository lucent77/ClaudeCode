'use client'

import { useState, useCallback } from 'react'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { CaseFilter } from '@/components/cases/case-filter'
import { CaseTable } from '@/components/cases/case-table'
import { CaseForm } from '@/components/cases/case-form'
import { useCases } from '@/hooks/use-cases'
import { useRealtimeCases } from '@/hooks/use-realtime'
import { useToast } from '@/hooks/use-toast'
import type { CaseWithRelations, Department } from '@/types'

interface CaseListClientProps {
  isAdmin: boolean
  userDepartment?: Department
}

export function CaseListClient({ isAdmin, userDepartment }: CaseListClientProps) {
  const { toast } = useToast()
  const { fetchCases, deleteCase } = useCases(
    isAdmin ? undefined : { department: userDepartment }
  )

  // 실시간 업데이트 구독
  useRealtimeCases()

  // 모달 상태
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [editingCase, setEditingCase] = useState<CaseWithRelations | null>(null)
  const [viewingCase, setViewingCase] = useState<CaseWithRelations | null>(null)

  // 케이스 상세 보기
  const handleViewCase = useCallback((caseData: CaseWithRelations) => {
    setViewingCase(caseData)
  }, [])

  // 케이스 수정
  const handleEditCase = useCallback((caseData: CaseWithRelations) => {
    setEditingCase(caseData)
    setIsFormOpen(true)
  }, [])

  // 케이스 삭제
  const handleDeleteCase = useCallback(
    async (caseData: CaseWithRelations) => {
      if (!confirm(`케이스 ${caseData.caseNumber}를 삭제하시겠습니까?`)) {
        return
      }

      const result = await deleteCase(caseData.id)

      if (result.success) {
        toast({
          title: '삭제 완료',
          description: '케이스가 삭제되었습니다.',
        })
      } else {
        toast({
          title: '삭제 실패',
          description: result.error,
          variant: 'destructive',
        })
      }
    },
    [deleteCase, toast]
  )

  // 작업자 할당 (추후 구현)
  const handleAssignCase = useCallback((caseData: CaseWithRelations) => {
    // TODO: 작업자 할당 모달 열기
    console.log('Assign case:', caseData.id)
  }, [])

  // 폼 닫기
  const handleFormClose = useCallback((open: boolean) => {
    setIsFormOpen(open)
    if (!open) {
      setEditingCase(null)
    }
  }, [])

  // 성공 시 새로고침
  const handleSuccess = useCallback(() => {
    fetchCases()
  }, [fetchCases])

  return (
    <div className="space-y-6">
      {/* 액션 바 */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <CaseFilter showDepartmentFilter={isAdmin} />

        {isAdmin && (
          <Button onClick={() => setIsFormOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            새 케이스
          </Button>
        )}
      </div>

      {/* 케이스 테이블 */}
      <div className="rounded-xl border bg-card">
        <CaseTable
          onViewCase={handleViewCase}
          onEditCase={isAdmin ? handleEditCase : undefined}
          onDeleteCase={isAdmin ? handleDeleteCase : undefined}
          onAssignCase={isAdmin ? handleAssignCase : undefined}
          isAdmin={isAdmin}
        />
      </div>

      {/* 케이스 생성/수정 폼 */}
      <CaseForm
        caseData={editingCase || undefined}
        open={isFormOpen}
        onOpenChange={handleFormClose}
        onSuccess={handleSuccess}
      />
    </div>
  )
}
