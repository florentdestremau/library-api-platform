import { describe, it, expect, beforeEach } from 'vitest'
import { setAccessToken, getAccessToken, clearAccessToken, isAuthenticated, decodeToken, isLibrarian } from '@/lib/auth'

describe('auth', () => {
  beforeEach(() => {
    clearAccessToken()
  })

  it('should start unauthenticated', () => {
    expect(isAuthenticated()).toBe(false)
    expect(getAccessToken()).toBeNull()
  })

  it('should set and get token', () => {
    setAccessToken('test-token')
    expect(getAccessToken()).toBe('test-token')
    expect(isAuthenticated()).toBe(true)
  })

  it('should clear token', () => {
    setAccessToken('test-token')
    clearAccessToken()
    expect(isAuthenticated()).toBe(false)
    expect(getAccessToken()).toBeNull()
  })

  it('should detect librarian role', () => {
    // Fake JWT with ROLE_LIBRARIAN
    const payload = { iat: 0, exp: 9999999999, roles: ['ROLE_LIBRARIAN', 'ROLE_MEMBER'], username: 'test@test.com' }
    const fakeJwt = `header.${btoa(JSON.stringify(payload))}.signature`
    expect(isLibrarian(fakeJwt)).toBe(true)
  })

  it('should return false for member without librarian role', () => {
    const payload = { iat: 0, exp: 9999999999, roles: ['ROLE_MEMBER'], username: 'test@test.com' }
    const fakeJwt = `header.${btoa(JSON.stringify(payload))}.signature`
    expect(isLibrarian(fakeJwt)).toBe(false)
  })
})
