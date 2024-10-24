<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdministrationController extends AbstractController
{
    #[Route('/administration', name: 'app_administration')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 10; // Number of users per page

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.email', 'ASC');

        $paginator = new Paginator($queryBuilder);
        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);

        return $this->render('administration/index.html.twig', [
            'users' => $paginator,
            'totalItems' => $totalItems,
            'pagesCount' => $pagesCount,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/administration/utilisateur/{id}', name: 'app_user_edit', defaults: ['id' => null])]
    public function editUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, ?User $user = null): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $isNewUser = $user === null;
        if ($isNewUser) {
            $user = new User();
        }

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_edit' => !$isNewUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if ($isNewUser && empty($plainPassword)) {
                $this->addFlash('error', 'Le mot de passe est obligatoire pour un nouvel utilisateur.');
                return $this->redirectToRoute('app_user_edit');
            }

            if (!empty($plainPassword)) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            $user->setRole($form->get('role')->getData());
            $user->setActif($form->get('actif')->getData());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $isNewUser ? 'Utilisateur créé avec succès.' : 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_administration');
        }

        return $this->render('administration/edit_user.html.twig', [
            'registrationForm' => $form->createView(),
            'user' => $user,
            'isNewUser' => $isNewUser,
        ]);
    }
}
