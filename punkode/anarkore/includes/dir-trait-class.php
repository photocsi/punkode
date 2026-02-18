<?php

namespace Punkode;

use Aws\S3\S3Client;           // <-- sì, gli use stanno sotto il namespace
use Aws\Exception\AwsException;
/*le funzioni di input tipo $this->option_pk() non sono collegate direttaemnte alla classe, ma inserite in classi che estendono input allora funzionano lo stesso*/

trait DIR_PK
{

    /**
     * EN: Wrapper to list "folders", choosing driver: 'local' or 's3'.
     * IT: Wrapper per elencare "cartelle", scegliendo il driver: 'local' o 's3'.
     *
     * $opts:
     *  - driver: 'local'|'s3' (default: costante PK_STORAGE_DRIVER o 'local')
     *  - bucket: (obbligatorio se driver = 's3')
     *  - region: es. 'eu-south-1' (default)
     */

    static function pk_show_folder($path)
    {
        // ✅ inizializzo $cartelle prima del ciclo
        $cartelle = [];
        if (is_dir($path)) {
            $result = scandir($path);
            $cartelle_tmp = array_diff($result, array('.', '..'));

            foreach ($cartelle_tmp as $value) {
                $cartelle[] = $value;
            }
        }

        return empty($cartelle) ? ['Nessuna cartella'] : $cartelle;
    }

    protected static function pk_show_folder_s3(string $path): array
    {
        // -----------------------
        // 1. Bucket / regione
        // -----------------------
        $bucket = getenv('S3_BUCKET') ?: '';
        $region = getenv('S3_REGION') ?: 'eu-south-1';

        if ($bucket === '') {
            echo "<script>console.log('[S3 DBG] Nessun bucket configurato');</script>";
            return ['Nessun bucket configurato'];
        }

        // -----------------------
        // 2. Path → keyPrefix
        //    - supporta sia:
        //      a) s3://bucket/albums/.../medium
        //      b) albums/.../medium
        // -----------------------
        $keyPrefix = '';

        if (strpos($path, 's3://') === 0) {
            // CASO A: "s3://bucket/albums/1/album/medium"
            $tmp   = substr($path, 5);          // "bucket/albums/1/album/medium"
            $parts = explode('/', $tmp, 2);     // ["bucket", "albums/1/album/medium"]

            if (count($parts) !== 2) {
                echo "<script>console.log('[S3 DBG] path malformato', " . json_encode($path) . ");</script>";
                return ['Nessuna cartella'];
            }

            $bucketFromPath = $parts[0];
            $keyPrefix      = $parts[1];

            // Se per assurdo in ENV non c’è il bucket, uso quello nel path
            if ($bucket === '' && $bucketFromPath !== '') {
                $bucket = $bucketFromPath;
            }
        } else {
            // CASO B: mi passi già il prefix logico "albums/1/album/medium"
            $keyPrefix = $path;
        }

        // Normalizza: niente slash all’inizio, uno alla fine
        $keyPrefix = trim($keyPrefix, '/');
        if ($keyPrefix !== '' && substr($keyPrefix, -1) !== '/') {
            $keyPrefix .= '/';
        }

        echo "<script>console.log('[S3 DBG] path origin', " . json_encode($path) . ");</script>";
        echo "<script>console.log('[S3 DBG] bucket', " . json_encode($bucket) . ");</script>";
        echo "<script>console.log('[S3 DBG] prefix', " . json_encode($keyPrefix) . ");</script>";

        // -----------------------
        // 3. Client S3 con chiavi da env
        // -----------------------
        $awsKey    = getenv('S3_KEY') ?: null;
        $awsSecret = getenv('S3_SECRET') ?: null;

        $clientConfig = [
            'version' => 'latest',
            'region'  => $region,
        ];

        if ($awsKey && $awsSecret) {
            $clientConfig['credentials'] = [
                'key'    => $awsKey,
                'secret' => $awsSecret,
            ];
        }

        try {
            $s3 = new S3Client($clientConfig);
        } catch (\Throwable $e) {
            error_log('[S3 DBG] Errore creazione client: ' . $e->getMessage());
            echo "<script>console.log('[S3 DBG] errore client S3', " . json_encode($e->getMessage()) . ");</script>";
            return ['Nessuna cartella (errore client S3)'];
        }

        // -----------------------
        // 4. listObjectsV2
        // -----------------------
        $folders = [];
        $params = [
            'Bucket'    => $bucket,
            'Prefix'    => $keyPrefix, // es. "albums/1/album/medium/"
            'Delimiter' => '/',        // fa emergere le "sottocartelle"
            'MaxKeys'   => 1000,
        ];

        try {
            do {
                $res = $s3->listObjectsV2($params);

                echo "<script>console.log('[S3 DBG] raw result', " . json_encode([
                    'KeyCount'    => $res['KeyCount']    ?? null,
                    'IsTruncated' => $res['IsTruncated'] ?? null,
                ]) . ");</script>";

                if (!empty($res['CommonPrefixes'])) {
                    foreach ($res['CommonPrefixes'] as $cp) {
                        $p    = rtrim($cp['Prefix'], '/'); // "albums/.../medium/NOME_CARTELLA"
                        $name = basename($p);             // "NOME_CARTELLA"
                        if ($name !== '') {
                            $folders[] = $name;
                            echo "<script>console.log('[S3 DBG] folder trovata', " . json_encode($name) . ");</script>";
                        }
                    }
                }

                if (!empty($res['IsTruncated']) && !empty($res['NextContinuationToken'])) {
                    $params['ContinuationToken'] = $res['NextContinuationToken'];
                } else {
                    unset($params['ContinuationToken']);
                }
            } while (!empty($params['ContinuationToken']));
        } catch (AwsException $e) {
            $msg = $e->getAwsErrorMessage() ?: $e->getMessage();
            error_log('[S3 DBG] AwsException in pk_show_folder_s3: ' . $msg);
            echo "<script>console.log('[S3 DBG] AwsException', " . json_encode($msg) . ");</script>";
            return ['Nessuna cartella (errore S3)'];
        }

        if (empty($folders)) {
            echo "<script>console.log('[S3 DBG] nessuna folder trovata per questo prefix');</script>";
            return ['Nessuna cartella S3'];
        }

        return $folders;
    }



