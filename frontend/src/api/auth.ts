import { apiClient } from './client'
import { getAccessToken, setAccessToken, decodeToken } from '@/lib/auth'

export interface LoginCredentials {
  username: string
  password: string
}

export interface LoginResponse {
  token: string
}

export async function login(credentials: LoginCredentials): Promise<LoginResponse> {
  const response = await apiClient.post<LoginResponse>('/auth/login', credentials)
  setAccessToken(response.data.token)
  return response.data
}

export async function logout(): Promise<void> {
  setAccessToken(null)
}

export function getCurrentUserInfo() {
  const token = getAccessToken()
  if (!token) return null
  return decodeToken(token)
}
