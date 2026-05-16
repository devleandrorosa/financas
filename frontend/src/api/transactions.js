import http from './http'

export const transactionsApi = {
  list: (params) => http.get('/transactions', { params }),
  create: (data) => http.post('/transactions', data),
  update: (id, data) => http.put(`/transactions/${id}`, data),
  remove: (id) => http.delete(`/transactions/${id}`),
}
