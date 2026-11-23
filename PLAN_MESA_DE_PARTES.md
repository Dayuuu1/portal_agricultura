# Plan de desarrollo: Mesa de Partes Virtual temporal

**Portal institucional de la Dirección Regional Agraria de Madre de Dios**
Versión 2 · 20 de noviembre de 2026 · Estado: **fase 1 implementada y desactivada**, a la espera de la aprobación de la entidad (ver secciones 11 y 13)

> **Nota legal.** Las referencias normativas de este documento sirven para orientar el diseño. Deben ser validadas por la Oficina de Asesoría Jurídica antes de poner en marcha el servicio, en especial los artículos citados y las reglas de notificación.

---

## 1. Contexto y objetivo

La entidad todavía no cuenta con un Sistema de Gestión Documental (SGD) propio. Hoy el portal solo tiene un ajuste, **«Mesa de partes virtual»** (`mesa_partes_url`, en *Datos del portal*), que muestra un enlace externo en la portada, en Contacto y en el Libro de Reclamaciones. Ese enlace está vacío: **no existe un canal digital de recepción de documentos**.

**Objetivo:** implementar en el portal una **Mesa de Partes Virtual (MPV) temporal**. En ella la ciudadanía crea una cuenta, presenta solicitudes con sus documentos, recibe un cargo de recepción, subsana observaciones y hace seguimiento. El personal de la entidad recibe, observa, deriva y responde desde el panel de administración.

**Carácter temporal:** el diseño debe permitir **exportar los expedientes** (datos, archivos e historial) al SGD definitivo cuando la entidad lo implemente.

---

## 2. Marco normativo y requisitos que se derivan

| Norma | Qué exige | Requisito para el sistema |
|---|---|---|
| **Ley N° 31170**, que dispone la implementación de mesas de partes digitales y notificaciones electrónicas | Mesa de partes digital disponible las 24 horas y los 7 días. Lo presentado en día u hora inhábil se considera presentado el primer día hábil siguiente. | **R1.** Recepción permanente y registro de dos fechas: la hora real de envío y la *fecha de presentación* para el cómputo de plazos. |
| **D.L. N° 1412**, Ley de Gobierno Digital, y su Reglamento, **D.S. N° 029-2021-PCM** | Marco de la mesa de partes digital, el procedimiento administrativo electrónico, la identidad digital y la casilla electrónica. | **R2.** Canal electrónico con identificación del ciudadano y bandeja de notificaciones. |
| **TUO de la Ley N° 27444, LPAG** (D.S. N° 004-2019-JUS), **art. 124**, requisitos de los escritos | Nombres y documento de identidad, domicilio, expresión concreta de lo pedido, fundamentos, autoridad a la que se dirige, relación de anexos, firma, y el número de expediente si es un trámite en curso. | **R3.** El formulario pide **solo** esos datos. |
| **TUO LPAG, art. 136**, observaciones a la documentación | No se puede negar la recepción. Se observa lo que falte y se otorgan **2 días hábiles** para subsanar; si no se subsana, se tiene por no presentado. | **R4.** Estado *Observado* con plazo automático y subsanación desde la cuenta. |
| **TUO LPAG**, cargo de recepción | Constancia con número de registro, fecha y hora. | **R5.** Cargo inmediato con número, fecha y hora, folios y huella de los archivos. |
| **TUO LPAG, art. 20.4**, notificación electrónica | Solo procede con **autorización expresa** del administrado. | **R6.** Casilla de autorización y registro de la fecha de depósito y de lectura de cada notificación. |
| **TUO LPAG**, principios de presunción de veracidad, informalismo y simplicidad, y **TUPA** | No exigir requisitos ni pagos que no estén en el TUPA o en una ley. | **R7.** Catálogo de tipos de trámite con sus requisitos, plazo y costo. La solicitud general siempre se recibe. |
| **TUO LPAG**, derecho a conocer el estado del procedimiento | Acceso al estado de la tramitación. | **R8.** Seguimiento con historial de movimientos. |
| **Ley N° 29733**, Protección de Datos Personales, y su Reglamento, **D.S. N° 016-2024-JUS** | Consentimiento informado, finalidad, plazo de conservación, derechos ARCO (acceso, rectificación, cancelación y oposición), medidas de seguridad, inscripción del banco de datos ante la Autoridad Nacional de Protección de Datos Personales (ANPD) y gestión de incidentes. | **R9.** Aviso de privacidad, solo los datos mínimos, almacenamiento privado, registro de accesos y política de conservación. |
| **Ley N° 27269**, Firmas y Certificados Digitales, y su Reglamento | Validez de la firma. | **R10.** Definir si basta el escrito firmado y escaneado más una declaración jurada, o si se pide firma digital (DNIe). |
| **D.U. N° 007-2020**, Marco de Confianza Digital, y NTP-ISO/IEC 27001 | Seguridad de la información e incidentes. | **R11.** HTTPS, cuentas verificadas, límites de intentos, copias de respaldo y auditoría. |
| **Ley N° 25323**, Sistema Nacional de Archivos | Conservación e integridad de los documentos. | **R12.** Huella SHA-256 de cada archivo, sin borrado de expedientes y exportación ordenada. |
| **Ley N° 29973**, Persona con Discapacidad (accesibilidad web) | Acceso sin barreras. | **R13.** Formularios accesibles. La mesa de partes física sigue existiendo y el personal puede registrar ahí lo presentado en papel. |
| **Modelo de Gestión Documental** (R.S.G. N° 001-2017-PCM/SGP) | Procesos de recepción, registro, derivación y archivo. | Referencia para los estados y la numeración. |

