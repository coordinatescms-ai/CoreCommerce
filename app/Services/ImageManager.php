<?php

namespace App\Services;

class ImageManager
{
    private const MAX_FILE_SIZE_BYTES = 5242880; // 5MB
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private string $publicRoot;

    public function __construct(?string $publicRoot = null)
    {
        $this->publicRoot = $publicRoot ?? realpath(__DIR__ . '/../../public') ?: (__DIR__ . '/../../public');
    }

    public function processUploadedProductImage(array $file, array $config = []): array
    {
        $this->validateUpload($file);

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mime = $this->detectMimeType($tmpName);
        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new \RuntimeException('Дозволені формати зображень: jpg, jpeg, png, webp.');
        }

        try {
            $resource = $this->createResourceFromFile($tmpName, $mime);
            if (!$resource) {
                throw new \RuntimeException('Не вдалося відкрити файл зображення.');
            }

            $width = imagesx($resource);
            $height = imagesy($resource);
            if ($width <= 0 || $height <= 0) {
                imagedestroy($resource);
                throw new \RuntimeException('Некоректні розміри зображення.');
            }

            $quality = (int) ($config['quality'] ?? 82);
            $quality = max(10, min(100, $quality));

            $thumbMaxWidth = max(1, (int) ($config['thumb_width'] ?? 200));
            $mediumMaxWidth = max(1, (int) ($config['medium_width'] ?? 800));
            $convertToWebp = !isset($config['auto_webp']) || (int) $config['auto_webp'] === 1;

            $watermarkPath = (string) ($config['watermark_path'] ?? '');
            $applyWatermark = !empty($config['apply_watermark']) && $watermarkPath !== '';

            $baseName = str_replace('.', '', uniqid('product_', true));
            $baseDir = '/uploads/products/gallery';

            $targetDirs = [
                'original' => $this->publicRoot . $baseDir . '/original/',
                'medium' => $this->publicRoot . $baseDir . '/medium/',
                'thumb' => $this->publicRoot . $baseDir . '/thumb/',
            ];

            foreach ($targetDirs as $dir) {
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    imagedestroy($resource);
                    throw new \RuntimeException('Не вдалося створити директорію для зображень.');
                }
            }

            $resultPaths = [];
            try {
                $originalResource = $this->cloneResource($resource, $width, $height);
                if ($applyWatermark) {
                    $this->applyWatermark($originalResource, $watermarkPath, (string) ($config['watermark_position'] ?? 'bottom-right'));
                }
                $resultPaths['original'] = $this->saveResource(
                    $originalResource,
                    $targetDirs['original'] . $baseName,
                    $mime,
                    $convertToWebp,
                    $quality,
                    $baseDir . '/original/'
                );
                imagedestroy($originalResource);

                $mediumResource = $this->resizeResource($resource, $width, $height, $mediumMaxWidth);
                if ($applyWatermark) {
                    $this->applyWatermark($mediumResource, $watermarkPath, (string) ($config['watermark_position'] ?? 'bottom-right'));
                }
                $resultPaths['medium'] = $this->saveResource(
                    $mediumResource,
                    $targetDirs['medium'] . $baseName,
                    $mime,
                    $convertToWebp,
                    $quality,
                    $baseDir . '/medium/'
                );
                imagedestroy($mediumResource);

                $thumbResource = $this->resizeResource($resource, $width, $height, $thumbMaxWidth);
                if ($applyWatermark) {
                    $this->applyWatermark($thumbResource, $watermarkPath, (string) ($config['watermark_position'] ?? 'bottom-right'));
                }
                $resultPaths['thumb'] = $this->saveResource(
                    $thumbResource,
                    $targetDirs['thumb'] . $baseName,
                    $mime,
                    $convertToWebp,
                    $quality,
                    $baseDir . '/thumb/'
                );
                imagedestroy($thumbResource);
            } catch (\Throwable $e) {
                foreach ($resultPaths as $path) {
                    $absolutePath = $this->publicRoot . $path;
                    if (is_file($absolutePath)) {
                        @unlink($absolutePath);
                    }
                }
                imagedestroy($resource);
                throw $e;
            }

            imagedestroy($resource);

