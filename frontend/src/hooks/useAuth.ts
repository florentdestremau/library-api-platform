import { useState, useCallback } from 'react'
import { useMutation } from '@tanstack/react-query'
import { login, logout } from '@/api/auth'
import { getAccessToken, decodeToken, isLibrarian, isMember, clearAccessToken } from '@/lib/auth'

export function useAuth() {
  const [, forceUpdate] = useState(0)

  const loginMutation = useMutation({
    mutationFn: login,
    onSuccess: () => forceUpdate((n) => n + 1),
  })

  const logoutFn = useCallback(async () => {
    await logout()
    forceUpdate((n) => n + 1)
  }, [])

  const token = getAccessToken()
  const payload = token ? decodeToken(token) : null

  return {
    isAuthenticated: !!token,
    isLibrarian: isLibrarian(token),
    isMember: isMember(token),
    userEmail: payload?.username ?? null,
    roles: payload?.roles ?? [],
    login: loginMutation.mutateAsync,
    logout: logoutFn,
    isLoading: loginMutation.isPending,
    error: loginMutation.error,
  }
}
