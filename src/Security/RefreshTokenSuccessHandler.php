<?php

namespace App\Security;

use App\Service\CustomTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class RefreshTokenSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $em,
        private CustomTokenGenerator $tokenGenerator
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();
        
        // Generate new JWT token
        $jwtToken = $this->jwtManager->create($user);
        
        // Generate new refresh token
        $newRefreshToken = new RefreshToken();
        $newRefreshToken->setRefreshToken($this->tokenGenerator->generateLongRefreshToken(128));
        $newRefreshToken->setUsername($user->getUserIdentifier());
        $newRefreshToken->setValid(new \DateTime('+30 days'));
        
        $this->em->persist($newRefreshToken);
        $this->em->flush();
        
        return new JsonResponse([
            'token' => $jwtToken,
            'refresh_token' => $newRefreshToken->getRefreshToken(),
            'refresh_token_expiration' => $newRefreshToken->getValid()->getTimestamp()
        ]);
    }
}
