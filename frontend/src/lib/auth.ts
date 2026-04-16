const TOKEN_COOKIE = 'jwt'
const TOKEN_TTL_SECONDS = 3600 // correspond à la durée du JWT (1h)

function setCookie(name: string, value: string, maxAge: number): void {
  const secure = location.protocol === 'https:' ? '; Secure' : ''
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Strict${secure}`
}

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

function deleteCookie(name: string): void {
  document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Strict`
}

export function getAccessToken(): string | null {
  return getCookie(TOKEN_COOKIE)
}

export function setAccessToken(token: string | null): void {
  if (token) {
    setCookie(TOKEN_COOKIE, token, TOKEN_TTL_SECONDS)
  } else {
    deleteCookie(TOKEN_COOKIE)
  }
}

export function clearAccessToken(): void {
  deleteCookie(TOKEN_COOKIE)
}

export function isAuthenticated(): boolean {
  return getAccessToken() !== null
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
