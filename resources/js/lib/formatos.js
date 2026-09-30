export const reais = (centavos) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(centavos / 100);

export const percentual = (valor) => `+${valor.toLocaleString('pt-BR', { minimumFractionDigits: 1 })}%`;
