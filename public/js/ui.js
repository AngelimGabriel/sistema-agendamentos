const UI = {
    // Mensagem temporária no canto da tela (sucesso ou erro).
    toast(message, type = 'error') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    },

    // Modal de confirmação. Devolve uma Promise que resolve true/false.
    // Usado na exclusão de usuários (requisito funcional RQF1.1).
    confirm(message, title = 'Confirmar') {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            overlay.innerHTML = `
                <div class="modal">
                    <h2>${title}</h2>
                    <p>${message}</p>
                    <div class="modal-actions">
                        <button class="btn btn-secondary" data-action="cancel">Cancelar</button>
                        <button class="btn btn-danger" data-action="confirm">Confirmar</button>
                    </div>
                </div>`;

            const close = (result) => {
                overlay.remove();
                resolve(result);
            };

            overlay.addEventListener('click', (event) => {
                if (event.target === overlay || event.target.dataset.action === 'cancel') {
                    close(false);
                }
                if (event.target.dataset.action === 'confirm') {
                    close(true);
                }
            });

            document.body.appendChild(overlay);
        });
    },
};
