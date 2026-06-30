let currentUser = null;
let editingAvailabilityId = null;
let bookingSlot = null;
let appointments = [];

const WEEKDAYS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

async function init() {
    currentUser = await requireAuth();
    if (!currentUser) return;

    document.getElementById('user-info').textContent =
        `${currentUser.name} (${roleLabel(currentUser.role)})`;
    document.getElementById('logout-btn').addEventListener('click', () => Auth.logout());

    populateWeekdaySelect();
    populateDaysCheckboxes();
    populateHourSelect(document.getElementById('a-start'));
    populateHourSelect(document.getElementById('a-end'));

    // Só admin gerencia a disponibilidade (case RQF2.2).
    if (currentUser.role === 'admin') {
        const btn = document.getElementById('new-availability-btn');
        btn.classList.remove('hidden');
        btn.addEventListener('click', () => openAvailabilityForm());
    }
    document.getElementById('availability-cancel').addEventListener('click', closeAvailabilityForm);
    document.getElementById('availability-form').addEventListener('submit', onAvailabilitySubmit);
    document.getElementById('attendant-select').addEventListener('change', onAttendantChange);

    document.getElementById('slot-date').value = todayIso();
    document.getElementById('slot-date').addEventListener('change', loadSlots);
    document.getElementById('booking-cancel').addEventListener('click', closeBookingForm);
    document.getElementById('booking-form').addEventListener('submit', onBookingSubmit);
    document.getElementById('status-filter').addEventListener('change', renderAppointments);

    await loadAttendants();
}

async function onAttendantChange() {
    await loadAvailability();
    await loadSlots();
    await loadAppointments();
}

async function loadAttendants() {
    try {
        const users = await API.get('/users');
        const attendants = users.filter((user) => user.role === 'attendant');
        const select = document.getElementById('attendant-select');
        select.innerHTML = '';

        for (const attendant of attendants) {
            const option = document.createElement('option');
            option.value = attendant.id;
            option.textContent = attendant.name;
            select.appendChild(option);
        }

        // Atendente só mexe na própria agenda: trava o seletor nele mesmo.
        if (currentUser.role === 'attendant') {
            select.value = currentUser.id;
            select.disabled = true;
        }

        if (select.value) {
            await onAttendantChange();
        }
    } catch (error) {
        UI.toast(error.message);
    }
}

function selectedAttendantId() {
    return document.getElementById('attendant-select').value;
}

async function loadAvailability() {
    const id = selectedAttendantId();
    if (!id) return;

    try {
        const items = await API.get(`/users/${id}/availability`);
        renderAvailability(items);
    } catch (error) {
        UI.toast(error.message);
    }
}

function renderAvailability(items) {
    const tbody = document.getElementById('availability-tbody');
    tbody.innerHTML = '';

    if (items.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.textContent = 'Nenhuma disponibilidade cadastrada.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    for (const item of items) {
        const tr = document.createElement('tr');
        tr.appendChild(textCell(WEEKDAYS[item.day_of_week]));
        tr.appendChild(textCell(item.start_time.slice(0, 5)));
        tr.appendChild(textCell(item.end_time.slice(0, 5)));
        tr.appendChild(textCell(item.active ? 'Sim' : 'Não'));

        const actionsCell = document.createElement('td');
        if (currentUser.role === 'admin') {
            const actions = document.createElement('div');
            actions.className = 'row-actions';
            actions.appendChild(actionButton('Editar', 'btn-secondary', () => openAvailabilityForm(item)));
            actions.appendChild(actionButton('Remover', 'btn-danger', () => removeAvailability(item)));
            actionsCell.appendChild(actions);
        }
        tr.appendChild(actionsCell);
        tbody.appendChild(tr);
    }
}

