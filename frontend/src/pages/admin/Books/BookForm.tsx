import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getBook, createBook, updateBook } from '@/api/books'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ArrowLeft } from 'lucide-react'

const schema = z.object({
  title: z.string().min(1, 'Requis'),
  isbn: z.string().optional(),
  publishedYear: z.string().optional(),
  publisher: z.string().optional(),
  language: z.string().min(1),
  pageCount: z.string().optional(),
  description: z.string().optional(),
})

type FormData = z.infer<typeof schema>

export default function BookForm() {
  const { id } = useParams<{ id: string }>()
  const isEdit = !!id
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data: book, isLoading } = useQuery({
    queryKey: ['books', id],
    queryFn: () => getBook(id!),
    enabled: isEdit,
  })

  const form = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      title: '', isbn: '', publishedYear: '', publisher: '',
      language: 'fr', pageCount: '', description: '',
    },
  })

  useEffect(() => {
    if (book) {
      form.reset({
        title: book.title,
        isbn: book.isbn ?? '',
        publishedYear: book.publishedYear != null ? String(book.publishedYear) : '',
        publisher: book.publisher ?? '',
        language: book.language,
        pageCount: book.pageCount != null ? String(book.pageCount) : '',
        description: book.description ?? '',
      })
    }
  }, [book, form])

  const mutation = useMutation({
    mutationFn: (data: FormData) => {
      const payload = {
        title: data.title,
        language: data.language,
        isbn: data.isbn || undefined,
        publisher: data.publisher || undefined,
        description: data.description || undefined,
        publishedYear: data.publishedYear ? parseInt(data.publishedYear) : undefined,
        pageCount: data.pageCount ? parseInt(data.pageCount) : undefined,
      }
      return isEdit ? updateBook(id!, payload) : createBook(payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books'] })
      toast.success(isEdit ? 'Ouvrage modifié' : 'Ouvrage créé')
      navigate('/admin/books')
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { detail?: string } } }
      toast.error(e.response?.data?.detail ?? 'Erreur')
    },
  })

  if (isEdit && isLoading) return <div className="p-6">Chargement...</div>

  return (
    <div className="p-6 max-w-2xl">
      <button onClick={() => navigate('/admin/books')} className="flex items-center gap-1 text-sm text-muted-foreground mb-6 hover:text-foreground">
        <ArrowLeft className="h-4 w-4" />
        Retour
      </button>
      <Card>
        <CardHeader>
          <CardTitle>{isEdit ? 'Modifier l\'ouvrage' : 'Nouvel ouvrage'}</CardTitle>
        </CardHeader>
        <CardContent>
          <Form {...form}>
            <form onSubmit={form.handleSubmit((d) => mutation.mutate(d))} className="space-y-4">
              <FormField control={form.control} name="title" render={({ field }) => (
                <FormItem>
                  <FormLabel>Titre *</FormLabel>
                  <FormControl><Input {...field} /></FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <div className="grid grid-cols-2 gap-4">
                <FormField control={form.control} name="isbn" render={({ field }) => (
                  <FormItem>
                    <FormLabel>ISBN</FormLabel>
                    <FormControl><Input placeholder="9782..." {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
                <FormField control={form.control} name="language" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Langue</FormLabel>
                    <FormControl><Input placeholder="fr" {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <FormField control={form.control} name="publisher" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Éditeur</FormLabel>
                    <FormControl><Input {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
                <FormField control={form.control} name="publishedYear" render={({ field }) => (
                  <FormItem>
                    <FormLabel>Année</FormLabel>
                    <FormControl><Input type="number" min={1000} max={2100} {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
              </div>
              <FormField control={form.control} name="pageCount" render={({ field }) => (
                <FormItem>
                  <FormLabel>Nombre de pages</FormLabel>
                  <FormControl><Input type="number" min={1} {...field} /></FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <FormField control={form.control} name="description" render={({ field }) => (
                <FormItem>
                  <FormLabel>Description</FormLabel>
                  <FormControl>
                    <textarea
                      className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <div className="flex gap-3 pt-2">
                <Button type="submit" disabled={mutation.isPending}>
                  {mutation.isPending ? 'Enregistrement...' : (isEdit ? 'Mettre à jour' : 'Créer')}
                </Button>
                <Button type="button" variant="outline" onClick={() => navigate('/admin/books')}>
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
