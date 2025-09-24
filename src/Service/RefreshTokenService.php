<?php
namespace App\Service;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Core\User\UserInterface;

class RefreshTokenService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CustomTokenGenerator $tokenGenerator
    ) {}

    public function create(UserInterface $user, int $ttlSeconds = 2592000): RefreshToken
    {
        $token = new RefreshToken();
        $token->setRefreshToken($this->tokenGenerator->generateLongRefreshToken(128))
              ->setUsername($user->getUserIdentifier())
              ->setValid(new \DateTime("+$ttlSeconds seconds"));

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    public function getUserByToken(string $refreshToken, UserInterface $userProvider): ?UserInterface
    {
        $rt = $this->em->getRepository(RefreshToken::class)->findOneBy(['refreshToken' => $refreshToken]);
        if (!$rt || $rt->getValid() < new \DateTime()) {
            return null;
        }

        $user = $userProvider->loadUserByIdentifier($rt->getUsername());
        if (!$user) return null;

        $this->em->remove($rt);
        $this->em->flush();

        return $user;
    }
}
