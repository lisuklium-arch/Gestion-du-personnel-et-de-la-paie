<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'projet8_rh_paie',
        'user' => 'root',
        'pass' => 'votre_mot_de_passe',
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
        'user' => 'doudyx1@gmail.com',
        'pass' => 'arhh lhpb jouj gvjp',
        'chiffrement' => 'tls',
        'expediteur_email' => 'doudyx1@gmail.com',
        'expediteur_nom' => 'PerPay',
    ],
];
