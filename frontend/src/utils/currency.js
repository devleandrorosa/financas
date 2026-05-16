export function formatBRL(cents) {
  if (cents == null) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(cents / 100)
}

export function parseCents(value) {
  const str = String(value).replace(/[^\d,]/g, '').replace(',', '.')
  return Math.round(parseFloat(str || '0') * 100)
}
