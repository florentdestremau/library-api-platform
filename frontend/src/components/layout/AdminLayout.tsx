import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import {
  BookOpen, Users, Library, ClipboardList, AlertTriangle,
  BookMarked, Bell, Settings, LayoutDashboard, LogOut,
} from 'lucide-react'

const navItems = [
  { to: '/admin', icon: LayoutDashboard, label: 'Tableau de bord', end: true },
  { to: '/admin/books', icon: BookOpen, label: 'Catalogue' },
  { to: '/admin/members', icon: Users, label: 'Adhérents' },
  { to: '/admin/loans', icon: Library, label: 'Emprunts' },
  { to: '/admin/loans/overdue', icon: AlertTriangle, label: 'Retards' },
  { to: '/admin/reservations', icon: BookMarked, label: 'Réservations' },
  { to: '/admin/notifications', icon: Bell, label: 'Notifications' },
  { to: '/admin/config', icon: Settings, label: 'Configuration' },
]

export default function AdminLayout() {
  const { userEmail, logout } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/login')
  }

  return (
    <div className="flex h-screen bg-background">
      {/* Sidebar */}
      <aside className="w-64 border-r bg-card flex flex-col">
        <div className="p-6 border-b">
          <div className="flex items-center gap-2">
            <BookOpen className="h-6 w-6 text-primary" />
            <span className="font-bold text-lg">Bibliothèque</span>
          </div>
          <p className="text-xs text-muted-foreground mt-1 truncate">{userEmail}</p>
        </div>

        <nav className="flex-1 p-4 space-y-1">
          {navItems.map(({ to, icon: Icon, label, end }) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
                  isActive
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                )
              }
            >
              <Icon className="h-4 w-4 flex-shrink-0" />
              {label}
            </NavLink>
          ))}
        </nav>

        <div className="p-4 border-t">
          <Button
            variant="ghost"
            size="sm"
            className="w-full justify-start text-muted-foreground"
            onClick={handleLogout}
          >
            <LogOut className="h-4 w-4 mr-2" />
            Déconnexion
          </Button>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-y-auto">
        <Outlet />
      </main>
    </div>
  )
}
