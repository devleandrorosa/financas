import http from './http'

export const familyApi = {
  get: () => http.get('/family'),
  invite: (data) => http.post('/family/invite', data),
  removeMember: (id) => http.delete(`/family/members/${id}`),
  updateRole: (id, role) => http.patch(`/family/members/${id}/role`, { role }),
}
