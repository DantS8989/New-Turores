# TutoresOn-Line — Proyecto Web (HTML Semántico + Backend PHP sin SQL)

Plataforma educativa para conectar estudiantes con tutores particulares. Construida con **pantallas completas en HTML5 semántico** y **scripts en PHP** dedicados exclusivamente a la gestión de sesiones y simulación de la base de datos.

---

## 📁 Estructura del Proyecto

```text
tutoresonline/
│
├── index.html               # Pantalla Principal (HTML5 semántico: invitados y con sesión)
├── login.html               # Pantalla de Inicio de Sesión (Formulario HTML5)
├── registro.html            # Pantalla de Registro (Formulario HTML5 con selección de rol)
│
├── login.php                # Backend: Procesa login, consulta base de datos e inicia sesión
├── registro.php             # Backend: Valida registro, inserta nuevo usuario e inicia sesión
├── logout.php               # Backend: Destruye la sesión PHP y redirige
├── check_session.php        # Backend: API JSON para consultar si hay sesión PHP activa
├── db.php                   # Capa de Base de Datos: Funciones de consulta e inserción (futuro SQL)
│
├── data/                    # Almacenamiento local (simula las tablas de la BD)
│   └── users.json           # Registros de usuarios en formato JSON
│
├── assets/                  # Estilos y comportamiento frontend
│   ├── css/
│   │   └── style.css        # Diseño moderno, responsivo y variables CSS
│   └── js/
│       └── main.js          # Lógica frontend: ver clave, filtros en vivo y mensajes
│
└── README.md                # Documentación del proyecto
```

---

## 🖥️ Separación entre HTML y PHP

1. **Pantallas Visuales (100% HTML5)**:
   - **`index.html`**: Estructura HTML con `<header>`, `<nav>`, `<main>`, `<section>`, `<article>` y `<footer>`. Presenta el buscador interactivo, materias populares y tutores destacados. Si el usuario inicia sesión en PHP, el navbar y el banner se adaptan automáticamente.
   - **`login.html`**: Formulario estándar con `<form action="login.php" method="POST">`, campos de correo y contraseña, y botones de autocompletado demo.
   - **`registro.html`**: Formulario estándar con `<form action="registro.php" method="POST">`, selector de rol (Estudiante / Tutor) y validaciones de contraseña.

2. **Capa Backend en PHP (Lo que en el futuro será la Base de Datos)**:
   - **`db.php`**: Concentra las operaciones tipo repositorio (`db_find_user_by_email()`, `db_insert_user()`, `db_get_users()`). Cuando se implemente MySQL, solo se cambian estas funciones por sentencias SQL (`SELECT`, `INSERT`), manteniendo el resto intacto.
   - **`login.php` / `registro.php`**: Reciben los datos por `$_POST`, interactúan con `db.php`, administran las sesiones con `session_start()` y `$_SESSION['user']`, y redirigen con los parámetros correspondientes.
   - **`logout.php`**: Destruye la sesión mediante `session_destroy()`.

---

## 🚀 Cómo ejecutar el proyecto

### Opción 1: Servidor integrado de PHP (Recomendado y rápido)
Abre la terminal de PowerShell en esta carpeta y ejecuta:
```bash
php -S localhost:8000
```
Luego abre tu navegador en: **[http://localhost:8000](http://localhost:8000)**

### Opción 2: Con XAMPP, WampServer o Laragon
1. Coloca la carpeta `tutoresonline` en `htdocs` (en XAMPP) o `www` (en Wamp/Laragon).
2. Inicia el servicio **Apache**.
3. Abre en tu navegador: `http://localhost/tutoresonline`

---

## 🔑 Cuentas de Prueba Pre-configuradas

| Rol | Correo Electrónico | Contraseña | Nombre |
|---|---|---|---|
| **Estudiante** | `estudiante@ejemplo.com` | `123456` | Carlos Gómez |
| **Tutor** | `tutor@ejemplo.com` | `123456` | Dra. Ana Martínez |
| **Tutor** | `roberto@ejemplo.com` | `123456` | Prof. Roberto Silva |
