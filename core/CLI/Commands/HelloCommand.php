<?php

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class HelloCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'Hello';
    }

    public function getDescription(): string
    {
        return 'Affiche un message de bienvenue.';
    }

    public function execute(array $argv): int
    {
        $name = $argv[2] ?? 'World';
        echo "Hello, $name ! \n";
        return 0;
    }

}