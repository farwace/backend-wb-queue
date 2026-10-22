<?php

namespace App\Services;

use Zxing\QrReader;

/**
 * Серверное декодирование QR-кода с фото (сверка с клиентским результатом).
 * Использует khanamiryan/qrcode-detector-decoder (порт ZXing) через GD.
 * Большие фото с камеры читаются медленно и не всегда с первого раза,
 * поэтому пробуем несколько масштабов — от большего к меньшему.
 */
class QrCodeDecoder
{
    /** Максимальные стороны изображения (px), на которых пробуем декодирование. */
    private const SCAN_SIZES = [1600, 1000, 600];

    /**
     * Читает QR с файла. Возвращает текст кода или null, если распознать не удалось.
     */
    public static function decodeFile(string $absPath): ?string
    {
        $info = @getimagesize($absPath);
        if (!$info) {
            return null;
        }
        [$width, $height] = $info;

        foreach (self::SCAN_SIZES as $maxSize) {
            $im = self::loadResized($absPath, $width, $height, $maxSize);
            if ($im === null) {
                continue;
            }
            try {
                // GD принудительно: imagick в production-образе (Alpine) отсутствует
                $reader = new QrReader($im, QrReader::SOURCE_TYPE_RESOURCE, false);
                $text = $reader->text();
            } catch (\Throwable) {
                $text = false;
            }
            imagedestroy($im);

            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }
            // Масштаб меньше исходного размера уже ничего не добавит
            if ($maxSize >= max($width, $height)) {
                break;
            }
        }
        return null;
    }

    /**
     * Загружает изображение через GD и, если нужно, уменьшает до $maxSize px по большей стороне.
     */
    private static function loadResized(string $absPath, int $width, int $height, int $maxSize): ?\GdImage
    {
        $contents = @file_get_contents($absPath);
        if ($contents === false) {
            return null;
        }
        $src = @imagecreatefromstring($contents);
        if (!$src instanceof \GdImage) {
            return null;
        }
        if ($width <= $maxSize && $height <= $maxSize) {
            return $src;
        }
        $scale = $maxSize / max($width, $height);
        $dst = imagescale($src, max(1, (int)round($width * $scale)), max(1, (int)round($height * $scale)));
        imagedestroy($src);
        return $dst instanceof \GdImage ? $dst : null;
    }
}
