import { apiClient, extractCollection } from './client'
import type { Reservation } from '@/types'

export async function getReservations(filters: { book?: string; member?: string; status?: string } = {}) {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== '') params.set(k, String(v))
  })
  const response = await apiClient.get(`/reservations?${params}`)
  return extractCollection<Reservation>(response.data)
}

export async function createReservation(bookId: string): Promise<Reservation> {
  const response = await apiClient.post<Reservation>('/reservations', { bookId })
  return response.data
}

export async function cancelReservation(id: string): Promise<void> {
  await apiClient.delete(`/reservations/${id}`)
}
