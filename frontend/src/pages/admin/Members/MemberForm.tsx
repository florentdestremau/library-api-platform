import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getMember, createMember, updateMember } from '@/api/members'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ArrowLeft } from 'lucide-react'

const schema = z.object({
  firstName: z.string().min(1, 'Requis'),
  lastName: z.string().min(1, 'Requis'),
  email: z.string().email('Email invalide'),
  phone: z.string().optional(),
  address: z.string().optional(),
  status: z.enum(['active', 'suspended', 'expired', 'archived']),
  maxLoans: z.string().min(1),
  maxReservations: z.string().min(1),
})

type FormData = z.infer<typeof schema>

export default function MemberForm() {
  const { id } = useParams<{ id: string }>()
  const isEdit = !!id
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data: member, isLoading } = useQuery({
    queryKey: ['members', id],
    queryFn: () => getMember(id!),
    enabled: isEdit,
  })

  const form = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      address: '',
      status: 'active',
      maxLoans: '5',
      maxReservations: '3',
    },
  })

  useEffect(() => {
    if (member) {
      form.reset({
        firstName: member.firstName,
        lastName: member.lastName,
        email: member.email,
        phone: member.phone ?? '',
        address: member.address ?? '',
        status: member.status,
        maxLoans: String(member.maxLoans),
        maxReservations: String(member.maxReservations),
      })
    }
  }, [member, form])

  const mutation = useMutation({
    mutationFn: (data: FormData) => {
      const payload = {
        ...data,
        maxLoans: parseInt(data.maxLoans),
        maxReservations: parseInt(data.maxReservations),
      }
      return isEdit ? updateMember(id!, payload) : createMember(payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['members'] })
      toast.success(isEdit ? 'Adhérent modifié' : 'Adhérent créé')
      navigate('/admin/members')
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { detail?: string } } }
      toast.error(e.response?.data?.detail ?? 'Erreur')
    },
  })

  if (isEdit && isLoading) return <div className="p-6">Chargement...</div>

  return (
    <div className="p-6 max-w-2xl">
      <button onClick={() => navigate('/admin/members')} className="flex items-center gap-1 text-sm text-muted-foreground mb-6 hover:text-foreground">
        <ArrowLeft className="h-4 w-4" />
        Retour
      </button>
      <Card>
        <CardHeader>
          <CardTitle>{isEdit ? 'Modifier l\'adhérent' : 'Nouvel adhérent'}</CardTitle>
        </CardHeader>
        <CardContent>
          <Form {...form}>
            <form onSubmit={form.handleSubmit((d) => mutation.mutate(d))} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <FormField control={form.control} name="firstName" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Prénom</FormLabel>
                    <FormControl><Input {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
                <FormField control={form.control} name="lastName" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Nom</FormLabel>
                    <FormControl><Input {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
              </div>
              <FormField control={form.control} name="email" render={({ field }) => (
                <FormItem>
                  <FormLabel>Email</FormLabel>
                  <FormControl><Input type="email" {...field} /></FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <FormField control={form.control} name="phone" render={({ field }) => (
                <FormItem>
                  <FormLabel>Téléphone</FormLabel>
                  <FormControl><Input {...field} /></FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <FormField control={form.control} name="address" render={({ field }) => (
                <FormItem>
                  <FormLabel>Adresse</FormLabel>
                  <FormControl><Input {...field} /></FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <div className="grid grid-cols-3 gap-4">
                <FormField control={form.control} name="status" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Statut</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl><SelectTrigger><SelectValue /></SelectTrigger></FormControl>
                      <SelectContent>
                        <SelectItem value="active">Actif</SelectItem>
                        <SelectItem value="suspended">Suspendu</SelectItem>
                        <SelectItem value="expired">Expiré</SelectItem>
                        <SelectItem value="archived">Archivé</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )} />
                <FormField control={form.control} name="maxLoans" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Max emprunts</FormLabel>
                    <FormControl><Input type="number" min={1} max={20} {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
                <FormField control={form.control} name="maxReservations" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Max réservations</FormLabel>
                    <FormControl><Input type="number" min={1} max={10} {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
              </div>
              <div className="flex gap-3 pt-2">
                <Button type="submit" disabled={mutation.isPending}>
                  {mutation.isPending ? 'Enregistrement...' : (isEdit ? 'Mettre à jour' : 'Créer')}
                </Button>
                <Button type="button" variant="outline" onClick={() => navigate('/admin/members')}>
                  Annuler
                </Button>
              </div>
            </form>
          </Form>
        </CardContent>
      </Card>
    </div>
  )
}
