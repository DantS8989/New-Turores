# TutoresOn-Line

Plataforma web educativa para conectar estudiantes con tutores particulares.
El proyecto utiliza HTML para las pantallas, CSS y JavaScript para la
interfaz, y PHP para gestionar los usuarios y las sesiones.

## Estructura del proyecto

```text
tutoresonline/
├── index.html
├── login.html
├── registro.html
├── login.php
├── registro.php
├── logout.php
├── check_session.php
├── db.php
├── data/
│   └── users.json
└── assets/
    ├── css/
    │   └── style.css
    └── js/
        └── main.js
```

## Funcionamiento general

- Las páginas HTML contienen las pantallas principales, los formularios de
  inicio de sesión y el registro de usuarios.
- `style.css` define la apariencia visual y la adaptación a distintos tamaños
  de pantalla.
- `main.js` añade comportamiento a la interfaz, como filtros, mensajes y
  comprobaciones de sesión.
- Los archivos PHP reciben los datos de los formularios, validan la
  información y administran el acceso de los usuarios.
- Las sesiones PHP permiten conservar la identidad del usuario mientras
  navega por el sitio. `check_session.php` informa al frontend si existe una
  sesión activa y `logout.php` la cierra.

## Datos y base de datos

Actualmente el proyecto utiliza `data/users.json` como almacenamiento local
de usuarios. Este archivo cumple una función similar a una tabla sencilla:
cada registro contiene los datos de un usuario, como su nombre, correo,
contraseña protegida y rol.

`db.php` funciona como una capa de acceso a datos. Allí se concentran las
operaciones para buscar, consultar y registrar usuarios, de modo que el resto
del proyecto no necesita acceder directamente al archivo JSON. Esta
separación facilita reemplazar posteriormente el almacenamiento local por una
base de datos SQL sin cambiar la lógica principal de los formularios y las
sesiones.

Las contraseñas se almacenan mediante un hash seguro y no como texto plano.
Además, la información de usuarios se mantiene dentro de la carpeta `data`
para evitar que forme parte de las pantallas públicas del sitio.
