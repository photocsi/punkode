<?php
/*
|-----------------------------------------------------------------------
| FILE: core/class-punk-background_work.php
| PURPOSE:
|   EN: Queue & background orchestration (enqueue, launch, status).
|   IT: Orchestrazione in coda: accodare job, lanciarli, leggere stato.
| NOTES:
|   - Pure PHP: il worker fa il lavoro pesante (resize+save).
|-----------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Core;

class PUNK_BackgroundWork
{
    /** @var string absolute path to the queue directory */
    protected string $queueDir;

    /** @var string absolute path to the worker script (CLI) */
    protected string $workerScript;

    /** @var string|null custom PHP binary for CLI (default auto-detect) */
    protected ?string $phpBinary;

    /**
     * @param string      $queueDir     Absolute path to queue dir (es. __DIR__.'/../queue')
     * @param string      $workerScript Absolute path to worker_once.php
     * @param string|null $phpBinary    PHP CLI binary; null = auto
     */
    public function __construct(string $queueDir, string $workerScript, ?string $phpBinary = null)
    {
        $this->queueDir     = rtrim($queueDir, "/\\");
        $this->workerScript = $workerScript;
        $this->phpBinary    = $phpBinary;
        $this->punk_ensure_dir($this->queueDir);
    }

    /**
     * EN: Enqueue a background job from stored originals.
     * IT: Accoda un job a partire dagli originali già salvati.
     *
     * $originals: output di punk_store_originals()
     * $sizes:     es. ['piccola'=>['w'=>800,'dir'=>'piccole','suffix'=>false], ...]
     * $opts:
     *   - dest_base  (string, required)  base dir per versioni finali
     *   - overwrite  (bool)              sovrascrittura versioni
     *   - disk       (mixed)             adapter/disk (opzionale)
     *   - group_path (string|null)       per sottocartelle per batch
     *   - dest_dir   (string|null)       tmp per resize (default sys tmp /nnf)
     */
    public function punk_enqueue_resize_from_originals(array $originals, array $sizes, array $opts = []): array
    {
        $destBase = rtrim((string)($opts['dest_base'] ?? ''), "/\\");
        if ($destBase === '') {
            // Consenti dest_base vuoto se tutte le sizes hanno 'dest'
            $allHaveDest = true;
            foreach ($sizes as $sz) {
                if (empty($sz['dest'])) {
                    $allHaveDest = false;
                    break;
                }
            }
            if (!$allHaveDest) {
                return ['ok' => false, 'error' => 'dest_base missing and some sizes have no dest'];
            }
        }


        // Filtra voci valide
        $items = [];
        foreach ($originals as $row) {
            $p = $row['original_path'] ?? null;
            if ($p && is_string($p)) {
                $items[] = [
                    'original_path' => $p,
                    'original_name' => $row['original_name'] ?? basename($p),
                    'orig_base'     => $row['orig_base'] ?? pathinfo($p, PATHINFO_FILENAME),
                ];
            }
        }
        if (!$items) return ['ok' => false, 'error' => 'no originals to process'];

        $jobId = bin2hex(random_bytes(12));
        $payload = [
            'job_id'     => $jobId,
            'created_at' => time(),
            'sizes'      => $sizes,
            'opts'       => [
                'dest_base'  => $destBase,
                'overwrite'  => (bool)($opts['overwrite'] ?? false),
                'disk'       => $opts['disk'] ?? null,
                'group_path' => $opts['group_path'] ?? null,
                'dest_dir'   => $opts['dest_dir'] ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nnf'),
            ],
            'items'      => $items,
        ];

        $jobFile = $this->punk_job_paths($jobId)['job'];
        $ok = @file_put_contents($jobFile, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if ($ok === false) {
            return ['ok' => false, 'error' => 'cannot write queue file'];
        }

        return ['ok' => true, 'job_id' => $jobId, 'count' => count($items)];
    }

    /**
     * EN: Launch the worker for a job (best-effort, async).
     * IT: Avvia il worker per un job (best-effort, asincrono).
     */
    public function punk_launch_worker(string $jobId): array
    {
        $paths = $this->punk_job_paths($jobId);
        if (!is_file($paths['job'])) {
            return ['ok' => false, 'error' => 'job not found'];
        }

        // PHP CLI
        $php = $this->phpBinary ?: (defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php');
        if (stripos($php, 'fpm') !== false) {
            $php = 'php';
        } // forza CLI se punta a FPM

        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWin) {
            // Windows: usa start /B e NUL, evita escapeshellarg con apici singoli
            $cmd = 'start "" /B "' . $php . '" "' . $this->workerScript . '" ' . $jobId . ' > NUL 2>&1';
        } else {
            // Unix-like
            $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($this->workerScript) . ' ' . escapeshellarg($jobId) . ' > /dev/null 2>&1 &';
        }

        // Prova a lanciare
        $ok = @exec($cmd) !== false;

        return $ok ? ['ok' => true, 'cmd' => $cmd]
            : ['ok' => false, 'cmd' => $cmd, 'error' => 'exec disabled or failed'];
    }


    /**
     * EN: Get current status: queued|running|done|error|missing
     * IT: Stato corrente del job: queued|running|done|error|missing
     */
    public function punk_status(string $jobId): array
    {
        $p = $this->punk_job_paths($jobId);
        if (is_file($p['done'])) {
            $json = @file_get_contents($p['done']);
            return ['ok' => true, 'status' => 'done', 'data' => $json ? json_decode($json, true) : null];
        }
        if (is_file($p['err'])) {
            return ['ok' => false, 'status' => 'error', 'error' => trim(@file_get_contents($p['err']) ?: 'unknown')];
        }
        if (is_file($p['running'])) {
            return ['ok' => true, 'status' => 'running'];
        }
        if (is_file($p['job'])) {
            return ['ok' => true, 'status' => 'queued'];
        }
        return ['ok' => false, 'status' => 'missing'];
    }

    /**
     * EN: Launch all pending jobs (helpful for cron).
     * IT: Avvia tutti i job in attesa (utile per cron).
     */
    public function punk_scan_and_launch_pending(): array
    {
        $launched = 0;
        $errors = 0;
        $list = [];
        foreach (glob($this->queueDir . '/*.json') as $jobFile) {
            $jobId = basename($jobFile, '.json');
            $st = $this->punk_status($jobId);
            if (($st['status'] ?? '') !== 'queued') continue;
            $res = $this->punk_launch_worker($jobId);
            $list[] = ['job_id' => $jobId, 'launched' => $res['ok'], 'error' => $res['error'] ?? null];
            $launched += $res['ok'] ? 1 : 0;
            $errors   += $res['ok'] ? 0 : 1;
        }
        return ['ok' => true, 'launched' => $launched, 'errors' => $errors, 'jobs' => $list];
    }

    // ---------------- internals ----------------

    protected function punk_ensure_dir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    protected function punk_job_paths(string $jobId): array
    {
        return [
            'job'     => $this->queueDir . DIRECTORY_SEPARATOR . $jobId . '.json',
            'running' => $this->queueDir . DIRECTORY_SEPARATOR . $jobId . '.running',
            'done'    => $this->queueDir . DIRECTORY_SEPARATOR . $jobId . '.done.json',
            'err'     => $this->queueDir . DIRECTORY_SEPARATOR . $jobId . '.err.txt',
        ];
    }
}
