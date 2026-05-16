import http from './http'

export const authApi = {
  register:       (data) => http.post('/auth/register', data),
  login:          (data) => http.post('/auth/login', data),
  logout:         ()     => http.post('/auth/logout'),
  acceptInvite:   (data) => http.post('/auth/invite/accept', data),
  updateProfile:  (data) => http.put('/auth/profile', data),
  updatePassword: (data) => http.put('/auth/password', data),
}
