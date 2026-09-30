// Mesmas fixtures que o backend confere em tests/Support/AssertsContract.php.
// Compara o formato (campos e tipos), não os valores. Regras: nulo na resposta é sempre aceito;
// nulo no contrato aceita qualquer tipo; em listas, todo item segue o primeiro exemplo.
const files = import.meta.glob('../../../contracts/precos/*.json', { eager: true, import: 'default' });

export function contract(name) {
    const key = Object.keys(files).find((path) => path.endsWith(`/${name}.json`));

    if (!key) {
        throw new Error(`Contrato ${name} não existe.`);
    }

    return structuredClone(files[key]);
}

const kind = (value) => (Array.isArray(value) ? 'lista' : value === null ? 'nulo' : typeof value);

export function shapeErrors(actual, expected, path = '$') {
    if (expected === null || actual === null) {
        return [];
    }

    if (typeof expected !== 'object') {
        return kind(actual) === kind(expected) ? [] : [`${path}: esperado ${kind(expected)}, recebido ${kind(actual)}`];
    }

    if (typeof actual !== 'object') {
        return [`${path}: esperado objeto ou lista, recebido ${kind(actual)}`];
    }

    if (Array.isArray(expected)) {
        if (!Array.isArray(actual)) {
            return [`${path}: esperado lista, recebido objeto`];
        }

        return actual.flatMap((item, i) => shapeErrors(item, expected[0] ?? null, `${path}[${i}]`));
    }

    if (Array.isArray(actual)) {
        return [`${path}: esperado objeto, recebido lista`];
    }

    return [
        ...Object.keys(expected).filter((campo) => !(campo in actual)).map((campo) => `${path}.${campo}: campo ausente`),
        ...Object.keys(actual).filter((campo) => !(campo in expected)).map((campo) => `${path}.${campo}: campo fora do contrato`),
        ...Object.keys(expected).filter((campo) => campo in actual).flatMap((campo) => shapeErrors(actual[campo], expected[campo], `${path}.${campo}`)),
    ];
}

export function expectShape(actual, expected) {
    const erros = shapeErrors(actual, expected);

    if (erros.length > 0) {
        throw new Error(`Fora do contrato:\n${erros.join('\n')}`);
    }
}
