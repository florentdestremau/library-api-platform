import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getBooks, getBook, createBook, updateBook, deleteBook, getBookCopies } from '@/api/books'
import type { BooksFilters } from '@/api/books'

export function useBooks(filters: BooksFilters = {}) {
  return useQuery({
    queryKey: ['books', filters],
    queryFn: () => getBooks(filters),
  })
}

export function useBook(id: string) {
  return useQuery({
    queryKey: ['books', id],
    queryFn: () => getBook(id),
    enabled: !!id,
  })
}

export function useBookCopies(bookId: string) {
  return useQuery({
    queryKey: ['book-copies', bookId],
    queryFn: () => getBookCopies(bookId),
    enabled: !!bookId,
  })
}

export function useCreateBook() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createBook,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['books'] }),
  })
}

export function useUpdateBook() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Parameters<typeof updateBook>[1] }) =>
      updateBook(id, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['books'] }),
  })
}

export function useDeleteBook() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteBook,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['books'] }),
  })
}
