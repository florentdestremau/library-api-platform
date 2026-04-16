import { apiClient, extractCollection } from './client'
import type { Book, BookCopy } from '@/types'

export interface BooksFilters {
  title?: string
  language?: string
  'genres.slug'?: string
  available?: boolean
  page?: number
  itemsPerPage?: number
  'order[title]'?: 'asc' | 'desc'
}

export async function getBooks(filters: BooksFilters = {}) {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== '') params.set(k, String(v))
  })
  const response = await apiClient.get(`/books?${params}`)
  return extractCollection<Book>(response.data)
}

export async function getBook(id: string): Promise<Book> {
  const response = await apiClient.get<Book>(`/books/${id}`)
  return response.data
}

export async function createBook(data: Partial<Book>): Promise<Book> {
  const response = await apiClient.post<Book>('/books', data)
  return response.data
}

export async function updateBook(id: string, data: Partial<Book>): Promise<Book> {
  const response = await apiClient.put<Book>(`/books/${id}`, data)
  return response.data
}

export async function deleteBook(id: string): Promise<void> {
  await apiClient.delete(`/books/${id}`)
}

export async function getBookCopies(bookId: string) {
  const response = await apiClient.get(`/book_copies?book=${bookId}`)
  return extractCollection<BookCopy>(response.data)
}

export async function createBookCopy(data: Partial<BookCopy> & { book: string }): Promise<BookCopy> {
  const response = await apiClient.post<BookCopy>('/book_copies', data)
  return response.data
}

export async function updateBookCopy(id: string, data: Partial<BookCopy>): Promise<BookCopy> {
  const response = await apiClient.put<BookCopy>(`/book_copies/${id}`, data)
  return response.data
}
