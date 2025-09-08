<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-image-wp.php
| DESCRIPTION: EN: WP editor resize, saves to $dest. IT: resize via editor WP, salva su $dest.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\wp;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ResizeInterFace;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_ResizeLogic;

class PUNK_ResizeWp implements PUNK_ResizeInterFace
{
    public function punk_resizeTo(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        if (!function_exists('wp_get_image_editor')) return false;
        $edit_img = \wp_get_image_editor($src);
        if (\is_wp_error($edit_img)) return false;
        $edit_img->set_quality($quality);
        $edit_img->resize($w, $h, false);
        PUNK_ResizeLogic::punk_ensureDir($dest);
        $saved = $edit_img->save($dest);
        if (\is_wp_error($saved)) return false;
        return ['path' => $saved['path'], 'width' => $saved['width'], 'height' => $saved['height']];
    }
}
