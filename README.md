# TutoresOn-Line

TutoresOn-Line es una plataforma web que conecta estudiantes con tutores
particulares. Permite crear cuentas, consultar tutores, administrar perfiles,
publicar información de contacto y enviar mensajes internos.

## Funcionamiento general

- Las personas pueden registrarse como estudiantes o tutores.
- El inicio de sesión administra la sesión y protege las funciones privadas.
- Los tutores pueden definir especialidad, descripción, tarifa, foto y datos de
  contacto.
- Los estudiantes pueden consultar los perfiles públicos de los tutores.
- Los datos de contacto detallados y la mensajería requieren una sesión activa.
- Las fotografías se validan, se procesan y se almacenan de forma segura.
- La interfaz utiliza HTML, CSS y JavaScript.
- La lógica del servidor utiliza PHP y expone endpoints JSON en `api/`.

## Conexión con la base de datos

La aplicación utiliza MySQL mediante PDO. La conexión se centraliza en
`aplicacion/arranque/db.php`, que carga las credenciales desde el archivo local
`configuracion/config.php`.

La configuración tiene esta estructura:

```php
return [
    'host'   => 'localhost',
    'dbname' => 'tutores_online',
    'user'   => 'root',
    'pass'   => '',
];
```

El archivo `configuracion/config.php` es local y no debe publicarse ni
incluirse en Git. `configuracion/config.example.php` contiene únicamente una
plantilla sin credenciales reales.

La base de datos almacena principalmente:

- Usuarios, roles y credenciales protegidas.
- Perfiles, especialidades, descripciones y tarifas.
- Fotografías y datos de contacto de los tutores.
- Mensajes entre usuarios.
- Intentos fallidos de inicio de sesión.

Las consultas utilizan PDO con parámetros preparados. El esquema completo se
encuentra en `base_datos/database.sql`.

## Estructura principal

```text
├── publico/                  Interfaz y recursos estáticos
├── api/                      Endpoints PHP
├── aplicacion/               Conexión, sesiones y servicios
├── configuracion/            Configuración local
├── base_datos/               Esquema MySQL
├── almacenamiento/subidas/   Archivos subidos por usuarios
└── .htaccess                 Reglas de acceso y protección
```

## Seguridad

- Contraseñas almacenadas con `password_hash`.
- Consultas preparadas mediante PDO.
- Protección CSRF en formularios sensibles.
- Sesiones con cookies `HttpOnly` y `SameSite`.
- Límite de intentos de inicio de sesión.
- Validación y recodificación de fotografías.
- Archivos internos protegidos mediante `.htaccess`.
