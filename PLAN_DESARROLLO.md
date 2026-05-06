# Plan de Desarrollo - Sistema Prestamista

## 1. Estrategia

El sistema se desarrollará por fases. El orden no es negociable: primero seguridad, multiempresa y base financiera; luego operaciones; después reportes, documentos y API móvil. Construir pantallas antes de cerrar aislamiento por empresa sería una mala decisión técnica porque aumentaría el riesgo de fuga de datos entre empresas.

## 2. Fase 0 - Base técnica ya iniciada

Estado: en progreso avanzado.

Incluye:

- Proyecto Laravel 12 creado.
- Migraciones principales creadas.
- Modelos Eloquent iniciales creados.
- Relaciones base creadas.
- Spatie Permission instalado y configurado con `company_id`.
- Roles y permisos iniciales creados.
- Servicios financieros iniciales creados.
- DomPDF instalado.
- Seeder demo creado.
- Validación base ejecutada.

Validación ya realizada:

- `php artisan migrate:fresh --seed`
- `php artisan test`
- `composer audit`
- `composer dump-autoload -o`

Pendiente de esta fase:

- Ajustar `.env` para MySQL real.
- Configurar nombre del sistema, locale español y timezone.
- Revisar PHP 8.3 en entorno objetivo.
- Habilitar extensión `zip` en PHP CLI.
- Mantener actualizado `PROMPT_MAESTRO.md` como contrato de alcance.

## 3. Fase 1 - Autenticación, seguridad y multiempresa

Objetivo:

Crear la base segura de acceso antes de exponer módulos.

Entregables:

- Login en español.
- Logout.
- Recuperación de contraseña si aplica.
- Layout base autenticado.
- Middleware de usuario activo.
- Middleware de empresa activa.
- Middleware de permiso.
- Contexto global de empresa para Spatie Permission.
- Restricción por `company_id` en queries del dominio.
- Vista de perfil básico.
- Configuración de sesión segura.

Archivos esperados:

- Controladores de autenticación.
- Middlewares.
- Providers.
- Layout Blade principal.
- Vistas auth.
- Tests de acceso.

Criterios de aceptación:

- Un usuario inactivo no puede entrar.
- Un usuario bloqueado no puede entrar.
- Un usuario solo accede a datos de su empresa.
- Un usuario sin permiso recibe 403.
- Los roles funcionan por empresa.

Validación:

- Tests feature de login.
- Tests de permisos.
- Tests de aislamiento por empresa.

## 4. Fase 2 - UI base tipo Argon Dashboard

Objetivo:

Construir una interfaz administrativa limpia, responsive y lista para módulos.

Entregables:

- Sidebar.
- Navbar superior.
- Layout responsive.
- Componentes de cards financieras.
- Componentes de tablas.
- Componentes de filtros.
- Componentes de badges por estado.
- Botones visibles de editar y eliminar.
- Estilos Bootstrap 5.
- Font Awesome.

Reglas:

- Toda la interfaz en español.
- No usar menús de tres puntos para acciones principales.
- No mezclar lógica de negocio en Blade.
- No crear pantallas de marketing; el sistema debe abrir al dashboard o login.

Criterios de aceptación:

- Funciona en escritorio, tablet y móvil.
- No hay textos cortados en botones o tablas.
- Los estados son visualmente claros.
- El layout permite crecer sin rehacer todo.

## 5. Fase 3 - Clientes, referencias y documentos adjuntos

Objetivo:

Crear el módulo completo de clientes.

Entregables:

- Listado con filtros.
- Crear cliente.
- Editar cliente.
- Ver detalle.
- Soft delete.
- Referencias personales.
- Documentos adjuntos.
- Historial de préstamos.
- Historial de pagos.
- Estado de cuenta preliminar.

Clases esperadas:

- `ClientController`
- `ClientReferenceController`
- `ClientDocumentController`
- `StoreClientRequest`
- `UpdateClientRequest`
- `ClientService`

Criterios de aceptación:

- No se permite duplicar código de cliente dentro de una misma empresa.
- Validación fuerte de datos.
- Documentos se guardan en storage privado o disco configurado.
- Cliente eliminado no rompe préstamos históricos.

Validación:

- Tests CRUD.
- Tests de aislamiento por empresa.
- Tests de validación.

## 6. Fase 4 - Cotizaciones y cálculo financiero

Objetivo:

Permitir simular préstamos sin afectar caja ni balances.

