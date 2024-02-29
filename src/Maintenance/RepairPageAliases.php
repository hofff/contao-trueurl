<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\Maintenance;

use Contao\System;
use Hofff\Contao\TrueUrl\TrueURL;

final class RepairPageAliases
{
    public function __invoke(): void
    {
        $trueUrl = System::getContainer()->get(TrueURL::class);
        $trueUrl->repair();
    }
}
