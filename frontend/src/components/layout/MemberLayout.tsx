import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { BookOpen, Library, BookMarked, User, Search, LogOut } from 'lucide-react'

const navItems = [
  { to: '/portail', icon: Library, label: 'Mes emprunts', end: true },
  { to: '/portail/reservations', icon: BookMarked, label: 'Mes réservations' },
  { to: '/portail/profil', icon: User, label: 'Mon profil' },
  { to: '/catalogue', icon: Search, label: 'Catalogue' },
]

export default function MemberLayout() {
  const { userEmail, logout } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/login')
  }

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b bg-card">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <BookOpen className="h-6 w-6 text-primary" />
            <span className="font-bold text-lg">Bibliothèque</span>
          </div>
          <nav className="flex items-center gap-4">
            {navItems.map(({ to, icon: Icon, label, end }) => (
              <NavLink
                key={to}
                to={to}
                end={end}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-2 text-sm px-3 py-2 rounded-md transition-colors',
                    isActive
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground hover:text-foreground'
                  )
                }
              >
                <Icon className="h-4 w-4" />
                {label}
              </NavLink>
            ))}
            <span className="text-xs text-muted-foreground">{userEmail}</span>
            <Button variant="ghost" size="sm" onClick={handleLogout}>
              <LogOut className="h-4 w-4 mr-1" />
              Déconnexion
            </Button>
          </nav>
        </div>
      </header>
      <main className="container mx-auto px-4 py-6">
        <Outlet />
      </main>
    </div>
  )
}
