<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Image;
use Contao\Session;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Asset\Packages;

final class PageDcaListener
{
    private static bool $blnRecurse = false;

    private ContaoFramework $framework;

    private Packages $packages;

    /** @var list<string> */
    private array $unrouteablePageTypes;

    /** @param list<string> $unrouteablePageTypes */
    public function __construct(ContaoFramework $framework, Packages $packages, array $unrouteablePageTypes)
    {
        $this->framework            = $framework;
        $this->packages             = $packages;
        $this->unrouteablePageTypes = $unrouteablePageTypes;
    }

    public function labelPage(
        $row,
        $label,
        DataContainer $dc = null,
        $imageAttribute = '',
        $blnReturnImage = false,
        $blnProtected = false
    ) {
        $blnWasRecurse = self::$blnRecurse;
        $arrCallback   = $blnWasRecurse ? [
            'tl_page',
            'addIcon',
        ] : $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];

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

        $arrAlias = $this->objTrueURL->splitAlias($row);

        if (!$arrAlias) {
            $label .= ' <span style="color:#CC5555;">[';
            $label .= $GLOBALS['TL_LANG']['tl_page']['errNoAlias'];
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

        $label .= '<span style="color:#b3b3b3;">[';
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

        if ($row['type'] == 'root') {
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

        $label .= $this->makeImage($image . '.png', $title);

        return $label;
    }


    protected function makeImage(string $image, string $title): string
    {
        return ' ' . Image::getHtml(
            $this->packages->getUrl('HofffContaoTrueUrlBundle/images/' . $image),
            $title, ' title="' . StringUtil::specialchars($title) . '"'
        );
    }
}
