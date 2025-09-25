<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UsernameCheckController extends AbstractController
{
    #[Route('/api/users/check-username', name: 'api_check_username', methods: ['POST'])]
    public function checkUsername(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['username']) || empty(trim($data['username']))) {
            return new JsonResponse(['error' => 'Username is required'], 400);
        }
        
        $username = trim($data['username']);
        $excludeUserId = $data['excludeUserId'] ?? null;
        
        $isAvailable = $userRepository->isUsernameAvailable($username, $excludeUserId);
        
        return new JsonResponse([
            'available' => $isAvailable,
            'username' => $username
        ]);
    }
}
