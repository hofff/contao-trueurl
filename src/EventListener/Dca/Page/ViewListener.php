<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Hofff\Contao\TrueUrl\TrueURL;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class ViewListener
{
    private static bool $blnRecurse = false;

    private ContaoFramework $framework;

    private Packages $packages;

    private TranslatorInterface $translator;

    private SessionInterface $session;

    private Security $security;

    private TrueURL $trueUrl;

    private RouterInterface $router;

    /** @var list<string> */
    private array $unrouteablePageTypes;

    /**
     * @param list<string> $unrouteablePageTypes
     */
    public function __construct(
        ContaoFramework $framework,
        Packages $packages,
        TranslatorInterface $translator,
        SessionInterface $session,
        Security $security,
        RouterInterface $router,
        TrueURL $trueUrl,
        array $unrouteablePageTypes
    ) {
        $this->framework            = $framework;
        $this->packages             = $packages;
        $this->translator           = $translator;
        $this->session              = $session;
        $this->security             = $security;
        $this->unrouteablePageTypes = $unrouteablePageTypes;
        $this->trueUrl              = $trueUrl;
        $this->router               = $router;
    }

    /**
     * @Callback(table="tl_page", target="config.onload")
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function onLoad(): void
    {
        $rootManipulator = PaletteManipulator::create()->addField(
            ['bbit_turl_rootInheritProxy', 'bbit_turl_defaultInherit'],
            'type'
        );

        $pageManipulator = PaletteManipulator::create()->addField(
            ['bbit_turl_inherit', 'bbit_turl_transparent', 'bbit_turl_ignoreRoot'],
            'type'
        );

        foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $selector => $palette) {
            if ($selector === '__selector__' || ! is_string($palette)) {
                continue;
            }

            if ($selector === 'root' || $selector === 'rootfallback') {
                $rootManipulator->applyToPalette($selector, 'tl_page');
                continue;
            }

            $pageManipulator->applyToPalette($selector, 'tl_page');
        }
    }

    /**
     * @param array<string,mixed> $row
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function labelPage(
        array $row,
        string $label,
        ?DataContainer $dataContainer = null,
        string $imageAttribute = '',
        bool $returnImage = false,
        bool $protect = false
    ): string {
        $wasRecurse = self::$blnRecurse;
        $callback   = $wasRecurse
            ? ['tl_page', 'addIcon']
            : $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];

        self::$blnRecurse = true;
        if (is_array($callback)) {
            $callback[0] = $this->framework->getAdapter(System::class)->importStatic($callback[0]);
        }

        $label            = $callback($row, $label, $dataContainer, $imageAttribute, $returnImage, $protect);
        self::$blnRecurse = false;

        if ($wasRecurse) {
            return $label;
        }

        if (in_array($row['type'], $this->unrouteablePageTypes, true)) {
            return $label;
        }

        $intMode = $this->getViewMode();
        if (! $intMode) {
            return $label;
        }

        $arrAlias = $this->trueUrl->splitAlias($row);

        if (! $arrAlias) {
            $label .= ' <span style="color:#CC5555;">[';
            $label .= $this->translate('errNoAlias');
            $label .= ']</span>';

            return $label;
        }

        if ($intMode === 1) {
            $label .= '<br />';
        } elseif (preg_match('@<a[^>]*><img[^>]*></a>@', $label, $arrMatch)) {
            $label = $arrMatch[0] . ' ';
        } else {
            $label = '';
        }

        if ($intMode === 1) {
            $label .= '<span style="color:#b3b3b3;display: inline-block; margin-left: 22px;">[';
        } else {
            $label .= '<span style="color:#b3b3b3;">[';
        }

        $strConnector = '';
        if ($arrAlias['root']) {
            $label       .= '<span style="color:#0C0;">' . $arrAlias['root'] . '</span>';
            $strConnector = '/';
        }

        if ($arrAlias['parent']) {
            $label       .= $strConnector . $arrAlias['parent'];
            $strConnector = '/';
        }

        if ($arrAlias['fragment']) {
            $label .= $strConnector . '<span style="color:#5C9AC9;">' . $arrAlias['fragment'] . '</span>';
        }

        $label .= ']</span>';
        $image  = '';
        $title  = '';

        if ($row['type'] === 'root') {
            $strTitle  = $this->translate('bbit_turl_rootInherit.0') . ': ';
            $strTitle .= $this->translate('bbit_turl_rootInheritOptions.' . $row['bbit_turl_rootInherit']);

            switch ($row['bbit_turl_rootInherit']) {
                default:
                case 'normal':
                    $label .= $this->makeImage('link.png', $strTitle);
                    break;
                case 'always':
                    $label .= $this->makeImage('link_add.png', $strTitle);
                    break;
                case 'never':
                    $label .= $this->makeImage('link_delete.png', $strTitle);
                    break;
            }
        } else {
            $row['bbit_turl_ignoreRoot'] && $label .= $this->makeImage(
                'link_delete.png',
                $this->translate('bbit_turl_ignoreRoot.0')
            );

            if ($row['bbit_turl_inherit']) {
                $image = 'link';
                $title = $this->translate('bbit_turl_inherit.0');
            } else {
                $image = 'link_break';
                $title = $this->translate('bbit_turl_break');
            }

            if ($row['bbit_turl_transparent']) {
                $arrAlias['err'] || $image .= '_go';
                $title                     .= "\n" . $this->translate('bbit_turl_transparent.0');
            }
        }

        if ($arrAlias['err']) {
            $image .= '_error';
            foreach ($arrAlias['err'] as $strError => &$strLabel) {
                $strLabel = $this->translate($strError);
            }

            unset($strLabel);
            $title .= "\n" . implode("\n", $arrAlias['err']);
        }

        if ($image) {
            $label .= $this->makeImage($image . '.png', $title);
        }

        if ($intMode === 1) {
            $label = '<div style="display:inline-block;vertical-align: top;">' . $label . '</div>';
        }

        return $label;
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_alias.button")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buttonAlias(
        string $href,
        string $label,
        string $title,
        string $class,
        string $attributes
    ): string {
        switch ($this->getViewMode()) {
            case 1:
                $translationKey = 'bbit_turl_aliasOnly';
                $intMode        = 2;
                break;

            case 2:
                $translationKey = 'bbit_turl_aliasHide';
                $intMode        = 0;
                break;

            default:
                $translationKey = 'bbit_turl_aliasShow';
                $intMode        = 1;
                break;
        }

        $label = $this->translate($translationKey . '.0');
        $title = $this->translate($translationKey . '.1');

        return sprintf(
            '%s<a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->isAdmin() ? '<br/><br/>' : ' &#160; :: &#160; ',
            $this->router->generate('hofff_contao_true_url_alias', ['bbit_turl_alias' => $intMode]),
            $class,
            StringUtil::specialchars($title),
            $attributes,
            $label
        );
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_regenerate.button")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buttonRegenerate(
        string $href,
        string $label,
        string $title,
        string $class,
        string $attributes
    ): string {
        return $this->isAdmin() ? sprintf(
            ' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_regenerate'),
            $class,
            StringUtil::specialchars($title),
            $attributes,
            $label
        ) : '';
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_repair.button")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buttonRepair(string $href, string $label, string $title, string $class, string $attributes): string
    {
        return $this->isAdmin() ? sprintf(
            ' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_repair'),
            $class,
            StringUtil::specialchars($title),
            $attributes,
            $label
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
        string $href,
        string $label,
        string $title,
        string $icon,
        string $attributes
    ): string {
        return $this->isAdmin() && Input::get('act') !== 'paste' ? sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_auto_inherit', ['id' => $row['id']]),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        ) : '';
    }

    private function makeImage(string $image, string $title): string
    {
        return ' ' . Image::getHtml(
            $this->packages->getUrl('images/' . $image, 'hofff_contao_true_url'),
            $title,
            ' title="' . StringUtil::specialchars($title) . '"'
        );
    }

    private function translate(string $key): string
    {
        return $this->translator->trans('tl_page.' . $key, [], 'contao_tl_page');
    }

    private function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function getViewMode(): int
    {
        $bag = $this->session->getBag('contao_backend');
        assert($bag instanceof AttributeBag);

        return (int) $bag->get('bbit_turl_alias');
    }
}
