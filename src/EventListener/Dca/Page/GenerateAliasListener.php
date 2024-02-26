<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\EventListener\DataContainer\PageUrlListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Hofff\Contao\TrueUrl\TrueURL;
use Symfony\Component\Security\Core\Security;

use function array_key_exists;
use function count;
use function trim;

final class GenerateAliasListener
{
    public function __construct(
        private readonly PageUrlListener $pageUrlListener,
        private readonly TrueURL $trueUrl,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly Security $security,
    ) {
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     *
     * @Callback(table="tl_page", target="fields.alias.save", priority=255)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function onSave($value, DataContainer $dataContainer)
    {
        if (! $dataContainer->activeRecord || $dataContainer->activeRecord->type === 'root') {
            return $value;
        }

        $inputAdapter = $this->framework->createInstance(Input::class);
        $set          = [];

        // Make sure that alias settings are saved before the alias is generated
        foreach (['bbit_turl_inherit', 'bbit_turl_transparent', 'bbit_turl_ignoreRoot'] as $column) {
            if (
                ! $this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_page::' . $column)
            ) {
                continue;
            }

            if (! array_key_exists($column, $_POST)) {
                continue;
            }

            $set[$column] = (bool) $inputAdapter->post($column);
        }

        if (count($set) === 0) {
            return $value;
        }

        $this->connection->update('tl_page', $set, ['id' => $dataContainer->id]);

        return $value;
    }

    /** @Callback(table="tl_page", target="config.onsubmit", priority=128) */
    public function onSubmit(DataContainer $dataContainer): void
    {
        if (! $dataContainer->activeRecord) {
            return;
        }

        $alias = (string) $dataContainer->activeRecord->alias;
        if ($alias === '') {
            /** @psalm-suppress InternalMethod */
            $alias = $this->pageUrlListener->generateAlias('', $dataContainer);
        }

        $alias = trim($alias, '/');

        if ($alias === '') {
            return;
        }

        $fragment = $this->trueUrl->extractFragment((int) $dataContainer->id, $alias);
        $this->trueUrl->update((int) $dataContainer->id, $fragment);
    }
}
