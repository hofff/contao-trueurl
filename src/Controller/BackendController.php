<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\Controller;

use Contao\Backend;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Hofff\Contao\TrueUrl\TrueURL;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

use function str_starts_with;

final class BackendController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TrueURL $trueUrl,
        private readonly Security $security,
    ) {
    }

    /**
     * @Route("/contao/trueurl/repair",
     *     name="hofff_contao_true_url_repair",
     *     methods={"GET"},
     *     defaults={"_scope": "backend"}
     * )
     */
    public function repairAction(Request $request): Response
    {
        $this->checkPermissions();

        $this->trueUrl->repair();

        return $this->redirectToRefererResponse($request);
    }

    /**
     * @Route("/contao/trueurl/auto-inherit",
     *     name="hofff_contao_true_url_auto_inherit",
     *     methods={"GET"},
     *     defaults={"_scope": "backend"}
     * )
     */
    public function autoInheritAction(Request $request): Response
    {
        $this->checkPermissions();

        $this->trueUrl->update($request->query->getInt('id'), null, true);

        return $this->redirectToRefererResponse($request);
    }

    private function redirectToRefererResponse(Request $request): Response
    {
        $this->framework->initialize();

        $referer = $this->framework->getAdapter(Backend::class)->getReferer();
        if (! str_starts_with($referer, '/')) {
            $referer = '/' . $referer;
        }

        return new RedirectResponse(
            $request->getSchemeAndHttpHost() . $referer,
            Response::HTTP_SEE_OTHER,
        );
    }

    private function checkPermissions(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        throw new AccessDeniedException();
    }
}
