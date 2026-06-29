INSERT INTO users (name, email, password, role) VALUES
    ('Administrador', 'admin@admin.com',   '$2y$10$Usv.kG3UiSt25rilSi2Nk.tGjpbupx9/ja5rC61OkPaqyeIMsm3yW', 'admin'),
    ('Ana Souza',     'ana@empresa.com',   '$2y$10$ybZn9U7q41AwK1I.RJJSJuj0gASGhhwe/BXILxzUEb83Ns.fhtl6m', 'attendant'),
    ('Bruno Lima',    'bruno@empresa.com', '$2y$10$ybZn9U7q41AwK1I.RJJSJuj0gASGhhwe/BXILxzUEb83Ns.fhtl6m', 'attendant');

-- Disponibilidade dos atendentes. day_of_week: 1=segunda ... 5=sexta.
INSERT INTO availability (user_id, day_of_week, start_time, end_time) VALUES
    ((SELECT id FROM users WHERE email = 'ana@empresa.com'),   1, '09:00', '12:00'),
    ((SELECT id FROM users WHERE email = 'ana@empresa.com'),   1, '13:00', '18:00'),
    ((SELECT id FROM users WHERE email = 'ana@empresa.com'),   3, '09:00', '12:00'),
    ((SELECT id FROM users WHERE email = 'bruno@empresa.com'), 2, '08:00', '12:00'),
    ((SELECT id FROM users WHERE email = 'bruno@empresa.com'), 4, '14:00', '18:00');

-- Agendamentos com clientes ficticios (mock), em 2026-07-06 (segunda), dentro da janela da Ana.
INSERT INTO appointments (attendant_id, date, start_time, end_time, client_name, client_email) VALUES
    ((SELECT id FROM users WHERE email = 'ana@empresa.com'), '2026-07-06', '09:00', '10:00', 'Carlos Mendes',  'carlos@cliente.com'),
    ((SELECT id FROM users WHERE email = 'ana@empresa.com'), '2026-07-06', '10:00', '11:00', 'Patricia Gomes', 'patricia@cliente.com');
