<?php

namespace App\Service;

use App\Entity\PdfTheme;

class PdfThemeLayoutService
{
    /**
     * @return array<string, float>
     */
    public function getDefaultAnchors(string $documentType): array
    {
        $base = [
            'header_x' => 15.0,
            'header_y' => 18.0,
            'header_w' => 180.0,
            'header_h' => 24.0,
            'title_x' => 15.0,
            'title_y' => 8.0,
            'title_w' => 180.0,
            'title_h' => 10.0,
            'client_x' => 15.0,
            'client_y' => 48.0,
            'client_w' => 180.0,
            'client_h' => 36.0,
            'table_x' => 15.0,
            'table_y' => 92.0,
            'table_w' => 180.0,
            'table_h' => 120.0,
            'totals_x' => 120.0,
            'totals_y' => 228.0,
            'totals_w' => 70.0,
            'totals_h' => 46.0,
            'footer_x' => 15.0,
            'footer_y' => 278.0,
            'footer_w' => 180.0,
            'footer_h' => 18.0,
            'signature_x' => 145.0,
            'signature_y' => 282.0,
            'signature_w' => 50.0,
            'signature_h' => 10.0,
            'header_title_font_size' => 14.0,
            'font_size' => 10.0,
            'line_height' => 5.0,
        ];

        if ($documentType === PdfTheme::TYPE_DELIVERY) {
            $base['totals_y'] = 245.0;
        }

        return $base;
    }

    /**
     * @param array<string, mixed> $rawAnchors
     * @return array<string, float>
     */
    public function normalize(string $documentType, array $rawAnchors): array
    {
        $defaults = $this->getDefaultAnchors($documentType);

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $rawAnchors) && is_numeric($rawAnchors[$key])) {
                $defaults[$key] = (float) $rawAnchors[$key];
            }
        }

        return $defaults;
    }
}
