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
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
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
        $path = $this->projectDir . '/' . ltrim($relativePath, '/');

        if (!is_file($path)) {
            $io->error(sprintf('Fichier introuvable : %s', $path));

            return Command::FAILURE;
        }

        $config = Yaml::parseFile($path);

        // On récupère le premier provider de type "memory", quel que soit son nom.
        $memoryUsers = [];
        foreach ($config['security']['providers'] ?? [] as $provider) {
            if (isset($provider['memory']['users'])) {
                $memoryUsers = $provider['memory']['users'];
                break;
            }
        }

        if ([] === $memoryUsers) {
            $io->warning(sprintf('Aucun utilisateur in-memory trouvé dans %s', $relativePath));

            return Command::SUCCESS;
        }

        $repository = $this->em->getRepository(User::class);
        $created = 0;
        $updated = 0;

        foreach ($memoryUsers as $identifier => $data) {
            if (!isset($data['password'])) {
                $io->warning(sprintf('Entrée "%s" ignorée : pas de mot de passe.', $identifier));

                continue;
            }

            $login = (string) $identifier;

            $user = $repository->findOneBy(['login' => $login]);
            if (null === $user) {
                $user = new User();
                // "name" est NOT NULL en base : valeur de repli pour un user créé.
                $user->setLogin($login);
                ++$created;
            } else {
                ++$updated;
            }

            // Hash recopié tel quel : jamais déchiffré ni régénéré.
            $user->setPassword($data['password']);
            $user->setRoles($data['roles'] ?? []);

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
