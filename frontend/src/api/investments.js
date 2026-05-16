import http from './http'

export const investmentsApi = {
  list: () => http.get('/investments'),
  create: (data) => http.post('/investments', data),
  update: (id, data) => http.put(`/investments/${id}`, data),
  remove: (id) => http.delete(`/investments/${id}`),
}
