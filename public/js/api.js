// Erro padronizado da API, carrega a mensagem amigável e o status HTTP.
class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.status = status;
    }
}

// Camada única de acesso ao backend. Toda chamada passa por aqui
// envia/recebe JSON, e em resposta de erro lança ApiError com a mensagem do backend.
const API = {
    async request(method, path, body) {
        const options = { method, headers: { 'Content-Type': 'application/json' } };
        if (body !== undefined) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(path, options);
        const text = await response.text();
        const data = text ? JSON.parse(text) : null;

        if (!response.ok) {
            const message = data && data.error ? data.error : 'Erro inesperado. Tente novamente.';
            throw new ApiError(message, response.status);
        }

        return data;
    },

    get(path) {
        return this.request('GET', path);
    },
    post(path, body) {
        return this.request('POST', path, body);
    },
    put(path, body) {
        return this.request('PUT', path, body);
    },
    delete(path) {
        return this.request('DELETE', path);
    },
};
