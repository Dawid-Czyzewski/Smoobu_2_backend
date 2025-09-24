<?php

namespace App\Service;

use Symfony\Component\Uid\Uuid;

class CustomTokenGenerator
{
    /**
     * Generate a longer refresh token by combining multiple UUIDs
     */
    public function generateLongRefreshToken(int $length = 128): string
    {
        // Generate multiple UUIDs and concatenate them
        $token = '';
        while (strlen($token) < $length) {
            $token .= Uuid::v4()->toString();
        }
        
        // Truncate to exact length if needed
        return substr($token, 0, $length);
    }
    
    /**
     * Generate a secure random token of specified length
     */
    public function generateSecureToken(int $length = 128): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';
        
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $token;
    }
    
    /**
     * Generate a base64 encoded random token
     */
    public function generateBase64Token(int $length = 128): string
    {
        // Calculate the number of bytes needed to get the desired length after base64 encoding
        $bytesNeeded = intval($length * 3 / 4);
        $bytes = random_bytes($bytesNeeded);
        $token = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        
        // Truncate to exact length if needed
        return substr($token, 0, $length);
    }
}