**Requisito institucional (no es código):** la MPV debe aprobarse mediante **resolución o directiva**. Esa norma fija el horario de recepción, el alcance (qué trámites), las reglas de notificación y los responsables, y debe publicarse en el portal.

**Alternativa a evaluar:** **Facilita Perú**, de la PCM, es gratuita y ya se ajusta a la normativa. Esta MPV puede convivir con ella o servir de puente hasta el SGD.

---

## 3. Alcance por fases

### Fase 1: mínimo legal (en producción con esto)
- Registro de ciudadanos con correo verificado, aviso de privacidad y autorizaciones (R2, R6, R9).
- Inicio de sesión, recuperación de contraseña y perfil.
- **Nuevo trámite** en un asistente de pasos, con documento principal y anexos (R3, R7).
- **Cargo de recepción** imprimible y enviado por correo (R5).
- **Bandeja de Mesa de Partes** en el panel: admitir y derivar, observar con plazo de 2 días hábiles, o declarar no presentado (R4).
- **Subsanación** desde la cuenta del ciudadano (R4).
- **Seguimiento** con hoja de ruta y **bandeja de notificaciones** (R6, R8).
- **Fecha de presentación** según horario y días hábiles, más una tabla de días no laborables (R1).
- **Consulta sin cuenta** con número de expediente y documento de identidad.

### Fase 2: atención por oficinas
- Rol por **unidad orgánica**: cada oficina ve solo lo derivado a ella, lo toma, lo re-deriva o responde con un documento.
- Control de plazos de atención: vencidos, por vencer y alertas por correo al personal.
- Reportes: expedientes por estado, por oficina y por tipo, y tiempos promedio.
- **Registro de documentos presentados en físico** (canal «presencial»).

### Fase 3: mejoras
- Validación de DNI con RENIEC vía **PIDE** (requiere convenio).
- Firma digital del escrito o del cargo; código QR de verificación en el cargo.
- Personas jurídicas con representante y vigencia de poder (si no entra en la fase 1).
- **Exportación** de expedientes al SGD definitivo.
- Segundo factor de autenticación opcional para ciudadanos.

---

## 4. Diseño funcional

### 4.1 Ciudadano (portal público)

