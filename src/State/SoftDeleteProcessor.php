<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Interface\HasSoftDeleteGuard;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();

        if(method_exists($data, 'setDeletedAt')) {
            $data
                ->setDeletedAt(new \DateTimeImmutable())
                ->setDeletedBy($user->getId())
            ;
        }
        /*
            if($data instanceof HasSoftDeleteGuard) {
                $blockers = $data->getSoftDeleteBlockers();
                if(!empty($blockers)) {
                    throw new UnprocessableEntityHttpException(implode(' ', $blockers));
                }
            }
        */
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
