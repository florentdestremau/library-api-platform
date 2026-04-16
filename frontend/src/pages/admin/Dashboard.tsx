import { useDashboardStats, useLoans } from '@/hooks/useLoans'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { formatDate, isOverdue } from '@/lib/utils'
import {
  Library, AlertTriangle, BookMarked, Users, BookOpen,
} from 'lucide-react'
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'

function StatCard({
  title, value, icon: Icon, variant = 'default',
}: {
  title: string; value: number | string; icon: React.ElementType; variant?: 'default' | 'warning' | 'destructive'
}) {
  const colors = {
    default: 'text-primary',
    warning: 'text-yellow-600',
    destructive: 'text-destructive',
  }
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <Icon className={`h-4 w-4 ${colors[variant]}`} />
      </CardHeader>
      <CardContent>
        <div className={`text-2xl font-bold ${colors[variant]}`}>{value}</div>
      </CardContent>
    </Card>
  )
}

export default function Dashboard() {
  const { data: stats, isLoading: statsLoading } = useDashboardStats()
  const { data: overdueData, isLoading: overdueLoading } = useLoans({ overdue: true })
  const { data: recentData } = useLoans({})

  if (statsLoading) {
    return (
      <div className="p-6 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-28" />
          ))}
        </div>
      </div>
    )
  }

  const topBooksData = (stats?.topBooks ?? []).map((b) => ({
    name: b.book_title.length > 20 ? b.book_title.substring(0, 20) + '...' : b.book_title,
    emprunts: b.borrow_count,
  }))

  return (
    <div className="p-6 space-y-6">
      <h1 className="text-3xl font-bold">Tableau de bord</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <StatCard title="Emprunts en cours" value={stats?.activeLoans ?? 0} icon={Library} />
        <StatCard
          title="Emprunts en retard"
          value={stats?.overdueLoans ?? 0}
          icon={AlertTriangle}
          variant={(stats?.overdueLoans ?? 0) > 0 ? 'destructive' : 'default'}
        />
        <StatCard title="Réservations" value={stats?.pendingReservations ?? 0} icon={BookMarked} />
        <StatCard title="Adhérents actifs" value={stats?.activeMembers ?? 0} icon={Users} />
        <StatCard title="Ouvrages" value={stats?.totalBooks ?? 0} icon={BookOpen} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Top livres */}
        <Card>
          <CardHeader>
            <CardTitle>Top ouvrages empruntés</CardTitle>
          </CardHeader>
          <CardContent>
            {topBooksData.length > 0 ? (
              <ResponsiveContainer width="100%" height={250}>
                <BarChart data={topBooksData} layout="vertical">
                  <XAxis type="number" />
                  <YAxis type="category" dataKey="name" width={140} tick={{ fontSize: 11 }} />
                  <Tooltip />
                  <Bar dataKey="emprunts" fill="hsl(var(--primary))" />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <p className="text-muted-foreground text-sm">Aucune donnée disponible</p>
            )}
          </CardContent>
        </Card>

        {/* Retards */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-destructive" />
              Emprunts en retard
            </CardTitle>
          </CardHeader>
          <CardContent>
            {overdueLoading ? (
              <Skeleton className="h-32" />
            ) : overdueData?.items.length === 0 ? (
              <p className="text-muted-foreground text-sm">Aucun retard</p>
            ) : (
              <div className="space-y-3">
                {overdueData?.items.slice(0, 5).map((loan) => (
                  <div key={loan.id} className="flex items-center justify-between text-sm border-b pb-2 last:border-0">
                    <div>
                      <p className="font-medium">{loan.bookCopy.book?.title}</p>
                      <p className="text-muted-foreground">
                        {loan.member.firstName} {loan.member.lastName}
                      </p>
                    </div>
                    <Badge variant="destructive">{formatDate(loan.dueDate)}</Badge>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Derniers emprunts */}
      <Card>
        <CardHeader>
          <CardTitle>Emprunts récents</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            {recentData?.items.slice(0, 10).map((loan) => (
              <div key={loan.id} className="flex items-center gap-4 text-sm py-2 border-b last:border-0">
                <div className="flex-1 min-w-0">
                  <p className="font-medium truncate">{loan.bookCopy.book?.title}</p>
                  <p className="text-muted-foreground">
                    {loan.member.firstName} {loan.member.lastName} — {loan.bookCopy.barcode}
                  </p>
                </div>
                <div className="text-right shrink-0">
                  <p>{formatDate(loan.borrowedAt)}</p>
                  <Badge
                    variant={loan.returnedAt ? 'secondary' : isOverdue(loan.dueDate) ? 'destructive' : 'default'}
                  >
                    {loan.returnedAt ? 'Rendu' : isOverdue(loan.dueDate) ? 'Retard' : 'En cours'}
                  </Badge>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
