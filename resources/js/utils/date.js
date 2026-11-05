/** Data de hoje no fuso do dispositivo, no formato AAAA-MM-DD. */
export function todayIso() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

const shortDate = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' });

/** Formata AAAA-MM-DD como DD/MM/AAAA. */
export function formatDate(iso) {
    return shortDate.format(new Date(`${iso}T00:00:00Z`));
}
