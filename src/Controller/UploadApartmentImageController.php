<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsController]
class UploadApartmentImageController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $uploadedFile = $request->files->get('image');

        if (!$uploadedFile) {
            return new JsonResponse(['error' => 'No image file provided'], 400);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedTypes)) {
            return new JsonResponse(['error' => 'Invalid file type. Only JPEG, PNG and WebP are allowed.'], 400);
        }

        if ($uploadedFile->getSize() > 512000) {
            return new JsonResponse(['error' => 'File size too large. Maximum size is 500KB.'], 400);
        }

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/apartments';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            $uploadedFile->move($uploadDir, $newFilename);

            $relativePath = '/uploads/apartments/' . $newFilename;

            return new JsonResponse([
                'message' => 'Image uploaded successfully',
                'filename' => $newFilename,
                'path' => $relativePath,
                'size' => $uploadedFile->getSize()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to upload image: ' . $e->getMessage()], 500);
        }
    }
}
