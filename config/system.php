<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dueño / creador del sistema
|--------------------------------------------------------------------------
|
| El correo aquí definido identifica al dueño del sistema. Solo ese usuario
| puede cambiar el tipo de licencia (plan) de cualquier empresa, incluyendo
| la suya propia. Cualquier otro administrador ve la licencia en modo lectura.
|
| Puede sobrescribirse con la variable de entorno SYSTEM_OWNER_EMAIL.
|
*/

return [
    'owner_email' => env('SYSTEM_OWNER_EMAIL', 'wailandkey@gmail.com'),
];
