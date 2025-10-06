<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Parses the HTTP_ACCEPT_LANGUAGE header to determine the user's preferred language.
 *
 * This function analyzes the 'Accept-Language' header string sent by the browser,
 * extracts language tags and their associated quality factor values (q-factors),
 * and returns them as an associative array sorted by priority.
 *
 * @param string $accept_language The raw 'Accept-Language' header string.
 * @return array An associative array where keys are language codes (e.g., 'en-US', 'en')
 * and values are their quality factors (e.g., 1.0, 0.9). The array is
 * sorted in descending order by quality factor.
 */
if (!function_exists('parse_accept_language')) {
    function parse_accept_language(string $accept_language): array
    {
        $languages = [];

        // Split the header string into individual language parts
        // e.g., "en-US,en;q=0.9,fr;q=0.8" -> ["en-US", "en;q=0.9", "fr;q=0.8"]
        $language_parts = explode(',', $accept_language);

        foreach ($language_parts as $part) {
            // Split each part into language code and quality factor
            // e.g., "en;q=0.9" -> ["en", "q=0.9"]
            $elements = explode(';', trim($part));
            $lang_code = strtolower(trim($elements[0]));
            $quality = 1.0; // Default quality factor

            if (isset($elements[1])) {
                $q_factor_str = trim($elements[1]);
                // Check if it is a valid quality factor string "q=..."
                if (strpos($q_factor_str, 'q=') === 0) {
                    $quality = (float) substr($q_factor_str, 2);
                }
            }

            // Add the language code and its quality to the array
            if ($lang_code) {
                $languages[$lang_code] = $quality;
            }
        }

        // Sort the languages by quality factor in descending order
        arsort($languages);

        return $languages;
    }
}