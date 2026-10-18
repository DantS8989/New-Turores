# TutoresOn-Line — Sesiones, cookies y registro en PHP

Proyecto en HTML5 semántico con un backend PHP dedicado a sesiones, la
cookie "Recordarme" y el registro/login de usuarios. Por ahora los datos
se guardan en `data/users.json` (sin base de datos SQL).

## Estructura

```text
tutoresonline-php/
├── index.html            # Pantalla principal (pública, se adapta si hay sesión)
├── login.html             # Formulario de inicio de sesión + casilla "Recordarme"
├── registro.html          # Formulario de registro (elige rol: estudiante o tutor)
│
├── login.php               # Verifica credenciales, abre sesión, crea cookie "Recordarme"
├── registro.php            # Valida y crea el usuario, abre sesión automáticamente
├── logout.php              # Cierra sesión y borra la cookie "Recordarme"
├── check_session.php       # API JSON: ¿hay sesión activa? (la consulta assets/js/main.js)
├── db.php                  # Acceso a datos + todas las funciones de sesión y cookies
│
├── data/
│   ├── users.json          # "Tabla" de usuarios (JSON)
│   └── .htaccess           # Bloquea el acceso directo a este archivo desde el navegador
│
└── assets/
    ├── css/style.css
    └── js/main.js
```

## Cómo ejecutarlo

```bash
php -S localhost:8000
```
y abre `http://localhost:8000`. También funciona con XAMPP/WampServer/Laragon copiando la carpeta a `htdocs` o `www`.

## Qué cambió respecto a la versión anterior

- **Contraseñas con hash real.** `registro.php` ya guarda las contraseñas con `password_hash()`, nunca en texto plano. Las cuentas demo que ya tenías en `users.json` (con contraseña en texto plano) se migran solas al hash seguro la primera vez que alguien inicia sesión con ellas: ese fue el motivo de guardarlas así en primer lugar, para no perder esas cuentas de prueba.
- **Se quitó un acceso indebido.** El `login.php` anterior dejaba entrar a cualquiera a las cuentas con `id` 1, 2 o 3 usando la clave `123456` o `password123`, sin importar si esa cuenta había cambiado su contraseña. Eso ya no existe.
- **"Recordarme" (cookie de 30 días).** Si marcas la casilla al iniciar sesión, `login.php` crea un token aleatorio, guarda solo su *hash* en `users.json` (el token real nunca se guarda) y lo manda en una cookie `httponly`. En cada visita, `check_session.php` usa esa cookie para reabrir la sesión sola si ya se cerró, y rota el token (uno robado deja de servir en cuanto tú vuelves a entrar). `logout.php` borra la cookie y el token guardados.
- **Sesión más segura.** La cookie de sesión de PHP ahora es `httponly` y `SameSite=Lax`, y se marca `secure` automáticamente si el sitio corre con HTTPS. Cada inicio de sesión llama a `session_regenerate_id()` para evitar fijación de sesión.
- **`data/` protegida.** Agregué `data/.htaccess` para que nadie pueda abrir `data/users.json` directamente escribiendo su URL (solo funciona con Apache; si usas Nginx avísame y te paso el bloque equivalente).
- **Rutas reorganizadas.** `style.css` y `main.js` ahora están en `assets/css/` y `assets/js/`, como ya los referenciaban `index.html`, `login.html` y `registro.html`.

## Pendiente (no incluido aquí)

Esto sigue usando `data/users.json`, no una base de datos SQL. El diseño de la base de datos (diagrama + script SQL) es un paso aparte que no tocamos en esta entrega.
