# Módulo de Reportes / Informes

Reportes financieros, operativos y de cartera. Pantalla principal con tarjetas en
`/reportes`, un sub-reporte por cada tarjeta, con filtros globales, exportación a
PDF/Excel, impresión y diseño responsive.

## Reportes disponibles

| Slug (URL `/reportes/...`) | Reporte | Método del servicio |
|---|---|---|
| `resumen-semanal` | Resumen semanal por día | `getWeeklySummary` |
| `semanal-consolidado` | Consolidado por cobrador/ruta | `getConsolidatedWeeklySummary` |
| `resumen-anual` | Resumen anual por mes | `getAnnualSummary` |
| `prestamos-entregados` | Préstamos desembolsados | `getDisbursedLoansReport` |
| `elegibles-renovar` | Clientes elegibles para renovar | `getRenewalEligibleClients` |
| `activos-atraso` | Activos con atraso (por nivel) | `getActiveOverdueClients` |
| `inactivos-atraso` | Inactivos con deuda pendiente | `getInactiveOverdueClients` |
| `gastos` | Gastos por período/categoría/usuario | `getExpensesReport` |
| `ganancias` | Ganancia bruta y neta | `getProfitReport` |
| `resumen-financiero` | Inversión, capital en calle, ROI | `getFinancialInvestmentSummary` |

El dashboard financiero clásico se conserva en `/reportes/financiero` (con PDF/CSV).

## Filtros (GET)

Todos los reportes aceptan por query string:

- `preset`: `today`, `this_week`, `last_week`, `week_before_last`, `this_month`, `this_year`, `custom`.
- `date_from`, `date_to`: usados con `preset=custom` (validación: `date_to >= date_from`).
- `year`: solo en resumen anual.
- `zone_id` (Sucursal = Zona), `route_id` (Ruta), `collector_id` (Cobrador).
- `search`: solo en préstamos entregados (nombre, código o teléfono).

Los filtros de ruta/zona se aplican vía la membresía del cliente
(`route_clients` → `routes` → `zones`); el de cobrador, por `collector_id` directo.
Los gastos solo se aíslan por empresa (la tabla no tiene cobrador/ruta).

## Exportación e impresión

- PDF: `GET /reportes/exportar/{slug}.pdf` (dompdf, una sola plantilla genérica).
- Excel: `GET /reportes/exportar/{slug}.xlsx` (PhpSpreadsheet, formato moneda + totales).
- Imprimir: botón en pantalla (`window.print()`, CSS de impresión oculta menú/filtros).
- WhatsApp: botón de compartir con enlace + período.

Los enlaces de exportación conservan todos los filtros aplicados.

## Reglas de cálculo

- **Capital cobrado** = `SUM(payments.principal_paid)` (pagos `valid`).
- **Rédito/Interés** = `SUM(payments.interest_paid)`. **Mora** = `SUM(payments.late_fee_paid)`.
- **Entregas** = `SUM(loans.principal_amount)` con `start_date` en el período y estado
  no cancelado/pendiente.
- **Gastos** = `SUM(expenses.amount)` por `expense_date`.
- **Total colectado** = capital + interés + mora.
- **Balance neto** = total colectado − entregas − gastos − comisiones.
- **Elegible para renovar**: ≥70% pagado **o** saldo ≤20% **o** última semana del préstamo,
  sin mora crítica (cuotas vencidas >15 días). Recomendación: Renovar / Revisar / No renovar.
- **Niveles de atraso**: 1-7, 8-15, 16-30, +30 días (por la cuota vencida más antigua).

## Permisos y plan

- Todas las rutas exigen el permiso `reports.view` (Administrador, Supervisor,
  Caja/Contabilidad, Legal). El rol Cobrador no lo tiene por defecto.
- Un usuario sin `collectors.manage` vinculado a un cobrador queda limitado a su
  propia cartera (`ReportScope`).
- **Importante (plan/licencia):** el middleware `EnsureMenuIsVisible` bloquea el
  módulo si el plan de la empresa no incluye `reports.index`. El plan `full` lo
  incluye (`menus => '*'`); el plan `prestamista` **no**. Para habilitar reportes en
  una empresa con plan `prestamista`, añadir `'reports.index'` a `config/plans.php`
  → `prestamista.menus`, o mover la empresa al plan `full`.

## Arquitectura

```
ReportFilters (DTO, presets + validación)
  └─ ReportScope (empresa + rol + zona/ruta/cobrador/cliente)
       └─ ReportService (10 métodos → { rows, totals, period, meta })
            ├─ ReportController (pantallas + exportación, catálogo único REPORTS)
            │    └─ vista única reports/report.blade.php + parciales
            └─ ReportPresenter (normaliza a columnas/filas/totales)
                 ├─ ReportPdfExporter (reports/pdf/report.blade.php)
                 └─ ReportExcelExporter (PhpSpreadsheet)
```

Cálculo en vivo con queries agregadas portables (SUM/COUNT, groupBy sobre columnas
reales). Sin tablas de snapshot: la capa está lista para cachear/snapshotear si el
volumen lo exige (Fase 5, diferida).

## Pruebas

`tests/Feature/ReportsModuleTest.php` cubre: carga de las 10 pantallas, índice de
tarjetas, totales del resumen semanal, aislamiento por empresa, exportación PDF+Excel,
validación de rango de fechas y 404 en tipo desconocido. El dashboard financiero
clásico sigue cubierto por `FinancialReportTest`.

Ejecutar: `php artisan test --filter Report`.
