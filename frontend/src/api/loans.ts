import { apiClient, extractCollection } from './client'
import type { Loan, DashboardStats } from '@/types'

export interface LoansFilters {
  member?: string
  overdue?: boolean
  page?: number
}

export async function getLoans(filters: LoansFilters = {}) {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== '') params.set(k, String(v))
  })
  const response = await apiClient.get(`/loans?${params}`)
  return extractCollection<Loan>(response.data)
}

export async function createLoan(data: {
  bookCopyId: string
  memberId: string
  durationDays?: number
}): Promise<Loan> {
  const response = await apiClient.post<Loan>('/loans', data)
  return response.data
}

export async function returnLoan(
  id: string,
  data: { condition: string; notes?: string }
): Promise<Loan> {
  const response = await apiClient.post<Loan>(`/loans/${id}/return`, data)
  return response.data
}

export async function renewLoan(id: string): Promise<Loan> {
  const response = await apiClient.post<Loan>(`/loans/${id}/renew`, {})
  return response.data
}

export async function getDashboardStats(): Promise<DashboardStats> {
  const response = await apiClient.get<DashboardStats>('/stats/dashboard')
  return response.data
}
