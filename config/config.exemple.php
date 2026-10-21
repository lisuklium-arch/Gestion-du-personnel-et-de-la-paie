<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'projet8_rh_paie',
        'user' => 'root',
        'pass' => 'votre_mot_de_passe_mysql',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => 'Perpay',
        'debug' => true,
        'timezone' => 'Africa/Douala'
    ],
    'mail' => [
        'host' => 'smtp.gmail.com',   
        'port' => 587,                          
        'user' => 'votre_adresse@gmail.com',
        'pass' => 'mot_de_passe_application_gmail',
        'chiffrement' => 'tls',
        'expediteur_email' => 'votre_adresse@gmail.com',
        'expediteur_nom' => 'PerPay',
    ],
];
