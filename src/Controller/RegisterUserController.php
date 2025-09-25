<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\InvoiceInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class RegisterUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $constraints = new Assert\Collection([
            'username' => [new Assert\NotBlank(), new Assert\Length(['min' => 3])],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank(), new Assert\Length(['min' => 6])],
            'name' => [new Assert\NotBlank()],
            'surname' => [new Assert\NotBlank()],
            'phone' => [new Assert\Optional([
                new Assert\Regex([
                    'pattern' => '/^\+[1-9]\d{1,14}$/',
                    'message' => 'Phone number must start with + and country code (e.g., +48123456789)'
                ])
            ])],
            'roles' => [new Assert\Optional([
                new Assert\Type('array'),
                new Assert\All([new Assert\Type('string')])
            ])],
            'invoiceInfo' => [new Assert\Optional([
                new Assert\Type('array')
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

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setSurname($data['surname']);
        $user->setPhone($data['phone'] ?? null);
        $user->setCreatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $roles = $data['roles'] ?? ['ROLE_USER'];
        $user->setRoles($roles);

        $this->em->persist($user);
        $this->em->flush();

        if (isset($data['invoiceInfo'])) {
            $invoiceData = $data['invoiceInfo'];
            
            $hasAnyInvoiceData = !empty($invoiceData['country']) || 
                               !empty($invoiceData['city']) || 
                               !empty($invoiceData['companyName']) || 
                               !empty($invoiceData['nip']) || 
                               !empty($invoiceData['address']) || 
                               !empty($invoiceData['email']);
            
            if ($hasAnyInvoiceData) {
                $invoiceInfo = new InvoiceInfo();
                $invoiceInfo->setUser($user);
                $invoiceInfo->setCountry(!empty($invoiceData['country']) ? $invoiceData['country'] : null);
                $invoiceInfo->setCity(!empty($invoiceData['city']) ? $invoiceData['city'] : null);
                $invoiceInfo->setCompanyName(!empty($invoiceData['companyName']) ? $invoiceData['companyName'] : null);
                $invoiceInfo->setNip(!empty($invoiceData['nip']) ? $invoiceData['nip'] : null);
                $invoiceInfo->setAddress(!empty($invoiceData['address']) ? $invoiceData['address'] : null);
                $invoiceInfo->setEmail(!empty($invoiceData['email']) ? $invoiceData['email'] : null);
                
                $this->em->persist($invoiceInfo);
                $this->em->flush();
            }
        }

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], 201);
    }
}
