<?php

namespace App\Controller;

use App\Entity\Link;
use App\Form\LinkType;
use App\Repository\LinkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/link")
 */
class LinkController extends AbstractController
{
    /**
     * @Route("/", name="link_index", methods={"GET"})
     * @Security("is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')")
     */
    public function index(LinkRepository $linkRepository): Response
    {
        return $this->render('link/index.html.twig', [
            'links' => $linkRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="link_new", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')")
     */
    public function new(Request $request): Response
    {
        $link = new Link();
        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($link);
            $entityManager->flush();
            $this->addFlash('success', 'Lien créée avec succès.');

            return $this->redirectToRoute('link_index');
        }

        return $this->render('link/new.html.twig', [
            'link' => $link,
            'form' => $form->createView(),
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
        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Lien modifiée avec succès.');

            return $this->redirectToRoute('link_index');
        }

        return $this->render('link/edit.html.twig', [
            'link' => $link,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="link_delete", methods={"POST"})
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