| Pantalla | Ruta propuesta | Contenido |
|---|---|---|
| Presentación | `/mesa-de-partes` | Qué se puede presentar, horario y regla de días inhábiles, requisitos, enlace a la directiva y botones **Crear cuenta**, **Ingresar** y **Consultar expediente**. |
| Registro | `/mesa-de-partes/registro` | Tipo de persona; tipo y número de documento; nombres o razón social; domicilio (departamento, provincia, distrito y dirección); celular; correo; contraseña. Tres casillas: **aviso de privacidad** (obligatoria), **autorizo la notificación electrónica** (art. 20.4) y **declaración de veracidad**. |
| Verificación de correo | `/mesa-de-partes/verificar` | Enlace firmado enviado al correo. Sin verificación no se puede presentar. |
| Ingreso y recuperación | `/mesa-de-partes/ingresar`, `/mesa-de-partes/recuperar` | Con límite de intentos. |
| Mis trámites | `/mi-cuenta/tramites` | Lista con número, asunto, estado, fecha de presentación, plazo y acciones pendientes (por ejemplo «Subsanar antes del 24/11»). |
| Nuevo trámite | `/mi-cuenta/tramites/nuevo` | Asistente de pasos (4.2). |
| Detalle | `/mi-cuenta/tramites/{código}` | Datos, archivos, **hoja de ruta**, observaciones, subsanación y respuestas descargables. |
| Notificaciones | `/mi-cuenta/notificaciones` | Bandeja tipo casilla, con fecha de depósito y de lectura. |
| Perfil | `/mi-cuenta/perfil` | Datos de contacto, contraseña, revocar la notificación electrónica y solicitudes ARCO. |
| Consulta sin cuenta | `/mesa-de-partes/consulta` | Número de expediente y documento de identidad. Muestra solo el estado y la hoja de ruta, sin archivos. |

### 4.2 Asistente «Nuevo trámite»
1. **Tipo de trámite.** Solicitud general, acceso a la información pública o un trámite del TUPA. Si es la continuación de un expediente, se indica su número.
2. **Detalle.** Asunto, unidad destinataria (opcional, porque Mesa de Partes deriva) y fundamentos o descripción.
3. **Archivos.**
   - Documento principal en PDF, firmado y escaneado o con firma digital, según la decisión 11.4.
   - Anexos en PDF, JPG o PNG.
   - Comprobante de pago si el trámite tiene costo.
   - Límites sugeridos: **10 MB por archivo, 30 MB por trámite, hasta 10 anexos**. Los **folios** se cuentan automáticamente (páginas de los PDF).
4. **Revisión y envío.** Resumen, declaración jurada y envío. Al terminar se muestra el **cargo**.

### 4.3 Cargo de recepción
Contiene:
- Número de registro (`MPV-2026-000123`).
- Fecha y hora real de envío, y fecha de presentación para plazos (si fue inhábil, el siguiente día hábil, con la leyenda correspondiente).
- Datos del administrado, tipo de trámite, asunto y unidad destinataria.
- Lista de archivos con folios y **huella SHA-256**.

Se muestra en pantalla en formato imprimible, se envía por correo y queda en la cuenta.
*Decisión técnica:* generar un PDF requiere agregar un paquete (por ejemplo dompdf), que necesita aprobación. Sin él, el cargo es una página HTML preparada para imprimir o guardar como PDF desde el navegador.

### 4.4 Personal de la entidad (panel de administración)
- **Bandeja de Mesa de Partes**, con pestañas Nuevos, Observados, Subsanados, Derivados y Vencidos. Acciones:
  - **Admitir y derivar** a una unidad orgánica, con un proveído opcional.
  - **Observar**, con el motivo; el plazo se calcula a 2 días hábiles y se notifica al ciudadano.
  - **Declarar no presentado** cuando vence el plazo sin subsanar, con notificación.
  - **Archivar**, con motivo.
- **Bandeja de la unidad** (fase 2): Tomar, Re-derivar y Responder (adjunta un PDF que se notifica al ciudadano) y Concluir.
- Indicadores en el escritorio: nuevos sin revisar, observaciones vencidas y expedientes por vencer.
- Cada acción queda en la **hoja de ruta** y en el **registro de actividad**.

