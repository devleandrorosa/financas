import http from './http'

export const bankAccountsApi = {
  list: () => http.get('/bank-accounts'),
  create: (data) => http.post('/bank-accounts', data),
  update: (id, data) => http.put(`/bank-accounts/${id}`, data),
  remove: (id) => http.delete(`/bank-accounts/${id}`),
}
