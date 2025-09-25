<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank()],
            'surname' => [new Assert\NotBlank()],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'username' => [new Assert\NotBlank(), new Assert\Length(['min' => 3])],
            'phone' => [new Assert\Optional([
                new Assert\Regex([
                    'pattern' => '/^\+[1-9]\d{1,14}$/',
                    'message' => 'Phone number must start with + and country code (e.g., +48123456789)'
                ])
            ])],
            'roles' => [new Assert\Optional([
                new Assert\Type('array'),
                new Assert\All([new Assert\Type('string')])
            ])]
        ]);

        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        // Check if username is available (excluding current user)
        if ($data['username'] !== $user->getUsername()) {
            if (!$this->userRepository->isUsernameAvailable($data['username'], $id)) {
                return new JsonResponse(['error' => 'Username is already taken'], 400);
            }
        }

        // Update user data
        $user->setName($data['name']);
        $user->setSurname($data['surname']);
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setPhone($data['phone'] ?? null);

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles(),
                'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s')
            ]
        ]);
    }
}
