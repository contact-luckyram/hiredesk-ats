<?php

return [
    // Shown on the careers site, offer letters and the new-hire portal.
    'company' => env('HIREDESK_COMPANY', 'Acme Health Partners'),
    'company_tagline' => env('HIREDESK_TAGLINE', 'Care for people. Grow your career.'),
    'brand_color' => env('HIREDESK_BRAND', '#0f766e'),

    // Skills the resume parser looks for (case-insensitive).
    'skills' => [
        'ACLS', 'BLS', 'PALS', 'CPR', 'Epic', 'Cerner', 'EHR', 'EMR', 'ICU', 'CCU', 'Med/Surg', 'Telemetry',
        'Ventilator', 'Wound care', 'IV therapy', 'Phlebotomy', 'Hospice', 'Home health', 'Medicare', 'OASIS',
        'HIPAA', 'ICD-10', 'CPT', 'Medical billing', 'Coding', 'Scheduling', 'Customer service', 'Excel',
        'QuickBooks', 'Salesforce', 'Microsoft Office', 'Spanish', 'Bilingual', 'Leadership', 'Case management',
        'Physical therapy', 'Occupational therapy', 'Pediatrics', 'Geriatrics', 'Dementia care', 'CNA', 'HHA',
        'LPN', 'RN', 'BSN', 'Help desk', 'Active Directory', 'Windows', 'Networking', 'SQL', 'Python', 'Driver\'s license',
    ],
];
