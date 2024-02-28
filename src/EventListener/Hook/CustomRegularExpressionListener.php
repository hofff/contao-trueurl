<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Widget;
use Symfony\Contracts\Translation\TranslatorInterface;

use function preg_match;

#[AsHook('addCustomRegexp')]
final class CustomRegularExpressionListener
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(string $regexp, mixed $value, Widget $widget): bool
    {
        if ($regexp !== 'trueurl') {
            return false;
        }

        if (! preg_match('/^[\pN\pL \.\/_-]*$/u', $value)) {
            $widget->addError($this->translator->trans('ERR.alnum', [$widget->label], 'contao_default'));
        }

        return true;
    }
}
