export interface ApiCollection<T> {
  'hydra:member': T[]
  'hydra:totalItems': number
  member?: T[]
  totalItems?: number
}

export interface ApiError {
  type: string
  title: string
  detail: string
  violations?: Array<{ propertyPath: string; message: string }>
}

export interface Author {
  id: string
  firstName: string
  lastName: string
  biography?: string
}

export interface Genre {
  id: string
  name: string
  slug: string
  parent?: Genre
  children?: Genre[]
}

export interface BookCopy {
  id: string
  barcode: string
  location?: string
  status: 'available' | 'borrowed' | 'reserved' | 'repair' | 'lost' | 'withdrawn'
  condition: 'good' | 'damaged' | 'poor'
  acquiredAt?: string
}

export interface Book {
  id: string
  title: string
  description?: string
  isbn?: string
  publishedYear?: number
  publisher?: string
  language: string
  pageCount?: number
  coverImagePath?: string
  createdAt: string
  updatedAt: string
  authors: Author[]
  genres: Genre[]
  copies?: BookCopy[]
  availableCopiesCount?: number
  totalCopiesCount?: number
}

export interface Member {
  id: string
  memberNumber: string
  firstName: string
  lastName: string
  email: string
  phone?: string
  address?: string
  birthDate?: string
  status: 'active' | 'suspended' | 'expired' | 'archived'
  maxLoans: number
  maxReservations: number
  membershipExpiry: string
  photoPath?: string
  createdAt: string
}

export interface User {
  id: string
  email: string
  role: 'ROLE_SUPER_ADMIN' | 'ROLE_ADMIN' | 'ROLE_LIBRARIAN' | 'ROLE_MEMBER'
  isActive: boolean
  member?: Member
}

export interface Loan {
  id: string
  bookCopy: BookCopy & { book?: Book }
  member: Member
  librarian?: User
  borrowedAt: string
  dueDate: string
  returnedAt?: string
  renewedCount: number
  returnCondition?: 'good' | 'damaged' | 'lost'
  lateFee: string
  notes?: string
}

export interface Reservation {
  id: string
  book: Book
  member: Member
  createdAt: string
  notifiedAt?: string
  expiresAt?: string
  status: 'pending' | 'notified' | 'fulfilled' | 'cancelled' | 'expired'
  queuePosition: number
}

export interface Notification {
  id: string
  member: Member
  type: string
  subject: string
  body: string
  sentAt?: string
  status: 'pending' | 'sent' | 'failed'
  createdAt: string
}

export interface DashboardStats {
  activeLoans: number
  overdueLoans: number
  pendingReservations: number
  activeMembers: number
  expiredMembers: number
  totalBooks: number
  topBooks: Array<{ book_title: string; book_id: string; borrow_count: number }>
}

export interface Configuration {
  key: string
  value: string
  description?: string
}
