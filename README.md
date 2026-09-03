# Sistema Web de Información para la Administración de Recursos Humanos

## Descripción

Sistema web desarrollado para apoyar la gestión y administración de los recursos humanos de la empresa **CONSTRUCTORA FLORES**, permitiendo centralizar y automatizar diferentes procesos relacionados con la administración del personal.

El sistema incorpora un módulo de **control de asistencia mediante reconocimiento facial**, permitiendo registrar y verificar la identidad de los empleados mediante tecnologías de reconocimiento facial.

## Objetivo

Desarrollar un sistema web que permita optimizar la gestión de recursos humanos mediante la centralización de la información, automatización de procesos administrativos y generación de información para apoyar la toma de decisiones.

## Módulos del sistema

El sistema contempla los siguientes módulos:

* **Gestión de empleados**

  * Registro, actualización y consulta de empleados.
  * Administración de información del personal.

* **Gestión de cargos y departamentos**

  * Administración de cargos.
  * Administración de departamentos y áreas.

* **Control de asistencia**

  * Registro de asistencia.
  * Control de puntualidad y retrasos.
  * Consulta del historial de asistencia.
  * Validación de ubicación.
  * Registro mediante reconocimiento facial.

* **Reconocimiento facial**

  * Registro del rostro de los empleados.
  * Actualización del registro facial.
  * Verificación de identidad.
  * Control de coincidencia facial.
  * Registro de información relacionada con los reconocimientos.

* **Gestión de ausencias**

  * Registro y consulta de ausencias.
  * Administración del estado de las ausencias.

* **Gestión de planillas**

  * Generación y consulta de planillas.
  * Cálculo de salarios.
  * Gestión de descuentos.
  * Gestión de adelantos.

* **Reportes y estadísticas**

  * Reportes relacionados con asistencia.
  * Reportes de empleados.
  * Reportes de planillas.
  * Consulta de información para apoyar la gestión administrativa.

* **Gestión de usuarios**

  * Administración de usuarios.
  * Control de acceso mediante roles y permisos.

* **Configuración**

  * Administración de parámetros generales del sistema.

## Tecnologías utilizadas

### Backend

* **Laravel**
* **PHP**
* **MySQL**
* **Composer**
* API REST

### Frontend

* **Vue.js**
* **JavaScript**
* **HTML5**
* **CSS3**
* **Axios**
* **Face API.js**

### Reconocimiento facial

El sistema utiliza un servicio independiente para determinadas operaciones relacionadas con el reconocimiento facial:

* **Python**
* **DeepFace**
* **TensorFlow**
* API para comunicación con el sistema web

### Infraestructura

* **Docker**
* **Docker Compose**
* **Nginx**
* **PHP-FPM**
* **MySQL**

## Estructura del proyecto

```text
RRHH_V01/
│
├── backend/
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   └── ...
│
├── frontend/
│   ├── public/
│   ├── src/
│   ├── img/
│   └── ...
│
├── face_service/
│   ├── Dockerfile
│   ├── main.py
│   └── requirements.txt
│
├── docker/
│   ├── app.dockerfile
│   └── nginx.conf
│
├── docker-compose.yml
├── .gitignore
└── README.md
```

## Reconocimiento facial

El sistema cuenta con un mecanismo de reconocimiento facial orientado al control de asistencia.

El proceso general contempla:

1. Selección del empleado para registrar su rostro.
2. Captura de imágenes del rostro.
3. Registro de las características faciales.
4. Verificación del rostro durante el marcado de asistencia.
5. Comparación entre el rostro capturado y el rostro registrado.
6. Validación del nivel de coincidencia.
7. Registro de la asistencia cuando la verificación es satisfactoria.
8. Registro de información relacionada con el reconocimiento.

El sistema también incorpora mecanismos de validación para reducir registros incorrectos y mantener información de las verificaciones realizadas.

## Requisitos

Para ejecutar el proyecto se recomienda contar con:

* Windows, Linux o macOS.
* Docker Desktop.
* Git.
* Navegador web moderno.
* Conexión a Internet para descargar las dependencias durante la configuración inicial.

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/vidal642/RRHH_V01.git
```

Ingresar al proyecto:

```bash
cd RRHH_V01
```

### 2. Configurar las variables de entorno

El proyecto utiliza archivos `.env` para las configuraciones locales.

Estos archivos **no se almacenan en el repositorio** por motivos de seguridad.

Para el backend, utilizar el archivo:

```text
backend/.env.example
```

como referencia para crear:

```text
backend/.env
```

Para el frontend:

```text
frontend/.env.example
```

como referencia para crear:

```text
frontend/.env
```

### 3. Ejecutar los contenedores

Desde la raíz del proyecto:

```bash
docker compose up -d --build
```

Para verificar los contenedores:

```bash
docker compose ps
```

### 4. Ejecutar las migraciones

Una vez iniciados los contenedores, ejecutar las migraciones de Laravel:

```bash
docker compose exec app php artisan migrate
```

Si el proyecto requiere datos iniciales:

```bash
docker compose exec app php artisan db:seed
```

> Los nombres de los servicios pueden variar dependiendo de la configuración definida en `docker-compose.yml`.

## Desarrollo

Para detener los contenedores:

```bash
docker compose down
```

Para volver a iniciarlos:

```bash
docker compose up -d
```

Para reconstruir las imágenes después de realizar cambios:

```bash
docker compose up -d --build
```

## Seguridad

Por seguridad, el repositorio no incluye archivos con información sensible, tales como:

* Archivos `.env`.
* Contraseñas.
* Credenciales de servicios.
* Claves privadas.
* Archivos temporales.
* Dependencias generadas localmente.

Los archivos `.env.example` sirven como referencia para configurar el entorno de desarrollo.

## Base de datos

El sistema utiliza **MySQL** como sistema gestor de base de datos.

La estructura de la base de datos se administra mediante las migraciones de Laravel ubicadas en:

```text
backend/database/migrations/
```

Esto permite recrear la estructura de la base de datos en un nuevo entorno mediante las herramientas de Laravel.

## Control de versiones

El proyecto utiliza **Git** para el control de versiones y **GitHub** como plataforma de almacenamiento del código fuente.

Repositorio:

https://github.com/vidal642/RRHH_V01

## Estado del proyecto

El sistema se encuentra en desarrollo como parte del proyecto de **Trabajo de Grado II**, incorporando progresivamente los módulos de administración de recursos humanos y el control de asistencia mediante reconocimiento facial.

## Autor

**Daniel**

Proyecto académico desarrollado para la gestión y administración de recursos humanos.

---

© 2026 - Sistema Web de Información para la Administración de Recursos Humanos
