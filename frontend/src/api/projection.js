import http from './http'

export const projectionApi = {
  get: (months = 6) => http.get('/projection', { params: { months } }),
}
