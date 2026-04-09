# Sistema Web de Gestion Hotelera

Aplicacion web para gestionar clientes, habitaciones, reservas y usuarios por roles
(administrador, recepcionista y cliente).

## Stack

- PHP 8+
- MySQL/MariaDB
- PDO
- Arquitectura MVC

## Estructura del proyecto

- `index.php`: punto de entrada y redireccion por sesion/rol.
- `config/`: configuracion de base de datos.
- `controllers/`: logica de flujo y validaciones de entrada.
- `models/`: acceso a datos y reglas de negocio.
- `views/`: interfaz por modulo y por rol.
- `utils/`: utilidades compartidas (sesion y validacion).
- `database/`: scripts SQL de esquema y semillas.

## Puesta en marcha

1. Crear una base de datos MySQL.
2. Importar el esquema principal:
	- `database/crear_base_datos_infinityfree.sql`.
3. Configurar credenciales en `config/database.php` o por variables de entorno:
	- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Asegurar que Apache apunte a la raiz del proyecto y abrir `index.php`.

## Notas operativas

- El sistema redirige automaticamente segun la sesion activa y el rol.
- Existen validaciones de negocio para mayoria de edad en clientes y coherencia de
  fechas en reservas.