### 4.5 Estados del expediente

```
Recibido ──► Observado ──► Subsanado ──► (vuelve a revisión)
   │             │
   │             └─(vence sin subsanar)─► No presentado
   ▼
Derivado ──► En atención ──► Atendido ──► Archivado
```

| Estado | Quién lo cambia | Visible al ciudadano |
|---|---|---|
| Recibido | Sistema, al enviar | Sí |
| Observado | Mesa de Partes | Sí, con motivo y plazo |
| Subsanado | Ciudadano | Sí |
| No presentado | Mesa de Partes | Sí |
| Derivado | Mesa de Partes o unidad | Sí, con el nombre de la unidad |
| En atención | Unidad | Sí |
| Atendido | Unidad, al responder | Sí, con la respuesta descargable |
| Archivado | Mesa de Partes o unidad | Sí, con motivo |

### 4.6 Notificaciones
- Cada evento relevante (recepción, observación, no presentado, derivación, respuesta) crea un registro en la **bandeja del ciudadano** con fecha de depósito.
- Si el ciudadano **autorizó** la notificación electrónica, además se envía un **correo**. Los correos de verificación de cuenta y de recuperación de contraseña se envían siempre.
- Al personal de Mesa de Partes le llega un correo por cada trámite nuevo (configurable).
- Todos los correos van por **cola**, para no demorar la respuesta de la página.

---

## 5. Reglas de negocio

1. **Fecha de presentación (R1).** Si el envío ocurre en día hábil y dentro del horario de recepción, es ese día. Si no, es el **siguiente día hábil**. El horario sale de la directiva; hoy el portal publica «Lunes a viernes de 8:00 a.m. a 4:00 p.m.».
2. **Días hábiles.** Lunes a viernes, sin feriados nacionales ni Semana Santa (ya implementado en `App\Support\BusinessDays`), y sin los **días no laborables** que decrete el Gobierno, cargados en una tabla nueva editable desde el panel.
3. **Plazo de subsanación.** 2 días hábiles contados desde la notificación de la observación (art. 136).
4. **Plazo de atención.** Lo define cada tipo de trámite según el TUPA o la ley especial. Valor por defecto: **30 días hábiles**.
5. **Numeración.** Correlativo anual sin huecos (`MPV-AAAA-NNNNNN`), generado dentro de una transacción, como hoy `Complaint::nextCode()`.
6. **Sin borrado.** Los expedientes y sus archivos no se eliminan desde el panel; solo se archivan.
7. **Recepción obligatoria.** El sistema nunca rechaza un envío por falta de requisitos de fondo. Solo valida lo técnico (formato y tamaño de archivo) y lo demás se observa.

---

## 6. Modelo de datos

| Tabla | Campos principales |
|---|---|
| `citizens` | `person_type` (natural/jurídica), `document_type`, `document_number`, `first_names`, `last_names`, `business_name`, `legal_representative_*`, `email` (único), `email_verified_at`, `phone`, `department`, `province`, `district`, `address`, `password`, `privacy_accepted_at`, `e_notification_authorized_at`, `truthfulness_declared_at`, `is_active`, `last_login_at`, `remember_token`, timestamps |
| `procedure_types` | `name`, `slug`, `description`, `requirements`, `max_days` (días hábiles), `fee` (nullable), `requires_payment_proof`, `default_unit_id`, `is_active`, `sort_order` |
| `submissions` | `code`, `citizen_id`, `procedure_type_id`, `channel` (virtual/presencial), `subject`, `description`, `related_code`, `target_unit_id`, `current_unit_id`, `assigned_user_id`, `status`, `submitted_at` (hora real), `filed_on` (fecha de presentación), `observation`, `observed_at`, `cure_deadline`, `due_on`, `answered_at`, `folios`, timestamps |
| `submission_files` | `submission_id`, `kind` (principal, anexo, subsanación, comprobante, respuesta), `disk`, `path`, `original_name`, `mime`, `size`, `pages`, `sha256`, `uploaded_by_type` y `uploaded_by_id` (ciudadano o personal), timestamps |
| `submission_movements` | `submission_id`, `action`, `from_status`, `to_status`, `from_unit_id`, `to_unit_id`, `user_id` (nullable), `note`, `visible_to_citizen`, `created_at` |
| `citizen_notifications` | `citizen_id`, `submission_id`, `title`, `body`, `deposited_at`, `read_at`, `emailed_at` |
| `non_working_days` | `date`, `description` |

