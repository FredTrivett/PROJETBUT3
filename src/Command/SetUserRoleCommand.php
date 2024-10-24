<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:set-user-role',
    description: 'Set a user\'s role',
)]
class SetUserRoleCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User\'s email')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to set (ROLE_USER, ROLE_GESTIONNAIRE, ROLE_ADMIN)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln('User not found');
            return Command::FAILURE;
        }

        $user->setRoles([$role]);
        $this->entityManager->flush();

        $output->writeln('User role updated successfully');
        return Command::SUCCESS;
    }
}
