<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Commande one-shot : recopie les utilisateurs du provider in-memory
 * (security.yaml) vers la base de données, en transférant le hash tel quel.
 *
 * À lancer une seule fois, dans l'environnement où le security.yaml complet
 * est présent, puis à supprimer avec le bloc in-memory.
 */
#[AsCommand(
    name: 'app:migrate-inmemory-users',
    description: 'Copie les utilisateurs in-memory (security.yaml) vers la base de données.',
)]
class MigrateInMemoryUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire(param: 'app.in_memory_user_migration')]
        private readonly array $inMemoryUserLinks,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'security-file',
            InputArgument::OPTIONAL,
            'Chemin (relatif à la racine du projet) du security.yaml contenant le provider in-memory',
            'config/packages/security.yaml',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $relativePath = (string) $input->getArgument('security-file');

        $config = $this->inMemoryUserLinks;

        if (!isset($this->inMemoryUserLinks)) {
            $io->error(sprintf('Config de migration introuvable : %s', 'app.in_memory_user_migration'));

            return Command::FAILURE;
        }

        if (empty($this->inMemoryUserLinks)) {
            $io->warning(sprintf('Aucun utilisateur in-memory trouvé dans %s', 'app.in_memory_user_migration'));

            return Command::SUCCESS;
        }


        $repository = $this->em->getRepository(User::class);
        $created = 0;
        $updated = 0;

        foreach ($this->inMemoryUserLinks as $identifier => $inMemoryUser) {
            if (!isset($inMemoryUser['linked_user_email'])) {
                $io->warning(sprintf('Entrée "%s" ignorée : aucun email fourni.', $identifier));

                continue;
            }
            $email = $inMemoryUser['linked_user_email'];

            $user = $repository->findOneBy(['email' => $email]);
            if (null === $user) {
                $user = new User();
                // "name" est NOT NULL en base : valeur de repli pour un user créé.
                $user->setName($identifier);
                $user->setEmail($email);
                ++$created;
            } else {
                ++$updated;
            }

            // Hash recopié tel quel : jamais déchiffré ni régénéré.
            $user->setLogin($email);
            $user->setPassword($inMemoryUser['password']);
            $user->setRoles($inMemoryUser['roles'] ?? []);

            $this->em->persist($user);
        }

        $this->em->flush();

        $io->success(sprintf(
            '%d utilisateur(s) traité(s) : %d créé(s), %d mis à jour.',
            $created + $updated,
            $created,
            $updated,
        ));

        return Command::SUCCESS;
    }
}