Entregables:

- Formulario de cotización.
- Resultado de cálculo.
- Tabla de cuotas preliminar.
- Estados de cotización.
- Aprobar, rechazar y convertir.

Servicios:

- `LoanCalculatorService`
- `LoanQuoteService`

Criterios de aceptación:

- Una cotización convertida no se convierte dos veces.
- El cálculo es reproducible.
- No se crean movimientos de caja desde cotizaciones.
- Soporta diario, semanal, quincenal y mensual.

Validación:

- Tests unitarios por método de cálculo.
- Tests de conversión.
- Tests de fechas de cuotas.

## 7. Fase 5 - Préstamos y generación de cuotas

Objetivo:

Crear préstamos reales con cuotas y control financiero inicial.

Entregables:

- Crear préstamo desde cero.
- Crear préstamo desde cotización.
- Asignar cliente.
- Asignar cobrador.
- Configurar mora.
- Generar cuotas.
- Crear movimiento de caja por desembolso.
- Ver detalle de préstamo.
- Ver tabla de cuotas.
- Cambiar estados permitidos.

Servicios:

- `LoanService`
- `InstallmentGeneratorService`
- `CashMovementService`
- `AuditService`

Criterios de aceptación:

- Crear préstamo es transaccional.
- No se genera préstamo sin cliente válido de la misma empresa.
- No se asigna cobrador de otra empresa.
- Cuotas suman el total esperado.
- Caja registra salida por desembolso.

Validación:

- Tests de creación.
- Tests de cuotas.
- Tests de caja.
- Tests de restricciones multiempresa.

## 8. Fase 6 - Pagos, recibos y anulaciones

Objetivo:

Implementar el flujo financiero más crítico del sistema.

Entregables:

- Registro de pago.
- Pago parcial.
- Distribución mora, interés y capital.
- Actualización de cuotas.
- Actualización de balance.
- Comisión de cobrador.
- Movimiento de caja.
- Recibo PDF.
- Anulación con reversión completa.

Servicios:

- `PaymentService`
- `PaymentCancellationService`
- `LateFeeService`
- `CollectorCommissionService`
- `CashMovementService`
- `DocumentGeneratorService`
- `AuditService`

Criterios de aceptación:

- Pago a préstamo saldado se rechaza.
- Pago a préstamo cancelado se rechaza.
- Pago parcial actualiza cuota a parcial.
- Pago completo marca cuota pagada.
- Pago que salda préstamo marca préstamo pagado.
- Anulación revierte todo.
- Anulación doble se rechaza.

Validación:

- Tests de pago completo.
- Tests de pago parcial.
- Tests de mora.
- Tests de anulación.
- Tests de concurrencia con `lockForUpdate`.

## 9. Fase 7 - Cobradores, zonas y rutas

Objetivo:

Organizar operación diaria de cobro.

Entregables:

- CRUD de cobradores.
- CRUD de zonas.
- CRUD de rutas.
- Asignación de cobrador a ruta.
- Asignación ordenada de clientes.
- Reporte diario por cobrador.
- Reporte de dinero pendiente por entregar.

Criterios de aceptación:

- No se asignan clientes de otra empresa.
- No se asignan cobradores de otra empresa.
- No se duplica cliente dentro de la misma ruta.
- Comisiones cuadran con pagos válidos.

## 10. Fase 8 - Gastos, caja y capital

Objetivo:

Cerrar la trazabilidad financiera.

Entregables:

- Categorías de gastos.
- Registro de gastos.
- Comprobante adjunto.
- Movimientos de caja.
- Inyección de capital.
- Retiro de capital.
- Ajustes controlados.
- Reporte de caja por fecha.

Criterios de aceptación:

- Todo gasto crea salida de caja.
- Toda inyección crea entrada.
- Todo retiro crea salida.
- Los movimientos no se editan libremente.
- Auditoría registra operaciones críticas.

## 11. Fase 9 - Documentos legales y PDF

Objetivo:

Generar documentación formal desde datos del sistema.

Entregables:

- Recibo de pago.
- Comprobante de desembolso.
- Pagaré notarial.
- Contrato de préstamo.
- Carta de saldo.
- Estado de cuenta.
- Guardado de documentos generados.

Criterios de aceptación:

- PDF se genera con datos correctos.
- PDF queda asociado a empresa, cliente y préstamo.
- No se puede generar documento de otra empresa.
- Plantillas imprimen correctamente.

