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

class AdministrationController extends AbstractController
{
    #[Route('/administration', name: 'app_administration')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('administration/index.html.twig', [
            'users' => $users,
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
                'attr' => ['placeholder' => 'Mot de passe'],
                'empty_data' => ''
            ])
            ->add('actif', CheckboxType::class, [
                'required' => false,
                'label' => 'Actif'
            ]);

        $form->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    // transform the array to a string
                    return $rolesArray;
                },
                function ($rolesArray) {
                    // transform the string back to an array
                    return $rolesArray;
                }
            ));

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('password')->getData()) {
                $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            } elseif ($isNew) {
                $this->addFlash('error', 'Le mot de passe est obligatoire pour un nouvel utilisateur.');
                return $this->redirectToRoute('app_user_edit');
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
