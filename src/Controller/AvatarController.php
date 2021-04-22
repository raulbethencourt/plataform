<?php

namespace App\Controller;

use App\Entity\Avatar;
use App\Form\AvatarType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AvatarController extends AbstractController
{
    /**
     * @Route("/student/avatar", name="edit_student_avatar")
     * @Route("/teacher/avatar", name="edit_teacher_avatar")
     * @Route("/user/avatar", name="edit_user_avatar")
     */
    public function new(EntityManager $em, Request $request): Response
    {
        $avatar = $this->getUser()->getAvatar();
        // Check if the image already exist
        if (!$avatar) {
            $avatar = new Avatar();
            $avatar->setUpdatedAt(new \DateTime());
            $avatar->setUser($this->getUser());
        }

        $form = $this->createForm(AvatarType::class, $avatar);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($avatar);
            $em->flush();
            $this->addFlash('success', 'Avatar ajouté avec succès.');

            switch ($this->getUser()->getRoles()[0]) {
                case 'ROLE_STUDENT':
                    return $this->redirectToRoute('student_profile');
                case 'ROLE_TEACHER':
                    return $this->redirectToRoute('teacher_profile');
                default:
                    return $this->redirectToRoute('user_profile');
            }
        }

        return $this->render(
            'avatar/edit.html.twig',
            [
                'form' => $form->createView(),
                'student' => $this->getUser(),
                'teacher' => $this->getUser(),
                'user' => $this->getUser(),
            ]
        );
    }
}
