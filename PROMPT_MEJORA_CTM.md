# PROMPT DE MEJORA - SISTEMA CTM (3 Módulos)

Usa este prompt como contexto inicial en cada sesión de trabajo sobre los módulos CTM.

---

## CONTEXTO DEL SISTEMA

Trabajo con un sistema web llamado **CTM (Contexto Unificado)** compuesto por 3 módulos PHP que gestionan el ciclo completo de equipamiento IT de una organización. Los 3 módulos comparten la base de datos MariaDB **contexto_unificado**. Producción corre en el servidor torito (172.16.1.223, nginx + PHP 8.5 + MariaDB 11.8); el desarrollo se hace en la notebook con Docker replicando ese entorno. El código es PHP procedural (sin frameworks), con funciones simples, sin objetos. Quiero mantener ese estilo: código sencillo en funciones.

### Módulos:

1. **ctm_inventarios** (CTM_062) - `D:\xampp\htdocs\ctm\contexto_unificado\ctm_inventarios`
   - Seguimiento de inventario IT: PCs (1,966), impresoras (297), monitores (538), otros (834)
   - Gestiona préstamos (62 activos) y reparaciones (6 activas)
   - Tablas: inve_pc, inve_impresoras, inve_monitores, inve_otros, inve_prestamos, inve_reparaciones, inve_menus, inve_id, inve_proveedores, inve_zonas_inventario, inve_estados, inve_pistasauditorias
   - Conecta a 3 BDs: contexto_unificado (RW), ctm_ocsweb (RO - OCS Inventory), morebius (RO)
   - Config: config/config.php, config/database.php (Singleton), config/session.php

2. **ctm_morebius** (AICO_666) - `D:\xampp\htdocs\ctm\contexto_unificado\ctm_morebius`
   - Asignación de equipos al personal: 3,128+ equipos, 554+ empleados
   - Artículos (celulares, notebooks, modems), organigrama, equipos por unidad
   - Tablas RO (base morebius): ctm_inventario, articulos_con, articulos_his, ctm_plantel, ctm_arbol
   - Tablas RW (contexto_unificado): more_menus, more_prestamos, more_reparaciones
   - Menús: INICIO, REPORTES, ADMINISTRACIÓN (7 sub), PERSONAL, EQUIPOS, UNIDADES

3. **ctm_presupuestos** (CTM_055) - `D:\xampp\htdocs\ctm\contexto_unificado\ctm_presupuestos`
   - Gestión presupuestaria con integración K2B
   - Ciclo: borrador (proyección multifactor) → revisión jerárquica (3 niveles) → aprobación → ejecución → cierre
   - 20 tablas ciclo_* + 4 tablas pres_k2b_* + 5 vistas + 1 stored procedure
   - Integra: K2B (compras), PHPMailer (email), Ollama (IA local), Twilio (SMS sin uso)
   - Menús: CONFIGURACIÓN (8 sub), PRESUPUESTOS (4), REVISIÓN (4), SOLICITUDES K2B (2), REPORTES (4), CIERRE (4)

### Servidores y entornos:

- **PRODUCCIÓN: servidor "torito", IP 172.16.1.223.** Ubuntu 26.04, nginx 1.28, PHP 8.5, MariaDB 11.8.
  Código en `/var/www/ctm/contexto_unificado`, servido en `https://172.16.1.223/ctm/contexto_unificado/`.
  Acceso SSH con el alias `ctm223` (usuario `serviciosau`). MariaDB con usuario `root`.
  **Es el servidor real en uso: nunca probar cambios directamente ahí.**
- **Test:** misma máquina, carpeta `/var/www/ctm/contexto_unificado_test` y base `contexto_unificado_test`,
  en `https://172.16.1.223/ctm/contexto_unificado_test/`.
- **Desarrollo:** notebook con Docker (nginx + PHP 8.5 + MariaDB 11.8, mismas versiones que producción),
  repo en `C:\dev\ctm\contexto_unificado`, URL `http://localhost:8080/ctm/contexto_unificado/`.
- La fuente de verdad del código es producción: el repo debe ser copia exacta de torito
  (`herramientas\traer_codigo_produccion.ps1`). Detalle completo en `ENTORNO_DESARROLLO.md`.

### Stack técnico común:
- PHP 8.2+ procedural (funciones, sin objetos ni frameworks)
- MariaDB 10.4 / MySQL
- Bootstrap 5.3.2 + Bootstrap Icons + SweetAlert2 (CDN)
- Database Singleton: Database::getInstance($db) con wrappers legacy (sql_do_query, sql_do_array)
- Sesiones PHP con validación IP + User-Agent, centralizado en ctm_entorno
- Menus dinámicos desde BD (inve_menus, more_menus, ciclo_menus) con niveles de acceso (nivacc)

