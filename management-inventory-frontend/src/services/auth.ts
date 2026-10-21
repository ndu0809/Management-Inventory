import api from './api'

export type LoginResponse = {
  message: string
  token: string
  user: {
    id: number
    name: string
    email: string
  }
}

export async function login(email: string, password: string) {
  const response = await api.post<LoginResponse>('/login', {
    email,
    password,
  })

  localStorage.setItem('auth_token', response.data.token)
  localStorage.setItem('auth_user', JSON.stringify(response.data.user))

  return response.data
}

export async function logout() {
  try {
    await api.post('/logout')
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }
}