---

## 7. Diseño técnico

### 7.1 Autenticación de ciudadanos
- **Guard y proveedor propios** (`citizen` y la tabla `citizens`), separados de los usuarios del panel (`users`). Un ciudadano **nunca** puede entrar a `/admin`.
- Broker de contraseñas propio, verificación de correo (`MustVerifyEmail`) y sesión con expiración.
- Contraseñas con un mínimo de 10 caracteres, comprobadas contra listas de contraseñas filtradas (`Password::uncompromised()`).

### 7.2 Archivos
- Disco **privado** `local` (`storage/app/private/mesa-de-partes/AAAA/MM/{uuid}.pdf`), igual que los adjuntos del Libro de Reclamaciones. Nunca en el disco público.
- Descarga solo a través de un controlador con **policy**: el ciudadano dueño del expediente o el personal con permiso.
- Validación del tipo **por contenido** (no solo por extensión), tamaño máximo, conteo de páginas y SHA-256 al guardar.
- Opcional: antivirus (ClamAV) en el servidor.

### 7.3 Permisos (panel)
- Nuevos módulos en `App\Support\Permissions`:
  - `mesa_partes`: ver, admitir y derivar, observar, archivar.
  - `expedientes_unidad` (fase 2): ver y responder lo de su unidad.
  - `tipos_tramite`: catálogo.
  - `dias_no_laborables`.
- Restricción por unidad orgánica, del mismo modo que hoy los roles limitan por tipo de documento (`Role.document_type_ids`).
- Rol sugerido «Mesa de Partes» en `RoleSeeder`.

### 7.4 Infraestructura (requisitos previos)
- **Correo real:** hoy `.env` tiene `MAIL_MAILER=log`, así que **no se envía ningún correo**. Se necesita un SMTP institucional o un servicio de envío.
- **Procesos en segundo plano:** worker de colas (`QUEUE_CONNECTION=database`) y **programador de tareas** (`schedule:run`) activos en el servidor, para correos y alertas de vencimiento.
- **Servidor:** HTTPS obligatorio, espacio en disco y **copias de respaldo** del almacenamiento privado y de la base de datos.

### 7.5 Seguridad
- Límites de intentos: registro 5 por minuto, ingreso 5 por minuto y envíos de trámites por hora.
- Campo trampa (honeypot) contra bots en el registro.
- Páginas de cuenta con `noindex` y cabeceras de seguridad.
- Registro de actividad de todas las acciones del personal; los movimientos del ciudadano quedan en la hoja de ruta.
- Procedimiento para incidentes de seguridad (notificación a la ANPD según el Reglamento de la Ley 29733).

### 7.6 Protección de datos personales
- El **aviso de privacidad**, antes del registro, indica:
  - el responsable;
  - la finalidad (tramitar las solicitudes y notificar);
  - el banco de datos y su inscripción;
  - el plazo de conservación;
  - las transferencias, si las hay;
  - cómo ejercer los derechos ARCO;
  - las consecuencias de no dar los datos.
- Pedir solo los datos necesarios: por ejemplo, no pedir fecha de nacimiento ni otros datos que el trámite no requiera.
- Política de conservación: los expedientes se rigen por las normas de archivo. Las cuentas sin trámites se pueden eliminar a pedido del titular.
- Inscribir el banco de datos «Mesa de Partes Virtual» ante la ANPD.

---

