<?php

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Core;

/**
 * PUNK_PathGuard
 * EN: Centralized sanity checks for filesystem paths (source/destination).
 * IT: Controlli centralizzati su path filesystem (sorgente/destinazione).
 */
final class PUNK_PathController
{
    /** EN: Normalize path (keep drive letters), collapse slashes. IT: Normalizza path. */
    public static function normalize(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path));
        // Non togliere il trailing su "X:" o sulla sola root
        if ($path !== '' && !preg_match('#^[A-Za-z]:$#', $path) && $path !== DIRECTORY_SEPARATOR) {
            $path = rtrim($path, DIRECTORY_SEPARATOR);
        }
        return $path;
    }

    /** EN: True if $child is the same as or inside $parent. IT: Vero se $child coincide o sta dentro $parent. */
    public static function isSubpath(string $child, string $parent): bool
    {
        $childNorm  = strtolower(self::normalize($child));
        $parentNorm = strtolower(self::normalize($parent));
        if ($childNorm === $parentNorm) return true;
        return str_starts_with($childNorm . DIRECTORY_SEPARATOR, $parentNorm . DIRECTORY_SEPARATOR);
    }

    /**
     * EN: Validate a source folder (exists, readable, not inside forbidden roots).
     * IT: Valida una cartella sorgente (esiste, leggibile, non dentro radici vietate).
     *
     * @param string $path       Sorgente (es. "F:\" o "C:\Foto")
     * @param string[] $denyRoots Cartelle da evitare (es. [$path_hd, $path_medium])
     * @return array{ok:bool, path?:string, error?:string}
     */
    public static function validateSource(string $path, array $denyRoots = []): array
    {
        $norm = self::normalize($path);

        // 👇 Se è solo "X:" (lettera unità), appendi la backslash per sicurezza
        if (preg_match('#^[A-Za-z]:$#', $norm) === 1) {
            $norm .= DIRECTORY_SEPARATOR;
        }

        if ($norm === '' || !is_dir($norm)) {
            return ['ok' => false, 'error' => 'Percorso non disponibile'];
        }
        if (!is_readable($norm)) {
            return ['ok' => false, 'error' => 'Sorgente non leggibile'];
        }
        foreach ($denyRoots as $root) {
            if (!$root) continue;
            if (self::isSubpath($norm, (string)$root)) {
                return ['ok' => false, 'error' => 'La sorgente è (o sta dentro) una cartella di destinazione'];
            }
        }
        return ['ok' => true, 'path' => $norm];
    }
}
