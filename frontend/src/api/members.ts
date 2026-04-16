import { apiClient, extractCollection } from './client'
import type { Member, Loan, Reservation } from '@/types'

export interface MembersFilters {
  firstName?: string
  lastName?: string
  email?: string
  memberNumber?: string
  status?: string
  page?: number
}

export async function getMembers(filters: MembersFilters = {}) {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== '') params.set(k, String(v))
  })
  const response = await apiClient.get(`/members?${params}`)
  return extractCollection<Member>(response.data)
}

export async function getMember(id: string): Promise<Member> {
  const response = await apiClient.get<Member>(`/members/${id}`)
  return response.data
}

export async function createMember(data: Partial<Member>): Promise<Member> {
  const response = await apiClient.post<Member>('/members', data)
  return response.data
}

export async function updateMember(id: string, data: Partial<Member>): Promise<Member> {
  const response = await apiClient.put<Member>(`/members/${id}`, data)
  return response.data
}

export async function getMemberLoans(id: string) {
  const response = await apiClient.get(`/loans?member=${id}`)
  return extractCollection<Loan>(response.data)
}

export async function getMyProfile(): Promise<Member> {
  const response = await apiClient.get<Member>('/me')
  return response.data
}

export async function getMyLoans() {
  const response = await apiClient.get('/my_loans')
  return extractCollection<Loan>(response.data)
}

export async function getMyReservations() {
  const response = await apiClient.get('/my_reservations')
  return extractCollection<Reservation>(response.data)
}
