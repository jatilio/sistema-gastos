# Publicación de Finanzas

## Resultado de la revisión local (3 de noviembre de 2026)

Se protegieron las rutas PHP mediante sesión, los formularios mediante un código contra solicitudes no autorizadas y las consultas de tarjetas y reportes mediante el propietario de los datos. Alberto conserva el acceso compartido autorizado. Se añadió límite de intentos de acceso, vencimiento por inactividad de 30 minutos y duración máxima de sesión de 12 horas, cookies HttpOnly/SameSite y Secure cuando se usa HTTPS. Los errores se registran sin mostrarse al visitante.

Dompdf se actualizó de 3.1.4 a 3.1.6 junto con sus dependencias. La auditoría de Composer no reportó avisos conocidos después de actualizar. Se deshabilitó la descarga de recursos remotos en los PDF y se protegieron salidas HTML/JavaScript.

Las comprobaciones incluyen acceso a rutas, formularios, archivos privados, permisos, inicio de sesión, pagos duplicados, saldos, auditoría de movimientos, pantallas y generación de los cuatro PDF. Las pruebas financieras usan tablas temporales o revierten sus operaciones; no se modificaron los saldos reales.

Esto es una revisión local del código y su comportamiento, no una certificación ni una prueba completa de penetración. La configuración del alojamiento todavía debe verificarse.

## Configuración del alojamiento

1. Crear una base de datos y una contraseña nuevas para este alojamiento. No reutilizar las credenciales locales. Conceder al usuario de la aplicación únicamente los permisos necesarios sobre su propia base; los permisos de creación de tablas y triggers se requieren durante la instalación/migración.
2. Configurar la raíz pública del sitio para que apunte exactamente a la carpeta `public`. El resto del proyecto debe quedar fuera del directorio publicado. La aplicación no debe publicarse desde la raíz del repositorio.
3. Instalar las dependencias con `composer install --no-dev --prefer-dist --optimize-autoloader`. Conservar `composer.lock`. No utilizar la carpeta antigua `dompdf-3.1.4`.
4. Configurar las credenciales mediante las variables `FINANZAS_DB_HOST`, `FINANZAS_DB_DB`, `FINANZAS_DB_USER`, `FINANZAS_DB_PASS` y, opcionalmente, `FINANZAS_DB_CHARSET`; o mediante `config/db.local.php`, que queda fuera del directorio público y fuera de Git.
5. Importar la copia de la base de datos por una conexión protegida. Verificar el acceso compartido, los triggers de saldos y auditoría y la tabla `intentos_acceso`. Para una instalación que aún no tenga esta última tabla, ejecutar por consola `php migrations/20261103_seguridad_acceso.php`. Revisar las demás migraciones según el estado de la base importada; no ejecutar scripts antiguos de generación de datos sin revisar.
6. Activar HTTPS y redirigir HTTP a HTTPS desde el alojamiento. Comprobar que PHP reconoce HTTPS y que las cookies tienen Secure. La aplicación rechaza HTTP para accesos públicos.
7. Aplicar las reglas de `public/.htaccess` si el servidor es Apache. En otros servidores, configurar reglas equivalentes: sin listado de carpetas, sin acceso a archivos ocultos, copias, registros ni PHP dentro de assets. Verificar que `.user.ini` se aplica o configurar sus valores desde el panel de PHP.
8. Mantener las copias de seguridad, logs, `.git`, scripts, migraciones y pruebas fuera del acceso público. No subir como contenido público scripts antiguos como `crear_admin.php`, `debug_password.php` o archivos de configuración. Los logs de errores deben guardarse en una ubicación privada.
9. Verificar en el dominio final: acceso sin sesión bloqueado, edición y pago autorizados, sesión de Alberto, reportes PDF, rechazo de formularios sin código, archivos privados inaccesibles y saldos intactos. Confirmar que los triggers se importaron sin un DEFINER incompatible con el proveedor.
10. Programar copias privadas y probar su restauración. Mantener PHP y dependencias actualizadas y usar una contraseña única para el panel del alojamiento.

No publicar datos reales hasta completar la verificación del dominio y la configuración del servidor. El servidor PHP integrado y `router.php` son únicamente para desarrollo local.
