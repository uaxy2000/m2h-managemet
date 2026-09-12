<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

class DatabaseBackup extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'Backup the database to Amazon S3 (no extra packages required)';

    private string $awsKey;
    private string $awsSecret;
    private string $region;
    private string $bucket;
    private string $prefix;

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $this->awsKey    = config('services.backup.key');
        $this->awsSecret = config('services.backup.secret');
        $this->region    = config('services.backup.region', 'eu-west-1');
        $this->bucket    = config('services.backup.bucket', 'zrr01');
        $this->prefix    = config('services.backup.prefix', 'm2h-backups');

        if (!$this->awsKey || !$this->awsSecret) {
            $this->error('BACKUP_AWS_KEY or BACKUP_AWS_SECRET not configured.');
            return 1;
        }

        $this->info('Dumping database...');
        $sql = $this->dumpDatabase();
        $this->info('Dump size: ' . number_format(strlen($sql)) . ' bytes');

        $gz = gzencode($sql, 9);
        $this->info('Compressed: ' . number_format(strlen($gz)) . ' bytes');

        $filename = date('Y-m-d_H-i-s') . '.sql.gz';
        $s3Key    = $this->prefix . '/' . $filename;

        $this->info("Uploading to s3://{$this->bucket}/{$s3Key} ...");
        $this->s3Put($s3Key, $gz, 'application/gzip');
        $this->info('Upload complete.');

        $deleted = $this->pruneOldBackups(60);
        $this->info("Pruned {$deleted} backup(s) older than 60 days.");

        return 0;
    }

    // ─── SQL Dump ─────────────────────────────────────────────────────────────

    private function dumpDatabase(): string
    {
        $pdo  = DB::connection()->getPdo();
        $sql  = "-- M2H Management — DB Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $sql   .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql   .= $create['Create Table'] . ";\n\n";

            $stmt  = $pdo->query("SELECT * FROM `{$table}`");
            $chunk = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $values  = array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    $row
                );
                $chunk[] = '(' . implode(', ', $values) . ')';

                if (count($chunk) === 500) {
                    $sql   .= "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $chunk) . ";\n";
                    $chunk  = [];
                }
            }

            if ($chunk) {
                $sql .= "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $chunk) . ";\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    // ─── Rotation ─────────────────────────────────────────────────────────────

    private function pruneOldBackups(int $days): int
    {
        $cutoff  = new \DateTime("-{$days} days");
        $objects = $this->s3List();
        $deleted = 0;

        foreach ($objects as $obj) {
            if (new \DateTime($obj['LastModified']) < $cutoff) {
                $this->s3Delete($obj['Key']);
                $this->line("  Deleted: {$obj['Key']}");
                $deleted++;
            }
        }

        return $deleted;
    }

    // ─── S3 API (pure PHP — no SDK required) ─────────────────────────────────

    private function s3Host(): string
    {
        return "{$this->bucket}.s3.{$this->region}.amazonaws.com";
    }

    private function s3Put(string $key, string $body, string $contentType): void
    {
        $now    = gmdate('Ymd\THis\Z');
        $date   = substr($now, 0, 8);
        $sha    = hash('sha256', $body);
        $host   = $this->s3Host();
        $uri    = '/' . $this->encodeUri($key);

        $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';
        $canonical     = "PUT\n{$uri}\n\ncontent-type:{$contentType}\nhost:{$host}\nx-amz-content-sha256:{$sha}\nx-amz-date:{$now}\n\n{$signedHeaders}\n{$sha}";
        $auth          = $this->buildAuth('PUT', $date, $now, $canonical, $signedHeaders);

        $ctx = stream_context_create(['http' => [
            'method'        => 'PUT',
            'header'        => "Authorization: {$auth}\r\nContent-Type: {$contentType}\r\nx-amz-content-sha256: {$sha}\r\nx-amz-date: {$now}\r\nContent-Length: " . strlen($body),
            'content'       => $body,
            'ignore_errors' => true,
        ]]);

        $res = file_get_contents("https://{$host}{$uri}", false, $ctx);

        if (!str_contains($http_response_header[0] ?? '', '200')) {
            throw new RuntimeException("S3 PUT failed: " . ($http_response_header[0] ?? 'no response') . "\n" . $res);
        }
    }

    private function s3List(): array
    {
        $now    = gmdate('Ymd\THis\Z');
        $date   = substr($now, 0, 8);
        $sha    = hash('sha256', '');
        $host   = $this->s3Host();
        $query  = 'list-type=2&prefix=' . rawurlencode($this->prefix . '/');

        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
        $canonical     = "GET\n/\n{$query}\nhost:{$host}\nx-amz-content-sha256:{$sha}\nx-amz-date:{$now}\n\n{$signedHeaders}\n{$sha}";
        $auth          = $this->buildAuth('GET', $date, $now, $canonical, $signedHeaders);

        $ctx = stream_context_create(['http' => [
            'method'        => 'GET',
            'header'        => "Authorization: {$auth}\r\nx-amz-content-sha256: {$sha}\r\nx-amz-date: {$now}",
            'ignore_errors' => true,
        ]]);

        $xml    = file_get_contents("https://{$host}/?{$query}", false, $ctx);
        $parsed = $xml ? simplexml_load_string($xml) : null;

        if (!$parsed) {
            return [];
        }

        $objects = [];
        foreach ($parsed->Contents ?? [] as $item) {
            $objects[] = [
                'Key'          => (string) $item->Key,
                'LastModified' => (string) $item->LastModified,
            ];
        }

        return $objects;
    }

    private function s3Delete(string $key): void
    {
        $now    = gmdate('Ymd\THis\Z');
        $date   = substr($now, 0, 8);
        $sha    = hash('sha256', '');
        $host   = $this->s3Host();
        $uri    = '/' . $this->encodeUri($key);

        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
        $canonical     = "DELETE\n{$uri}\n\nhost:{$host}\nx-amz-content-sha256:{$sha}\nx-amz-date:{$now}\n\n{$signedHeaders}\n{$sha}";
        $auth          = $this->buildAuth('DELETE', $date, $now, $canonical, $signedHeaders);

        $ctx = stream_context_create(['http' => [
            'method'        => 'DELETE',
            'header'        => "Authorization: {$auth}\r\nx-amz-content-sha256: {$sha}\r\nx-amz-date: {$now}",
            'ignore_errors' => true,
        ]]);

        file_get_contents("https://{$host}{$uri}", false, $ctx);
    }

    // ─── AWS Signature V4 ─────────────────────────────────────────────────────

    private function buildAuth(string $method, string $date, string $now, string $canonical, string $signedHeaders): string
    {
        $scope  = "{$date}/{$this->region}/s3/aws4_request";
        $toSign = "AWS4-HMAC-SHA256\n{$now}\n{$scope}\n" . hash('sha256', $canonical);
        $sigKey = $this->signingKey($date);
        $sig    = hash_hmac('sha256', $toSign, $sigKey);

        return "AWS4-HMAC-SHA256 Credential={$this->awsKey}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$sig}";
    }

    private function signingKey(string $date): string
    {
        $k1 = hash_hmac('sha256', $date,           "AWS4{$this->awsSecret}", true);
        $k2 = hash_hmac('sha256', $this->region,   $k1,                      true);
        $k3 = hash_hmac('sha256', 's3',             $k2,                      true);
        return hash_hmac('sha256', 'aws4_request',  $k3,                      true);
    }

    private function encodeUri(string $key): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $key)));
    }
}
