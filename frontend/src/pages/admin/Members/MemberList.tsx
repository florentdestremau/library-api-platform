import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getMembers } from '@/api/members'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Search, Eye } from 'lucide-react'
import { formatDate } from '@/lib/utils'

const statusLabels: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
  active: { label: 'Actif', variant: 'default' },
  suspended: { label: 'Suspendu', variant: 'destructive' },
  expired: { label: 'Expiré', variant: 'secondary' },
  archived: { label: 'Archivé', variant: 'outline' },
}

export default function MemberList() {
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['members', search],
    queryFn: () => getMembers({ lastName: search || undefined }),
  })

  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Adhérents</h1>
        <Link to="/admin/members/new">
          <Button>
            <Plus className="h-4 w-4 mr-2" />
            Nouvel adhérent
          </Button>
        </Link>
      </div>

      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Rechercher par nom..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-9"
        />
      </div>

      <div className="text-sm text-muted-foreground">{data?.total ?? 0} adhérent(s)</div>

      <div className="border rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>N° adhérent</TableHead>
              <TableHead>Nom</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Statut</TableHead>
              <TableHead>Expiration</TableHead>
              <TableHead className="text-right">Actions</TableHead>
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
                  Aucun adhérent trouvé
                </TableCell>
              </TableRow>
            ) : (
              data?.items.map((member) => {
                const status = statusLabels[member.status] ?? { label: member.status, variant: 'outline' as const }
                return (
                  <TableRow key={member.id}>
                    <TableCell className="font-mono text-sm">{member.memberNumber}</TableCell>
                    <TableCell className="font-medium">
                      {member.firstName} {member.lastName}
                    </TableCell>
                    <TableCell className="text-muted-foreground text-sm">{member.email}</TableCell>
                    <TableCell>
                      <Badge variant={status.variant}>{status.label}</Badge>
                    </TableCell>
                    <TableCell className="text-sm">{formatDate(member.membershipExpiry)}</TableCell>
                    <TableCell className="text-right">
                      <Link to={`/admin/members/${member.id}`}>
                        <Button variant="ghost" size="sm"><Eye className="h-4 w-4" /></Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                )
              })
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  )
}
