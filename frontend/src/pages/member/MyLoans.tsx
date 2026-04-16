import { useMyLoans, useRenewLoan } from '@/hooks/useLoans'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { toast } from 'sonner'
import { formatDate, isOverdue, daysUntil } from '@/lib/utils'
import { RotateCcw, AlertTriangle, CheckCircle } from 'lucide-react'

export default function MyLoans() {
  const { data, isLoading } = useMyLoans()
  const renewLoan = useRenewLoan()

  async function handleRenew(loanId: string) {
    try {
      await renewLoan.mutateAsync(loanId)
      toast.success('Renouvellement confirmé')
    } catch (err: unknown) {
      const error = err as { response?: { data?: { detail?: string } } }
      toast.error(error.response?.data?.detail ?? 'Impossible de renouveler')
    }
  }

  const activeLoans = data?.items.filter((l) => !l.returnedAt) ?? []
  const pastLoans = data?.items.filter((l) => l.returnedAt) ?? []

  if (isLoading) return <Skeleton className="h-64" />

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Mes emprunts</h1>

      <div className="space-y-4">
        <h2 className="text-lg font-semibold">En cours ({activeLoans.length})</h2>
        {activeLoans.length === 0 ? (
          <Card>
            <CardContent className="py-8 text-center text-muted-foreground">
              <CheckCircle className="h-8 w-8 mx-auto mb-2 text-green-500" />
              Aucun emprunt en cours
            </CardContent>
          </Card>
        ) : (
          activeLoans.map((loan) => {
            const days = daysUntil(loan.dueDate)
            const overdue = isOverdue(loan.dueDate)
            return (
              <Card key={loan.id} className={overdue ? 'border-destructive' : days <= 3 ? 'border-yellow-400' : ''}>
                <CardHeader className="pb-2">
                  <div className="flex items-start justify-between">
                    <CardTitle className="text-base">{loan.bookCopy.book?.title}</CardTitle>
                    {overdue ? (
                      <Badge variant="destructive" className="flex items-center gap-1">
                        <AlertTriangle className="h-3 w-3" />
                        En retard
                      </Badge>
                    ) : days <= 3 ? (
                      <Badge variant="outline" className="border-yellow-400 text-yellow-700">
                        J-{days}
                      </Badge>
                    ) : (
                      <Badge>En cours</Badge>
                    )}
                  </div>
                </CardHeader>
                <CardContent className="space-y-2">
                  <div className="text-sm text-muted-foreground">
                    <span>Emprunté le {formatDate(loan.borrowedAt)}</span>
                    {' · '}
                    <span>Retour prévu le <strong>{formatDate(loan.dueDate)}</strong></span>
                  </div>
                  {overdue && (
                    <p className="text-sm text-destructive">
                      Pénalités : {loan.lateFee} €
                    </p>
                  )}
                  {loan.renewedCount === 0 && !overdue && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleRenew(loan.id)}
                      disabled={renewLoan.isPending}
                    >
                      <RotateCcw className="h-3 w-3 mr-1" />
                      Renouveler
                    </Button>
                  )}
                  {loan.renewedCount > 0 && (
                    <p className="text-xs text-muted-foreground">Renouvellement déjà effectué</p>
                  )}
                </CardContent>
              </Card>
            )
          })
        )}
      </div>

      {pastLoans.length > 0 && (
        <div className="space-y-4">
          <h2 className="text-lg font-semibold">Historique ({pastLoans.length})</h2>
          <div className="divide-y border rounded-lg">
            {pastLoans.slice(0, 24).map((loan) => (
              <div key={loan.id} className="px-4 py-3 flex items-center justify-between text-sm">
                <div>
                  <p className="font-medium">{loan.bookCopy.book?.title}</p>
                  <p className="text-muted-foreground">
                    {formatDate(loan.borrowedAt)} → {formatDate(loan.returnedAt)}
                  </p>
                </div>
                <Badge variant="secondary">Rendu</Badge>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
