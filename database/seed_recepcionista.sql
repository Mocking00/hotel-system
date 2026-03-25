USE hotel_gestion;

-- Crea (o actualiza) un usuario de recepcionista para pruebas locales.
-- Usuario: recepcion
-- Contrasena: Recepcion#2026

INSERT INTO USUARIO (username, password, rol, activo)
VALUES (
  'recepcion',
  '$2y$10$Q9wty/n/NXO2lH1iikB/A.QkHV5eVsi4YQn5Y2V24.95tusGNP2OW',
  'recepcionista',
  1
)
ON DUPLICATE KEY UPDATE
  password = VALUES(password),
  rol = 'recepcionista',
  activo = 1;
