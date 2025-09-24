<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\RefreshToken;
use App\Service\CustomTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        CustomTokenGenerator $tokenGenerator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            return new JsonResponse(['error' => 'username and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => $username]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $accessToken = $jwtManager->create($user);

        $refreshToken = new RefreshToken();
        $refreshToken->setRefreshToken($tokenGenerator->generateLongRefreshToken(128));
        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setValid(new \DateTime('+30 days'));

        $em->persist($refreshToken);
        $em->flush();

        return new JsonResponse([
            'token' => $accessToken,
            'refresh_token' => $refreshToken->getRefreshToken(),
        ]);
    }
}
