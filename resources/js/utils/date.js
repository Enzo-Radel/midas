/** Data de hoje no fuso do dispositivo, no formato AAAA-MM-DD. */
export function todayIso() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

const dayLabel = new Intl.DateTimeFormat('pt-BR', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' });

/** Soma dias a uma data AAAA-MM-DD. */
function addDays(iso, days) {
    const date = new Date(`${iso}T00:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);

    return date.toISOString().slice(0, 10);
}

/**
 * Rótulo do separador de um dia: "Hoje", "Ontem" ou, por exemplo, "seg, 2 nov".
 * `today` pode ser informado para facilitar testes.
 */
export function formatDayLabel(iso, today = todayIso()) {
    if (iso === today) return 'Hoje';
    if (iso === addDays(today, -1)) return 'Ontem';

    const label = dayLabel.format(new Date(`${iso}T00:00:00Z`)).replace(/\./g, '').replace(/ de /g, ' ');

    return label.charAt(0).toUpperCase() + label.slice(1);
}
