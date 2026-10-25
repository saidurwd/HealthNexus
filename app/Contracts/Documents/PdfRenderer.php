<?php

namespace App\Contracts\Documents;

interface PdfRenderer
{
    /** @param  string  $paper  thermal80 | a4 | a5 | label (from config/documents.php) */
    public function render(string $html, string $paper): string;
}
