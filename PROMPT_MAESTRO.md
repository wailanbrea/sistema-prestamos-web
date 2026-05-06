# Prompt Maestro - Sistema Prestamista

Actúa como un arquitecto senior full-stack experto en Laravel 12, PHP 8.3, MySQL 8, Bootstrap 5, Argon Dashboard, seguridad empresarial, sistemas financieros, reportes PDF/Excel, APIs para Android, impresión Bluetooth y arquitectura SaaS multiempresa.

Quiero desarrollar un sistema web profesional de préstamos tipo prestamista llamado **Sistema Prestamista**.

El sistema debe permitir gestionar empresas, usuarios, clientes, cotizaciones, préstamos, cuotas, cobradores, rutas, pagos, moras, atrasos, gastos, caja, documentos legales, recibos, reportes financieros, dashboard de inversión y futura integración con app Android para cobradores e impresión Bluetooth.

## Reglas técnicas obligatorias

- Backend en Laravel 12.
- PHP 8.3 o superior en producción.
- MySQL 8 o superior.
- Blade + Bootstrap 5 con estilo visual tipo Argon Dashboard.
- Interfaz completamente en español.
- Sistema multiempresa mediante `company_id`.
- Roles y permisos con Spatie Laravel Permission usando contexto por empresa.
- Seguridad estricta: CSRF, validaciones fuertes, autorización, auditoría, HTTPS y aislamiento por empresa.
- Ningún usuario puede ver, modificar, exportar ni imprimir datos de otra empresa.
- Toda lógica financiera debe vivir en servicios, no en controladores ni vistas.
- Toda operación financiera crítica debe ejecutarse con transacciones de base de datos.
- Pagos y anulaciones deben usar bloqueo de filas con `lockForUpdate`.
- Todo documento generado debe quedar registrado y asociado a empresa, cliente y préstamo cuando aplique.
- No usar dependencias abandonadas o con vulnerabilidades conocidas.

## Módulos requeridos

### 1. Dashboard financiero

- Capital invertido.
- Capital prestado.
- Capital disponible.
- Cobros del día.
- Ganancia neta.
- Intereses generados.
- Gastos.
- Préstamos activos.
- Préstamos saldados.
- Préstamos atrasados.
- Clientes morosos.
- Cobradores activos.
- Estado de inversión.
- Gráficas de cobros.
- Gráficas de préstamos por estado.

### 2. Clientes

- Registro completo de cliente.
- Cédula o identificación.
- Teléfonos.
- Dirección.
- Trabajo e ingresos.
- Foto.
- Estado.
- Nivel de riesgo.
- Referencias personales.
- Documentos adjuntos.
- Historial de préstamos.
- Historial de pagos.
- Estado de cuenta.

### 3. Cotizaciones

- Simular préstamos.
- Calcular cuotas.
- Calcular interés total.
- Calcular total a pagar.
- Generar tabla de amortización preliminar.
- Aprobar, rechazar o convertir a préstamo.

### 4. Préstamos

Frecuencias:

- Diario.
- Semanal.
- Quincenal.
- Mensual.

Métodos:

- Interés fijo.
- Cuota fija.
- Capital más interés.
- Solo interés.
- Amortización francesa.

Variantes:

- Diario incluyendo todos los días.
- Diario excluyendo domingos.
- Mora fija.
- Mora diaria fija.
- Mora diaria porcentual.
- Fecha de primer pago configurable.

### 5. Pagos y cobros

- Pagos completos.
- Pagos parciales.
- Aplicar pago primero a mora, luego interés y luego capital.
- Actualizar cuotas.
- Actualizar balance.
- Marcar préstamo como saldado si balance queda en cero.
- Crear movimiento de caja.
- Calcular comisión del cobrador.
- Generar recibo PDF.
- Anular pago con reversión completa.
- Registrar auditoría.

### 6. Cobradores

- Crear cobradores.
- Asociarlos o no a usuarios.
- Configurar comisión por porcentaje.
- Configurar comisión fija.
- Asignar rutas.
- Monitorear cobros.
- Ver dinero pendiente por entregar.
- Ver reporte diario por cobrador.

### 7. Moras y atrasos