    /**
     * =============================================================================
     * EN: Hybrid folder listing
     *     Returns ONLY folder names that exist BOTH:
     *     - locally under medium
     *     - on S3 under originali
     *
     * IT: Lista cartelle in modalità hybrid
     *     Ritorna SOLO i nomi cartella che esistono SIA:
     *     - in locale dentro medium
     *     - su S3 dentro originali
     * =============================================================================
     *
     * @param string $pathMediumLocal  Local FS path (medium) e.g. /var/www/.../medium
     * @param string $pathOriginaliS3  S3 base e.g. s3://bucket/prefix/originali
     *
     * @return array
     */
    protected static function pk_show_folder_hybrid(string $pathMediumLocal, string $pathOriginaliS3): array
    {
        $local = self::pk_show_folder($pathMediumLocal);
        $s3    = self::pk_show_folder_s3($pathOriginaliS3);

        // Normalizzo "nessuna cartella" in array vuoti per fare intersect
        $local = (is_array($local) && !in_array('Nessuna cartella', $local, true)) ? $local : [];
        $s3    = (is_array($s3) && !in_array('Nessuna cartella S3', $s3, true) && !in_array('Nessun bucket configurato', $s3, true)) ? $s3 : [];

        // SOLO cartelle presenti in entrambi
        $common = array_values(array_intersect($local, $s3));

        sort($common, SORT_NATURAL | SORT_FLAG_CASE);

        return empty($common) ? ['Nessuna cartella'] : $common;
    }

