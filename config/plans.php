<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Planes / Licencias del sistema
|--------------------------------------------------------------------------
|
| Cada plan define qué ítems de menú (por su 'route' en config/navigation.php)
| están disponibles para la empresa. 'menus' => '*' significa todos.
| Las pantallas de gestión (Configuración y Roles) se incluyen siempre para
| que el administrador pueda ver su plan y gestionar accesos.
|
*/

return [
    'prestamista' => [
        'label' => 'Plan Prestamista',
        'description' => 'Solo el flujo de préstamos: clientes, cotizaciones, préstamos y cobros.',
        'menus' => [
            'dashboard',
            'clients.index',
            'loan-quotes.index',
            'loans.index',
            'payments.index',
            'accounts-payable.index',
            'contracts.index',
            'reports.index',
            'settings.index',
            'roles.index',
        ],
    ],

    'full' => [
        'label' => 'Plan Full Prestamista',
        'description' => 'Acceso completo: préstamos, operación, rutas, cobradores, caja, reportes y más.',
        'menus' => '*',
    ],
];
