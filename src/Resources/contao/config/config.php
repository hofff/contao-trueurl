<?php

declare(strict_types=1);

use Hofff\Contao\TrueUrl\Maintenance\RepairPageAliases;

$GLOBALS['TL_PURGE']['custom']['hofff_trueurl']['callback'] = [RepairPageAliases::class, '__invoke'];
