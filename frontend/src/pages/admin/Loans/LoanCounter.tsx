import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { useCreateLoan, useReturnLoan } from '@/hooks/useLoans'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { formatDate } from '@/lib/utils'
import { BookOpen, RotateCcw } from 'lucide-react'

const loanSchema = z.object({
  memberId: z.string().min(1, 'ID adhérent requis'),
  bookCopyId: z.string().min(1, 'Code-barres requis'),
  durationDays: z.number().optional(),
})

const returnSchema = z.object({
  loanId: z.string().min(1, 'ID emprunt requis'),
  condition: z.enum(['good', 'damaged', 'lost']),
  notes: z.string().optional(),
})

export default function LoanCounter() {
  const createLoan = useCreateLoan()
  const returnLoan = useReturnLoan()
  const [createdLoan, setCreatedLoan] = useState<{ id: string; dueDate: string } | null>(null)

  const loanForm = useForm<z.infer<typeof loanSchema>>({
    resolver: zodResolver(loanSchema),
    defaultValues: { memberId: '', bookCopyId: '' },
  })

  const returnForm = useForm<z.infer<typeof returnSchema>>({
    resolver: zodResolver(returnSchema),
    defaultValues: { loanId: '', condition: 'good' },
  })

  async function onCreateLoan(values: z.infer<typeof loanSchema>) {
    try {
      const loan = await createLoan.mutateAsync(values)
      setCreatedLoan({ id: loan.id, dueDate: loan.dueDate })
      loanForm.reset()
      toast.success(`Emprunt enregistré — retour prévu le ${formatDate(loan.dueDate)}`)
    } catch (err: unknown) {
      const error = err as { response?: { data?: { detail?: string } } }
      toast.error(error.response?.data?.detail ?? 'Erreur lors de l\'emprunt')
    }
  }

  async function onReturnLoan(values: z.infer<typeof returnSchema>) {
    try {
      await returnLoan.mutateAsync({ id: values.loanId, data: { condition: values.condition, notes: values.notes } })
      returnForm.reset()
      toast.success('Retour enregistré avec succès')
    } catch (err: unknown) {
      const error = err as { response?: { data?: { detail?: string } } }
      toast.error(error.response?.data?.detail ?? 'Erreur lors du retour')
    }
  }

  return (
    <div className="p-6 space-y-6">
      <h1 className="text-2xl font-bold">Gestion des emprunts</h1>

      <Tabs defaultValue="loan">
        <TabsList>
          <TabsTrigger value="loan">
            <BookOpen className="h-4 w-4 mr-2" />
            Nouvel emprunt
          </TabsTrigger>
          <TabsTrigger value="return">
            <RotateCcw className="h-4 w-4 mr-2" />
            Retour
          </TabsTrigger>
        </TabsList>

        <TabsContent value="loan">
          <Card className="max-w-lg">
            <CardHeader>
              <CardTitle>Enregistrer un emprunt</CardTitle>
            </CardHeader>
            <CardContent>
              <Form {...loanForm}>
                <form onSubmit={loanForm.handleSubmit(onCreateLoan)} className="space-y-4">
                  <FormField
                    control={loanForm.control}
                    name="memberId"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Numéro d'adhérent ou ID</FormLabel>
                        <FormControl>
                          <Input placeholder="BIB-2026-00001 ou UUID" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={loanForm.control}
                    name="bookCopyId"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Code-barres exemplaire ou ID</FormLabel>
                        <FormControl>
                          <Input placeholder="BC000101 ou UUID" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <Button type="submit" disabled={createLoan.isPending} className="w-full">
                    {createLoan.isPending ? 'Enregistrement...' : 'Enregistrer l\'emprunt'}
                  </Button>
                </form>
              </Form>

              {createdLoan && (
                <div className="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                  <p className="text-green-800 font-medium">Emprunt enregistré</p>
                  <p className="text-sm text-green-600">
                    Date de retour : <Badge>{formatDate(createdLoan.dueDate)}</Badge>
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="return">
          <Card className="max-w-lg">
            <CardHeader>
              <CardTitle>Enregistrer un retour</CardTitle>
            </CardHeader>
            <CardContent>
              <Form {...returnForm}>
                <form onSubmit={returnForm.handleSubmit(onReturnLoan)} className="space-y-4">
                  <FormField
                    control={returnForm.control}
                    name="loanId"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>ID de l'emprunt</FormLabel>
                        <FormControl>
                          <Input placeholder="UUID de l'emprunt" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={returnForm.control}
                    name="condition"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>État de retour</FormLabel>
                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="good">Bon état</SelectItem>
                            <SelectItem value="damaged">Endommagé</SelectItem>
                            <SelectItem value="lost">Perdu</SelectItem>
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <Button type="submit" disabled={returnLoan.isPending} className="w-full">
                    {returnLoan.isPending ? 'Enregistrement...' : 'Enregistrer le retour'}
                  </Button>
                </form>
              </Form>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
