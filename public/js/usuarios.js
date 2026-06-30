let currentUser = null;
let editingId = null;

async function init() {
    currentUser = await requireAuth();
    if (!currentUser) return;

    document.getElementById('user-info').textContent =
        `${currentUser.name} (${roleLabel(currentUser.role)})`;
    document.getElementById('logout-btn').addEventListener('click', () => Auth.logout());

    // "Novo usuário" só aparece para admin (requisito funcional RQF1.2).
    if (currentUser.role === 'admin') {
        const btn = document.getElementById('new-user-btn');
        btn.classList.remove('hidden');
        btn.addEventListener('click', openCreateForm);
    }

    document.getElementById('form-cancel').addEventListener('click', closeForm);
    document.getElementById('user-form').addEventListener('submit', onSubmit);

    await loadUsers();
}

async function loadUsers() {
    try {
        const users = await API.get('/users');
        renderUsers(users);
    } catch (error) {
        UI.toast(error.message);
    }
}

function renderUsers(users) {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = '';

    for (const user of users) {
        const tr = document.createElement('tr');
        tr.appendChild(textCell(user.name));
        tr.appendChild(textCell(user.email));

        const typeCell = document.createElement('td');
        const tag = document.createElement('span');
        tag.className = 'tag';
        tag.textContent = roleLabel(user.role);
        typeCell.appendChild(tag);
        tr.appendChild(typeCell);

        const actionsCell = document.createElement('td');
        const actions = document.createElement('div');
        actions.className = 'row-actions';

        // Editar: admin edita qualquer um; atendente só o próprio (case RQF1.3).
        if (currentUser.role === 'admin' || currentUser.id === user.id) {
            actions.appendChild(actionButton('Editar', 'btn-secondary', () => openEditForm(user)));
        }
        // Excluir: apenas admin (case RQF1.1).
        if (currentUser.role === 'admin') {
            actions.appendChild(actionButton('Excluir', 'btn-danger', () => removeUser(user)));
        }

        actionsCell.appendChild(actions);
        tr.appendChild(actionsCell);
        tbody.appendChild(tr);
    }
}

function openCreateForm() {
    editingId = null;
    document.getElementById('form-title').textContent = 'Novo usuário';
    document.getElementById('user-form').reset();

    // Na criação, todos os campos aparecem e o tipo é editável.
    setVisible('field-email', true);
    setVisible('field-password', true);
    setVisible('field-password-confirmation', true);
    document.getElementById('f-role').disabled = false;

    openModal();
}

function openEditForm(user) {
    editingId = user.id;
    document.getElementById('form-title').textContent = 'Editar usuário';
    document.getElementById('user-form').reset();
    document.getElementById('f-name').value = user.name;
    document.getElementById('f-role').value = user.role;

    // Edição não altera e-mail nem senha (requisito funcional RQF1.3) -> escondemos esses campos.
    setVisible('field-email', false);
    setVisible('field-password', false);
    setVisible('field-password-confirmation', false);

    // Tipo de Usuário, só o admin altera, e nunca o próprio. Ao se editar, o campo fica desabilitado.
    const editingSelf = currentUser.id === user.id;
    document.getElementById('f-role').disabled = currentUser.role !== 'admin' || editingSelf;

    openModal();
}

async function onSubmit(event) {
    event.preventDefault();

    const name = document.getElementById('f-name').value.trim();
    const role = document.getElementById('f-role').value;

    if (name === '') {
        UI.toast('O nome é obrigatório.');
        return;
    }

    try {
        if (editingId === null) {
            const email = document.getElementById('f-email').value.trim();
            const password = document.getElementById('f-password').value;
            const confirmation = document.getElementById('f-password-confirmation').value;

            if (email === '' || password === '' || confirmation === '') {
                UI.toast('Preencha todos os campos obrigatórios.');
                return;
            }
            if (password !== confirmation) {
                UI.toast('A confirmação de senha não confere.');
                return;
            }

            await API.post('/users', {
                name,
                role,
                email,
                password,
                password_confirmation: confirmation,
            });
            UI.toast('Usuário criado com sucesso.', 'success');
        } else {
            await API.put(`/users/${editingId}`, { name, role });
            UI.toast('Usuário atualizado.', 'success');
        }

        closeForm();
        await loadUsers();
    } catch (error) {
        UI.toast(error.message); // validações/permissões do backend
    }
}

async function removeUser(user) {
    const confirmed = await UI.confirm(`Excluir o usuário "${user.name}"?`, 'Excluir usuário');
    if (!confirmed) return;

    try {
        await API.delete(`/users/${user.id}`);
        UI.toast('Usuário excluído.', 'success');
        await loadUsers();
    } catch (error) {
        UI.toast(error.message);
    }
}

// helpers de DOM
function setVisible(id, visible) {
    document.getElementById(id).classList.toggle('hidden', !visible);
}

function openModal() {
    document.getElementById('form-modal').classList.remove('hidden');
}

function closeForm() {
    document.getElementById('form-modal').classList.add('hidden');
}

init();
