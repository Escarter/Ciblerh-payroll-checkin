<?php

return [
    /**
     * Control if the seeder should create a user per role while seeding the data.
     */
    'create_users' => true,

    /**
     * Control if all the tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_structure' => [
        'admin' => [
            'employee' => 'c,r,u,d',
            'absence' => 'r,u,d',
            'advance_salary' => 'r,u,d',
            'company' => 'c,r,u,d',
            'department' => 'c,r,u,d',
            'company' => 'c,r,u,d',
            'overtime' => 'r,u,d',
            'service' => 'c,r,u,d',
            'ticking' => 'r,u,d',
            'profile' => 'r,u',
            'payslip' => 'c,r,u,d',
            'export'=>'r',
            'import'=>'r',
        ],
        'manager' => [
            'employee' => 'c,r,u,d',
            'absence' => 'r,u,d',
            'advance_salary' => 'r,u,d',
            'company' => 'c,r,u,d',
            'department' => 'c,r,u,d',
            'service' => 'c,r,u,d',
            'overtime' => 'r,u,d',
            'ticking' => 'r,u,d',
            'profile' => 'r,u',
            'payslip' => 'c,r',
            'export'=>'r',
            'import'=>'r',
        ],
        'supervisor' => [
            'employee' => 'r,u',
            'absence' =>'r,u,d',
            'overtime' => 'r,u',
            'ticking' => 'r,u',
            'profile' => 'r,u',
            'payslip' => 'c,r,u',
            'export'=>'r',
        ],
        'employee' => [
            'absence' => 'c,r,u',
            'advance_salary' => 'c,r,u',
            'overtime' => 'c,r,u,d',
            'ticking' => 'c,r,u,d',
            'profile' => 'r,u',
        ],
    ],

    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete'
    ]
];
