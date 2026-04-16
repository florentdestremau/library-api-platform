import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getLoans, createLoan, returnLoan, renewLoan, getDashboardStats } from '@/api/loans'
import { getMyLoans, getMyReservations } from '@/api/members'
import type { LoansFilters } from '@/api/loans'

export function useLoans(filters: LoansFilters = {}) {
  return useQuery({
    queryKey: ['loans', filters],
    queryFn: () => getLoans(filters),
  })
}

export function useMyLoans() {
  return useQuery({
    queryKey: ['my-loans'],
    queryFn: getMyLoans,
  })
}

export function useMyReservations() {
  return useQuery({
    queryKey: ['my-reservations'],
    queryFn: getMyReservations,
  })
}

export function useDashboardStats() {
  return useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: getDashboardStats,
    refetchInterval: 60_000,
  })
}

export function useCreateLoan() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createLoan,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] })
      queryClient.invalidateQueries({ queryKey: ['book-copies'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard-stats'] })
    },
  })
}

export function useReturnLoan() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: { condition: string; notes?: string } }) =>
      returnLoan(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] })
      queryClient.invalidateQueries({ queryKey: ['book-copies'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard-stats'] })
    },
  })
}

export function useRenewLoan() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: renewLoan,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] })
      queryClient.invalidateQueries({ queryKey: ['my-loans'] })
    },
  })
}
