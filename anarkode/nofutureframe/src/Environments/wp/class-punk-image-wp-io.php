<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-image-wp-io.php
| DESCRIPTION: EN: multi-source input helper. IT: helper input multi-sorgente.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\Wp;

class PUNK_ResizeWp
{
    public function punk_analyzeInput($input): array
    {
        $is_array = is_array($input);
        $item = $is_array ? reset($input) : $input;
        $type = 'unknown';
        if (is_int($item) || ctype_digit((string)$item)) $type = 'wp_attachment_id';
        elseif (filter_var($item, FILTER_VALIDATE_URL)) $type = 'url';
        elseif (is_string($item) && file_exists($item)) $type = 'local_path';
        elseif (is_string($item) && function_exists('is_uploaded_file') && @is_uploaded_file($item)) $type = 'uploaded_temp_file';
        return ['is_array' => $is_array, 'type' => $type];
    }
    public function punk_resolvePath($item, string $type): string|false
    {
        switch ($type) {
            case 'url':
                if (!function_exists('download_url')) return false;
                $tmp = \download_url($item);
                return (\is_wp_error($tmp)) ? false : $tmp;
            case 'local_path':
                return file_exists($item) ? $item : false;
            case 'wp_attachment_id':
                if (!function_exists('get_attached_file')) return false;
                $p = \get_attached_file((int)$item);
                return ($p && file_exists($p)) ? $p : false;
            case 'uploaded_temp_file':
                return (function_exists('is_uploaded_file') && @is_uploaded_file($item)) ? $item : false;
        }
        return false;
    }
    public function punk_resizeMany($image_input, callable $resizer): array
    {
        $out = [];
        $a = $this->punk_analyzeInput($image_input);
        $items = $a['is_array'] ? $image_input : [$image_input];
        $type = $a['type'];
        foreach ($items as $it) {
            $p = $this->punk_resolvePath($it, $type);
            if ($p === false) continue;
            $r = $resizer($p);
            if ($r !== false) $out[] = $r;
        }
        return $out;
    }
}
