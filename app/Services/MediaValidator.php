<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class MediaValidator
{
    private const FACEBOOK_IMAGE_MAX_SIZE = 4 * 1024 * 1024; // 4MB
    private const FACEBOOK_VIDEO_MAX_SIZE = 1024 * 1024 * 1024; // 1GB
    private const FACEBOOK_MIN_WIDTH = 600;
    private const FACEBOOK_MIN_HEIGHT = 315;

    private const LINKEDIN_IMAGE_MAX_SIZE = 5 * 1024 * 1024; // 5MB
    private const LINKEDIN_DOCUMENT_MAX_SIZE = 5 * 1024 * 1024; // 5MB
    private const LINKEDIN_MIN_WIDTH = 552;
    private const LINKEDIN_MIN_HEIGHT = 368;

    public function validate(UploadedFile $file, string $platform): ValidationResult
    {
        $errors = [];
        
        // Check if file exists and is valid
        if (!$file->isValid()) {
            $errors[] = 'File upload failed';
            return new ValidationResult(false, $errors);
        }

        // Get file info
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $isImage = str_starts_with($mimeType, 'image/');
        $isVideo = str_starts_with($mimeType, 'video/');
        $isDocument = str_starts_with($mimeType, 'application/');

        // Platform-specific validation
        $errors = match (strtolower($platform)) {
            'facebook' => $this->validateFacebook($file, $size, $isImage, $isVideo),
            'linkedin' => $this->validateLinkedIn($file, $size, $isImage, $isDocument),
            default => ['Unsupported platform'],
        };

        return new ValidationResult(empty($errors), $errors);
    }

    private function validateFacebook(UploadedFile $file, int $size, bool $isImage, bool $isVideo): array
    {
        $errors = [];

        if ($isImage) {
            if ($size > self::FACEBOOK_IMAGE_MAX_SIZE) {
                $errors[] = 'Image size must be less than 4MB for Facebook';
            }

            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions) {
                [$width, $height] = $dimensions;
                if ($width < self::FACEBOOK_MIN_WIDTH || $height < self::FACEBOOK_MIN_HEIGHT) {
                    $errors[] = "Image dimensions must be at least " . self::FACEBOOK_MIN_WIDTH . "x" . self::FACEBOOK_MIN_HEIGHT . " for Facebook";
                }
            }
        } elseif ($isVideo) {
            if ($size > self::FACEBOOK_VIDEO_MAX_SIZE) {
                $errors[] = 'Video size must be less than 1GB for Facebook';
            }
        } else {
            $errors[] = 'Facebook only supports images and videos';
        }

        return $errors;
    }

    private function validateLinkedIn(UploadedFile $file, int $size, bool $isImage, bool $isDocument): array
    {
        $errors = [];

        if ($isImage) {
            if ($size > self::LINKEDIN_IMAGE_MAX_SIZE) {
                $errors[] = 'Image size must be less than 5MB for LinkedIn';
            }

            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions) {
                [$width, $height] = $dimensions;
                if ($width < self::LINKEDIN_MIN_WIDTH || $height < self::LINKEDIN_MIN_HEIGHT) {
                    $errors[] = "Image dimensions must be at least " . self::LINKEDIN_MIN_WIDTH . "x" . self::LINKEDIN_MIN_HEIGHT . " for LinkedIn";
                }
            }
        } elseif ($isDocument) {
            if ($size > self::LINKEDIN_DOCUMENT_MAX_SIZE) {
                $errors[] = 'Document size must be less than 5MB for LinkedIn';
            }
        } else {
            $errors[] = 'LinkedIn only supports images and documents';
        }

        return $errors;
    }
}

class ValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = []
    ) {}

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorMessage(): string
    {
        return implode(', ', $this->errors);
    }
}
