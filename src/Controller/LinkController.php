<?php

namespace App\Controller;

use App\Entity\Link;
use App\Form\LinkType;
use App\Repository\LinkRepository;
use App\Service\FindEntity;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @Route("/link")
 */
class LinkController extends AbstractController
{
    private $breadCrumbs;

    private $find;

    public function __construct(Breadcrumbs $breadCrumbs, FindEntity $find)
    {
        $this->breadCrumbs = $breadCrumbs;
        $this->find = $find;
    }

    /**
     * @Route("/", name="link_index", methods={"GET"})
     * @Security("is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')")
     */
    public function index(LinkRepository $linkRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();
        if ('ROLE_ADMIN' === $user->getRoles()[0] || 'ROLE_SUPER_ADMIN' === $user->getRoles()[0]) {
            $links = $linkRepo->findAll();
        } else {
            $links = $linkRepo->findByVisibilityOrCreator(true, $user->getUsername());
        }

        if ($request->query->get('classroom_id')) {
            $classroom = $this->find->findClassroom();
            $this->breadCrumbs
                ->addRouteItem('Acueille', 'user_show')
                ->addRouteItem('Classe',
                    'classroom_show',
                    ['id' => $classroom->getId()]
                )
                ->addRouteItem('Créer une lien', 'link_new', ['classroom_id' => $classroom->getId()])
            ;
        } else {
            $role = $this->getUser()->getRoles()[0];
            switch ($role) {
                case 'ROLE_TEACHER':
                    $this->breadCrumbs->addRouteItem('Accueil', 'teacher_show');
                    break;
                default:
                    $this->breadCrumbs->addRouteItem('Accueil', 'user_show');
                    break;
            }
        }
        $this->breadCrumbs->addRouteItem('Liens', 'link_index');

        $links = $paginator->paginate(
            $links,
            $request->query->getInt('page', 1),
            10
        );

        $links->setCustomParameters([
            'align' => 'center',
            'rounded' => true,
        ]);

        return $this->render('link/index.html.twig', [
            'links' => $links,
            'classroom_id' => $request->query->get('classroom_id'),
        ]);
    }

    /**
     * @Route("/new", name="link_new", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')")
     */
    public function new(Request $request, FindEntity $find): Response
    {
        $link = new Link();
        $link->setCreator($this->getUser()->getUsername());

        $classroom_id = $request->query->get('classroom_id');
        if (isset($classroom_id)) {
            $classroom = $find->findClassroom();
            $link->addClassroom($classroom);
            $this->breadCrumbs
                ->addRouteItem('Acueille', 'user_show')
                ->addRouteItem('Classe',
                    'classroom_show',
                    ['id' => $classroom->getId()]
                )
            ;
        } else {
            $role = $this->getUser()->getRoles()[0];
            switch ($role) {
                case 'ROLE_TEACHER':
                    $this->breadCrumbs->addRouteItem('Accueil', 'teacher_show');
                    break;
                default:
                    $this->breadCrumbs->addRouteItem('Accueil', 'user_show');
                    break;
            }
            $this->breadCrumbs->addRouteItem('Liens', 'link_index');
        }
        $this->breadCrumbs->addRouteItem('Créer une lien', 'link_new');

        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($link);
            $entityManager->flush();
            $this->addFlash('success', 'Lien créée avec succès.');

            if (isset($classroom_id)) {
                return $this->redirectToRoute('classroom_show', [
                    'id' => $classroom_id,
                ]);
            }

            return $this->redirectToRoute('link_index');
        }

        return $this->render('link/new.html.twig', [
            'link' => $link,
            'form' => $form->createView(),
            'classroom_id' => $classroom_id,
        ]);
    }

    /**
     * @Route("/{id}", name="link_show", methods={"GET"})
     * @Security("is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN') or is_granted('ROLE_STUDENT')")
     */
    public function show(Link $link): Response
    {
        return $this->render('link/show.html.twig', [
            'link' => $link,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="link_edit", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')")
     */
    public function edit(Request $request, Link $link): Response
    {
        $classroom = $this->find->findClassroom();
        if ($request->query->get('classroom_id')) {
            $this->breadCrumbs
                ->addRouteItem('Acueille', 'user_show')
                ->addRouteItem('Classe',
                    'classroom_show',
                    ['id' => $classroom->getId()]
                )
                ->addRouteItem('Créer une lien', 'link_new', ['classroom_id' => $classroom->getId()])
                ->addRouteItem('Liens', 'link_index', ['classroom_id' => $classroom->getId()])
                ->addRouteItem('Editer une lien', 'link_edit', ['id' => $link->getId()])
            ;
        } else {
            $role = $this->getUser()->getRoles()[0];
            switch ($role) {
                case 'ROLE_TEACHER':
                    $this->breadCrumbs->addRouteItem('Accueil', 'teacher_show');
                    break;
                default:
                    $this->breadCrumbs->addRouteItem('Accueil', 'user_show');
                    break;
            }
            $this->breadCrumbs
                ->addRouteItem('Liens', 'link_index')
                ->addRouteItem('Editer une lien', 'link_edit', ['id' => $link->getId()])
            ;
        }

        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Lien modifiée avec succès.');

            if (isset($classroom)) {
                return $this->redirectToRoute('link_index', [
                    'classroom_id' => $classroom->getId(),
                ]);
            }

            return $this->redirectToRoute('link_index');
        }

        return $this->render('link/edit.html.twig', [
            'link' => $link,
            'form' => $form->createView(),
            'classroom_id' => $request->query->get('classroom_id'),
        ]);
    }

    /**
     * @Route("/{id}", name="link_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Link $link): Response
    {
        if ($this->isCsrfTokenValid('delete'.$link->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($link);
            $entityManager->flush();
            $this->addFlash('success', 'Lien supprimée avec succès.');
        }

        return $this->redirectToRoute('link_index');
    }
}
