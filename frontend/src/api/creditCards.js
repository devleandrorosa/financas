import http from './http'

export const creditCardsApi = {
  list: () => http.get('/credit-cards'),
  create: (data) => http.post('/credit-cards', data),
  update: (id, data) => http.put(`/credit-cards/${id}`, data),
  remove: (id) => http.delete(`/credit-cards/${id}`),
  statements: (id) => http.get(`/credit-cards/${id}/statements`),
  payStatement: (statementId) => http.patch(`/credit-cards/statements/${statementId}/pay`),
}
