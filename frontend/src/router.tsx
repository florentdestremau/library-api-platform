import { createBrowserRouter, Navigate } from 'react-router-dom'
import { lazy, Suspense } from 'react'
import { Skeleton } from '@/components/ui/skeleton'
import { getAccessToken, isLibrarian, isMember } from '@/lib/auth'

const Login = lazy(() => import('@/pages/auth/Login'))
const AdminLayout = lazy(() => import('@/components/layout/AdminLayout'))
const MemberLayout = lazy(() => import('@/components/layout/MemberLayout'))
const Dashboard = lazy(() => import('@/pages/admin/Dashboard'))
const BookListAdmin = lazy(() => import('@/pages/admin/Books/BookListAdmin'))
const BookForm = lazy(() => import('@/pages/admin/Books/BookForm'))
const LoanCounter = lazy(() => import('@/pages/admin/Loans/LoanCounter'))
const LoanList = lazy(() => import('@/pages/admin/Loans/LoanList'))
const MemberList = lazy(() => import('@/pages/admin/Members/MemberList'))
const MemberDetail = lazy(() => import('@/pages/admin/Members/MemberDetail'))
const MemberForm = lazy(() => import('@/pages/admin/Members/MemberForm'))
const BookList = lazy(() => import('@/pages/catalog/BookList'))
const MyLoans = lazy(() => import('@/pages/member/MyLoans'))
const MyReservations = lazy(() => import('@/pages/member/MyReservations'))

function Loading() {
  return <div className="p-8"><Skeleton className="h-64 w-full" /></div>
}

function RequireAuth({ children, requireLibrarian = false }: {
  children: React.ReactNode
  requireLibrarian?: boolean
}) {
  const token = getAccessToken()
  if (!token) return <Navigate to="/login" replace />
  if (requireLibrarian && !isLibrarian(token)) return <Navigate to="/portail" replace />
  return <>{children}</>
}

function S({ component: Component }: { component: React.ComponentType }) {
  return (
    <Suspense fallback={<Loading />}>
      <Component />
    </Suspense>
  )
}

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <S component={Login} />,
  },
  {
    path: '/catalogue',
    element: (
      <div className="container mx-auto px-4 py-6">
        <h1 className="text-2xl font-bold mb-6">Catalogue</h1>
        <S component={BookList} />
      </div>
    ),
  },
  {
    path: '/admin',
    element: (
      <RequireAuth requireLibrarian>
        <S component={AdminLayout} />
      </RequireAuth>
    ),
    children: [
      { index: true, element: <S component={Dashboard} /> },
      { path: 'books', element: <S component={BookListAdmin} /> },
      { path: 'books/new', element: <S component={BookForm} /> },
      { path: 'books/:id/edit', element: <S component={BookForm} /> },
      { path: 'loans', element: <S component={LoanCounter} /> },
      { path: 'loans/list', element: <S component={LoanList} /> },
      { path: 'loans/overdue', element: <S component={LoanList} /> },
      { path: 'members', element: <S component={MemberList} /> },
      { path: 'members/new', element: <S component={MemberForm} /> },
      { path: 'members/:id', element: <S component={MemberDetail} /> },
      { path: 'members/:id/edit', element: <S component={MemberForm} /> },
      {
        path: 'reservations',
        element: (
          <div className="p-6">
            <h1 className="text-2xl font-bold">Réservations</h1>
            <p className="text-muted-foreground mt-2">Liste des réservations en cours</p>
          </div>
        ),
      },
      {
        path: 'notifications',
        element: (
          <div className="p-6">
            <h1 className="text-2xl font-bold">Notifications</h1>
            <p className="text-muted-foreground mt-2">Historique des notifications envoyées</p>
          </div>
        ),
      },
      {
        path: 'config',
        element: (
          <div className="p-6">
            <h1 className="text-2xl font-bold">Configuration</h1>
            <p className="text-muted-foreground mt-2">Paramètres de l'application</p>
          </div>
        ),
      },
    ],
  },
  {
    path: '/portail',
    element: (
      <RequireAuth>
        <S component={MemberLayout} />
      </RequireAuth>
    ),
    children: [
      { index: true, element: <S component={MyLoans} /> },
      { path: 'reservations', element: <S component={MyReservations} /> },
      {
        path: 'profil',
        element: (
          <div>
            <h1 className="text-2xl font-bold">Mon profil</h1>
          </div>
        ),
      },
    ],
  },
  {
    path: '/',
    element: <Navigate to="/catalogue" replace />,
  },
  {
    path: '*',
    element: (
      <div className="flex items-center justify-center h-screen">
        <div className="text-center">
          <h1 className="text-4xl font-bold">404</h1>
          <p className="text-muted-foreground mt-2">Page introuvable</p>
        </div>
      </div>
    ),
  },
])
