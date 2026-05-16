import http from './http'

export const recurringRulesApi = {
  list: () => http.get('/recurring-rules'),
  create: (data) => http.post('/recurring-rules', data),
  update: (id, data) => http.put(`/recurring-rules/${id}`, data),
  toggle: (id) => http.patch(`/recurring-rules/${id}/toggle`),
  remove: (id) => http.delete(`/recurring-rules/${id}`),
}
