<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\CallbackTransformer;
use Doctrine\ORM\Tools\Pagination\Paginator;

class AdministrationController extends AbstractController
{
    #[Route('/administration', name: 'app_administration')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10; // Nombre d'utilisateurs par page

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

    #[Route('/administration/utilisateurs/saisie/{id}', name: 'app_user_edit', defaults: ['id' => null])]
    public function editUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ?User $user = null): Response
    {
        $isNew = !$user;
        if ($isNew) {
            $user = new User();
        }

        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Email',
                'attr' => ['placeholder' => 'Email']
            ])
            ->add('nom', TextType::class, [
                'required' => true,
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Nom']
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom']
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Gestionnaire' => 'ROLE_GESTIONNAIRE',
                    'Administrateur' => 'ROLE_ADMIN'
                ],
                'multiple' => true,
                'expanded' => true
            ])
            ->add('password', PasswordType::class, [
                'required' => $isNew,
                'label' => 'Mot de passe',
                'attr' => ['placeholder' => 'Laissez vide pour ne pas changer'],
                'empty_data' => '',
                'mapped' => false
            ])
            ->add('actif', CheckboxType::class, [
                'required' => false,
                'label' => 'Actif'
            ]);

        $form->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    return $rolesArray;
                },
                function ($rolesArray) {
                    return $rolesArray;
                }
            ));

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();

            if ($isNew && empty($plainPassword)) {
                $this->addFlash('error', 'Le mot de passe est obligatoire pour un nouvel utilisateur.');
                return $this->redirectToRoute('app_user_edit');
            }

            if (!empty($plainPassword)) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Utilisateur créé avec succès.' : 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_administration');
        }

        return $this->render('administration/edit_user.html.twig', [
            'form' => $form->createView(),
            'isNew' => $isNew
        ]);
    }
}
