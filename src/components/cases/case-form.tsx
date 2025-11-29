'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/hooks/use-toast'
import { DEPARTMENT_LABELS } from '@/lib/utils'
import type { CaseWithRelations, CaseCreateInput, Department } from '@/types'

interface CaseFormProps {
  caseData?: CaseWithRelations
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: () => void
}

const departments: Department[] = [
  'FRONT_DESK',
  'PRE_CAD',
  'SOLIDEX_DESIGN',
  'COCR_DESIGN',
  'PRINT_3D_DESIGN',
  'PRE_CAM',
  'SOLIDEX',
  'COCR',
  'PRINT_3D',
]

export function CaseForm({
  caseData,
  open,
  onOpenChange,
  onSuccess,
}: CaseFormProps) {
  const router = useRouter()
  const { toast } = useToast()
  const [isLoading, setIsLoading] = useState(false)

  // 폼 상태
  const [formData, setFormData] = useState<Partial<CaseCreateInput>>({
    labDoctorName: '',
    patientName: '',
    panNumber: '',
    quantity: 1,
    toothNumbers: [],
    toothColor: '',
    implantType: '',
    dueDate: '',
    cocrType: '',
    solidexType: '',
    print3dType: '',
    noteOptions: [],
    noteText: '',
    workflow: [],
  })

  const [toothNumbersInput, setToothNumbersInput] = useState('')
  const [selectedWorkflow, setSelectedWorkflow] = useState<Department[]>([])

  // 수정 모드에서 데이터 로드
  useEffect(() => {
    if (caseData) {
      setFormData({
        labDoctorName: caseData.labDoctorName,
        patientName: caseData.patientName,
        panNumber: caseData.panNumber || '',
        quantity: caseData.quantity,
        toothNumbers: caseData.toothNumbers as string[],
        toothColor: caseData.toothColor || '',
        implantType: caseData.implantType || '',
        dueDate: new Date(caseData.dueDate).toISOString().split('T')[0],
        cocrType: caseData.cocrType || '',
        solidexType: caseData.solidexType || '',
        print3dType: caseData.print3dType || '',
        noteOptions: caseData.noteOptions as string[],
        noteText: caseData.noteText || '',
        workflow: caseData.workflow as Department[],
      })
      setToothNumbersInput((caseData.toothNumbers as string[]).join(', '))
      setSelectedWorkflow(caseData.workflow as Department[])
    } else {
      // 초기화
      setFormData({
        labDoctorName: '',
        patientName: '',
        panNumber: '',
        quantity: 1,
        toothNumbers: [],
        toothColor: '',
        implantType: '',
        dueDate: '',
        cocrType: '',
        solidexType: '',
        print3dType: '',
        noteOptions: [],
        noteText: '',
        workflow: [],
      })
      setToothNumbersInput('')
      setSelectedWorkflow([])
    }
  }, [caseData, open])

  const handleInputChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target
    setFormData((prev) => ({ ...prev, [name]: value }))
  }

  const handleWorkflowToggle = (dept: Department) => {
    setSelectedWorkflow((prev) => {
      if (prev.includes(dept)) {
        return prev.filter((d) => d !== dept)
      }
      return [...prev, dept]
    })
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)

    try {
      // 치아 번호 파싱
      const toothNumbers = toothNumbersInput
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)

      const payload = {
        ...formData,
        toothNumbers,
        workflow: selectedWorkflow,
        quantity: Number(formData.quantity) || 1,
      }

      const url = caseData ? `/api/cases/${caseData.id}` : '/api/cases'
      const method = caseData ? 'PUT' : 'POST'

      // 수정 시 version 추가
      if (caseData) {
        (payload as Record<string, unknown>).version = caseData.version
      }

      const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })

      const result = await response.json()

      if (!response.ok) {
        if (result.code === 'CONFLICT') {
          toast({
            title: '충돌 발생',
            description: result.error,
            variant: 'destructive',
          })
        } else {
          toast({
            title: '오류',
            description: result.error || '저장에 실패했습니다',
            variant: 'destructive',
          })
        }
        return
      }

      toast({
        title: '성공',
        description: caseData ? '케이스가 수정되었습니다' : '케이스가 생성되었습니다',
      })

      onOpenChange(false)
      onSuccess?.()
      router.refresh()
    } catch (error) {
      toast({
        title: '오류',
        description: '저장 중 오류가 발생했습니다',
        variant: 'destructive',
      })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {caseData ? '케이스 수정' : '새 케이스 생성'}
          </DialogTitle>
          <DialogDescription>
            케이스 정보를 입력하세요. * 표시는 필수 항목입니다.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* 기본 정보 */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="labDoctorName">LAB/DOCTOR *</Label>
              <Input
                id="labDoctorName"
                name="labDoctorName"
                value={formData.labDoctorName}
                onChange={handleInputChange}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="patientName">환자명 *</Label>
              <Input
                id="patientName"
                name="patientName"
                value={formData.patientName}
                onChange={handleInputChange}
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label htmlFor="panNumber">Pan Number</Label>
              <Input
                id="panNumber"
                name="panNumber"
                value={formData.panNumber}
                onChange={handleInputChange}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="quantity">개수 *</Label>
              <Input
                id="quantity"
                name="quantity"
                type="number"
                min={1}
                value={formData.quantity}
                onChange={handleInputChange}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="dueDate">마감일 *</Label>
              <Input
                id="dueDate"
                name="dueDate"
                type="date"
                value={formData.dueDate as string}
                onChange={handleInputChange}
                required
              />
            </div>
          </div>

          {/* 치아 정보 */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="toothNumbers">치아 번호 (쉼표로 구분)</Label>
              <Input
                id="toothNumbers"
                value={toothNumbersInput}
                onChange={(e) => setToothNumbersInput(e.target.value)}
                placeholder="11, 12, 21"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="toothColor">치아색</Label>
              <Input
                id="toothColor"
                name="toothColor"
                value={formData.toothColor}
                onChange={handleInputChange}
                placeholder="A2, B1 등"
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="implantType">임플란트 종류</Label>
            <Input
              id="implantType"
              name="implantType"
              value={formData.implantType}
              onChange={handleInputChange}
            />
          </div>

          {/* 작업 유형 */}
          <div className="grid grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label htmlFor="solidexType">Solidex Type</Label>
              <Input
                id="solidexType"
                name="solidexType"
                value={formData.solidexType}
                onChange={handleInputChange}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="cocrType">Cocr Type</Label>
              <Input
                id="cocrType"
                name="cocrType"
                value={formData.cocrType}
                onChange={handleInputChange}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="print3dType">3D Print Type</Label>
              <Input
                id="print3dType"
                name="print3dType"
                value={formData.print3dType}
                onChange={handleInputChange}
              />
            </div>
          </div>

          {/* 워크플로우 */}
          <div className="space-y-2">
            <Label>워크플로우 (거쳐야 할 부서 선택) *</Label>
            <div className="flex flex-wrap gap-2">
              {departments.map((dept) => (
                <Button
                  key={dept}
                  type="button"
                  variant={selectedWorkflow.includes(dept) ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => handleWorkflowToggle(dept)}
                >
                  {DEPARTMENT_LABELS[dept]}
                </Button>
              ))}
            </div>
            {selectedWorkflow.length > 0 && (
              <p className="text-sm text-muted-foreground">
                순서: {selectedWorkflow.map((d) => DEPARTMENT_LABELS[d]).join(' → ')}
              </p>
            )}
          </div>

          {/* NOTE */}
          <div className="space-y-2">
            <Label htmlFor="noteText">NOTE</Label>
            <textarea
              id="noteText"
              name="noteText"
              value={formData.noteText}
              onChange={handleInputChange}
              className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="추가 메모..."
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isLoading}
            >
              취소
            </Button>
            <Button type="submit" disabled={isLoading || selectedWorkflow.length === 0}>
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {caseData ? '수정' : '생성'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