- Calcular cuotas vencidas.
- Calcular días de atraso.
- Calcular mora fija.
- Calcular mora diaria fija.
- Calcular mora diaria porcentual.
- Reportar atrasados.
- Reportar clientes críticos.
- Marcar préstamos como legales o castigados.

### 8. Caja, gastos e inversión

- Desembolsos como salida.
- Pagos como entrada.
- Gastos como salida.
- Comisiones como salida.
- Inyección de capital.
- Retiro de capital.
- Ajustes controlados.
- Resumen financiero.
- Estado de inversión.

### 9. Documentos

- Recibo de pago.
- Comprobante de desembolso.
- Pagaré notarial.
- Contrato de préstamo.
- Carta de saldo.
- Estado de cuenta.

### 10. Reportes

- Préstamos activos.
- Préstamos saldados.
- Préstamos atrasados.
- Clientes morosos.
- Cobros diarios.
- Cobros por cobrador.
- Ganancias por período.
- Gastos por período.
- Estado de inversión.
- Balance por cliente.
- Comisiones de cobradores.
- PDF.
- Excel con dependencia moderna y segura.

### 11. App Android futura

Preparar API para:

- Login seguro.
- Rutas asignadas.
- Clientes asignados.
- Préstamos asignados.
- Cuotas pendientes.
- Registro de pagos.
- Consulta de recibos.
- Payload para impresión Bluetooth.

La app Android debe imprimir vía Bluetooth usando datos confirmados por el backend. El backend es la fuente de verdad para balances, pagos, recibos y auditoría.

## Base de datos requerida

- `companies`
- `company_settings`
- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `clients`
- `client_references`
- `client_documents`
- `collectors`
- `zones`
- `routes`
- `route_clients`
- `loan_quotes`
- `loans`
- `loan_installments`
- `payments`
- `payment_details`
- `collector_commissions`
- `cash_movements`
- `expense_categories`
- `expenses`
- `documents`
- `notifications`
- `audit_logs`

## Relaciones principales

- Una empresa tiene muchos usuarios.
- Una empresa tiene muchos clientes.
- Una empresa tiene muchos préstamos.
- Una empresa tiene muchos cobradores.
- Una empresa tiene muchos pagos.
- Una empresa tiene muchos gastos.
- Una empresa tiene una configuración.
- Un cliente tiene muchos préstamos.
- Un cliente tiene muchos pagos.
- Un cliente tiene muchas referencias.
- Un cliente tiene muchos documentos.
- Un cobrador tiene muchos préstamos.
- Un cobrador tiene muchos pagos.
- Un cobrador tiene muchas comisiones.
- Una zona tiene muchas rutas.
- Una ruta tiene muchos clientes mediante `route_clients`.
- Una cotización puede convertirse en un préstamo.
- Un préstamo tiene muchas cuotas.
- Un préstamo tiene muchos pagos.
- Un préstamo tiene muchos documentos.
- Un pago puede afectar muchas cuotas mediante `payment_details`.
- Un pago puede generar una comisión.
- Todo movimiento financiero genera trazabilidad en `cash_movements`.
- Todo evento crítico genera auditoría en `audit_logs`.

## Servicios requeridos

- `LoanCalculatorService`
- `LoanQuoteService`
- `LoanService`
- `InstallmentGeneratorService`
- `PaymentService`
- `PaymentCancellationService`
- `LateFeeService`
- `CollectorCommissionService`
- `CashMovementService`
- `DocumentGeneratorService`
- `DashboardService`
- `ReportService`
- `AuditService`
- `MobileReceiptPayloadService`

## Criterios de calidad

- Código mantenible, claro y modular.
- Controladores delgados.
- Form Requests para validación.
- Servicios para reglas de negocio.
- Pruebas unitarias para cálculos.
- Pruebas de integración para pagos y anulaciones.
- Pruebas de aislamiento por empresa.
- `composer audit` sin vulnerabilidades.
- Migraciones ejecutables desde cero.
- UI en español.
- PDF verificables.
- Reportes consistentes con caja.

No priorices velocidad sobre calidad estructural. Si una decisión técnica puede romper seguridad, balances, rendimiento o mantenibilidad, corrígela antes de implementar.
