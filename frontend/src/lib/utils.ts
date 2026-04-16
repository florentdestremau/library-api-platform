import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatDate(date: string | Date | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export function formatDateTime(date: string | Date | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formatCurrency(amount: string | number): string {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(Number(amount))
}

export function isOverdue(dueDate: string | null | undefined, returnedAt?: string | null): boolean {
  if (!dueDate || returnedAt) return false
  return new Date(dueDate) < new Date()
}

export function daysUntil(date: string | Date): number {
  const target = new Date(date)
  const now = new Date()
  const diff = target.getTime() - now.getTime()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}
