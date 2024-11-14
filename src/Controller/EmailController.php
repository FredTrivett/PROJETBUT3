<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends AbstractController
{
    public function __construct(
        private readonly EmailService $emailService
    ) {}

    #[Route('/send-email', name: 'send_email', methods: ['POST'])]
    public function sendEmail(): JsonResponse
    {
        try {
            $this->emailService->sendEmail(
                'frederictrivett@gmail.com',
                'Test Email',
                '<h1>Hello!</h1><p>This is a test email from Resend.</p>'
            );

            return $this->json(['status' => 'Email sent successfully']);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    #[Route('/email-form', name: 'email_form')]
    public function showForm(): Response
    {
        return $this->render('email/form.html.twig');
    }
}
