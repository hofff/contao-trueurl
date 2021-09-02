<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\Session;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Hofff\Contao\TrueUrl\TrueURL;

use function array_unshift;
use function is_array;
use function str_replace;

final class PageDcaListener
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

    /** @param list<string> $unrouteablePageTypes */
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

    /** @Callback(table="tl_page", target="config.onload") */
    public function onLoad()
    {
        foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) {
            if ($strSelector === '__selector__') {
                continue;
            }

            if ($strSelector === 'root' || $strSelector === 'rootfallback') {
                $strPalette = str_replace(
                    ',type',
                    ',type,bbit_turl_rootInheritProxy,bbit_turl_defaultInherit',
                    $strPalette
                );
            } else {
                $strPalette = str_replace(
                    ',type',
                    ',type,bbit_turl_inherit,bbit_turl_transparent,bbit_turl_ignoreRoot',
                    $strPalette
                );
            }
        }
        unset($strPalette);

        $arrConfig = &$GLOBALS['TL_DCA']['tl_page']['config'];
        foreach (['oncreate'] as $strCallback) {
            $strKey             = $strCallback . '_callback';
            $arrConfig[$strKey] = (array) $arrConfig[$strKey];
            array_unshift($arrConfig[$strKey], [self::class, $strCallback . 'Page']);
        }
    }

    public function oncreatePage($strTable, $intID, $arrSet, $objDC)
    {
        if (!$arrSet['pid']) {
            return;
        }

        $objParent = PageModel::findWithDetails($arrSet['pid']);
        $intRootID = $objParent->type == 'root' ? $objParent->id : $objParent->rootId;

        if ($intRootID) {
            $strQuery          = <<<EOT
SELECT	bbit_turl_defaultInherit
FROM	tl_page
WHERE	id = ?
EOT;
            $blnDefaultInherit = Database::getInstance()->prepare($strQuery)->execute($intRootID)->bbit_turl_defaultInherit;
            $strQuery          = <<<EOT
UPDATE	tl_page
SET		bbit_turl_root = ?,
		bbit_turl_inherit = ?
WHERE	id = ?
EOT;
            Database::getInstance()->prepare($strQuery)->execute($intRootID, $blnDefaultInherit, $intID);
        } else {
            $strQuery = <<<EOT
UPDATE	tl_page
SET		bbit_turl_root = 0
WHERE	id = ?
EOT;
            Database::getInstance()->prepare($strQuery)->execute($intID);
        }
    }

    /**
     * @Callback(table="tl_page", target="list.label.label")
     */
    public function labelPage(
        $row,
        $label,
        DataContainer $dc = null,
        $imageAttribute = '',
        $blnReturnImage = false,
        $blnProtected = false
    ) {
        $blnWasRecurse = self::$blnRecurse;
        $arrCallback   = $blnWasRecurse
            ? ['tl_page', 'addIcon']
            : $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];

        self::$blnRecurse = true;
        if (is_array($arrCallback)) {
            $arrCallback[0] = System::importStatic($arrCallback[0]);
        }
        $label = call_user_func(
            $arrCallback,
            $row,
            $label,
            $dc,
            $imageAttribute,
            $blnReturnImage,
            $blnProtected
        );
        self::$blnRecurse = false;

        if ($blnWasRecurse) {
            return $label;
        }

        if (in_array($row['type'], $this->unrouteablePageTypes, true)) {
            return $label;
        }

        $intMode = Session::getInstance()->get('bbit_turl_alias');
        if (!$intMode) {
            return $label;
        }

        $arrAlias = $this->trueUrl->splitAlias($row);

        if (!$arrAlias) {
            $label .= ' <span style="color:#CC5555;">[';
            $label .= $this->translate('errNoAlias');
            $label .= ']</span>';

            return $label;
        }

        if ($intMode == 1) {
            $label .= '<br />';
        } elseif (preg_match('@<a[^>]*><img[^>]*></a>@', $label, $arrMatch)) {
            $label = $arrMatch[0] . ' ';
        } else {
            $label = '';
        }

        if ($intMode == 1) {
            $label .= '<span style="color:#b3b3b3;display: inline-block; margin-left: 22px;">[';
        } else {
            $label .= '<span style="color:#b3b3b3;">[';
        }

        if ($arrAlias['root']) {
            $label        .= '<span style="color:#0C0;">' . $arrAlias['root'] . '</span>';
            $strConnector = '/';
        }
        if ($arrAlias['parent']) {
            $label        .= $strConnector . $arrAlias['parent'];
            $strConnector = '/';
        }
        if ($arrAlias['fragment']) {
            $label .= $strConnector . '<span style="color:#5C9AC9;">' . $arrAlias['fragment'] . '</span>';
        }
        $label .= ']</span>';

        if ($row['type'] === 'root') {
            $strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit'][0] . ': ';
            switch ($row['bbit_turl_rootInherit']) {
                default:
                case 'normal':
                    $label .= $this->makeImage(
                        'link.png',
                        $strTitle . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions']['normal']
                    );
                    break;
                case 'always':
                    $label .= $this->makeImage(
                        'link_add.png',
                        $strTitle . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions']['always']
                    );
                    break;
                case 'never':
                    $label .= $this->makeImage(
                        'link_delete.png',
                        $strTitle . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions']['never']
                    );
                    break;
            }
        } else {
            $row['bbit_turl_ignoreRoot'] && $label .= $this->makeImage(
                'link_delete.png',
                $GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot'][0]
            );

            if ($row['bbit_turl_inherit']) {
                $image = 'link';
                $title = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit'][0];
            } else {
                $image = 'link_break';
                $title = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_break'];
            }
            if ($row['bbit_turl_transparent']) {
                $arrAlias['err'] || $image .= '_go';
                $title .= "\n" . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent'][0];
            }
        }

        if ($arrAlias['err']) {
            $image .= '_error';
            foreach ($arrAlias['err'] as $strError => &$strLabel) {
                $strLabel = $GLOBALS['TL_LANG']['tl_page'][$strError];
            }
            $title .= "\n" . implode("\n", $arrAlias['err']);
        }

        if ($image) {
            $label .= $this->makeImage($image . '.png', $title);
        }

        if ($intMode == 1) {
            $label = '<div style="display:inline-block;vertical-align: top;">' . $label . '</div>';
        }

        return $label;
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_alias.button")
     */
    public function buttonAlias($strHREF, $label, $title, $strClass, $strAttributes, $strTable, $intRoot): string
    {
        switch (Session::getInstance()->get('bbit_turl_alias')) {
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
            $strClass,
            StringUtil::specialchars($title),
            $strAttributes,
            $label
        );
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_regenerate.button")
     */
    public function buttonRegenerate($strHREF, $strLabel, $strTitle, $strClass, $strAttributes): string
    {
        return $this->isAdmin() ? sprintf(
            ' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_regenerate'),
            $strClass,
            StringUtil::specialchars($strTitle),
            $strAttributes,
            $strLabel
        ) : '';
    }

    /**
     * @Callback(table="tl_page", target="list.global_operations.bbit_turl_repair.button")
     */
    public function buttonRepair($strHREF, $strLabel, $strTitle, $strClass, $strAttributes): string
    {
        return $this->isAdmin() ? sprintf(
            ' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_repair'),
            $strClass,
            StringUtil::specialchars($strTitle),
            $strAttributes,
            $strLabel
        ) : '';
    }

    /**
     * @Callback(table="tl_page", target="list.operations.bbit_turl_autoInherit.button")
     */
    public function buttonAutoInherit($arrRow, $strHREF, $strLabel, $strTitle, $strIcon, $strAttributes): string
    {
        return ($this->isAdmin() && Input::get('act') !== 'paste') ? sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $this->router->generate('hofff_contao_true_url_auto_inherit', ['id' => $arrRow['id']]),
            StringUtil::specialchars($strTitle),
            $strAttributes,
            Image::getHtml($strIcon, $strLabel)
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

    private function translate(string $key, array $params = []): string
    {
        return $this->translator->trans('tl_page.' . $key, $params, 'contao_tl_page');
    }

    private function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
