import { useMyReservations } from '@/hooks/useLoans'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { cancelReservation } from '@/api/reservations'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { toast } from 'sonner'
import { formatDate } from '@/lib/utils'

const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
  pending: { label: 'En attente', variant: 'default' },
  notified: { label: 'Disponible', variant: 'default' },
  fulfilled: { label: 'Récupéré', variant: 'secondary' },
  cancelled: { label: 'Annulé', variant: 'outline' },
  expired: { label: 'Expiré', variant: 'destructive' },
}

export default function MyReservations() {
  const { data, isLoading } = useMyReservations()
  const queryClient = useQueryClient()

  const cancel = useMutation({
    mutationFn: cancelReservation,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['my-reservations'] })
      toast.success('Réservation annulée')
    },
    onError: () => toast.error('Erreur lors de l\'annulation'),
  })

  if (isLoading) return <Skeleton className="h-64" />

  const active = data?.items.filter((r) => ['pending', 'notified'].includes(r.status)) ?? []

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Mes réservations</h1>

      {active.length === 0 ? (
        <Card>
          <CardContent className="py-8 text-center text-muted-foreground">
            Aucune réservation en cours
          </CardContent>
        </Card>
      ) : (
        active.map((reservation) => {
          const status = statusMap[reservation.status]
          return (
            <Card key={reservation.id}
              className={reservation.status === 'notified' ? 'border-green-400' : ''}
            >
              <CardHeader className="pb-2">
                <div className="flex items-start justify-between">
                  <CardTitle className="text-base">{reservation.book.title}</CardTitle>
                  <Badge variant={status.variant}>{status.label}</Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-2">
                <p className="text-sm text-muted-foreground">
                  Position : {reservation.queuePosition} · Réservé le {formatDate(reservation.createdAt)}
                </p>
                {reservation.status === 'notified' && reservation.expiresAt && (
                  <p className="text-sm text-green-700 font-medium">
                    L'ouvrage est disponible ! Récupérez-le avant le {formatDate(reservation.expiresAt)}
                  </p>
                )}
                {['pending', 'notified'].includes(reservation.status) && (
                  <Button
                    variant="outline"
                    size="sm"
                    className="text-destructive"
                    onClick={() => cancel.mutate(reservation.id)}
                    disabled={cancel.isPending}
                  >
                    Annuler la réservation
                  </Button>
                )}
              </CardContent>
            </Card>
          )
        })
      )}
    </div>
  )
}
