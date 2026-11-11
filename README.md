# TutoresOn-Line

Plataforma web para conectar estudiantes con tutores particulares.
HTML, CSS y JavaScript para la interfaz; PHP y MySQL para registro, sesiones y tutores.

## Estructura

```text
tutoresonline/
├── public/                 Interfaz pública y recursos estáticos
│   ├── *.html               Pantallas de la aplicación
│   └── assets/              CSS y JavaScript
├── api/                    Endpoints PHP consumidos por la interfaz
├── app/
│   ├── bootstrap/db.php     PDO, sesiones, CSRF y utilidades comunes
│   └── services/fotos.php   Validación y procesamiento de fotografías
├── config/
│   └── config.example.php   Plantilla de credenciales
├── database/                Esquemas y migraciones SQL
├── storage/uploads/fotos/   Archivos subidos por usuarios
├── .htaccess                Enrutamiento, bloqueo y cabeceras de seguridad
├── .gitignore               Exclusiones del repositorio
└── README.md                Documentación del proyecto
```

## Instalación

1. **Base de datos:** crea una base MySQL (en el hosting, desde cPanel o phpMyAdmin) e importa `database/database.sql`.
   Si ya tenías la versión anterior importada, importa solo `database/migracion_perfiles.sql` para añadir lo nuevo.
2. **Credenciales:** copia `config/config.example.php` como `config/config.php` y escribe el host, la base, el usuario y la contraseña.
   - En local con USBWebserver: `host` = `localhost`, `user` = `root` y la contraseña que tenga tu MySQL (suele estar vacía).
   - En el hosting usa un usuario propio de la base de datos, **nunca `root`**.
3. **Subir:** sube todos los archivos a `public_html` (o la carpeta del dominio), incluido `.htaccess`, y crea `config/config.php`.
4. **HTTPS:** cuando el hosting tenga certificado SSL, descomenta en `.htaccess` el bloque de redirección a HTTPS y la línea de HSTS.

Requiere PHP 7.4 o superior con las extensiones PDO MySQL (`pdo_mysql`).

## Perfiles, precios, contacto y mensajes

- **Perfil propio** (`perfil.html`): estudiantes y tutores editan nombre, descripción y foto. Los tutores añaden además especialidad, **precio por hora**, WhatsApp y correo de contacto.
- **Perfil de tutor** (`tutor.html?id=N`): solo se puede ver con una cuenta iniciada. Si no la hay, `tutor_perfil.php` responde 401 y la página envía a `login.html`; al iniciar sesión se vuelve al perfil que se quería ver.
- **Contacto:** el WhatsApp y el correo de contacto del tutor se entregan solo a cuentas iniciadas. Además hay mensajes dentro de la plataforma (`mensajes.html`): se puede escribir a un tutor o responder a quien ya escribió, con un máximo de 10 mensajes por hora.
- **Foto:** JPG, PNG o WebP de máximo 2 MB. Con la extensión GD se recorta en cuadrado y se recodifica como JPG de 512 px (elimina metadatos y contenido oculto). Se guarda con nombre aleatorio en `storage/uploads/fotos/`, donde `.htaccess` impide ejecutar código.

## Seguridad incluida

- Consultas preparadas (PDO) y contraseñas con `password_hash`; mínimo 8 caracteres.
- Token CSRF en login, registro y logout (el cierre de sesión solo funciona por POST).
- Límite de intentos de login: 5 fallos por correo o 30 por IP cada 15 minutos.
- Mensaje de error único en el login ("Correo o contraseña incorrectos") para no revelar qué correos existen.
- Los errores y datos del formulario viajan en la sesión (mensaje flash), no en la URL.
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS; ID regenerado al iniciar sesión.
- Cookie "Recordarme": solo se guarda el hash del token, se rota en cada uso y un token falso no puede borrar el de otra persona.
- Los errores del servidor se registran en el log y nunca se muestran al visitante.
- `.htaccess` mantiene la lógica y la configuración fuera de la zona pública, bloquea archivos internos y carpetas ocultas como `.git`, y añade cabeceras de seguridad (CSP, X-Frame-Options, nosniff, Referrer-Policy y Permissions-Policy).
- El JavaScript inserta datos del servidor solo con `textContent` (sin XSS) y no hay scripts en línea.

## Antes de publicar

- Confirma que `config/config.php` no está en el repositorio de Git.
- Si alguna vez subiste contraseñas reales a Git (por ejemplo en `users.json`), cámbialas: siguen visibles en el historial aunque borres el archivo.
- Comprueba los encabezados en <https://securityheaders.com> y el sitio en <https://observatory.mozilla.org>.

## Pendiente para próximas fases

Reseñas, tarifas, mensajería con tutores y agenda de sesiones. Las tablas `materias`, `tutores_materias` y `sesiones` ya están diseñadas en `database/database.sql`, pero todavía no las usa el código PHP.
