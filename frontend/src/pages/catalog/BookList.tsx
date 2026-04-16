import { useState, useCallback } from 'react'
import { Link } from 'react-router-dom'
import { useBooks } from '@/hooks/useBooks'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { BookOpen, Search } from 'lucide-react'

export default function BookList() {
  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')

  const debounce = useCallback((fn: (v: string) => void, delay: number) => {
    let timer: ReturnType<typeof setTimeout>
    return (value: string) => {
      clearTimeout(timer)
      timer = setTimeout(() => fn(value), delay)
    }
  }, [])

  const debouncedSet = useCallback(debounce(setDebouncedSearch, 300), [debounce])

  function handleSearch(value: string) {
    setSearch(value)
    debouncedSet(value)
  }

  const { data, isLoading } = useBooks({ title: debouncedSearch || undefined })

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Rechercher un ouvrage..."
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <span className="text-sm text-muted-foreground">
          {data?.total ?? 0} résultat(s)
        </span>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {Array.from({ length: 6 }).map((_, i) => (
            <Skeleton key={i} className="h-40" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {data?.items.map((book) => (
            <Card key={book.id} className="hover:shadow-md transition-shadow">
              <CardHeader className="pb-2">
                <CardTitle className="text-base leading-tight">
                  <Link to={`/catalogue/${book.id}`} className="hover:text-primary">
                    {book.title}
                  </Link>
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                  {book.authors.map((a) => `${a.firstName} ${a.lastName}`).join(', ')}
                </p>
              </CardHeader>
              <CardContent className="space-y-2">
                {book.genres.length > 0 && (
                  <div className="flex flex-wrap gap-1">
                    {book.genres.map((g) => (
                      <Badge key={g.id} variant="secondary" className="text-xs">
                        {g.name}
                      </Badge>
                    ))}
                  </div>
                )}
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-1 text-sm">
                    <BookOpen className="h-3 w-3" />
                    <span>{book.publishedYear ?? '-'}</span>
                  </div>
                  <Badge
                    variant={(book.availableCopiesCount ?? 0) > 0 ? 'default' : 'destructive'}
                  >
                    {book.availableCopiesCount ?? 0}/{book.totalCopiesCount ?? 0} dispo.
                  </Badge>
                </div>
                <Link to={`/catalogue/${book.id}`}>
                  <Button variant="outline" size="sm" className="w-full">
                    Voir détails
                  </Button>
                </Link>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
