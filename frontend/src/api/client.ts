import axios from 'axios'
import { getAccessToken, clearAccessToken } from '@/lib/auth'

const VITE_API_URL = import.meta.env.VITE_API_URL ?? ''

export const apiClient = axios.create({
  baseURL: VITE_API_URL + '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

apiClient.interceptors.request.use((config) => {
  const token = getAccessToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAccessToken()
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export function extractCollection<T>(data: unknown): { items: T[]; total: number } {
  // Tableau JSON direct
  if (Array.isArray(data)) {
    return { items: data as T[], total: data.length }
  }
  if (!data || typeof data !== 'object') return { items: [], total: 0 }
  const d = data as Record<string, unknown>
  const items = (d['hydra:member'] ?? d['member'] ?? []) as T[]
  const total = (d['hydra:totalItems'] ?? d['totalItems'] ?? (items as unknown[]).length) as number
  return { items, total }
}
