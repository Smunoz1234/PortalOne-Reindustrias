# PortalOne

## Descripción General
**PortalOne** es un sistema integral basado en PHP, diseñado para una sinergia perfecta con **SAP**. Ofrece un conjunto robusto de funcionalidades empresariales, desde la gestión de tareas diarias hasta la administración avanzada de archivos, usuarios, pagos y categorías. Esta solución integral es el núcleo para una gestión empresarial eficaz.

## Características Técnicas
Desarrollado con **PHP** y **JavaScript**, PortalOne se destaca por su experiencia de usuario fluida y su rendimiento excepcional. La arquitectura del sistema está construida sobre cimientos sólidos, proporcionando una interfaz intuitiva que permite una gestión de datos sin esfuerzo dentro del ecosistema SAP, llevando la eficiencia y productividad a nuevos niveles.

## Estructura de Ramas
- **Main**: La rama de producción, que contiene el código más estable y seguro.
- **Testing**: Dedicada a las pruebas de integración y aseguramiento de la calidad.
- **Development**: El espacio de trabajo para el desarrollo de nuevas funcionalidades.

## Guía de Instalación

### Para Nuevas Instalaciones
1. Clone el repositorio en la rama deseada con `git clone -b nombre_de_la_rama https://github.com/DesarrolloNedugaTech/PortalOne.git`.

2. Configure `includes/entorno.php` con los parámetros de su entorno.

### Para Actualizaciones
1. Elimine el directorio `.git` de la versión anterior.
2. Copie `.git` desde una versión más reciente.
3. Actualice `includes/entorno.php` con la nueva configuración.
4. Verifique la rama con `git branch`.
5. Actualice los cambios con `git pull origin nombre_de_la_rama`.