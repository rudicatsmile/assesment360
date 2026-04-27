<?php

return [
    'admin_slugs' => ['super_admin', 'admin'],
    'evaluator_slugs' => ['guru', 'tata_usaha', 'orang_tua', 'user'],
    'questionnaire_target_slugs' => ['guru', 'tata_usaha', 'orang_tua'],
    'questionnaire_target_aliases' => [
        'guru' => 'guru_staf',
        'tata_usaha' => 'guru_staf',
        'orang_tua' => 'komite',
    ],
    'dashboard_role_slugs' => [
        'teacher' => 'guru',
        'staff' => 'tata_usaha',
        'parent' => 'orang_tua',
    ],
    'role_labels' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'guru' => 'Guru',
        'tata_usaha' => 'Tata Usaha',
        'orang_tua' => 'Orang Tua',
        'user' => 'User',
    ],
    'role_aliases' => [
        'super_admin' => 'admin',
    ],
    'legacy_allowed_slugs' => ['admin', 'guru', 'tata_usaha', 'orang_tua', 'user'],
    'default_legacy_role_slug' => 'user',
    'ci_guard_excluded_slugs' => ['user'],
    'middleware_aliases' => [
        'admin_gate' => 'access.admin',
        'evaluator_gate' => 'access.evaluator',
        'role_gate' => 'access.role',
        'role_redirect' => 'access.role.redirect',
    ],
    'admin_route' => [
        'prefix' => 'admin',
        'name' => 'admin.',
    ],
    'role_definitions' => [
        ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Akses penuh seluruh modul', 'prosentase' => 100, 'is_active' => true],
        ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Akses manajemen aplikasi', 'prosentase' => 90, 'is_active' => true],
        ['name' => 'Guru', 'slug' => 'guru', 'description' => 'Evaluator guru', 'prosentase' => 70, 'is_active' => true],
        ['name' => 'Tata Usaha', 'slug' => 'tata_usaha', 'description' => 'Evaluator staf TU', 'prosentase' => 60, 'is_active' => true],
        ['name' => 'Orang Tua', 'slug' => 'orang_tua', 'description' => 'Evaluator orang tua', 'prosentase' => 50, 'is_active' => true],
        ['name' => 'User', 'slug' => 'user', 'description' => 'Pengguna umum', 'prosentase' => 40, 'is_active' => true],
    ],
    'dashboard_paths' => [
        'super_admin' => '/admin/dashboard',
        'admin' => '/admin/dashboard',
        'guru' => '/fill/dashboard/guru',
        'tata_usaha' => '/fill/dashboard/staff',
        'orang_tua' => '/fill/dashboard/parent',
        'user' => '/fill/dashboard/guru',
        // 'user_old' => '/fill/questionnaires',
        'pengurus_yayasan' => '/fill/dashboard/staff',
        'guru_staf' => '/fill/dashboard/guru',
        'rekan_kerja' => '/fill/dashboard/guru',
        'komite' => '/fill/dashboard/guru',
        'siswa' => '/fill/dashboard/guru',
        'diri_sendiri(_kepala_sekolah)' => '/fill/dashboard/guru',
    ],
];
