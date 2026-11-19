<?php

declare(strict_types=1);

return [
    'driver'   => 'mysql',
    'db_host'     => 'localhost',
    'db_name' => 'septima20',
    'db_user' => 'septima',
    'db_pass' => '12mesiacikovia@@',
    'db_charset' => 'utf8mb4',
    'session_name' => 'rezervacie_session',
    // Microsoft OAuth settings
    'microsoft_client_id' => '4ea3068e-e1e8-4c20-9f2b-9cbe521225dc',
    'microsoft_client_secret' => 'tzh8Q~sdwzU.CsQGyg24f9AMQnTIwd5-dDTKcb2F',
    'microsoft_tenant_id' => 'd236f07f-482e-47b1-b634-f7c1736fbfc5', // 'common' for any Microsoft account, or specific tenant ID
    'microsoft_redirect_uri' => 'https://web.cenada.space/~rezervacie/microsoft_callback.php',
];
