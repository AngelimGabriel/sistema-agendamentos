const Auth = {
    // Retorna o usuário da sessão, ou null se não houver sessão (401).
    async current() {
        try {
            return await API.get('/me');
        } catch {
            return null;
        }
    },

    async logout() {
        await API.post('/logout');
        window.location.href = '/';
    },
};

// Guard das páginas internas: se não estiver logado, manda para o login.
// Retorna o usuário logado quando há sessão.
async function requireAuth() {
    const user = await Auth.current();
    if (!user) {
        window.location.href = '/';
        return null;
    }
    return user;
}

// Tradução do tipo de usuário para exibição (banco usa inglês, a tela mostra em português).
function roleLabel(role) {
    return role === 'admin' ? 'Administrador' : 'Atendente';
}
