<?php

/**
 * Role-Based Access Configuration
 * ─────────────────────────────────────────────────────────────────────────────
 * Central definition of every role in the system and its capabilities.
 * The RoleMiddleware reads from this file — adding a new role or changing
 * hierarchy requires only a single edit here, not changes across route files.
 *
 * AC-16: Centralised role-to-permission mapping
 * AC-10: super-admin hierarchy is defined here and enforced by middleware
 * AC-18: Multi-role support — a user may hold comma-separated roles in future
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Defined Roles (in ascending privilege order)
    |--------------------------------------------------------------------------
    | 'sales'       — Sales Representatives: own proposals only
    | 'admin'       — Platform Administrators: full user & proposal management
    | 'super-admin' — Super Administrators: all admin routes + system config
    */

    'roles' => ['sales', 'admin', 'super-admin'],

    /*
    |--------------------------------------------------------------------------
    | Role Hierarchy
    |--------------------------------------------------------------------------
    | A role listed here implicitly satisfies every role beneath it.
    | Example: 'super-admin' satisfies 'admin', which satisfies 'sales'.
    |
    | This means middleware('role:admin') allows both 'admin' AND 'super-admin'
    | without requiring separate route definitions.
    */

    'hierarchy' => [
        'super-admin' => ['admin', 'sales', 'super-admin'],
        'admin'       => ['admin'],
        'sales'       => ['sales'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Home Routes (post-login redirect targets)
    |--------------------------------------------------------------------------
    | When a user is redirected due to insufficient permissions the system
    | uses these routes as their "permitted home page" link (AC-13, AC-15).
    */

    'home_routes' => [
        'super-admin' => 'admin.users.index',
        'admin'       => 'admin.users.index',
        'sales'       => 'dashboard',
        'default'     => 'dashboard',
    ],

];
