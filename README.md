# Sistema de Inventario Web

Aplicación web para la gestión de productos, clientes, ventas, compras, usuarios y control de inventario.

## Equipo
**Innovación Web**

### Integrantes
- Kleber Iván Peña Cadena
- Christel Scarlet Alvear Pamintuan
- Evelyn Bethzabe Ramírez Ponce
- Jorge Elías Zavala García

## Tecnologías utilizadas
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- GitHub

## Módulos principales
- Productos
- Clientes
- Ventas
- Compras
- Usuarios
- Reportes y control de inventario

## Modelo de autenticación

**Sesión + cookie**

Se eligió este modelo porque el Sistema de Inventario Web está desarrollado en PHP y MySQL. Es una alternativa sencilla para mantener identificado al usuario y controlar los roles del sistema, reduciendo la complejidad de implementación.

## Roles

El sistema trabaja con roles simples:

- **Administrador:** acceso a las funciones administrativas autorizadas.
- **Empleado:** acceso a las funciones permitidas según su rol.

## Estado del proyecto

Proyecto académico en desarrollo para la materia Web Application Capstone, orientado a la creación de un Sistema de Inventario Web.

## Buenas prácticas de seguridad

Para fortalecer la seguridad del Sistema de Inventario Web se consideran las siguientes prácticas:

- Las contraseñas deben almacenarse utilizando hash seguro y nunca en texto plano.
- Las consultas a la base de datos deben realizarse de forma parametrizada para prevenir inyección SQL.
- La autenticación se gestiona mediante sesión y cookies.
- Los roles y permisos deben verificarse en el servidor antes de permitir acciones administrativas.
- Los mensajes de error no deben mostrar información sensible del sistema.
- Las acciones importantes del sistema deben registrarse sin almacenar contraseñas ni datos sensibles.
