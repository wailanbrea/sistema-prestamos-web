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
                ],
                [
                    'label' => 'Clientes',
                    'route' => 'clients.index',
                    'icon' => 'fa-users',
                    'permission' => 'clients.view',
                ],
                [
                    'label' => 'Cotizaciones',
                    'route' => 'loan-quotes.index',
                    'icon' => 'fa-calculator',
                    'permission' => 'quotes.manage',
                ],
                [
                    'label' => 'Préstamos',
                    'route' => 'loans.index',
                    'icon' => 'fa-file-invoice-dollar',
                    'permission' => 'loans.view',
                ],
                [
                    'label' => 'Cobros',
                    'route' => 'payments.index',
                    'icon' => 'fa-cash-register',
                    'permission' => 'payments.create',
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
                ],
                [
                    'label' => 'Zonas y rutas',
                    'route' => 'routes.index',
                    'icon' => 'fa-route',
                    'permission' => 'routes.manage',
                ],
                [
                    'label' => 'Mapa de cobros',
                    'route' => 'routes.map',
                    'icon' => 'fa-map-location-dot',
                    'permission' => 'routes.manage',
                ],
                [
                    'label' => 'Seguimiento',
                    'route' => 'routes.tracking',
                    'icon' => 'fa-location-crosshairs',
                    'permission' => 'routes.manage',
                ],
                [
                    'label' => 'Gastos',
                    'route' => 'expenses.index',
                    'icon' => 'fa-receipt',
                    'permission' => 'expenses.manage',
                ],
                [
                    'label' => 'Caja',
                    'route' => 'cash-movements.index',
                    'icon' => 'fa-vault',
                    'permission' => 'cash.view',
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
                ],
                [
                    'label' => 'Reportes',
                    'route' => 'reports.index',
                    'icon' => 'fa-file-pdf',
                    'permission' => 'reports.view',
                ],
                [
                    'label' => 'Configuración',
                    'route' => 'settings.index',
                    'icon' => 'fa-gear',
                    'permission' => 'settings.manage',
                ],
            ],
        ],
    ],
];
