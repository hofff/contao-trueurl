<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Hofff\Contao\TrueUrl\DataContainer\Page\LabelViewMode;
use Hofff\Contao\TrueUrl\TrueURL;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function str_starts_with;
use function substr;

#[AsCallback('tl_page', 'list.label.label')]
final class GenerateLabelListener
{
    private static bool $blnRecurse = false;

    /** @param list<string> $unrouteablePageTypes */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly Packages $packages,
        private readonly Security $security,
        private readonly TrueURL $trueUrl,
        private readonly array $unrouteablePageTypes,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __invoke(
        array $row,
        string $label,
        DataContainer|null $dataContainer = null,
        mixed $imageAttribute = '',
        bool $returnImage = false,
        bool $protect = false,
    ): string {
        $wasRecurse = self::$blnRecurse;
        $callback   = $wasRecurse
            ? ['tl_page', 'addIcon']
            : $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];

        $label = $this->generateLabelUsingCallback(
            $callback,
            $imageAttribute,
            $row,
            $label,
            $dataContainer,
            $returnImage,
            $protect,
        );

        if ($wasRecurse || in_array($row['type'], $this->unrouteablePageTypes, true)) {
            return $label;
        }

        $viewMode = $this->getViewMode();
        if ($viewMode === LabelViewMode::TITLE_ONLY) {
            return $label;
        }

        $splitAlias = $this->trueUrl->splitAlias($row);

        if ($splitAlias === null) {
            return $label . $this->generateNoAliasError();
        }

        $label  = $this->generateAliasPath($viewMode, $label, $splitAlias);
        $image  = '';
        $title  = '';
        $label .= $this->addAliasHintIcon($row, $splitAlias, $image, $title);

        $this->addErrorHint($splitAlias, $image, $title);

        if ($image) {
            $label .= $this->makeImage($image . '.png', $title);
        }

        if ($viewMode->isCombined()) {
            $label = '<div style="display:inline-block;vertical-align: top;">' . $label . '</div>';
        }

        return $label;
    }

    private function getViewMode(): LabelViewMode
    {
        $user = $this->security->getUser();

        if (! $user instanceof BackendUser) {
            return LabelViewMode::TITLE_AND_ALIAS;
        }

        return LabelViewMode::from($user->hofff_trueurl_view_mode);
    }

    private function translate(string $key): string
    {
        return $this->translator->trans('tl_page.' . $key, [], 'contao_tl_page');
    }

    private function makeImage(string $image, string $title): string
    {
        $path = $this->packages->getUrl('images/' . $image, 'hofff_contao_true_url');
        if (str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }

        return ' ' . Image::getHtml($path, $title, ' title="' . StringUtil::specialchars($title) . '"');
    }

    /**
     * @param array{0: string|object, 1: string}|callable $callback
     * @param array<string,mixed>                         $row
     */
    private function generateLabelUsingCallback(
        array|callable $callback,
        mixed $imageAttribute,
        array $row,
        string $label,
        DataContainer|null $dataContainer,
        bool $returnImage,
        bool $protect,
    ): mixed {
        if (is_array($callback)) {
            $callback[0] = $this->framework->getAdapter(System::class)->importStatic($callback[0]);
        }

        $imageAttribute = is_string($imageAttribute) ? $imageAttribute : '';

        self::$blnRecurse = true;

        /** @psalm-suppress PossiblyInvalidFunctionCall */
        $label =  $callback($row, $label, $dataContainer, $imageAttribute, $returnImage, $protect);

        self::$blnRecurse = false;

        return $label;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<string,mixed> $splitAlias
     */
    private function addAliasHintIcon(array $row, array $splitAlias, string &$image, string &$title): string
    {
        if ($row['type'] !== 'root') {
            $label = '';

            if ($row['bbit_turl_ignoreRoot']) {
                $label .= $this->makeImage('link_delete.png', $this->translate('bbit_turl_ignoreRoot.0'));
            }

            if ($row['bbit_turl_inherit']) {
                $image = 'link';
                $title = $this->translate('bbit_turl_inherit.0');
            } else {
                $image = 'link_break';
                $title = $this->translate('bbit_turl_break');
            }

            if ($row['bbit_turl_transparent']) {
                if (! $splitAlias['err']) {
                    $image .= '_go';
                }

                $title .= "\n" . $this->translate('bbit_turl_transparent.0');
            }

            return $label;
        }

        $rootTitle = $this->translate('bbit_turl_rootInherit.0') . ': ' . $this->translate(
            'bbit_turl_rootInheritOptions.' . ($row['bbit_turl_rootInherit'] ?: 'normal'),
        );

        $icon = match ($row['bbit_turl_rootInherit']) {
            'always' => 'link_add.png',
            'never' => 'link_delete',
            default => 'link.png',
        };

        return $this->makeImage($icon, $rootTitle);
    }

    /** @param array<string,mixed> $splitAlias */
    private function generateAliasPath(LabelViewMode $viewMode, string $label, array $splitAlias): string
    {
        if ($viewMode->isCombined()) {
            $label .= '<br />';
        } elseif (preg_match('@<a[^>]*>(<img[^>]*>\s*)+</a>@', $label, $match)) {
            $label = $match[0] . ' ';
        } else {
            $label = '';
        }

        if ($viewMode->isCombined()) {
            $label .= '<span style="color:#b3b3b3;display: inline-block; margin-left: 22px;">[';
        } else {
            $label .= '<span style="color:#b3b3b3;">[';
        }

        $connector = '';
        if ($splitAlias['root']) {
            $label    .= '<span style="color:#0C0;">' . $splitAlias['root'] . '</span>';
            $connector = '/';
        }

        if ($splitAlias['parent']) {
            $label    .= $connector . $splitAlias['parent'];
            $connector = '/';
        }

        if ($splitAlias['fragment']) {
            $label .= $connector . '<span style="color:#5C9AC9;">' . $splitAlias['fragment'] . '</span>';
        }

        $label .= ']</span>';

        return $label;
    }

    private function generateNoAliasError(): string
    {
        return ' <span style="color:#CC5555;">[' . $this->translate('errNoAlias') . ']</span>';
    }

    /** @param array<string,mixed> $splitAlias */
    private function addErrorHint(array $splitAlias, string &$image, string &$title): void
    {
        if (! isset($splitAlias['err']) || ! is_array($splitAlias['err'])) {
            return;
        }

        $image .= '_error';
        foreach ($splitAlias['err'] as $strError => &$strLabel) {
            $strLabel = $this->translate($strError);
        }

        unset($strLabel);
        $title .= "\n" . implode("\n", $splitAlias['err']);
    }
}
