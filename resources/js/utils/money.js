const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

/** Formata um valor em centavos como moeda (ex.: 2550 -> "R$ 25,50"). */
export function formatCents(cents) {
    return currency.format(cents / 100);
}

/**
 * Converte o texto digitado para o formato aceito pelo servidor.
 * "25,50" e "25.50" viram "25.50"; "1.000,50" vira "1000.50".
 */
export function normalizeAmount(text) {
    const value = String(text ?? '').trim().replace(/\s/g, '');

    if (value.includes(',')) {
        return value.replace(/\./g, '').replace(',', '.');
    }

    return value;
}

/** Converte o valor vindo do servidor ("25.50") para o texto do campo ("25,50"). */
export function toAmountInput(amount) {
    return String(amount).replace('.', ',');
}
