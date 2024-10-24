<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GestionController extends AbstractController
{
    #[Route('/gestion', name: 'app_gestion')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        return $this->render('gestion/index.html.twig', [
            'evenements' => $evenementRepository->findAll(),
        ]);
    }
}
