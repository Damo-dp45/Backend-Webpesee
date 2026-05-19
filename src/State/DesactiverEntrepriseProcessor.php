<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\ReferenceStatus;
use App\Entity\Entreprise;
use App\Repository\EntrepriseRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DesactiverEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private EntrepriseRepository $entrepriseRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Entreprise $data */

        $entreprise = $this->entrepriseRepository->findOneBy([
            'id' => $uriVariables['id']
        ]);
        if(!$entreprise) {
            throw new NotFoundHttpException('Entreprise introuvable');
        }
        $entreprise->setStatut($data->getStatut() === ReferenceStatus::ACTIF->value ? ReferenceStatus::SUSPENDU->value : ReferenceStatus::ACTIF->value);

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
