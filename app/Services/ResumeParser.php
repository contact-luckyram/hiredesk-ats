<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Lightweight resume parser: pulls text from TXT, DOCX and simple PDFs, then
 * finds email, phone, years of experience and known skills.
 */
class ResumeParser
{
    public function extractText(string $path, ?string $extension = null): string
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));

        try {
            return match ($extension) {
                'txt', 'md' => (string) file_get_contents($path),
                'docx' => $this->docx($path),
                'pdf' => $this->pdf($path),
                default => '',
            };
        } catch (\Throwable) {
            return '';
        }
    }

    public function parse(string $text): array
    {
        preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $email);
        preg_match('/(\+?1[\s.-]?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', $text, $phone);
        preg_match('/(\d{1,2})\+?\s+years?/i', $text, $years);

        $skills = collect(config('hiredesk.skills'))
            ->filter(fn (string $skill) => preg_match('/(?<![A-Za-z])'.preg_quote($skill, '/').'(?![A-Za-z])/i', $text))
            ->values()
            ->all();

        return [
            'email' => $email[0] ?? null,
            'phone' => $phone[0] ?? null,
            'years_experience' => isset($years[1]) ? (int) $years[1] : null,
            'skills' => $skills,
        ];
    }

    private function docx(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return trim(html_entity_decode(strip_tags(str_replace(['</w:p>', '<w:tab/>'], ["\n", ' '], $xml))));
    }

    /** Reads text from uncompressed or Flate-compressed PDF text streams. */
    private function pdf(string $path): string
    {
        $data = (string) file_get_contents($path);
        $text = '';

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $streams);
        foreach ($streams[1] as $stream) {
            $decoded = @gzuncompress($stream) ?: (@gzinflate($stream) ?: $stream);
            preg_match_all('/\((.*?)(?<!\\\\)\)\s*Tj|\[(.*?)\]\s*TJ/s', $decoded, $m, PREG_SET_ORDER);
            foreach ($m as $part) {
                $chunk = $part[1] !== '' ? $part[1] : preg_replace('/\)\s*-?\d+\s*\(/', '', trim($part[2] ?? '', '()'));
                $text .= stripcslashes((string) $chunk).' ';
            }
            $text .= "\n";
        }

        return trim(Str::squish($text)) !== '' ? $text : '';
    }
}