    /**
     * =============================================================================
     * EN: Recursively deletes a directory on local filesystem.
     * IT: Cancella ricorsivamente una cartella sul filesystem locale.
     *
     * WARNING / ATTENZIONE:
     * - Use cancella_dir_sicura() if you want a root constraint.
     * - Usa cancella_dir_sicura() se vuoi vincolare a una root consentita.
     * =============================================================================
     */
    public static function cancella_dir(string $dir): void
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        if ($dir === '' || !is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path) && !is_link($path)) {
                self::cancella_dir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    /**
     * =============================================================================
     * EN: Safe recursive local delete with optional allowed root constraint.
     * IT: Delete locale ricorsivo SICURO con vincolo opzionale di root consentita.
     *
     * @param string $dir            Absolute path to delete
     * @param string $rootConsentita Absolute allowed root; if provided, $dir must be inside it
     * =============================================================================
     */
    public static function cancella_dir_sicura(string $dir, string $rootConsentita = ''): void
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        if ($dir === '' || !is_dir($dir)) {
            return;
        }

        if ($rootConsentita !== '') {
            $rootReal = realpath($rootConsentita);
            $dirReal  = realpath($dir);

            if ($rootReal === false || $dirReal === false) {
                return;
            }

            $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $dirReal  = rtrim($dirReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            // EN: Refuse deletion outside allowed root
            // IT: Rifiuto cancellazione fuori dalla root consentita
            if (strpos($dirReal, $rootReal) !== 0) {
                return;
            }
        }

        // EN: Temporary workaround for deprecated warnings (your current note)
        // IT: Workaround temporaneo per warning deprecated (nota che hai già)
        $old = error_reporting(E_ALL & ~E_DEPRECATED);
        self::cancella_dir($dir);
        error_reporting($old);
    }

    /**
     * =============================================================================
     * EN: Build an S3 client using ENV keys if present.
     * IT: Crea un client S3 usando le chiavi ENV se presenti.
     * =============================================================================
     */
    protected static function crea_client_s3(): ?S3Client
    {
        $region = getenv('S3_REGION') ?: 'eu-south-1';

        $awsKey    = getenv('S3_KEY') ?: null;
        $awsSecret = getenv('S3_SECRET') ?: null;

        $clientConfig = [
            'version' => 'latest',
            'region'  => $region,
        ];

        if ($awsKey && $awsSecret) {
            $clientConfig['credentials'] = [
                'key'    => $awsKey,
                'secret' => $awsSecret,
            ];
        }

        try {
            return new S3Client($clientConfig);
        } catch (\Throwable $e) {
            error_log('[S3 DBG] Errore creazione client: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * =============================================================================
     * EN: Normalizes input path into an S3 prefix (no leading slash, ends with /).
     * IT: Normalizza un path in un prefix S3 (no slash iniziale, termina con /).
     *
     * Supports:
     * - "s3://bucket/albums/1/album"
     * - "albums/1/album"
     *
     * Returns array: [bucket, prefix]
     * - bucket is taken from ENV unless provided in s3:// path
     * =============================================================================
     */
    protected static function normalizza_bucket_prefix_s3(string $pathOrPrefix): array
    {
        $bucketEnv = getenv('S3_BUCKET') ?: '';
        $bucket    = $bucketEnv;
        $prefix    = '';

        if (strpos($pathOrPrefix, 's3://') === 0) {
            $tmp   = substr($pathOrPrefix, 5);      // "bucket/...."
            $parts = explode('/', $tmp, 2);         // ["bucket", "rest"]

            if (count($parts) === 2) {
                $bucketFromPath = $parts[0];
                $prefix         = $parts[1];

                if ($bucket === '' && $bucketFromPath !== '') {
                    $bucket = $bucketFromPath;
                }
            }
        } else {
            $prefix = $pathOrPrefix;
        }

        $prefix = trim($prefix, '/');
        if ($prefix !== '' && substr($prefix, -1) !== '/') {
            $prefix .= '/';
        }

        return [$bucket, $prefix];
    }

    /**
     * =============================================================================
     * EN: Deletes all objects under an S3 prefix (recursive "folder delete").
     * IT: Cancella tutti gli oggetti sotto un prefix S3 (delete cartella ricorsiva).
     *
     * @param string $bucket Bucket name (if empty, taken from ENV)
     * @param string $prefix Prefix like "albums/1/album/" (will be normalized)
     * =============================================================================
     */
    public static function cancella_prefix_s3(string $bucket, string $prefix): void
    {
        if ($bucket === '') {
            $bucket = getenv('S3_BUCKET') ?: '';
        }
        if ($bucket === '') {
            error_log('[S3 DBG] cancella_prefix_s3: bucket mancante');
            return;
        }

        $prefix = trim($prefix, '/');
        if ($prefix !== '' && substr($prefix, -1) !== '/') {
            $prefix .= '/';
        }

        $s3 = self::crea_client_s3();
        if (!$s3) {
            return;
        }

        $continuationToken = null;

        try {
            do {
                $params = [
                    'Bucket'  => $bucket,
                    'Prefix'  => $prefix,
                    'MaxKeys' => 1000,
                ];
                if ($continuationToken) {
                    $params['ContinuationToken'] = $continuationToken;
                }

                $res = $s3->listObjectsV2($params);

                $keys = [];
                if (!empty($res['Contents'])) {
                    foreach ($res['Contents'] as $obj) {
                        if (!empty($obj['Key'])) {
                            $keys[] = ['Key' => (string)$obj['Key']];
                        }
                    }
                }

                // Delete in chunks of 1000 (AWS limit per deleteObjects)
                if (!empty($keys)) {
                    $chunks = array_chunk($keys, 1000);
                    foreach ($chunks as $chunk) {
                        $s3->deleteObjects([
                            'Bucket' => $bucket,
                            'Delete' => [
                                'Objects' => $chunk,
                                'Quiet'   => true,
                            ],
                        ]);
                    }
                }

                $isTruncated = !empty($res['IsTruncated']);
                $continuationToken = ($isTruncated && !empty($res['NextContinuationToken']))
                    ? (string)$res['NextContinuationToken']
                    : null;
            } while ($continuationToken !== null);
        } catch (AwsException $e) {
            $msg = $e->getAwsErrorMessage() ?: $e->getMessage();
            error_log('[S3 DBG] AwsException in cancella_prefix_s3: ' . $msg);
        }
    }

    /**
     * EN: Builds allowed album prefix "albums/{id_azienda}/{tabella}".
     * IT: Costruisce il prefix consentito "albums/{id_azienda}/{tabella}".
     */
    protected static function prefix_album_consentito(int $idAzienda, string $tabella): string
    {
        $tabella = trim($tabella);
        // EN: hard sanitize: only letters, numbers, underscore, dash
        // IT: sanitizzazione dura: solo lettere, numeri, underscore, dash
        $tabella = preg_replace('/[^a-zA-Z0-9_-]/', '', $tabella) ?: '';

        if ($idAzienda <= 0 || $tabella === '') {
            return '';
        }

        return 'albums/' . $idAzienda . '/' . $tabella;
    }

    /**
     * EN: Normalize a filesystem path (no need for realpath()).
     * IT: Normalizza un path filesystem (senza dipendere da realpath()).
     */
    private static function pk_norm_path(string $path, bool $trailSlash = false): string
    {
        $path = trim($path);
        if ($path === '') return '';

        // uniforma separatori
        $path = str_replace(['\\', '//'], ['/', '/'], $path);

        // rimuove "./"
        $path = preg_replace('#/\.(/|$)#', '/', $path);

        // collassa "a/b/../c" (best-effort)
        $parts = [];
        foreach (explode('/', $path) as $p) {
            if ($p === '' || $p === '.') continue;
            if ($p === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $p;
        }

        // mantieni leading slash se c’era
        $leading = (substr($path, 0, 1) === '/') ? '/' : '';
        $out = $leading . implode('/', $parts);

        if ($trailSlash) {
            $out = rtrim($out, '/') . '/';
        } else {
            $out = rtrim($out, '/');
        }

        return $out;
    }

    /**
     * EN: Check if $path is inside $scope (both must be normalized with trailing slash).
     * IT: Controlla se $path è dentro $scope (entrambi normalizzati con slash finale).
     */
    private static function pk_is_inside(string $path, string $scope): bool
    {
        $path  = self::pk_norm_path($path, true);
        $scope = self::pk_norm_path($scope, true);

        return $path === $scope || str_starts_with($path, $scope);
    }

    /**
     * EN: Recursive delete (directory + all files).
     * IT: Cancellazione ricorsiva (cartella + contenuto).
     */
    private static function cancella_dir_recursive(string $dir): void
    {
        $dir = self::pk_norm_path($dir, false);

        if ($dir === '' || !is_dir($dir)) {
            return;
        }

        $items = @scandir($dir);
        if (!is_array($items)) return;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $full = $dir . '/' . $item;

            if (is_dir($full) && !is_link($full)) {
                self::cancella_dir_recursive($full);
                @rmdir($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($dir);
    }

    /**
     * =============================================================================
     * EN: "Delete everything" helper with strict album scope constraints.
     * IT: Helper "cancella tutto" con vincolo stretto allo scope dell’album.
     *
     * @param array $args Keys:
     *  - id_azienda: int
     *  - tabella: string
     *
     *  - dir_locale: string (absolute local dir to delete)
     *  - root_consentita: string (absolute root where albums live, e.g. D20_DATA_ROOT)
     *
     *  - s3_path: string (either "albums/..../.." or "s3://bucket/albums/..../..")
     *  - s3_bucket: string
     * =============================================================================
     */
    public static function cancella_tutto(array $args): void
    {
        $idAzienda = (int)($args['id_azienda'] ?? 0);
        $tabella   = (string)($args['tabella'] ?? '');

        $tabellaSafe = preg_replace('/[^a-zA-Z0-9_-]/', '', $tabella) ?: '';
        if ($tabellaSafe === '') {
            return;
        }


        // -------------------------
        // 1) LOCALE (solo dentro /var/www/d20/img/{id_azienda}/{tabella})
        // -------------------------
        $dirLocale      = (string)($args['dir_locale'] ?? '');
        $rootConsentita = (string)($args['root_consentita'] ?? '');

        // Normalizza root consentita (deve finire con slash)
        $rootConsentita = self::pk_norm_path($rootConsentita, true);

        // Scope consentito: /var/www/d20/img/{id}/ + tabella
        $allowedLocalScope = self::pk_norm_path($rootConsentita . $tabellaSafe, true);

        // Se dir_locale non è affidabile, ricostruiscilo dallo scope
        if ($dirLocale === '') {
            $dirLocale = $allowedLocalScope;
        }
        $dirLocaleNorm = self::pk_norm_path($dirLocale, true);

        // 🔍 DEBUG opzionale (poi lo togli)
        // error_log('[D20_DELETE][LOCAL] dir=' . $dirLocaleNorm . ' allowed=' . $allowedLocalScope);

        // Cancella solo se dirLocale è dentro allowedLocalScope (o uguale)
        if (!self::pk_is_inside($dirLocaleNorm, $allowedLocalScope)) {
            // error_log('[D20_DELETE][LOCAL][BLOCKED] dir fuori scope');
            return;
        }

        self::cancella_dir_recursive($dirLocaleNorm);

        // -------------------------
        // 2) S3 (solo prefix albums/{id}/{tabella}/...)
        // -------------------------
        $s3Path   = (string)($args['s3_path'] ?? '');
        $bucketIn = (string)($args['s3_bucket'] ?? '');

        // EN: robust bucket read fallback
        // IT: lettura bucket robusta
        $bucketIn = $bucketIn !== '' ? $bucketIn : (string)($_ENV['S3_BUCKET'] ?? getenv('S3_BUCKET') ?? '');

        if ($s3Path !== '' && $bucketIn !== '') {
            // Normalize input into bucket + prefix
            [$bucketNorm, $prefixNorm] = self::normalizza_bucket_prefix_s3($s3Path);

            $bucket = $bucketIn !== '' ? $bucketIn : $bucketNorm;

            // Allowed S3 scope strictly albums/{id}/{tabella}/
            $allowedS3 = 'albums/' . $idAzienda . '/' . $tabellaSafe . '/';

            $prefixNormCheck = rtrim(ltrim((string)$prefixNorm, '/'), '/') . '/';

            if ($bucket !== '' && $prefixNormCheck !== '' && strpos($prefixNormCheck, $allowedS3) === 0) {
                self::cancella_prefix_s3($bucket, $prefixNorm);
            }
        }
    }
}
