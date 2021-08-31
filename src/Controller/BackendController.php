<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\Controller;

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

use Hofff\Contao\TrueUrl\TrueURL;

use function assert;
use function max;
use function min;

final class BackendController
{
    private SessionInterface $session;

    private ContaoFramework $framework;

    private TrueURL $trueUrl;

    public function __construct(SessionInterface $session, ContaoFramework $framework, TrueURL $trueUrl)
    {
        $this->session   = $session;
        $this->framework = $framework;
        $this->trueUrl   = $trueUrl;
    }

    /**
     * @Route("/contao/trueurl/alias",
     *     name="hofff_contao_true_url_alias",
     *     methods={"GET"},
     *     defaults={"_scope": "backend"}
     * )
     */
    public function aliasAction(Request $request): Response
    {
        $bag = $this->session->getBag('contao_backend');
        assert($bag instanceof AttributeBag);
        $bag->set('bbit_turl_alias', max(0, min(2, $request->query->getInt('bbit_turl_alias'))));

        return $this->redirectToRefererResponse($request);
    }

    /**
     * @Route("/contao/trueurl/regenerate",
     *     name="hofff_contao_true_url_regenerate",
     *     methods={"GET"},
     *     defaults={"_scope": "backend"}
     * )
     */
    public function regenerateAction(Request $request): Response
    {
        $this->trueUrl->regeneratePageRoots();

        return $this->redirectToRefererResponse($request);
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
        $this->trueUrl->update($request->query->getInt('id'), null, true);

        return $this->redirectToRefererResponse($request);
    }

    private function redirectToRefererResponse(Request $request): Response
    {
        $this->framework->initialize();

        return new RedirectResponse(
            $request->getSchemeAndHttpHost() . '/' . $this->framework->getAdapter(Backend::class)->getReferer(),
            Response::HTTP_SEE_OTHER
        );
    }
}
