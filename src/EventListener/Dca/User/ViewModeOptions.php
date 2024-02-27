<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\User;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Hofff\Contao\TrueUrl\DataContainer\Page\LabelViewMode;

use function array_map;

#[AsCallback('tl_user', 'fields.hofff_trueurl_view_mode.options')]
final class ViewModeOptions
{
    /** @return list<string> */
    public function __invoke(): array
    {
        return array_map(static fn (LabelViewMode $mode) => $mode->value, LabelViewMode::cases());
    }
}