## 8. Componentes existentes que se reutilizan

| Componente | Ubicación | Uso en la MPV |
|---|---|---|
| Días hábiles con feriados y Semana Santa | `app/Support/BusinessDays.php` | Fecha de presentación, plazo de subsanación y vencimientos. Se amplía con la tabla de días no laborables. |
| Numeración anual en transacción | `Complaint::nextCode()` | Código `MPV-AAAA-NNNNNN`. |
| Adjuntos en disco privado | `ComplaintController` | Almacenamiento de archivos. |
| Constancia con ruta firmada | `complaints.receipt` | Cargo de recepción. |
| Correos y plantillas | `app/Mail/Complaint*.php` | Notificaciones. |
| Tipos de documento de identidad | `app/Enums/IdentityDocumentType.php` | Registro de ciudadanos. |
| Roles, permisos y auditoría | `App\Support\Permissions`, `Role`, `LogsActivity` | Personal y trazabilidad. |
| Unidades orgánicas | `OrganizationalUnit` | Derivación. |
| Datos del portal | `Setting` (`schedule`, `mesa_partes_url`) | Horario y enlace de acceso. |

Cuando la MPV esté activa, los accesos de la portada, Contacto y Libro de Reclamaciones apuntarán a `/mesa-de-partes`. El ajuste `mesa_partes_url` se mantiene para poder volver a un sistema externo (Facilita o el futuro SGD).

---

## 9. Pruebas y criterios de aceptación (fase 1)

**Pruebas automatizadas (PHPUnit, feature):**
- **Registro e ingreso:**
  - Rechaza un documento de identidad mal formado y un correo duplicado.
  - Exige las casillas obligatorias.
  - Sin verificación de correo no se puede presentar.
  - Un ciudadano no puede entrar a `/admin`.
- **Fecha de presentación:** un envío el sábado, un feriado o fuera de horario queda presentado el siguiente día hábil, y uno dentro de horario queda presentado el mismo día.
- **Archivos:** solo PDF en el principal, respeta los límites de tamaño, cuenta los folios y guarda el SHA-256 en disco privado.
- **Cargo y numeración:** el cargo se genera con número correlativo sin huecos.
- **Ciclo de observación:** al observar, el plazo queda en 2 días hábiles y el ciudadano lo ve y lo recibe por correo si autorizó. Si subsana, el expediente vuelve a revisión; si no lo hace, Mesa de Partes lo declara no presentado.
- **Derivación:** la unidad destino aparece en la hoja de ruta del ciudadano.
- **Accesos:** un ciudadano no puede ver ni descargar expedientes de otro, y el personal sin permiso no ve la bandeja.
- **Consulta sin cuenta:** muestra el estado solo si el número y el documento coinciden.
- **Notificaciones:** se envían por correo solo con autorización expresa, y siempre quedan en la bandeja.

**Criterios de aceptación:**
- Un ciudadano completa registro, verificación, envío y cargo en menos de 10 minutos y sin ayuda.
- El personal de Mesa de Partes procesa un expediente (admitir y derivar, u observar) en no más de tres clics desde la bandeja.
- Todas las fechas y plazos coinciden con un cálculo manual en días hábiles.

---

## 10. Riesgos

| Riesgo | Mitigación |
|---|---|
| Canal sin respaldo normativo | Aprobar una directiva o resolución antes de abrirlo al público. |
| Correos que no llegan (hoy `MAIL_MAILER=log`) | Configurar SMTP y probarlo antes del lanzamiento. Las notificaciones también quedan en la bandeja de la cuenta. |
| Observaciones sin revisar dentro de plazo | Alertas en el escritorio y correo diario al personal. Definir quién cubre las ausencias. |
| Archivos maliciosos o pesados | Validar el tipo por contenido, aplicar límites de tamaño y, de ser posible, antivirus. |
| Pérdida de documentos | Copias de respaldo del disco privado y de la base de datos, y huellas SHA-256. |
| Migración al SGD definitivo | Numeración estándar, hoja de ruta completa y exportación en la fase 3. |
| Barrera para quien no usa internet | La mesa de partes física sigue y el personal registra lo presentado en papel (fase 2). |

