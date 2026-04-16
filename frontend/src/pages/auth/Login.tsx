import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuth } from '@/hooks/useAuth'
import { isLibrarian as checkLibrarian } from '@/lib/auth'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { BookOpen } from 'lucide-react'

const loginSchema = z.object({
  username: z.string().email('Email invalide'),
  password: z.string().min(1, 'Mot de passe requis'),
})

type LoginForm = z.infer<typeof loginSchema>

const DEMO_ACCOUNTS = [
  { label: 'Admin', username: 'admin@bibliotheque.fr', password: 'Admin1234!' },
  { label: 'Bibliothécaire', username: 'bibliothecaire@bibliotheque.fr', password: 'Biblio1234!' },
  { label: 'Adhérent', username: 'alice.martin@example.com', password: 'password123' },
]

export default function Login() {
  const navigate = useNavigate()
  const { login, isLoading } = useAuth()

  const form = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
    defaultValues: { username: '', password: '' },
  })

  async function onSubmit(values: LoginForm) {
    try {
      const { token } = await login(values)
      toast.success('Connexion réussie')
      if (checkLibrarian(token)) {
        navigate('/admin')
      } else {
        navigate('/portail')
      }
    } catch {
      toast.error('Identifiants incorrects')
    }
  }

  async function loginAs(username: string, password: string) {
    form.setValue('username', username)
    form.setValue('password', password)
    try {
      const { token } = await login({ username, password })
      toast.success('Connexion réussie')
      if (checkLibrarian(token)) {
        navigate('/admin')
      } else {
        navigate('/portail')
      }
    } catch {
      toast.error('Identifiants incorrects')
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/50">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <div className="flex justify-center mb-4">
            <BookOpen className="h-12 w-12 text-primary" />
          </div>
          <CardTitle className="text-2xl">Bibliothèque</CardTitle>
          <CardDescription>Connectez-vous à votre compte</CardDescription>
        </CardHeader>
        <CardContent>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
              <FormField
                control={form.control}
                name="username"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                      <Input type="email" placeholder="votre@email.fr" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Mot de passe</FormLabel>
                    <FormControl>
                      <Input type="password" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <Button type="submit" className="w-full" disabled={isLoading}>
                {isLoading ? 'Connexion...' : 'Se connecter'}
              </Button>
            </form>
          </Form>
          <div className="mt-6 p-4 bg-muted rounded-lg space-y-2">
            <p className="text-xs font-medium text-muted-foreground mb-3">Comptes de démo — cliquez pour vous connecter :</p>
            <div className="flex gap-2">
              {DEMO_ACCOUNTS.map((account) => (
                <button
                  key={account.username}
                  onClick={() => loginAs(account.username, account.password)}
                  disabled={isLoading}
                  className="flex-1 text-xs px-3 py-2 rounded-md border border-border bg-background hover:bg-accent hover:text-accent-foreground transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span className="block font-medium">{account.label}</span>
                  <span className="block text-muted-foreground truncate">{account.username}</span>
                </button>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
