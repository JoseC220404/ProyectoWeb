Solicitudes UCLV
Aquí tienes el README.md profesional listo para tu profesor:
Markdown
Copy
Code
Preview
# Sistema de Gestión de Solicitudes Académicas UCLV

**Universidad Central "Marta Abreu" de Las Villas**  
Proyecto de Práctica Profesional / Trabajo de Curso  
Tecnologías Web con Laravel 12 + PHP 8.2

---

## 1. Descripción General

Sistema web integral para la gestión automatizada de solicitudes académicas estudiantiles (cambio de carrera, revisión de notas, solicitud de títulos, etc.). 

Permite el trámite digital end-to-end: desde que el estudiante crea la solicitud, pasando por la revisión de secretaría, hasta la resolución final (aprobación/rechazo), manteniendo un historial completo de auditoría de cada cambio de estado.

---

## 2. Stack Tecnológico

| Capa | Tecnología |
|------|------------|
| **Backend** | PHP 8.2 + Laravel 12 (MVC, Eloquent ORM) |
| **Frontend** | Blade + Tailwind CSS 3.4 + Alpine.js 3 |
| **Base de Datos** | MySQL 8.0 (o MariaDB) |
| **Autenticación** | Laravel Breeze (Middleware Auth) |
| **Icons** | FontAwesome 6 |
| **Control de Versiones** | Git + GitHub |

---

## 3. Arquitectura del Sistema

### Modelo MVC Implementado
- **Models**: Relaciones Eloquent (User, Student, Solicitud, Estado, HistorialEstado, etc.)
- **Controllers**: Lógica de negocio con autorización por roles
- **Views**: Blade components con layouts responsivos (Dark Mode integrado)

### Patrones Aplicados
- **Repository Pattern**: Consultas complejas encapsuladas en controladores
- **Transaction Safety**: Todas las operaciones críticas usan `DB::transaction()`
- **Soft History**: Tabla `historial_estados` para auditoría completa (quién, cuándo, por qué cambió)

---

## 4. Flujo de Trabajo (Workflow)

```mermaid
Estudiante → Crea Solicitud [Pendiente]
     ↓
Secretaría → Revisa (Panel Pendientes)
     ↓
    ┌──────────┬──────────┐
    ↓          ↓          ↓
En Revisión Aprobado   Rechazado
    ↓
Decano/Admin → Emite Resolución Final
Estados del Sistema
Pendiente (Naranja) - Recién creada, editable por estudiante
En Revisión (Azul) - Secretaría analizando documentación
Aprobado (Verde) - Trámite exitoso
Rechazado (Rojo) - Denegado con justificación
5. Roles y Permisos
Estudiante
Crear solicitudes (solo si está matriculado)
Ver historial de sus propias solicitudes
Editar/eliminar solo solicitudes en estado "Pendiente"
Secretaría
Dashboard con contadores en tiempo real
Panel de Pendientes (vista administrativa)
Cambiar estado: Pendiente → En Revisión
Rechazar solicitudes directamente
Agregar observaciones en cada cambio
Administrador
Gestión completa de estudiantes (CRUD)
Gestión de usuarios y roles
Acceso a todas las solicitudes
Capacidad de editar cualquier estado (override)
6. Funcionalidades Clave
A. Gestión de Estados con Historial Completo
Cada cambio de estado genera un registro en historial_estados con:
Usuario que realizó el cambio
Estado anterior y nuevo (string + FK)
Timestamp exacto
Observaciones/motivo del cambio
B. Validaciones de Negocio
No se puede editar una solicitud ya en proceso (En revisión/Aprobada/Rechazada)
No se puede eliminar solicitudes procesadas
Transacciones SQL atómicas (rollback automático si falla)
C. UI/UX Premium
Dark Mode: Persistente en localStorage
Responsive: Sidebar colapsable en móviles, tables adaptativas
Glassmorphism: Efectos visuales modernos (backdrop-blur)
Real-time Feedback: Alertas sin recarga (session flashes)
D. Filtros y Búsqueda
Búsqueda por nombre de estudiante (like %text%)
Filtro por tipo de solicitud
Filtro por estado (en dashboard)
7. Instalación Local
Requisitos Previos
PHP ≥ 8.2 (ext: mbstring, xml, mysql, gd)
Composer 2.x
Node.js 18+ y NPM
MySQL 8.0 o MariaDB 10.6
Pasos
bash
Copy
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/sistema-solicitudes-uclv.git
cd sistema-solicitudes-uclv

# 2. Instalar dependencias
composer install
npm install && npm run build

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# Editar .env con tus credenciales de base de datos:
# DB_DATABASE=decanato
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Crear base de datos y poblar
php artisan migrate:fresh --seed

# 5. Iniciar servidor
php artisan serve
# Acceder a: http://127.0.0.1:8000
8. Datos de Prueba (Seeders)
El sistema incluye datos demo ejecutables con migrate:fresh --seed:
Table
Copy
Rol	Email	Contraseña	Uso
Admin	admin@uclv.edu.cu	password	Gestión total
Secretaría	secretaria@uclv.edu.cu	password	Procesar solicitudes
Estudiante	estudiante1@uclv.edu.cu	password	Crear trámites
Tipos de Solicitudes precargados:
Solicitud de Título
Cambio de Carrera
Revisión de Notas
Justificante de Inasistencia
Prórroga de Estudios
9. Estructura de Base de Datos (Resumen)
Tablas principales:
users (autenticación, perfiles)
students (datos académicos, relación 1:1 con users)
solicituds (solicitudes propiamente dichas, FK a students y estados)
estados (catálogo: Pendiente, En revisión, Aprobado, Rechazado)
historial_estados (log de auditoría, relación 1:N con solicitudes)
tipo_solicituds (catálogo de trámites disponibles)
validacions y resolucions (extensión para flujos complejos)
Relaciones clave:
User 1:1 Student
Student 1:N Solicitud
Solicitud N:1 Estado (actual)
Solicitud 1:N HistorialEstado (trazabilidad)
10. Capturas de Funcionalidad
Dashboard Administrativo
docs/screens/dashboard.png
Vista con estadísticas en tiempo real y lastest activity
Panel de Pendientes
docs/screens/pendientes.png
Tabla administrativa con acciones rápidas (Check/Rechazar)
Detalle de Solicitud
docs/screens/show.png
Vista completa con historial de cambios y botones de resolución
11. Consideraciones Técnicas
Seguridad Implementada
Middleware auth en todas las rutas protegidas
Validación de ownership (estudiante solo ve sus solicitudes)
CSRF tokens en todos los formularios
Hash de contraseñas (bcrypt)
Prevención de Mass Assignment (fillable definidos)
Optimizaciones
Eager Loading (with()) en consultas N+1
Paginación (10-15 items por página)
Indexación BD en campos de búsqueda frecuente


RECALCAR Q ESTA VERSION ES PARA DEMOSTRAR LO APRENDIDO EN LA ASIGNATURA POR LO Q NO TIENE EL SISTEMA DE ROLES Y TODO LOS USUARIOS ENTRAN POR IGUAL PARA Q SE PUEDA MANIPULAR LA PAGINA LIBREMENTE 