---

## 11. Decisiones pendientes de la entidad

| # | Decisión | Opciones | Impacto |
|---|---|---|---|
| 11.1 | Aprobación del servicio | Resolución o directiva con horario, alcance y reglas | Requisito para lanzar |
| 11.2 | Trámites que recibirá | Solo solicitudes generales · más acceso a la información (Ley 27806) · TUPA completo | Catálogo de tipos de trámite y plazos |
| 11.3 | Quién atiende | Una persona de trámite documentario que deriva · cada oficina responde en el portal | Fase 1 o fase 2 de la bandeja por unidad |
| 11.4 | Firma del escrito | PDF firmado y escaneado más declaración jurada · firma digital (DNIe) | Validación del documento principal |
| 11.5 | Correo de envío | SMTP institucional · servicio externo | Requisito para lanzar |
| 11.6 | Personas jurídicas | En la fase 1 · en la fase 3 | Campos del registro y del poder |
| 11.7 | Cargo en PDF | Agregar un paquete para generar PDF (requiere aprobación) · página imprimible | Dependencias |
| 11.8 | Validación con RENIEC | Gestionar el convenio PIDE · solo validación de formato | Fase 3 |
| 11.9 | Alternativa externa | Evaluar Facilita Perú en paralelo · solo el portal | Estrategia |

---

## 12. Orden de trabajo sugerido (fase 1)

1. Migraciones y modelos: `citizens`, `procedure_types`, `submissions`, `submission_files`, `submission_movements`, `citizen_notifications` y `non_working_days`, con factories y seeders.
2. Guard `citizen`: registro, verificación de correo, ingreso, recuperación y perfil.
3. Cálculo de la fecha de presentación y de los plazos (`BusinessDays` más días no laborables más horario).
4. Asistente «Nuevo trámite», almacenamiento privado, folios, SHA-256 y cargo.
5. Panel: catálogo de tipos de trámite, días no laborables y bandeja de Mesa de Partes con sus acciones y la hoja de ruta.
6. Subsanación, notificaciones (bandeja y correo por cola) y consulta sin cuenta.
7. Permisos, rol «Mesa de Partes», indicadores en el escritorio y accesos desde la portada.
8. Pruebas de la sección 9, revisión de accesibilidad y prueba completa en un entorno de pruebas con SMTP real.

---

## 13. Estado de la implementación (20 de noviembre de 2026)

La fase 1 está construida y probada, pero **el servicio está apagado** (`mpv_enabled = 0`). Mientras siga apagado, la página `/mesa-de-partes` explica que el servicio todavía no recibe documentos, no se pueden crear cuentas y los enlaces del portal siguen apuntando al canal anterior.

### 13.1 Qué se construyó

