import http from './http'

export const budgetsApi = {
  list: (year, month) => http.get('/budgets', { params: { year, month } }),
  save: (data) => http.post('/budgets', data),
  remove: (id) => http.delete(`/budgets/${id}`),
}
