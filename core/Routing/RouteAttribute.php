<?php

/* ===== /Core/Routing/RouteAttribute.php ===== */

namespace Corelia\Routing;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RouteAttribute
{
    public string $path;
    public array $methods;

    public function __construct(string $path, array $methods = ['GET'])
    {
        $this->path = $path;
        $this->methods = $methods;
    }
}
