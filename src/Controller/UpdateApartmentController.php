<?php

namespace App\Controller;

use App\Entity\Apartment;
use App\Repository\ApartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

#[AsController]
class UpdateApartmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApartmentRepository $apartmentRepository,
        private ValidatorInterface $validator,
        private SluggerInterface $slugger,
        private ImageManager $imageManager
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $apartment = $this->apartmentRepository->find($id);
        if (!$apartment) {
            return new JsonResponse(['error' => 'Apartment not found'], 404);
        }

        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank()],
            'priceForClean' => [new Assert\NotBlank(), new Assert\Type('numeric')],
            'picture' => [new Assert\Optional()],
            'image' => [new Assert\Optional()],
            'vat' => [new Assert\NotBlank(), new Assert\Type('numeric')],
            'canFaktura' => [new Assert\Type('boolean')]
        ]);

        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $picturePath = $data['picture'] ?? null;
        
        if (!empty($data['image'])) {
            $base64Image = $data['image'];
            
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $imageType = $type[1];
                
                $allowedTypes = ['jpeg', 'jpg', 'png', 'webp'];
                if (!in_array($imageType, $allowedTypes)) {
                    return new JsonResponse(['error' => 'Invalid file type. Only JPEG, PNG and WebP are allowed.'], 400);
                }
                
                $imageData = base64_decode($base64Image);
                if ($imageData === false) {
                    return new JsonResponse(['error' => 'Invalid base64 image data'], 400);
                }
                
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/apartments';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $slug = $this->slugger->slug($data['name'])->lower();
                $filename = 'apartment-' . uniqid() . '.jpg';
                $filePath = $uploadDir . '/' . $filename;
                
                try {
                    $image = $this->imageManager->read($imageData);
                    $image->resize(800, 600, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    $image->toJpeg(85)->save($filePath);
                    $picturePath = '/uploads/apartments/' . $filename;
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Failed to process image: ' . $e->getMessage()], 400);
                }
            } else {
                return new JsonResponse(['error' => 'Invalid image format'], 400);
            }
        }

        $apartment->setName($data['name']);
        $apartment->setPriceForClean($data['priceForClean']);
        $apartment->setPicture($picturePath);
        $apartment->setVat($data['vat']);
        $apartment->setCanFaktura($data['canFaktura']);

        $this->em->persist($apartment);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Apartment updated successfully',
            'apartment' => [
                'id' => $apartment->getId(),
                'name' => $apartment->getName(),
                'priceForClean' => $apartment->getPriceForClean(),
                'picture' => $apartment->getPicture(),
                'vat' => $apartment->getVat(),
                'canFaktura' => $apartment->getCanFaktura(),
                'createdAt' => $apartment->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }
}
