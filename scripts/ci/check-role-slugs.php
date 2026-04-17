<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__, 2);
$rbacConfigPath = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rbac.php';

if (!is_file($rbacConfigPath)) {
    fwrite(STDERR, "[role-slug-check] File config rbac tidak ditemukan: {$rbacConfigPath}" . PHP_EOL);
    exit(2);
}

/** @var array<string, mixed> $rbac */
$rbac = require $rbacConfigPath;

$slugs = [];
foreach ((array) ($rbac['role_definitions'] ?? []) as $definition) {
    $slug = (string) ($definition['slug'] ?? '');
    if ($slug !== '') {
        $slugs[] = $slug;
    }
}
foreach ((array) ($rbac['admin_slugs'] ?? []) as $slug) {
    $slug = (string) $slug;
    if ($slug !== '') {
        $slugs[] = $slug;
    }
}
foreach ((array) ($rbac['evaluator_slugs'] ?? []) as $slug) {
    $slug = (string) $slug;
    if ($slug !== '') {
        $slugs[] = $slug;
    }
}
foreach ((array) ($rbac['questionnaire_target_slugs'] ?? []) as $slug) {
    $slug = (string) $slug;
    if ($slug !== '') {
        $slugs[] = $slug;
    }
}
foreach ((array) ($rbac['dashboard_paths'] ?? []) as $slug => $path) {
    unset($path);
    $slug = (string) $slug;
    if ($slug !== '') {
        $slugs[] = $slug;
    }
}

$excludedSlugs = array_values(array_unique(array_filter(
    (array) ($rbac['ci_guard_excluded_slugs'] ?? []),
    static fn(mixed $slug): bool => is_string($slug) && $slug !== ''
)));

$slugs = array_values(array_unique($slugs));
$slugs = array_values(array_filter(
    $slugs,
    static fn(string $slug): bool => !in_array($slug, $excludedSlugs, true)
));

if ($slugs === []) {
    fwrite(STDERR, "[role-slug-check] Tidak ada slug role di config/rbac.php" . PHP_EOL);
    exit(2);
}

$quotedAlternation = implode('|', array_map(static fn(string $slug): string => preg_quote($slug, '/'), $slugs));
$pattern = '/(["\'])(?:' . $quotedAlternation . ')\\1/';

$excludedDirectories = [
    '.git',
    '.clinerules',
    '.idea',
    '.vscode',
    'vendor',
    'node_modules',
    'storage',
    'bootstrap' . DIRECTORY_SEPARATOR . 'cache',
];

$allowedFiles = [
    realpath($rbacConfigPath) ?: $rbacConfigPath,
    realpath(__FILE__) ?: __FILE__,
];

/** @var array<int, array{file:string,line:int,snippet:string}> $violations */
$violations = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $fileInfo, string $key, RecursiveIterator $iterator) use ($rootPath, $excludedDirectories): bool {
            unset($iterator, $key);
            $path = $fileInfo->getPathname();
            if ($fileInfo->isDir()) {
                foreach ($excludedDirectories as $excludedDirectory) {
                    $excludedPath = $rootPath . DIRECTORY_SEPARATOR . $excludedDirectory;
                    if (str_starts_with($path, $excludedPath)) {
                        return false;
                    }
                }
            }

            return true;
        }
    ),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
        continue;
    }

    $filePath = $fileInfo->getPathname();
    $realPath = realpath($filePath) ?: $filePath;
    if (in_array($realPath, $allowedFiles, true)) {
        continue;
    }

    $contents = @file_get_contents($filePath);
    if (!is_string($contents) || $contents === '') {
        continue;
    }

    if (!preg_match_all($pattern, $contents, $contentMatches, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    foreach ($contentMatches[0] as [$matchText, $offset]) {
        $line = substr_count(substr($contents, 0, (int) $offset), "\n") + 1;
        $lineText = strtok(substr($contents, (int) $offset), "\n");
        $snippet = is_string($lineText) ? trim($lineText) : trim((string) $matchText);

        $violations[] = [
            'file' => str_replace($rootPath . DIRECTORY_SEPARATOR, '', $filePath),
            'line' => $line,
            'snippet' => $snippet,
        ];
    }
}

if ($violations === []) {
    fwrite(STDOUT, "[role-slug-check] OK: tidak ada literal role slug di luar config/rbac.php" . PHP_EOL);
    exit(0);
}

fwrite(STDERR, "[role-slug-check] FAIL: ditemukan literal role slug di luar config/rbac.php" . PHP_EOL);
foreach ($violations as $violation) {
    fwrite(
        STDERR,
        sprintf("- %s:%d | %s", $violation['file'], $violation['line'], $violation['snippet']) . PHP_EOL
    );
}

exit(1);
