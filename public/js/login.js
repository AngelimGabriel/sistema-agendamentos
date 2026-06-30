// Se já houver sessão, pula o login e vai direto para a aplicação.
Auth.current().then((user) => {
    if (user) {
        window.location.href = '/agendamentos.html';
    }
});

const form = document.getElementById('login-form');

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    try {
        await API.post('/login', { email, password });
        window.location.href = '/agendamentos.html';
    } catch (error) {
        UI.toast(error.message); // mensagem amigável vinda do backend (requisito não funcional RQNF3)
    }
});
