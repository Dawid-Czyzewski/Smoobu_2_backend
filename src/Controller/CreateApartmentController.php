<?php

namespace App\Controller;

use App\Entity\Apartment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

#[AsController]
class CreateApartmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private ImageManager $imageManager
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);

        error_log('Request method: ' . $request->getMethod());
        error_log('Content type: ' . $request->headers->get('Content-Type'));
        error_log('Request data: ' . json_encode($data));

        if (empty($data)) {
            return new JsonResponse(['error' => 'No data provided'], 400);
        }

        $picturePath = null;
        if (!empty($data['image'])) {
            $base64Image = $data['image'];
            
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $imageType = $type[1];
                
                $allowedTypes = ['jpeg', 'jpg', 'png', 'webp'];
                if (!in_array($imageType, $allowedTypes)) {
                    return new JsonResponse(['error' => 'Invalid file type. Only JPEG, PNG and WebP are allowed.'], 400);
                }
                
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/apartments';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $newFilename = 'apartment-' . uniqid() . '.jpg';
                $imageData = base64_decode($base64Image);
                
                $image = $this->imageManager->read($imageData);
                $image->resize(1200, 1200, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                $compressedData = $image->toJpeg(85);
                file_put_contents($uploadDir . '/' . $newFilename, $compressedData);
                
                $picturePath = '/uploads/apartments/' . $newFilename;
            } else {
                return new JsonResponse(['error' => 'Invalid image format'], 400);
            }
        }

        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        if (empty($data['priceForClean']) || !is_numeric($data['priceForClean'])) {
            $errors[] = 'Price for clean must be a valid number';
        }
        if (empty($data['vat']) || !is_numeric($data['vat'])) {
            $errors[] = 'VAT must be a valid number';
        }
        if (!isset($data['canFaktura'])) {
            $errors[] = 'Can faktura is required';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        $apartment = new Apartment();
        $apartment->setName($data['name']);
        $apartment->setPriceForClean($data['priceForClean']);
        $apartment->setPicture($picturePath);
        $apartment->setVat($data['vat']);
        
        $canFaktura = $data['canFaktura'];
        if (is_string($canFaktura)) {
            $canFaktura = in_array($canFaktura, ['true', '1', 'yes', 'on'], true);
        } elseif (is_bool($canFaktura)) {
        } else {
            $canFaktura = (bool)$canFaktura;
        }
        $apartment->setCanFaktura($canFaktura);

        $this->em->persist($apartment);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Apartment created successfully',
            'apartment' => [
                'id' => $apartment->getId(),
                'name' => $apartment->getName(),
                'priceForClean' => $apartment->getPriceForClean(),
                'picture' => $apartment->getPicture(),
                'vat' => $apartment->getVat(),
                'canFaktura' => $apartment->getCanFaktura(),
                'createdAt' => $apartment->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], 201);
    }


    private function parseFormData($content, $files)
    {
        $data = [];
        
        $boundary = null;
        if (preg_match('/boundary=(.+)$/', $content, $matches)) {
            $boundary = '--' . $matches[1];
        }
        
        if (!$boundary) {
            return $data;
        }
        
        $parts = explode($boundary, $content);
        
        foreach ($parts as $part) {
            if (empty(trim($part)) || $part === '--') {
                continue;
            }
            
            $lines = explode("\r\n", $part);
            $header = $lines[0] ?? '';
            
            if (preg_match('/name="([^"]+)"/', $header, $matches)) {
                $fieldName = $matches[1];
                
                if (preg_match('/filename="([^"]+)"/', $header, $fileMatches)) {
                    continue;
                }
                
                $value = '';
                $inValue = false;
                for ($i = 1; $i < count($lines); $i++) {
                    $line = $lines[$i];
                    if (empty($line)) {
                        $inValue = true;
                        continue;
                    }
                    if ($inValue) {
                        $value = $line;
                        break;
                    }
                }
                
                $data[$fieldName] = $value;
            }
        }
        
        return $data;
    }

}
