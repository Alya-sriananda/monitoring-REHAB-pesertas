<?php

namespace App\Services;

use App\Models\TemplatePesan;

class TemplatePesanService
{
    /**
     * Render a message template with the provided context.
     *
     * @param  array  $context  ['nama' => '', 'noka' => '', 'periode' => '', 'nominal' => '']
     */
    public function render(TemplatePesan $template, array $context): string
    {
        $message = $template->isi_template;

        // Default empty strings for missing context keys
        $nama = $context['nama'] ?? '';
        $noka = $context['noka'] ?? '';
        $periode = $context['periode'] ?? '';
        $nominal = isset($context['nominal']) ? 'Rp '.number_format($context['nominal'], 0, ',', '.') : '';

        // Replace placeholders safely
        $message = str_replace('{nama}', $nama, $message);
        $message = str_replace('{noka}', $noka, $message);
        $message = str_replace('{periode}', $periode, $message);
        $message = str_replace('{nominal}', $nominal, $message);

        return $message;
    }
}