function openAvailabilityForm(item = null) {
    editingAvailabilityId = item ? item.id : null;
    const editing = item !== null;

    document.getElementById('availability-title').textContent =
        editing ? 'Editar disponibilidade' : 'Adicionar disponibilidade';

    // Criar: vários dias (checkboxes). Editar: um dia (select).
    document.getElementById('field-days').classList.toggle('hidden', editing);
    document.getElementById('field-day').classList.toggle('hidden', !editing);

    if (editing) {
        document.getElementById('a-day').value = item.day_of_week;
    } else {
        document.querySelectorAll('.day-checkbox').forEach((cb) => { cb.checked = false; });
    }

    document.getElementById('a-start').value = editing ? item.start_time.slice(0, 5) : '08:00';
    document.getElementById('a-end').value = editing ? item.end_time.slice(0, 5) : '12:00';
    document.getElementById('a-active').checked = editing ? item.active : true;

    document.getElementById('availability-modal').classList.remove('hidden');
}

function closeAvailabilityForm() {
    document.getElementById('availability-modal').classList.add('hidden');
}

async function onAvailabilitySubmit(event) {
    event.preventDefault();

    const start = document.getElementById('a-start').value;
    const end = document.getElementById('a-end').value;
    const active = document.getElementById('a-active').checked;

    if (end <= start) {
        UI.toast('A hora final deve ser maior que a hora inicial.');
        return;
    }

    try {
        if (editingAvailabilityId === null) {
            // Criação: um ou mais dias selecionados nos checkboxes.
            const days = [...document.querySelectorAll('.day-checkbox:checked')].map((cb) => Number(cb.value));
            if (days.length === 0) {
                UI.toast('Selecione ao menos um dia da semana.');
                return;
            }
            await API.post('/availability', {
                user_id: Number(selectedAttendantId()),
                days,
                start_time: start,
                end_time: end,
                active,
            });
            UI.toast('Disponibilidade adicionada.', 'success');
        } else {
            // Edição: uma janela, um dia.
            await API.put(`/availability/${editingAvailabilityId}`, {
                day_of_week: Number(document.getElementById('a-day').value),
                start_time: start,
                end_time: end,
                active,
            });
            UI.toast('Disponibilidade atualizada.', 'success');
        }
        closeAvailabilityForm();
        await loadAvailability();
        await loadSlots(); // a grade de horários depende da disponibilidade
    } catch (error) {
        UI.toast(error.message);
    }
}

async function removeAvailability(item) {
    const label = `${WEEKDAYS[item.day_of_week]} (${item.start_time.slice(0, 5)}–${item.end_time.slice(0, 5)})`;
    const confirmed = await UI.confirm(`Remover a disponibilidade de ${label}?`, 'Remover disponibilidade');
    if (!confirmed) return;

    try {
        await API.delete(`/availability/${item.id}`);
        UI.toast('Disponibilidade removida.', 'success');
        await loadAvailability();
        await loadSlots(); // a grade de horários depende da disponibilidade
    } catch (error) {
        UI.toast(error.message);
    }
}

// Horários disponíveis e agendamento
async function loadSlots() {
    const id = selectedAttendantId();
    const date = document.getElementById('slot-date').value;
    const container = document.getElementById('slots-container');

    if (!id || !date) {
        container.innerHTML = '';
        return;
    }

    try {
        const data = await API.get(`/users/${id}/available-slots?date=${date}`);
        renderSlots(data.slots);
    } catch (error) {
        UI.toast(error.message);
    }
}

function renderSlots(slots) {
    const container = document.getElementById('slots-container');
    container.innerHTML = '';

    if (slots.length === 0) {
        container.textContent = 'Nenhum horário neste dia (atendente sem disponibilidade ativa).';
        return;
    }

    const grid = document.createElement('div');
    grid.className = 'slot-grid';

    for (const slot of slots) {
        const cell = document.createElement('div');
        cell.className = `slot ${slot.available ? 'available' : 'taken'}`;
        cell.textContent = `${slot.start}–${slot.end}`;
        // Ocupado: exibido em cinza, não clicável (case RQF2.3).
        if (slot.available) {
            cell.addEventListener('click', () => openBookingForm(slot));
        }
        grid.appendChild(cell);
    }

    container.appendChild(grid);
}

function openBookingForm(slot) {
    bookingSlot = slot;
    const attendantName = document.getElementById('attendant-select').selectedOptions[0].textContent;
    const date = document.getElementById('slot-date').value;

    document.getElementById('booking-info').textContent =
        `${attendantName} — ${formatDate(date)}, ${slot.start} às ${slot.end}`;
    document.getElementById('booking-form').reset();
    document.getElementById('booking-modal').classList.remove('hidden');
}

