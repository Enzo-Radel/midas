// Nome do produto em todas as telas: "Café Pilão", ou só "Café" quando não há marca.
export const nomeExibido = ({ nome, marca }) => (marca ? `${nome} ${marca}` : nome);
