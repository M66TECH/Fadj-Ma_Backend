<?php

return [

    // Appliquer CORS aux routes API et au cookie Sanctum si utilisé
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Autoriser toutes les méthodes HTTP
    'allowed_methods' => ['*'],

    // Origines explicitement autorisées
    'allowed_origins' => [
        'https://fadj-ma-frontend-rho.vercel.app',
    ],

    // Autoriser tous les sous-domaines Vercel commençant par "fadj-ma-frontend-"
    // Exemple: https://fadj-ma-frontend-xyz.vercel.app
    'allowed_origins_patterns' => [
        '#^https://fadj-ma-frontend-.*\\.vercel\\.app$#',
    ],

    // Autoriser tous les headers courants (inclut Authorization)
    'allowed_headers' => ['*'],

    // Headers exposés au client (laisser vide si non nécessaire)
    'exposed_headers' => [],

    // Durée de cache du préflight (en secondes). 0 = pas de cache
    'max_age' => 0,

    // Utiliser les cookies d'authentification cross-site
    // Laisser à false si vous utilisez des tokens Bearer
    'supports_credentials' => false,

];