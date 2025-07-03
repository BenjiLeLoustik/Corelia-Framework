<?php

/* ===== /Core/CLI/Commands/ServeCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class ServeCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Lance un serveur PHP de développement.';
    }

    public function execute(array $argv): int
    {
        $base = __DIR__ . '/../../../';

        $host = $argv[2] ?? 'localhost';
        $port = $argv[3] ?? '8000';

        $public = is_dir($base . 'public') ? $base . 'public' : $base . 'src';

        echo "\033[32m Serveur de développement : http://$host:$port \033[0m \n";

        passthru("php -S $host:$port -t $public");
        return 0;
    }

}