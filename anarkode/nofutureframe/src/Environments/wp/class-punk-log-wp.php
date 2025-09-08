<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-log-wp.php
| DESCRIPTION: EN: WP simple file logger. IT: logger semplice su file per WP.
|--------------------------------------------------------------------------
*/
namespace Punkode\Anarkode\NoFutureFrame\Environments\wp;
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_LogServiceInterface;
class PUNK_Log_Wp implements PUNK_LogServiceInterface {
    public function punk_log(string $m,string $l='info'): void {
        if(!function_exists('wp_upload_dir')) return; $u=\wp_upload_dir();
        $dir=rtrim($u['basedir'],'/\\').'/punkode'; if(!is_dir($dir)) @mkdir($dir,0755,true);
        $f=$dir.'/nofutureframe.log'; $d=date('Y-m-d H:i:s'); @file_put_contents($f,"[$d][".strtoupper($l)."] $m\n",FILE_APPEND);
    }
}