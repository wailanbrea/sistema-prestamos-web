<?php

declare(strict_types=1);

return [
    'clients' => [
        'title' => 'Clientes',
        'description' => 'Registro de clientes, referencias, documentos, historial de préstamos y estado de cuenta.',
        'next_steps' => ['CRUD con Form Requests', 'Referencias personales', 'Documentos adjuntos', 'Historial financiero'],
    ],
    'loan-quotes' => [
        'title' => 'Cotizaciones',
        'description' => 'Simulación de préstamos, cálculo de cuotas, intereses y conversión controlada a préstamo.',
        'next_steps' => ['Formulario de cotización', 'Tabla de amortización', 'Aprobación/rechazo', 'Conversión a préstamo'],
    ],
    'loans' => [
        'title' => 'Préstamos',
        'description' => 'Creación de préstamos, asignación de cobrador, generación de cuotas y control de balances.',
        'next_steps' => ['Crear préstamo', 'Generar cuotas', 'Desembolso en caja', 'Detalle financiero'],
    ],
    'payments' => [
        'title' => 'Cobros',
        'description' => 'Registro de pagos, distribución entre mora, interés y capital, recibos y anulaciones.',
        'next_steps' => ['Registrar pago', 'Generar recibo', 'Comisión de cobrador', 'Anulación transaccional'],
    ],
    'collectors' => [
        'title' => 'Cobradores',
        'description' => 'Gestión de cobradores, comisiones, rutas asignadas y monitoreo de cobros.',
        'next_steps' => ['CRUD de cobradores', 'Comisiones', 'Cobros diarios', 'Dinero pendiente por entregar'],
    ],
    'routes' => [
        'title' => 'Zonas y rutas',
        'description' => 'Organización territorial de cobros, rutas, zonas y orden de clientes.',
        'next_steps' => ['CRUD de zonas', 'CRUD de rutas', 'Asignación de clientes', 'Orden de recorrido'],
    ],
    'expenses' => [
        'title' => 'Gastos',
        'description' => 'Registro de gastos, categorías, comprobantes e impacto en resumen financiero.',
        'next_steps' => ['Categorías', 'Registro de gasto', 'Movimiento de caja', 'Reporte por período'],
    ],
    'cash-movements' => [
        'title' => 'Caja',
        'description' => 'Libro de movimientos financieros: desembolsos, cobros, gastos, capital y ajustes.',
        'next_steps' => ['Movimientos por fecha', 'Trazabilidad', 'Entradas/salidas', 'Resumen de caja'],
    ],
    'documents' => [
        'title' => 'Documentos',
        'description' => 'Generación y almacenamiento de recibos, pagarés, contratos, cartas de saldo y estados de cuenta.',
        'next_steps' => ['Plantillas PDF', 'Guardar documentos', 'Asociar a cliente/préstamo', 'Payload móvil'],
    ],
    'reports' => [
        'title' => 'Reportes',
        'description' => 'Reportes financieros y operativos exportables a PDF y Excel seguro.',
        'next_steps' => ['Atrasados', 'Ganancias', 'Cobros por cobrador', 'Estado de inversión'],
    ],
    'settings' => [
        'title' => 'Configuración',
        'description' => 'Parámetros de empresa: moneda, prefijos, mora por defecto, aprobaciones y reglas operativas.',
        'next_steps' => ['Datos de empresa', 'Reglas de préstamo', 'Prefijos', 'Permisos'],
    ],
];
