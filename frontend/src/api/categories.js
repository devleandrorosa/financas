import http from './http'

export const categoriesApi = {
  list: () => http.get('/categories'),
  flat: () => http.get('/categories/flat'),
  create: (data) => http.post('/categories', data),
  update: (id, data) => http.put(`/categories/${id}`, data),
  remove: (id) => http.delete(`/categories/${id}`),
}
