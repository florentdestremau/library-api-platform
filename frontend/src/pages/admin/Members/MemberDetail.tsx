import { useNavigate, useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getMember, getMemberLoans } from '@/api/members'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { formatDate, isOverdue } from '@/lib/utils'
import { ArrowLeft, Edit, Mail, Phone, MapPin, Calendar } from 'lucide-react'
import type { Member } from '@/types'

const statusMap: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
  active: { label: 'Actif', variant: 'default' },
  suspended: { label: 'Suspendu', variant: 'destructive' },
  expired: { label: 'Expiré', variant: 'secondary' },
  archived: { label: 'Archivé', variant: 'outline' },
}

export default function MemberDetail() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const { data: member, isLoading } = useQuery({
    queryKey: ['members', id],
    queryFn: () => getMember(id!),
    enabled: !!id,
  })

  const { data: loansData } = useQuery({
    queryKey: ['member-loans', id],
    queryFn: () => getMemberLoans(id!),
    enabled: !!id,
  })

  if (isLoading) return <div className="p-6"><Skeleton className="h-64" /></div>
  if (!member) return <div className="p-6">Adhérent introuvable</div>

  const status = statusMap[member.status] ?? { label: member.status, variant: 'outline' as const }

  return (
    <div className="p-6 space-y-6 max-w-4xl">
      <div className="flex items-center justify-between">
        <button onClick={() => navigate('/admin/members')} className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
          <ArrowLeft className="h-4 w-4" />
          Retour
        </button>
        <Link to={`/admin/members/${id}/edit`}>
          <Button variant="outline" size="sm">
            <Edit className="h-4 w-4 mr-2" />
            Modifier
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <div className="flex items-start justify-between">
              <div>
                <CardTitle className="text-xl">{member.firstName} {member.lastName}</CardTitle>
                <p className="text-sm text-muted-foreground font-mono mt-1">{member.memberNumber}</p>
              </div>
              <Badge variant={status.variant}>{status.label}</Badge>
            </div>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex items-center gap-2 text-sm">
              <Mail className="h-4 w-4 text-muted-foreground" />
              <span>{member.email}</span>
            </div>
            {member.phone && (
              <div className="flex items-center gap-2 text-sm">
                <Phone className="h-4 w-4 text-muted-foreground" />
                <span>{member.phone}</span>
              </div>
            )}
            {member.address && (
              <div className="flex items-center gap-2 text-sm">
                <MapPin className="h-4 w-4 text-muted-foreground" />
                <span>{member.address}</span>
              </div>
            )}
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="h-4 w-4 text-muted-foreground" />
              <span>Adhésion jusqu'au {formatDate(member.membershipExpiry)}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Quotas</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Emprunts simultanés</span>
              <span className="font-medium">{member.maxLoans}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Réservations simultanées</span>
              <span className="font-medium">{member.maxReservations}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Inscrit le</span>
              <span className="font-medium">{formatDate(member.createdAt)}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Historique des emprunts ({loansData?.total ?? 0})</CardTitle></CardHeader>
        <CardContent>
          {!loansData?.items.length ? (
            <p className="text-muted-foreground text-sm">Aucun emprunt</p>
          ) : (
            <div className="space-y-2">
              {loansData.items.map((loan) => (
                <div key={loan.id} className="flex items-center justify-between text-sm py-2 border-b last:border-0">
                  <div>
                    <p className="font-medium">{loan.bookCopy.book?.title}</p>
                    <p className="text-muted-foreground text-xs">
                      {formatDate(loan.borrowedAt)} → {loan.returnedAt ? formatDate(loan.returnedAt) : 'en cours'}
                    </p>
                  </div>
                  <Badge variant={loan.returnedAt ? 'secondary' : isOverdue(loan.dueDate) ? 'destructive' : 'default'}>
                    {loan.returnedAt ? 'Rendu' : isOverdue(loan.dueDate) ? 'Retard' : 'En cours'}
                  </Badge>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
