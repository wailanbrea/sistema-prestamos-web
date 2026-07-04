# Cambios pendientes para Android

Fecha: 2026-07-04

Este archivo debe actualizarse cada vez que se hagan cambios en la web/backend que requieran paridad o ajuste en la app Android.

## 2026-07-04 — ESTADO: sincronizado

Todo lo listado abajo quedó implementado (backend API v2 + app Android) el 2026-07-04:

- API: `admin/loans` y `collector/loans` ahora incluyen `overdue_installments_count`,
  `overdue_amount_due` y `amount_due_today` por préstamo (scope `Loan::withDueSummary()`).
- API: `collector/loans` acepta `search` (número, cliente, teléfono, cédula);
  `admin/loans` amplió `search` a teléfono/cédula.
- API: login y `/profile` exponen en `company` las listas efectivas
  `enabled_loan_calculation_methods` y `enabled_payment_allocation_modes`.
- App: búsqueda de cartera contra el backend con debounce; toggle Ver todos/activos ya existía.
- App: el listado muestra cuotas vencidas y "Debe hoy" sin abrir el detalle.
- App: `german_amortization` en todos los catálogos de método de cálculo.
- App: dropdowns de método y selector de modos de pago filtran por la configuración
  habilitada (CompositionLocals `LocalEnabledCalculationMethods` / `LocalEnabledAllocationModes`;
  backend viejo = null = mostrar todo). Al editar se conserva el método actual del préstamo.
- App: mora (condonar) y detalle de tipo de pago aplicado ya estaban desde commits anteriores.

## 2026-07-04 (detalle original)

### Prestamos

- Agregar soporte para buscar prestamos por nombre de cliente y numero de prestamo, usando el filtro `q` del listado web/backend.
- Mantener el comportamiento de ocultar prestamos pagados por defecto.
- Agregar opcion `Ver todos` / `Ver activos` usando `include_paid=1` cuando se quieran incluir prestamos saldados.
- Mostrar en el listado de prestamos las cuotas vencidas y el monto que debe pagar hoy sin entrar al detalle.

### Modelos de prestamo

- Agregar el metodo `german_amortization`.
- Etiqueta: `Amortizacion alemana`.
- Descripcion funcional: amortizacion de capital fija; cada cuota paga el mismo capital y el interes se calcula sobre el balance restante, por eso la cuota total baja con el tiempo.
- La app debe leer/usar los mismos codigos que backend:
  - `flat_interest`
  - `fixed_installment`
  - `capital_plus_interest`
  - `interest_only`
  - `german_amortization`
  - `french_amortization`

### Configuracion de tipos habilitados

- Backend ahora permite habilitar/deshabilitar tipos de prestamo y tipos de pago desde Configuracion.
- La app no debe mostrar opciones deshabilitadas si el backend expone esa configuracion.
- Si una opcion deshabilitada se intenta enviar de todos modos, backend la rechazara.
- Campos nuevos en `company_settings`:
  - `enabled_loan_calculation_methods`
  - `enabled_payment_allocation_modes`

### Tipos de pago / reparto

- La app debe mostrar claramente el tipo de pago aplicado en recibos, historial y detalle de prestamo.
- Campos nuevos/esperados en pagos:
  - `allocation_mode`
  - `target_installment_id`
  - `excess_action`
  - `capital_prepaid`
  - `change_given`
- Modos soportados:
  - `auto`: paga mora, interes y capital en orden.
  - `principal_and_interest`: paga interes y capital, sin mora.
  - `interest_only`: paga solo interes; no baja capital.
  - `principal_only`: paga solo capital; puede dejar interes o mora pendiente.
  - `current_plus_capital`: paga la cuota actual y aplica el monto indicado como abono directo a capital.
  - `custom`: modo web para asignaciones detalladas; no debe enviarse desde Android si la pantalla no soporta asignaciones por cuota.
- Para saldar antes de tiempo, Android debe usar `current_plus_capital` y preguntar cuanto se abonara al capital.

### Mora

- La app debe soportar quitar/condonar mora por cuota usando el endpoint existente cuando el usuario tenga permiso.
- Al quitar mora, refrescar detalle de cuota y detalle del prestamo.

### Validaciones importantes

- Si el prestamo tiene mora pendiente, no debe marcarse como pagado hasta que la mora este pagada o condonada.
- Si se aplica un abono a capital, los intereses futuros deben recalcularse sobre el nuevo balance cuando el modelo del prestamo lo requiera.
- En prestamos solo interes, un pago a capital no debe asumirse como pago de cuotas futuras salvo que el usuario seleccione explicitamente ese comportamiento.