| Área | Detalle |
|---|---|
| Copia de seguridad previa | `backup_portal_dramdd_2026-11-20_1259_antes_mesa_de_partes.sql` en la raíz del proyecto (excluida de git) |
| Base de datos | Migración `2026_11_20_130116_create_mesa_de_partes_tables`: ciudadanos, tipos de trámite, expedientes, archivos, hoja de ruta, casilla de notificaciones y días no laborables. Agrega 15 ajustes del grupo `mesa_partes` y 3 tipos de trámite base, editables y desactivables |
| Cuentas de ciudadanos | Guard `citizen` separado del panel. Registro de persona natural o jurídica, con aviso de privacidad, declaración de veracidad y autorización opcional de notificación electrónica. Verificación de correo, ingreso con límite de intentos, recuperación de contraseña, perfil y suspensión desde el panel |
| Presentación | Asistente de 4 pasos. El documento principal va en PDF y los anexos en PDF, JPG o PNG. Comprobante de pago cuando el trámite lo exige. Se calculan folios y huella SHA-256, y los archivos se guardan en el disco privado |
| Fecha de presentación | Recepción 24/7. Lo que llega fuera del horario o en un día no hábil cuenta desde el día hábil siguiente (Ley 31170). Se excluyen los feriados nacionales, la Semana Santa y los días no laborables registrados en el panel |
| Cargo | Número `{PREFIJO}-AAAA-NNNNNN`, fecha y hora, folios, huellas, plazo y base legal. Se puede imprimir o guardar como PDF desde el navegador |
| Seguimiento | Hoja de ruta con fecha y hora de cada paso. Las notas internas no se muestran al ciudadano. Consulta sin cuenta con número y documento de identidad, que puede desactivarse |
| Observaciones | El plazo para subsanar es configurable (2 días hábiles por defecto, art. 136). El ciudadano subsana desde su cuenta. Vencido el plazo, la entidad puede declarar el documento «no presentado» |
| Notificaciones | Casilla en la cuenta, con constancia de lectura. Los avisos formales solo se envían por correo si el ciudadano lo autorizó (art. 20.4). La mesa de partes recibe un aviso de cada documento nuevo y de cada subsanación |
| Panel | Grupo «Mesa de partes» con: expedientes (pestañas por revisar, observados, en trámite, plazo vencido y concluidos); acciones observar, admitir y derivar, registrar respuesta, en atención, no presentado, archivar y nota interna; administrados; tipos de trámite; días no laborables; configuración. El escritorio muestra tareas pendientes |
| Permisos | Módulos `mesa_partes` (ver y atender) y `mesa_partes_config` (configuración). En instalaciones nuevas se crea el rol «Mesa de partes» |
| Pruebas | 25 pruebas del módulo; 226 en total, todas aprobadas |

### 13.2 Qué se configura sin programar (Mesa de partes › Configuración)

Encendido o apagado del servicio; horario de atención; norma que aprueba el servicio; plazo para subsanar; plazo de atención por defecto; tamaño máximo por archivo y por trámite; cantidad de anexos; cuentas de personas jurídicas; consulta sin cuenta; correo que recibe los avisos; prefijo del número; indicaciones y base legal que ve la ciudadanía. Cada tipo de trámite tiene su propio plazo, costo, requisitos, oficina por defecto y la opción de exigir comprobante. Los días no laborables se registran en su propia pantalla.

### 13.3 Antes de encender el servicio

1. Aprobar el servicio con resolución o directiva (11.1) y registrar su número en la configuración.
2. Configurar el SMTP institucional (11.5) en **Configuración › Correo y acceso**, enviar el correo de prueba y activarlo. Esa página reemplaza los valores de `.env`, guarda la contraseña cifrada y solo la ven los administradores. Mientras no se active, sigue `MAIL_MAILER=log` y no sale ningún correo. Los correos se envían al momento, sin cola. Si uno falla, el error queda en el log y la notificación sigue disponible en la casilla del ciudadano.
3. Crear en el panel el rol «Mesa de partes» y asignarlo al personal. El seeder solo lo crea en instalaciones nuevas.
4. Revisar los tipos de trámite base y registrar los días no laborables del año.
5. Hacer una prueba completa en un entorno de pruebas con correo real.
6. Validar con Asesoría Jurídica si la entidad debe integrarse a la plataforma MESA DIGITAL PERÚ (D.S. N° 098-2025-PCM). Este módulo es temporal y no reemplaza esa obligación.

### 13.4 Límites conocidos

- No hay firma digital ni validación con RENIEC (11.4 y 11.8). El escrito se presenta escaneado y firmado, con declaración jurada de veracidad. Mientras tanto, el personal comprueba el documento y marca la cuenta con «Marcar identidad como verificada» en la ficha del administrado; cada expediente muestra si la identidad está verificada.
- Sin un SGD, la atención interna se registra a mano: derivación a una oficina, respuesta y archivo. Las oficinas no tienen todavía una bandeja propia (fase 2).
- Los días no laborables registrados afectan también el cómputo de plazos del Libro de Reclamaciones.
