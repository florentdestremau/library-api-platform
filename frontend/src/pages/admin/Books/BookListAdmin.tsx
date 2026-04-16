import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useBooks, useDeleteBook } from '@/hooks/useBooks'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { toast } from 'sonner'
import { Plus, Search, Trash2, Edit, Eye } from 'lucide-react'

export default function BookListAdmin() {
  const [search, setSearch] = useState('')
  const [deleteId, setDeleteId] = useState<string | null>(null)

  const { data, isLoading } = useBooks({ title: search || undefined })
  const deleteBook = useDeleteBook()

  async function handleDelete() {
    if (!deleteId) return
    try {
      await deleteBook.mutateAsync(deleteId)
      toast.success('Ouvrage supprimé')
      setDeleteId(null)
    } catch {
      toast.error('Erreur lors de la suppression')
    }
  }

  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Catalogue</h1>
        <Link to="/admin/books/new">
          <Button>
            <Plus className="h-4 w-4 mr-2" />
            Ajouter un ouvrage
          </Button>
        </Link>
      </div>

      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Rechercher..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-9"
        />
      </div>

      <div className="text-sm text-muted-foreground">{data?.total ?? 0} ouvrage(s)</div>

      <div className="border rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Titre</TableHead>
              <TableHead>Auteur(s)</TableHead>
              <TableHead>ISBN</TableHead>
              <TableHead>Disponible</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 5 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : data?.items.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                  Aucun ouvrage trouvé
                </TableCell>
              </TableRow>
            ) : (
              data?.items.map((book) => (
                <TableRow key={book.id}>
                  <TableCell className="font-medium max-w-xs truncate">{book.title}</TableCell>
                  <TableCell className="text-muted-foreground text-sm">
                    {book.authors.map((a) => `${a.firstName} ${a.lastName}`).join(', ')}
                  </TableCell>
                  <TableCell className="text-muted-foreground text-sm">{book.isbn ?? '-'}</TableCell>
                  <TableCell>
                    <Badge variant={(book.availableCopiesCount ?? 0) > 0 ? 'default' : 'secondary'}>
                      {book.availableCopiesCount ?? 0}/{book.totalCopiesCount ?? 0}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-2">
                      <Link to={`/admin/books/${book.id}`}>
                        <Button variant="ghost" size="sm"><Eye className="h-4 w-4" /></Button>
                      </Link>
                      <Link to={`/admin/books/${book.id}/edit`}>
                        <Button variant="ghost" size="sm"><Edit className="h-4 w-4" /></Button>
                      </Link>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive"
                        onClick={() => setDeleteId(book.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <Dialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirmer la suppression</DialogTitle>
            <DialogDescription>
              Cette action est irréversible. L'ouvrage sera supprimé définitivement.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId(null)}>Annuler</Button>
            <Button variant="destructive" onClick={handleDelete} disabled={deleteBook.isPending}>
              Supprimer
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
