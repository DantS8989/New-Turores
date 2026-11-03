# TutoresOn-Line

Plataforma web para conectar estudiantes con tutores particulares.
HTML, CSS y JavaScript para la interfaz; PHP y MySQL para registro, sesiones y tutores.

## Estructura

```text
tutoresonline/
├── index.html            Página principal (lista de tutores registrados)
├── login.html            Inicio de sesión
├── registro.html         Registro de estudiante o tutor
├── php/
│   ├── login.php         Procesa el login
│   ├── registro.php      Procesa el registro
│   ├── logout.php        Cierra la sesión (POST + CSRF)
│   ├── check_session.php Estado de sesión + token CSRF (JSON)
│   ├── flash.php         Mensajes de una sola vez: errores, bienvenida (JSON)
│   ├── tutores.php       Tutores registrados: nombre y especialidad (JSON)
│   ├── db.php            Conexión PDO, sesiones, CSRF, límite de intentos, "Recordarme"
│   └── config.example.php Plantilla de credenciales (copiar como config.php)
├── database.sql          Esquema MySQL (sin usuarios de prueba)
├── .htaccess             Bloqueo de archivos, HTTPS, cabeceras de seguridad
├── .gitignore            Excluye config.php
└── assets/
    ├── css/style.css
    └── js/main.js
```

## Instalación

1. **Base de datos:** crea una base MySQL (en el hosting, desde cPanel o phpMyAdmin) e importa `database.sql`.
2. **Credenciales:** copia `php/config.example.php` como `php/config.php` y escribe el host, la base, el usuario y la contraseña.
   - En local con USBWebserver: `host` = `localhost`, `user` = `root` y la contraseña que tenga tu MySQL (suele estar vacía).
   - En el hosting usa un usuario propio de la base de datos, **nunca `root`**.
3. **Subir:** sube todos los archivos a `public_html` (o la carpeta del dominio), incluido `.htaccess`, y crea allí `config.php`.
4. **HTTPS:** cuando el hosting tenga certificado SSL, descomenta en `.htaccess` el bloque de redirección a HTTPS y la línea de HSTS.

Requiere PHP 7.4 o superior con las extensiones PDO MySQL (`pdo_mysql`).

## Seguridad incluida

- Consultas preparadas (PDO) y contraseñas con `password_hash`; mínimo 8 caracteres.
- Token CSRF en login, registro y logout (el cierre de sesión solo funciona por POST).
- Límite de intentos de login: 5 fallos por correo o 30 por IP cada 15 minutos.
- Mensaje de error único en el login ("Correo o contraseña incorrectos") para no revelar qué correos existen.
- Los errores y datos del formulario viajan en la sesión (mensaje flash), no en la URL.
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS; ID regenerado al iniciar sesión.
- Cookie "Recordarme": solo se guarda el hash del token, se rota en cada uso y un token falso no puede borrar el de otra persona.
- Los errores del servidor se registran en el log y nunca se muestran al visitante.
- `.htaccess` bloquea `php/db.php`, `php/config.php`, `database.sql`, `README.md` y carpetas ocultas como `.git`, y añade cabeceras de seguridad (CSP, X-Frame-Options, nosniff, Referrer-Policy y Permissions-Policy).
- El JavaScript inserta datos del servidor solo con `textContent` (sin XSS) y no hay scripts en línea.

## Antes de publicar

- Confirma que `config.php` no está en el repositorio de Git.
- Si alguna vez subiste contraseñas reales a Git (por ejemplo en `users.json`), cámbialas: siguen visibles en el historial aunque borres el archivo.
- Comprueba los encabezados en <https://securityheaders.com> y el sitio en <https://observatory.mozilla.org>.

## Pendiente para próximas fases

Reseñas, tarifas, mensajería con tutores y agenda de sesiones. Las tablas `materias`, `tutores_materias` y `sesiones` ya están diseñadas en `database.sql`, pero todavía no las usa el código PHP.
