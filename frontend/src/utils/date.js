export function formatDate(value) {
  if (!value) return ''
  const d = new Date(value + (value.length === 10 ? 'T00:00:00' : ''))
  return d.toLocaleDateString('pt-BR')
}

export function currentYearMonth() {
  const now = new Date()
  return { year: now.getFullYear(), month: now.getMonth() + 1 }
}

export function monthLabel(year, month) {
  return new Date(year, month - 1, 1).toLocaleDateString('pt-BR', {
    month: 'long',
    year: 'numeric',
  })
}
