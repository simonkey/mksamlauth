<?php

declare(strict_types=1);

namespace DMK\MKSamlAuth\Service;

use DMK\MKSamlAuth\EnricherRegistry;
use DMK\MKSamlAuth\Manager\FrontendUserManager;
use DMK\MKSamlAuth\Model\FrontendUser;
use TYPO3\CMS\Core\SingletonInterface;

final class UserCreator implements SingletonInterface
{
    private $enrichers;

    private $manager;

    public function __construct(EnricherRegistry $registry, FrontendUserManager $manager)
    {
        $this->enrichers = $registry;
        $this->manager = $manager;
    }

    public function updateOrCreate(array $attributes, array $record)
    {
        $userFolder = $record['userFolder'];

        if (!($attributes['uid'] ?? false)) {
            throw new \LogicException('The idp does not return any "uid". Please check the configuration in the idp settings.');
        }

        $user = $this->manager->getRepository()->findByUsername($attributes['uid'], $record['userFolder']);

        if (null === $user) {
            $user = new FrontendUser([
                'username' => $attributes['uid'],
                'pid' => $userFolder,
                'crdate' => \time(),
            ]);
        }



        unset($attributes['uid']);

        $this->enrichers->process($user, [
            'idp' => $record,
            'attributes' => $attributes,
        ]);

        $user->setProperty('tstamp', \time());

        $this->manager->save($user);

        return $user;
    }
}
