<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/password-reset')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordResetTokenRepository $tokenRepository,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    #[Route('/request', name: 'password_reset_request', methods: ['POST'])]
    public function requestReset(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        
        $emailConstraint = new Assert\Email();
        $errors = $this->validator->validate($email, $emailConstraint);
        
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            return new JsonResponse([
                'message' => 'If the email exists in our system, you will receive a password reset link.'
            ]);
        }

        $this->tokenRepository->invalidateUserTokens($user);

        $resetToken = new PasswordResetToken($user);
        $this->entityManager->persist($resetToken);
        $this->entityManager->flush();

        try {
            $this->emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getName() . ' ' . $user->getSurname(),
                $resetToken->getId()
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to send reset email. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'If the email exists in our system, you will receive a password reset link.'
        ]);
    }

    #[Route('/verify', name: 'password_reset_verify', methods: ['POST'])]
    public function verifyToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['token'])) {
            return new JsonResponse(['error' => 'Token is required'], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->tokenRepository->findValidToken($data['token']);
        
        if (!$token) {
            return new JsonResponse(['error' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'valid' => true,
            'email' => $token->getUser()->getEmail()
        ]);
    }

    #[Route('/reset', name: 'password_reset_reset', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['token']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Token and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->tokenRepository->findValidToken($data['token']);
        
        if (!$token) {
            return new JsonResponse(['error' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        $user = $token->getUser();
        
        $passwordConstraint = new Assert\Length(['min' => 6]);
        $errors = $this->validator->validate($data['password'], $passwordConstraint);
        
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Password must be at least 6 characters long'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        $token->setUsed(true);
        
        $this->entityManager->flush();

        try {
            $this->emailService->sendPasswordResetConfirmation(
                $user->getEmail(),
                $user->getName() . ' ' . $user->getSurname()
            );
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => 'Password has been reset successfully'
        ]);
    }
}
