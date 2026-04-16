import { useLoans } from '@/hooks/useLoans'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { formatDate, isOverdue } from '@/lib/utils'

export default function LoanList() {
  const { data, isLoading } = useLoans({})

  return (
    <div className="p-6 space-y-4">
      <h1 className="text-2xl font-bold">Emprunts en cours</h1>
      <div className="text-sm text-muted-foreground">{data?.total ?? 0} emprunt(s)</div>

      <div className="border rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Ouvrage</TableHead>
              <TableHead>Code-barres</TableHead>
              <TableHead>Adhérent</TableHead>
              <TableHead>Emprunté le</TableHead>
              <TableHead>Retour prévu</TableHead>
              <TableHead>Statut</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 6 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : data?.items.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                  Aucun emprunt en cours
                </TableCell>
              </TableRow>
            ) : (
              data?.items.map((loan) => (
                <TableRow key={loan.id}>
                  <TableCell className="font-medium max-w-xs truncate">
                    {loan.bookCopy.book?.title}
                  </TableCell>
                  <TableCell className="text-muted-foreground text-sm font-mono">
                    {loan.bookCopy.barcode}
                  </TableCell>
                  <TableCell>
                    {loan.member.firstName} {loan.member.lastName}
                    <span className="block text-xs text-muted-foreground">{loan.member.memberNumber}</span>
                  </TableCell>
                  <TableCell className="text-sm">{formatDate(loan.borrowedAt)}</TableCell>
                  <TableCell className="text-sm">{formatDate(loan.dueDate)}</TableCell>
                  <TableCell>
                    {loan.returnedAt ? (
                      <Badge variant="secondary">Rendu</Badge>
                    ) : isOverdue(loan.dueDate) ? (
                      <Badge variant="destructive">En retard</Badge>
                    ) : (
                      <Badge>En cours</Badge>
                    )}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  )
}
