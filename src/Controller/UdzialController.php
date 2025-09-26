<?php

namespace App\Controller;

use App\Entity\Udzial;
use App\Entity\User;
use App\Entity\Apartment;
use App\Repository\UdzialRepository;
use App\Repository\UserRepository;
use App\Repository\ApartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/udzialy')]
#[IsGranted('ROLE_ADMIN')]
class UdzialController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UdzialRepository $udzialRepository,
        private UserRepository $userRepository,
        private ApartmentRepository $apartmentRepository
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $udzialy = $this->udzialRepository->findAll();
        
        $data = [];
        foreach ($udzialy as $udzial) {
            $data[] = [
                'id' => $udzial->getId(),
                'user' => [
                    'id' => $udzial->getUser()->getId(),
                    'name' => $udzial->getUser()->getName(),
                    'surname' => $udzial->getUser()->getSurname(),
                    'username' => $udzial->getUser()->getUsername()
                ],
                'apartment' => [
                    'id' => $udzial->getApartment()->getId(),
                    'name' => $udzial->getApartment()->getName()
                ],
                'procent' => $udzial->getProcent()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id']) || !isset($data['apartment_id']) || !isset($data['procent'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        $user = $this->userRepository->find($data['user_id']);
        $apartment = $this->apartmentRepository->find($data['apartment_id']);

        if (!$user || !$apartment) {
            return new JsonResponse(['error' => 'User or apartment not found'], 404);
        }

        $existingUdzial = $this->udzialRepository->findByUserAndApartment($data['user_id'], $data['apartment_id']);
        if ($existingUdzial) {
            return new JsonResponse(['error' => 'Share already exists for this user and apartment'], 400);
        }

        $totalPercentage = $this->udzialRepository->getTotalPercentageForApartment($data['apartment_id']);
        $newPercentage = (float)$data['procent'];
        
        if ($totalPercentage + $newPercentage > 100) {
            return new JsonResponse(['error' => 'Total percentage cannot exceed 100%'], 400);
        }

        $udzial = new Udzial();
        $udzial->setUser($user);
        $udzial->setApartment($apartment);
        $udzial->setProcent($data['procent']);

        $this->em->persist($udzial);
        $this->em->flush();

        return new JsonResponse([
            'id' => $udzial->getId(),
            'user' => [
                'id' => $udzial->getUser()->getId(),
                'name' => $udzial->getUser()->getName(),
                'surname' => $udzial->getUser()->getSurname(),
                'username' => $udzial->getUser()->getUsername()
            ],
            'apartment' => [
                'id' => $udzial->getApartment()->getId(),
                'name' => $udzial->getApartment()->getName()
            ],
            'procent' => $udzial->getProcent()
        ], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $udzial = $this->udzialRepository->find($id);
        
        if (!$udzial) {
            return new JsonResponse(['error' => 'Share not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['procent'])) {
            $totalPercentage = $this->udzialRepository->getTotalPercentageForApartment($udzial->getApartment()->getId());
            $currentPercentage = (float)$udzial->getProcent();
            $newPercentage = (float)$data['procent'];
            
            if (($totalPercentage - $currentPercentage + $newPercentage) > 100) {
                return new JsonResponse(['error' => 'Total percentage cannot exceed 100%'], 400);
            }

            $udzial->setProcent($data['procent']);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $udzial->getId(),
            'user' => [
                'id' => $udzial->getUser()->getId(),
                'name' => $udzial->getUser()->getName(),
                'surname' => $udzial->getUser()->getSurname(),
                'username' => $udzial->getUser()->getUsername()
            ],
            'apartment' => [
                'id' => $udzial->getApartment()->getId(),
                'name' => $udzial->getApartment()->getName()
            ],
            'procent' => $udzial->getProcent()
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $udzial = $this->udzialRepository->find($id);
        
        if (!$udzial) {
            return new JsonResponse(['error' => 'Share not found'], 404);
        }

        $this->em->remove($udzial);
        $this->em->flush();

        return new JsonResponse(['message' => 'Share deleted successfully']);
    }

    #[Route('/apartment/{apartmentId}', methods: ['PUT'])]
    public function updateApartmentShares(int $apartmentId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['shareholders']) || !is_array($data['shareholders'])) {
            return new JsonResponse(['error' => 'Missing or invalid shareholders array'], 400);
        }

        $apartment = $this->apartmentRepository->find($apartmentId);
        if (!$apartment) {
            return new JsonResponse(['error' => 'Apartment not found'], 404);
        }

        // Sprawdź czy łączny procent nie przekracza 100%
        $totalPercentage = 0;
        foreach ($data['shareholders'] as $shareholder) {
            if (!isset($shareholder['user_id']) || !isset($shareholder['procent'])) {
                return new JsonResponse(['error' => 'Missing user_id or procent in shareholder data'], 400);
            }
            $totalPercentage += (float)$shareholder['procent'];
        }

        if ($totalPercentage > 100) {
            return new JsonResponse(['error' => 'Total percentage cannot exceed 100%'], 400);
        }

        // Usuń wszystkie istniejące udziały dla tego apartamentu
        $existingShares = $this->udzialRepository->findByApartment($apartmentId);
        foreach ($existingShares as $share) {
            $this->em->remove($share);
        }

        // Utwórz nowe udziały
        $createdShares = [];
        foreach ($data['shareholders'] as $shareholderData) {
            $user = $this->userRepository->find($shareholderData['user_id']);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found: ' . $shareholderData['user_id']], 404);
            }

            $udzial = new Udzial();
            $udzial->setUser($user);
            $udzial->setApartment($apartment);
            $udzial->setProcent($shareholderData['procent']);

            $this->em->persist($udzial);
            $createdShares[] = [
                'id' => $udzial->getId(),
                'user' => [
                    'id' => $udzial->getUser()->getId(),
                    'name' => $udzial->getUser()->getName(),
                    'surname' => $udzial->getUser()->getSurname(),
                    'username' => $udzial->getUser()->getUsername()
                ],
                'apartment' => [
                    'id' => $udzial->getApartment()->getId(),
                    'name' => $udzial->getApartment()->getName()
                ],
                'procent' => $udzial->getProcent()
            ];
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Apartment shares updated successfully',
            'shares' => $createdShares
        ]);
    }

    #[Route('/user/{userId}', methods: ['PUT'])]
    public function updateUserShares(int $userId, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['shares'])) {
            return new JsonResponse(['error' => 'Invalid data. Expected shares array.'], 400);
        }

        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Delete all existing shares for this user
        $existingShares = $this->em->getRepository(Udzial::class)->findBy(['user' => $user]);
        foreach ($existingShares as $share) {
            $this->em->remove($share);
        }

        $createdShares = [];
        foreach ($data['shares'] as $shareData) {
            if (!isset($shareData['apartment_id']) || !isset($shareData['procent'])) {
                return new JsonResponse(['error' => 'Each share must have apartment_id and procent'], 400);
            }

            $apartment = $this->em->getRepository(Apartment::class)->find($shareData['apartment_id']);
            if (!$apartment) {
                return new JsonResponse(['error' => 'Apartment not found: ' . $shareData['apartment_id']], 404);
            }

            $udzial = new Udzial();
            $udzial->setUser($user);
            $udzial->setApartment($apartment);
            $udzial->setProcent($shareData['procent']);

            $this->em->persist($udzial);
            $createdShares[] = [
                'id' => $udzial->getId(),
                'user' => [
                    'id' => $udzial->getUser()->getId(),
                    'name' => $udzial->getUser()->getName(),
                    'surname' => $udzial->getUser()->getSurname(),
                    'username' => $udzial->getUser()->getUsername()
                ],
                'apartment' => [
                    'id' => $udzial->getApartment()->getId(),
                    'name' => $udzial->getApartment()->getName()
                ],
                'procent' => $udzial->getProcent()
            ];
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => 'User shares updated successfully',
            'shares' => $createdShares
        ]);
    }
}
