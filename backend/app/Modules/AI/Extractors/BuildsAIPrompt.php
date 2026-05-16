<?php

namespace App\Modules\AI\Extractors;

trait BuildsAIPrompt
{
    private function buildPrompt(string $categoryNames): string
    {
        $categoryHint = $categoryNames
            ? "\n- Tente sugerir uma categoria para cada transação com base nesta lista: {$categoryNames}"
            : '';

        return <<<PROMPT
Analise o documento financeiro acima e extraia as transações de compra/receita reais.

Retorne APENAS um array JSON válido (sem markdown, sem explicações, sem texto adicional) com este formato exato:
[
  {
    "description": "descrição da transação",
    "amount": 1550,
    "type": "expense",
    "date": "2026-05-01"
  }
]

Regras obrigatórias:
- amount: inteiro em centavos (R\$ 15,50 → 1550), sempre positivo
- type: "expense" para débitos/saques/compras, "income" para créditos/reembolsos/receitas
- date: formato YYYY-MM-DD
- Se a data exata não estiver disponível, use a data do lançamento no documento

IGNORE obrigatoriamente (NÃO inclua no JSON):
- Pagamentos da fatura do cartão de crédito (ex.: "Pagamento em", "Pagamento recebido")
- Linhas de total/resumo (ex.: "Total a pagar", "Saldo devedor", "Valor mínimo")
- Transferências entre contas do mesmo titular
- IOF, encargos e juros cobrados separadamente (que já fazem parte de outra transação)
- Saldo anterior e saldo atual
- Linhas com valor zero

Retorne SOMENTE o array JSON, sem nenhum texto antes ou depois.{$categoryHint}

PROMPT;
    }

    private function parseJson(string $text, string $provider): array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = trim($text);

        $items = json_decode($text, true);

        if (! is_array($items)) {
            throw new \RuntimeException("Resposta do {$provider} inválida: {$text}");
        }

        return $items;
    }
}