            return $resultPaths;
        } finally {
            $this->cleanupUploadedTempFile($tmpName);
        }
    }

    private function validateUpload(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Помилка завантаження файлу.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE_BYTES) {
            throw new \RuntimeException('Максимальний розмір одного зображення — 5MB.');
        }
    }

    private function detectMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $filePath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        return is_string($mime) ? $mime : '';
    }

    private function createResourceFromFile(string $filePath, string $mime)
    {
        if ($mime === 'image/jpeg') {
            return @imagecreatefromjpeg($filePath);
        }

        if ($mime === 'image/png') {
            return @imagecreatefrompng($filePath);
        }

        if ($mime === 'image/webp') {
            return @imagecreatefromwebp($filePath);
        }

        return false;
    }

    private function resizeResource($source, int $sourceWidth, int $sourceHeight, int $maxWidth)
    {
        if ($sourceWidth <= $maxWidth) {
            return $this->cloneResource($source, $sourceWidth, $sourceHeight);
        }

        $ratio = $sourceHeight / $sourceWidth;
        $newWidth = $maxWidth;
        $newHeight = (int) max(1, round($newWidth * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        return $resized;
    }

    private function cloneResource($source, int $width, int $height)
    {
        $copy = imagecreatetruecolor($width, $height);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        $transparent = imagecolorallocatealpha($copy, 0, 0, 0, 127);
        imagefilledrectangle($copy, 0, 0, $width, $height, $transparent);
        imagecopy($copy, $source, 0, 0, 0, 0, $width, $height);

        return $copy;
    }

    private function saveResource($resource, string $targetBasePath, string $sourceMime, bool $convertToWebp, int $quality, string $publicPrefix): string
    {
        $extension = 'webp';
        if (!$convertToWebp && $sourceMime === 'image/jpeg') {
            $extension = 'jpg';
        } elseif (!$convertToWebp && $sourceMime === 'image/png') {
            $extension = 'png';
        }

        $targetPath = $targetBasePath . '.' . $extension;
        $saved = false;

        if ($extension === 'webp') {
            imagesavealpha($resource, true);
            $saved = imagewebp($resource, $targetPath, $quality);
        } elseif ($extension === 'jpg') {
            $saved = imagejpeg($resource, $targetPath, $quality);
        } elseif ($extension === 'png') {
            imagesavealpha($resource, true);
            $pngCompression = (int) round((100 - $quality) * 9 / 100);
            $saved = imagepng($resource, $targetPath, max(0, min(9, $pngCompression)));
        }

        if (!$saved) {
            throw new \RuntimeException('Не вдалося зберегти зображення.');
        }

        return $publicPrefix . basename($targetPath);
    }

    private function applyWatermark($targetImage, string $watermarkPath, string $position): void
    {
        $absoluteWatermarkPath = $this->publicRoot . $watermarkPath;
        if (!is_file($absoluteWatermarkPath)) {
            return;
        }

        $watermark = @imagecreatefrompng($absoluteWatermarkPath);
        if (!$watermark) {
            return;
        }

        imagealphablending($targetImage, true);
        imagesavealpha($targetImage, true);

        $targetW = imagesx($targetImage);
        $targetH = imagesy($targetImage);
        $wmW = imagesx($watermark);
        $wmH = imagesy($watermark);

        if ($wmW <= 0 || $wmH <= 0 || $targetW <= 0 || $targetH <= 0) {
            imagedestroy($watermark);
            return;
        }

        $offset = 12;
        $x = $offset;
        $y = $offset;

        switch ($position) {
            case 'top-right':
                $x = $targetW - $wmW - $offset;
                $y = $offset;
                break;
            case 'center':
                $x = (int) round(($targetW - $wmW) / 2);
                $y = (int) round(($targetH - $wmH) / 2);
                break;
            case 'bottom-left':
                $x = $offset;
                $y = $targetH - $wmH - $offset;
                break;
            case 'bottom-right':
                $x = $targetW - $wmW - $offset;
                $y = $targetH - $wmH - $offset;
                break;
            case 'top-left':
            default:
                $x = $offset;
                $y = $offset;
                break;
        }

        $x = max(0, $x);
        $y = max(0, $y);

        imagecopy($targetImage, $watermark, $x, $y, 0, 0, $wmW, $wmH);
        imagedestroy($watermark);
    }

    private function cleanupUploadedTempFile(string $tmpName): void
    {
        if ($tmpName === '' || !is_file($tmpName)) {
            return;
        }

        if (is_uploaded_file($tmpName)) {
            @unlink($tmpName);
        }
    }
}
