ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('administrador', 'invitado', 'consejo_superior') NOT NULL DEFAULT 'invitado';
