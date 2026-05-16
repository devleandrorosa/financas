import http from './http'

export const aiImportApi = {
  upload: (file) => {
    const form = new FormData()
    form.append('file', file)
    return http.post('/ai/import', form, { headers: { 'Content-Type': 'multipart/form-data' } })
  },
  status: (sessionId) => http.get(`/ai/import/${sessionId}`),
  confirm: (sessionId, items) => http.post(`/ai/import/${sessionId}/confirm`, { items }),
}
