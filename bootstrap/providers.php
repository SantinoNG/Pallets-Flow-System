<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class, // Agregar esta línea
];
