<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;

#[Route('/gestion/evenements')]
class EvenementController extends AbstractController
{
    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository): Response
    {
        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenementRepository->findAll(),
        ]);
    }

    #[Route('/nouveau', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès.');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié avec succès.');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/users', name: 'app_evenement_users', methods: ['GET', 'POST'])]
    public function manageUsers(Request $request, Evenement $evenement, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $userIds = $request->request->all('users');
            $selectedUsers = $userRepository->findBy(['id' => $userIds]);

            foreach ($evenement->getUsers() as $user) {
                if (!in_array($user->getId(), $userIds)) {
                    $evenement->removeUser($user);
                }
            }

            foreach ($selectedUsers as $user) {
                $evenement->addUser($user);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Les utilisateurs ont été associés avec succès.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Number of users per page

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.email', 'ASC');

        if ($query) {
            $queryBuilder->where('u.email LIKE :query OR u.nom LIKE :query OR u.prenom LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        $paginator = new Paginator($queryBuilder);
        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);

        return $this->render('evenement/manage_users.html.twig', [
            'evenement' => $evenement,
            'users' => $paginator,
            'totalItems' => $totalItems,
            'pagesCount' => $pagesCount,
            'page' => $page,
            'limit' => $limit,
            'query' => $query,
        ]);
    }
}
