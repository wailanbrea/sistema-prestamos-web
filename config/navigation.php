<?php

declare(strict_types=1);

return [
    'sections' => [
        [
            'label' => 'Principal',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'fa-chart-line',
                    'permission' => 'dashboard.view',
                    'match' => ['dashboard'],
                ],
                [
                    'label' => 'Clientes',
                    'route' => 'clients.index',
                    'icon' => 'fa-users',
                    'permission' => 'clients.view',
                    'match' => ['clients.'],
                ],
                [
                    'label' => 'Cotizaciones',
                    'route' => 'loan-quotes.index',
                    'icon' => 'fa-calculator',
                    'permission' => 'quotes.manage',
                    'match' => ['loan-quotes.'],
                ],
                [
                    'label' => 'Préstamos',
                    'route' => 'loans.index',
                    'icon' => 'fa-file-invoice-dollar',
                    'permission' => 'loans.view',
                    'match' => ['loans.'],
                ],
                [
                    'label' => 'Cobros',
                    'route' => 'payments.index',
                    'icon' => 'fa-cash-register',
                    'permission' => 'payments.create',
                    'match' => ['payments.'],
                ],
            ],
        ],
        [
            'label' => 'Operación',
            'items' => [
                [
                    'label' => 'Cobradores',
                    'route' => 'collectors.index',
                    'icon' => 'fa-motorcycle',
                    'permission' => 'collectors.manage',
                    'match' => ['collectors.'],
                ],
                [
                    'label' => 'Zonas y rutas',
                    'route' => 'routes.index',
                    'icon' => 'fa-route',
                    'permission' => 'routes.manage',
                    'match' => ['routes.', 'zones.'],
                ],
                [
                    'label' => 'Mapa de cobros',
                    'route' => 'routes.map',
                    'icon' => 'fa-map-location-dot',
                    'permission' => 'routes.manage',
                    'match' => ['routes.map'],
                ],
                [
                    'label' => 'Seguimiento',
                    'route' => 'routes.tracking',
                    'icon' => 'fa-location-crosshairs',
                    'permission' => 'routes.manage',
                    'match' => ['routes.tracking'],
                ],
                [
                    'label' => 'Historial rutas',
                    'route' => 'routes.tracking.history',
                    'icon' => 'fa-clock-rotate-left',
                    'permission' => 'routes.manage',
                    'match' => ['routes.tracking.history'],
                ],
                [
                    'label' => 'Gastos',
                    'route' => 'expenses.index',
                    'icon' => 'fa-receipt',
                    'permission' => 'expenses.manage',
                    'match' => ['expenses.', 'expense-categories.'],
                ],
                [
                    'label' => 'Cuentas por pagar',
                    'route' => 'accounts-payable.index',
                    'icon' => 'fa-file-invoice',
                    'permission' => 'accounts-payable.manage',
                    'match' => ['accounts-payable.'],
                ],
                [
                    'label' => 'Acreedores',
                    'route' => 'creditors.index',
                    'icon' => 'fa-hand-holding-dollar',
                    'permission' => 'accounts-payable.manage',
                    'match' => ['creditors.'],
                ],
                [
                    'label' => 'Caja',
                    'route' => 'cash-movements.index',
                    'icon' => 'fa-vault',
                    'permission' => 'cash.view',
                    'match' => ['cash-movements.'],
                ],
            ],
        ],
        [
            'label' => 'Análisis',
            'items' => [
                [
                    'label' => 'Documentos',
                    'route' => 'documents.index',
                    'icon' => 'fa-file-signature',
                    'permission' => 'documents.generate',
                    'match' => ['documents.'],
                ],
                [
                    'label' => 'Contratos',
                    'route' => 'contracts.index',
                    'icon' => 'fa-file-contract',
                    'permission' => 'legal.manage',
                    'match' => ['contracts.'],
                ],
                [
                    'label' => 'Reportes',
                    'route' => 'reports.index',
                    'icon' => 'fa-file-pdf',
                    'permission' => 'reports.view',
                    'match' => ['reports.'],
                ],
                [
                    'label' => 'Configuración',
                    'route' => 'settings.index',
                    'icon' => 'fa-gear',
                    'permission' => 'settings.manage',
                    'match' => ['settings.'],
                ],
                [
                    'label' => 'Roles',
                    'route' => 'roles.index',
                    'icon' => 'fa-user-shield',
                    'permission' => 'users.manage',
                    'match' => ['roles.', 'users.'],
                ],
            ],
        ],
    ],
];
