<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entreprise;
use App\Entity\Input\RegisterInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private UserPasswordHasherInterface $hasher,
        private EntityManagerInterface $em
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var RegisterInput $data */

        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $data->email]);
        if($existingUser) {
            throw new ConflictHttpException('Cet email est déjà utilisé');
        }

        $this->em->wrapInTransaction(function () use ($data, &$user) // Au lieu de '->beginTransaction()'
        {
            $entreprise = new Entreprise()
                ->setNom($data->nomEntreprise)
                ->setContact1($data->contact1)
                ->setContact2($data->contact2)
                ->setAdresse($data->adresse)
                ->setCodeentreprise($data->codeentreprise)
                ->setSolde($data->solde ?? 0)
            ;
            $this->em->persist($entreprise);

            $user = new User()
                ->setNom($data->nom)
                ->setPrenom($data->prenom)
                ->setEmail($data->email)
                ->setEntreprise($entreprise)
                ->setRoles(['ROLE_ADMIN']);

            $hashedPassword = $this->hasher->hashPassword(
                $user,
                $data->password
            );
            $user->setPassword($hashedPassword);
            $this->em->persist($user);
        });

        return $this->processor->process($user, $operation, $uriVariables, $context);
    }
}