function closeBookingForm() {
    document.getElementById('booking-modal').classList.add('hidden');
}

async function onBookingSubmit(event) {
    event.preventDefault();

    const clientName = document.getElementById('b-client-name').value.trim();
    if (clientName === '') {
        UI.toast('O nome do cliente é obrigatório.');
        return;
    }

    try {
        await API.post('/appointments', {
            attendant_id: Number(selectedAttendantId()),
            date: document.getElementById('slot-date').value,
            start_time: bookingSlot.start,
            client_name: clientName,
            client_email: document.getElementById('b-client-email').value.trim(),
        });
        UI.toast('Agendamento criado com sucesso.', 'success');
        closeBookingForm();
        await loadSlots();
        await loadAppointments();
    } catch (error) {
        UI.toast(error.message);
    }
}

function formatDate(iso) {
    const [year, month, day] = iso.split('-');
    return `${day}/${month}/${year}`;
}

function todayIso() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

// --- Agenda do atendente ---
async function loadAppointments() {
    const id = selectedAttendantId();
    if (!id) return;

    try {
        appointments = await API.get(`/users/${id}/appointments`);
        renderAppointments();
    } catch (error) {
        UI.toast(error.message);
    }
}

function renderAppointments() {
    // Filtro de status (padrão: apenas agendados).
    const filter = document.getElementById('status-filter').value;
    const items = filter === 'all' ? appointments : appointments.filter((a) => a.status === filter);

    const tbody = document.getElementById('appointments-tbody');
    tbody.innerHTML = '';

    if (items.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.textContent = 'Nenhum agendamento para o filtro selecionado.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    for (const item of items) {
        const tr = document.createElement('tr');
        tr.appendChild(textCell(formatDate(item.date)));
        tr.appendChild(textCell(`${item.start_time.slice(0, 5)}–${item.end_time.slice(0, 5)}`));
        tr.appendChild(textCell(item.client_name));
        tr.appendChild(textCell(item.client_email || '—'));

        const statusCell = document.createElement('td');
        const tag = document.createElement('span');
        tag.className = `tag ${item.status}`;
        tag.textContent = item.status === 'scheduled' ? 'Agendado' : 'Cancelado';
        statusCell.appendChild(tag);
        tr.appendChild(statusCell);

        const actionsCell = document.createElement('td');
        // Cancelar só faz sentido em agendamento ativo.
        if (item.status === 'scheduled') {
            const actions = document.createElement('div');
            actions.className = 'row-actions';
            actions.appendChild(actionButton('Cancelar', 'btn-danger', () => cancelAppointment(item)));
            actionsCell.appendChild(actions);
        }
        tr.appendChild(actionsCell);
        tbody.appendChild(tr);
    }
}

async function cancelAppointment(item) {
    const label = `${item.client_name} em ${formatDate(item.date)} às ${item.start_time.slice(0, 5)}`;
    const confirmed = await UI.confirm(`Cancelar o agendamento de ${label}?`, 'Cancelar agendamento');
    if (!confirmed) return;

    try {
        await API.put(`/appointments/${item.id}/cancel`);
        UI.toast('Agendamento cancelado.', 'success');
        await loadAppointments();
        await loadSlots(); // o horário cancelado volta a ficar livre na grade
    } catch (error) {
        UI.toast(error.message);
    }
}

function populateWeekdaySelect() {
    const select = document.getElementById('a-day');
    WEEKDAYS.forEach((label, value) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        select.appendChild(option);
    });
}

function populateDaysCheckboxes() {
    const container = document.getElementById('days-checkboxes');
    WEEKDAYS.forEach((label, value) => {
        const wrapper = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = value;
        checkbox.className = 'day-checkbox';
        wrapper.appendChild(checkbox);
        wrapper.appendChild(document.createTextNode(` ${label}`));
        container.appendChild(wrapper);
    });
}

function populateHourSelect(select) {
    for (let hour = 0; hour <= 23; hour++) {
        const value = `${String(hour).padStart(2, '0')}:00`;
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    }
}

init();