Validación:

- Render visual de PDFs.
- Tests de permisos.
- Tests de existencia de archivo.

## 12. Fase 10 - Dashboard y reportes

Objetivo:

Dar control financiero y operativo al usuario.

Entregables:

- Dashboard principal.
- Reporte de préstamos activos.
- Reporte de préstamos saldados.
- Reporte de préstamos atrasados.
- Reporte de clientes morosos.
- Cobros diarios.
- Cobros por cobrador.
- Ganancias por período.
- Gastos por período.
- Estado de inversión.
- Balance por cliente.
- Comisiones.
- Exportación PDF.
- Exportación Excel segura cuando se resuelva dependencia.

Servicios:

- `DashboardService`
- `ReportService`

Criterios de aceptación:

- Totales del dashboard cuadran con caja.
- Reportes filtran por fechas.
- Reportes filtran por empresa.
- Exportaciones respetan permisos.

## 13. Fase 11 - API básica para Android

Objetivo:

Preparar integración futura con app Android de cobradores.

Entregables:

- Autenticación API segura.
- Endpoint de rutas asignadas.
- Endpoint de clientes asignados.
- Endpoint de préstamos asignados.
- Endpoint para registrar pagos.
- Endpoint para consultar recibo.
- Endpoint para payload de impresión Bluetooth.
- Endpoint de sincronización básica.

Reglas:

- El cobrador solo ve sus datos.
- No exponer endpoints administrativos.
- Diseñar respuestas estables y versionadas.
- El backend calcula y confirma pagos; Android no recalcula balances finales.
- Toda impresión Bluetooth debe usar recibos confirmados por el servidor.
- Los pagos offline, si se implementan, deben tener identificador único del dispositivo para evitar duplicados.

Endpoints sugeridos:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`
- `GET /api/v1/collector/routes`
- `GET /api/v1/collector/clients`
- `GET /api/v1/collector/loans`
- `GET /api/v1/collector/installments/due`
- `POST /api/v1/payments`
- `GET /api/v1/payments/{payment}/receipt`
- `GET /api/v1/payments/{payment}/print-payload`
- `POST /api/v1/sync/payment-attempts`

Validación:

- Tests API.
- Tests de permisos.
- Tests de payload inválido.

## 14. Fase 12 - Hardening de producción

Objetivo:

Dejar el sistema listo para VPS y operación real.

Entregables:

- Configuración Apache.
- `.env` de producción documentado.
- Cache de configuración.
- Cache de rutas.
- Queue configurada.
- Logs rotables.
- Backups de base de datos.
- Política de archivos subidos.
- Revisión de permisos de storage.
- Revisión de seguridad.

Validación:

- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `composer audit`
- Pruebas de smoke en Apache.

## 15. Orden recomendado de implementación inmediata

1. Configurar `.env` para MySQL local.
2. Implementar autenticación y layout base.
3. Implementar middleware multiempresa.
4. Crear tests de aislamiento por empresa.
5. Construir módulo de clientes.
6. Construir cotizaciones.
7. Construir préstamos.
8. Construir pagos.
9. Construir anulaciones.
10. Construir dashboard y reportes.

## 16. Riesgos y decisiones pendientes

### PHP

El entorno actual usa PHP 8.2.12. Producción debe usar PHP 8.3 o superior para cumplir el requisito.

### Excel

No se debe instalar Laravel Excel viejo. Si Packagist o PHP impiden instalar versión moderna, se debe posponer Excel o implementar CSV temporal, dejando claro que CSV no reemplaza Excel avanzado.

### Concurrencia

Pagos y anulaciones deben usar transacciones y bloqueo de filas. Sin esto, dos pagos simultáneos pueden corromper balances.

### Multiempresa

El mayor riesgo del sistema es una consulta sin `company_id`. Se deben escribir pruebas específicas para este punto desde la Fase 1.

### Documentos legales

Los textos legales deben ser revisados por un abogado local antes de producción. Técnicamente se pueden generar, pero el contenido legal no debe inventarse.

## 17. Definición de terminado

Una fase se considera terminada solo si:

- El código compila.
- Las migraciones corren desde cero.
- Los tests relevantes pasan.
- No hay vulnerabilidades en `composer audit`.
- La UI está en español.
- Los permisos fueron validados.
- El aislamiento por empresa fue probado.
- Los casos de error están manejados.
- La documentación del módulo quedó actualizada.
