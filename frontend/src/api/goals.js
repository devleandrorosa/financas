import http from './http'

export const goalsApi = {
  list: () => http.get('/goals'),
  create: (data) => http.post('/goals', data),
  update: (id, data) => http.put(`/goals/${id}`, data),
  progress: (id, amount) => http.patch(`/goals/${id}/progress`, { amount }),
  remove: (id) => http.delete(`/goals/${id}`),
}