### Preferencia de código:
- Funciones simples, sin clases ni objetos
- Español para nombres de variables y comentarios
- Prepared statements con mysqli
- Mantener la estructura existente de archivos

---

## PROBLEMAS IDENTIFICADOS (por prioridad)

### CRÍTICOS (Fase 1 - resolver primero):

1. **SQL Injection en los 3 módulos**: La función `sani_input()` usa blacklist de strings (remueve palabras como SELECT, INSERT) en vez de prepared statements. Muchas queries usan concatenación directa: `"WHERE legajo = '$legajo'"`. Necesito reemplazar TODA query con parámetros por `mysqli_stmt` con placeholders `?`.

2. **Credenciales en texto plano**:
   - config.php de los 3 módulos tiene password de Gmail y BD en texto plano
   - inve_pc tiene campos clave1-clave5 con credenciales en texto plano
   - ctm_presupuestos usa SHA256 sin salt para contraseñas de usuario
   
3. **Sin protección CSRF**: Ningún formulario tiene token CSRF.

4. **Session timeout de 365 días en Morebius**: Debe ser 30 minutos.

5. **Dashboard Morebius roto**: Muestra "Página no encontrada o sin contenido asignado". La ruta en more_menus para el menú INICIO no apunta a un archivo válido.

### IMPORTANTES (Fase 2):

6. **Datos incompletos en inventarios**: Equipos recientes (P2370-P2375) con marca "S/E", IP "S/E", RAM "0". No hay validación de campos obligatorios al dar de alta.

7. **Sin paginación**: Los listados (1,966+ PCs) cargan todo sin paginar.

8. **Código legacy coexistente**: Archivos _old, copy, v1 conviven con v2 en los 3 módulos.

9. **Sin índices de BD** en columnas frecuentes: codigopc, legajo, ip, cc, esquema.

10. **Sin transacciones**: Operaciones multi-tabla (cierre de período, prestamos, reparaciones) no usan BEGIN/COMMIT/ROLLBACK.

11. **Parsing K2B frágil**: SUBSTRING_INDEX(esquema, '-', N) repetido en múltiples queries. Debería ser función SQL o vista.

12. **Ollama sin fallback**: Si el servicio cae, las notificaciones fallan silenciosamente.

13. **13 endpoints AJAX en Morebius + 6 en Presupuestos sin documentación**.

### MEJORAS FUNCIONALES (Fase 3):

14. Dashboard ejecutivo unificado que cruce presupuesto + inventario + asignación.
15. Export Excel/PDF en todos los listados.
16. Notificaciones automáticas de vencimiento de garantías.
17. Tabla junction para préstamos multi-activo (actualmente 1 activo por tipo por préstamo).
18. Trail de auditoría completo en los 3 módulos.

---

## INSTRUCCIONES DE TRABAJO

Cuando trabajes en este sistema:

1. **Lee primero** el archivo que vas a modificar completo antes de editarlo.
2. **Mantén el estilo**: funciones simples, sin clases, PHP procedural.
3. **No rompas lo que funciona**: haz cambios incrementales y probables.
4. **Prepared statements siempre**: toda query con parámetros debe usar `$stmt = $con->prepare("... WHERE campo = ?"); $stmt->bind_param("s", $variable);`
5. **Respeta la estructura de archivos existente**: config/, includes/, web/mod/, web/int/ajax/.
6. **Comenta en español**: cada función nueva debe tener un comentario breve sobre qué hace.
7. **Prueba en localhost**: el sistema corre en http://localhost/ctm/contexto_unificado/

### Función helper para prepared statements (agregar a includes/functions.php de cada módulo):

```php
// Ejecuta query con prepared statement y retorna resultado
function query_segura($con, $sql, $tipos = "", $params = []) {
    $stmt = $con->prepare($sql);
    if ($tipos && $params) {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}
```

### Función para CSRF (agregar a config/session.php de cada módulo):

```php
// Genera token CSRF si no existe
function generar_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida token CSRF del formulario
function validar_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// HTML del campo hidden para formularios
function campo_csrf() {
    return '<input type="hidden" name="csrf_token" value="' . generar_csrf() . '">';
}
```

---

## CÓMO USAR ESTE PROMPT

Copia todo esto al inicio de una nueva conversación y luego pide la tarea específica. Ejemplos:

- "Aplica prepared statements en ctm_inventarios/includes/functions.php"
- "Corrige el dashboard roto de Morebius revisando more_menus"
- "Agrega validación de campos obligatorios en el alta de PCs"
- "Implementa paginación en el listado de PCs (web/mod/inventario/pc/listar_v2.php)"
- "Agrega CSRF a todos los formularios de ctm_presupuestos"
- "Crea la función SQL para parsear esquema K2B en vez de SUBSTRING_INDEX"
- "Reduce el session timeout de Morebius a 30 minutos"
- "Agrega índices a las tablas de inventarios en las columnas de búsqueda"
