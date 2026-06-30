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

// Helpers de DOM reutilizados.
function textCell(text) {
    const td = document.createElement('td');
    td.textContent = text;
    return td;
}

function actionButton(label, variant, onClick) {
    const button = document.createElement('button');
    button.className = `btn ${variant} btn-sm`;
    button.textContent = label;
    button.addEventListener('click', onClick);
    return button;
}

// Seções colapsáveis: clicar no cabeçalho expande/colapsa o corpo.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.collapsible-header').forEach((header) => {
        header.addEventListener('click', (event) => {
            // Ignora cliques em botões dentro do cabeçalho (ex: "Adicionar").
            if (event.target.closest('button')) return;
            header.closest('.collapsible').classList.toggle('collapsed');
        });
    });
});
