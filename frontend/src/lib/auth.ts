// Stockage du token JWT en mémoire (pas localStorage)
let accessToken: string | null = null

export function getAccessToken(): string | null {
  return accessToken
}

export function setAccessToken(token: string | null): void {
  accessToken = token
}

export function clearAccessToken(): void {
  accessToken = null
}

export function isAuthenticated(): boolean {
  return accessToken !== null
}

export interface JwtPayload {
  iat: number
  exp: number
  roles: string[]
  username: string
}

export function decodeToken(token: string): JwtPayload | null {
  try {
    const base64Url = token.split('.')[1]
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    )
    return JSON.parse(jsonPayload) as JwtPayload
  } catch {
    return null
  }
}

export function hasRole(token: string | null, role: string): boolean {
  if (!token) return false
  const payload = decodeToken(token)
  return payload?.roles?.includes(role) ?? false
}

export function isLibrarian(token: string | null): boolean {
  return hasRole(token, 'ROLE_LIBRARIAN')
}

export function isMember(token: string | null): boolean {
  return hasRole(token, 'ROLE_MEMBER')
}
