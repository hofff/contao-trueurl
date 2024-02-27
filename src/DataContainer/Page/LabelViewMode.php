<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\DataContainer\Page;

enum LabelViewMode: string
{
    case TITLE_AND_ALIAS = 'title_and_alias';

    case TITLE_ONLY = 'title_only';

    case ALIAS_ONLY = 'alias_only';

    public function isCombined(): bool
    {
        return $this === self::TITLE_AND_ALIAS;
    }
}
