<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

use function sprintf;

final class ViewListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_regenerate.button")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buttonRegenerate(
        string|null $href,
        string $label,
        string $title,
        string|null $class,
        string $attributes,
    ): string {
        return $this->isAdmin() ? sprintf(
            ' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_regenerate'),
            (string) $class,
            StringUtil::specialchars($title),
            $attributes,
            $label,
        ) : '';
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_repair.button")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buttonRepair(
        string|null $href,
        string $label,
        string $title,
        string|null $class,
        string $attributes,
    ): string {
        return $this->isAdmin() ? sprintf(
            ' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_repair'),
            (string) $class,
            StringUtil::specialchars($title),
            $attributes,
            $label,
        ) : '';
    }

    /**
     * @param array<string,mixed> $row
     *
     * @Callback(table="tl_page", target="list.operations.bbit_turl_autoInherit.button")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buttonAutoInherit(
        array $row,
        string|null $href,
        string $label,
        string $title,
        string $icon,
        string $attributes,
    ): string {
        return $this->isAdmin() && Input::get('act') !== 'paste' ? sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_auto_inherit', ['id' => $row['id']]),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label),
        ) : '';
    }

    private function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
