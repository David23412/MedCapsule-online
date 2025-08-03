<?php

namespace App\Services\Audit\Performance;

/**
 * Image Optimization Service
 * Optimizes images for web delivery
 */
class ImageOptimizationService
{
    /**
     * Optimize image for web delivery
     */
    public function optimizeImage(string $imagePath, int $quality = 85): bool
    {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                return $this->optimizeJpeg($imagePath, $quality);
            case 'image/png':
                return $this->optimizePng($imagePath);
            case 'image/webp':
                return $this->optimizeWebp($imagePath, $quality);
            default:
                return false;
        }
    }
    
    /**
     * Optimize JPEG image
     */
    private function optimizeJpeg(string $imagePath, int $quality): bool
    {
        $image = imagecreatefromjpeg($imagePath);
        if (!$image) {
            return false;
        }
        
        $result = imagejpeg($image, $imagePath, $quality);
        imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Optimize PNG image
     */
    private function optimizePng(string $imagePath): bool
    {
        $image = imagecreatefrompng($imagePath);
        if (!$image) {
            return false;
        }
        
        // Enable PNG compression
        imagesavealpha($image, true);
        $result = imagepng($image, $imagePath, 9);
        imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Optimize WebP image
     */
    private function optimizeWebp(string $imagePath, int $quality): bool
    {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $image = imagecreatefromwebp($imagePath);
        if (!$image) {
            return false;
        }
        
        $result = imagewebp($image, $imagePath, $quality);
        imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Convert image to WebP format
     */
    public function convertToWebp(string $sourcePath, string $destinationPath, int $quality = 85): bool
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        $image = null;
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        $result = imagewebp($image, $destinationPath, $quality);
        imagedestroy($image);
        
        return $result;
    }
}