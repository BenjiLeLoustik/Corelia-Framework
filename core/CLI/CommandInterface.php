<?php

/* ===== /Core/CLI/CommandInterface.php ===== */

namespace Corelia\CLI;

interface CommandInterface
{

    public function getName(): string;
    public function getDescription(): string;
    public function execute( array $argv ): int;

}
