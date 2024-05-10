# PortalOne

Este repositorio aloja un sistema o aplicación web basado en PHP, diseñado para integrarse con SAP y ofrecer una amplia gama de funcionalidades empresariales. Desde la gestión de actividades hasta la administración de archivos, usuarios, pagos y categorías, esta solución abarca aspectos clave de la gestión empresarial.

Utilizando tecnologías como PHP y JavaScript, el sistema está diseñado para brindar una experiencia fluida y eficiente. Con una arquitectura sólida y una interfaz intuitiva, esta aplicación facilita la interacción y el control de datos dentro del entorno SAP, optimizando así los procesos de negocio y mejorando la productividad.

## Ramas del repositorio

- **Main:** Rama principal, donde se encuentra el código estable y listo para producción.
- **Testing:** Rama de pruebas, donde se realizan pruebas de integración y calidad antes de fusionar el código a la rama principal.
- **Development:** Rama de desarrollo, donde se trabajan las nuevas funcionalidades y características antes de ser enviadas a pruebas.

## Instrucciones de instalación

### Instalación desde cero

1. Clone el repositorio desde la rama correspondiente al entorno que desea configurar:
   'git clone -b nombre_de_la_rama https://github.com/DesarrolloNedugaTech/PortalOne.git'
2. Modifique el archivo includes/entorno.php según las configuraciones específicas de su entorno.

### Actualización de un repositorio existente
1. Borre el directorio `.git` de la versión desactualizada de PortalOne.
2. Copie el directorio `.git` desde una versión actualizada de PortalOne.
3. Modifique el archivo `includes/entorno.php` según las configuraciones específicas de su entorno.
4. Valide que esté ubicado en la rama correspondiente al entorno que está configurando.
   'git branch'
5. Haga pull de los cambios:
   'git pull origin nombre_de_la_rama'