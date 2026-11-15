<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Separate databases per tenant
    |--------------------------------------------------------------------------
    |
    | false (default for now): every tenant uses the central DB (bidadmin).
    | Super admin and all app data live together so the platform owner can
    | see everything. Keep TenantManager / {tenant} routes for later.
    |
    | true: CREATE DATABASE bidadmin_{slug} per tenant (full SaaS isolation).
    |
    */
    'separate_databases' => env('TENANCY_SEPARATE_DATABASES', false),

];
