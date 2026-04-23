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
- `utils/`: utilidades compartidas (validacion, rutas, etc.).
- `database/`: scripts SQL de esquema y semillas.

## Modulos principales

- Autenticacion y usuarios.
- Gestion de clientes.
- Gestion de habitaciones.
- Gestion de reservas.
- Reportes operativos de reservas.

## Puesta en marcha

1. Crear una base de datos MySQL/MariaDB.
2. Importar el esquema principal:
	 - `database/crear_base_datos_infinityfree.sql`
3. Configurar credenciales en `config/database.php` o por variables de entorno:
	 - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Asegurar que Apache apunte a la raiz del proyecto.
5. Abrir `index.php`.

## Enrutamiento y URLs

El proyecto usa rutas basadas en controladores y un helper para evitar fallos por
rutas relativas al cambiar de contexto (vista directa vs vista renderizada por
controlador):

- `utils/url_helper.php`
	- `app_base_path()`
	- `app_url($path)`

Recomendacion: para nuevos enlaces entre modulos, usar `app_url()` o URLs a
controladores siguiendo el patron actual, evitando hardcodear rutas frágiles.

## Navegacion lateral (estado actual)

Se estandarizo la barra lateral en modulos de gestion:

- Orden de menu: `Dashboard -> Reservas -> Habitaciones -> Clientes`
- Para vistas de administracion se incluye adicionalmente: `Reportes`
- Botones de panel con dimensiones consistentes:
	- `font-size: 14px`
	- `min-height: 52px`
	- icono `20px` con ancho fijo `24px`

## Convenciones de interfaz recientes

- En el dashboard de cliente, el panel superior conserva solo el boton de cierre
	de sesion (estilo rojo).
- En la barra lateral de modulos, el subtitulo bajo el logo se unifico como:
	`Panel de Administracion`.

## Notas operativas

- El sistema redirige automaticamente segun sesion activa y rol.
- Existen validaciones de negocio para mayoria de edad en clientes y coherencia de
	fechas en reservas.
- Si `php` no esta disponible en el PATH del sistema, ejecutar validaciones con la
	instalacion de PHP de XAMPP (o desde el navegador) segun el entorno local.
