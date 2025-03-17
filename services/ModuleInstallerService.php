<?php

namespace humhub\modules\nucleus\services;

use Yii;
use yii\web\HttpException;
use yii\helpers\FileHelper;
use humhub\services\MigrationService;

/**
 * ModuleInstallerService handles the installation of custom core modules from GitHub
 */
class ModuleInstallerService
{
    private string $tempPath;
    private string $modulesPath;
    private MigrationService $migrationService;

    public function __construct()
    {
        $this->tempPath = Yii::getAlias('@runtime/module_downloads');
        $this->modulesPath = Yii::getAlias('@humhub/modules');
        $this->migrationService = new MigrationService();

        if (!is_dir($this->tempPath)) {
            FileHelper::createDirectory($this->tempPath);
        }
    }

    public function installFromGitHub(string $githubUrl, string $branch = 'master'): array
    {
        try {
            if (!$this->isValidGitHubUrl($githubUrl)) {
                return ['success' => false, 'message' => 'Invalid GitHub URL.'];
            }

            $repoInfo = $this->extractRepoInfo($githubUrl);
            $downloadId = uniqid();
            $downloadPath = "{$this->tempPath}/{$downloadId}";
            FileHelper::createDirectory($downloadPath);

            $zipUrl = "https://github.com/{$repoInfo['owner']}/{$repoInfo['repo']}/archive/refs/heads/{$branch}.zip";
            $zipFile = "{$downloadPath}/module.zip";

            if (!$this->downloadFile($zipUrl, $zipFile) || !$this->extractZip($zipFile, "{$downloadPath}/extracted")) {
                return ['success' => false, 'message' => 'Failed to download or extract module.'];
            }

            $directories = glob("{$downloadPath}/extracted/*", GLOB_ONLYDIR);
            if (empty($directories)) {
                return ['success' => false, 'message' => 'No module found in package.'];
            }

            $moduleInfo = $this->getModuleInfo($directories[0]);
            if (!$moduleInfo || !$moduleInfo['id']) {
                return ['success' => false, 'message' => 'Could not determine module ID.'];
            }

            $moduleId = $moduleInfo['id'];
            $moduleNamespace = $moduleInfo['namespace'] ?? "humhub\\modules\\{$moduleId}";
            $moduleTargetPath = "{$this->modulesPath}/{$moduleId}";
            $backupPath = null;

            if (is_dir($moduleTargetPath)) {
                $backupPath = "{$moduleTargetPath}_backup_" . date('YmdHis');
                rename($moduleTargetPath, $backupPath);
            }

            FileHelper::copyDirectory($directories[0], $moduleTargetPath);

            $migrationResult = $this->runMigrations($moduleId, $moduleNamespace);

            FileHelper::removeDirectory($downloadPath);

            if ($backupPath !== null) {
                if ($migrationResult['success']) {
                    FileHelper::removeDirectory($backupPath);
                } else {
                    FileHelper::removeDirectory($moduleTargetPath);
                    rename($backupPath, $moduleTargetPath);
                }
            }

            if (!$migrationResult['success']) {
                return [
                    'success' => false, 
                    'message' => "Module {$moduleId} files installed but migrations failed: {$migrationResult['message']}"
                ];
            }

            return [
                'success' => true, 
                'message' => "Module {$moduleId} installed successfully." . 
                             ($migrationResult['migrationsApplied'] > 0 ? " {$migrationResult['migrationsApplied']} migrations applied." : " No migrations needed."), 
                'moduleId' => $moduleId
            ];
        } catch (\Exception $e) {
            Yii::error('Error installing module: ' . $e->getMessage(), 'nucleus');
            return ['success' => false, 'message' => 'Installation error: ' . $e->getMessage()];
        }
    }

    /**
     * Validates a GitHub URL
     * 
     * @param string $url The URL to validate
     * @return bool Whether the URL is valid
     */
    private function isValidGitHubUrl($url)
    {
        return preg_match('/^https?:\/\/github\.com\/[\w-]+\/[\w-]+(\/?|\/.+)$/', $url);
    }

    /**
     * Extracts repository owner and name from GitHub URL
     * 
     * @param string $url The GitHub URL
     * @return array ['owner' => string, 'repo' => string]
     */
    private function extractRepoInfo($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));

        return [
            'owner' => $parts[0],
            'repo' => $parts[1]
        ];
    }

    /**
     * Downloads a file from URL
     * 
     * @param string $url The URL to download from
     * @param string $savePath Path to save the file
     * @return bool Whether the download was successful
     */
    private function downloadFile($url, $savePath)
    {
        $ch = curl_init($url);
        $fp = fopen($savePath, 'w');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $success = curl_exec($ch);

        if (!$success) {
            Yii::error('Download failed: ' . curl_error($ch), 'nucleus');
        }

        curl_close($ch);
        fclose($fp);

        return $success && file_exists($savePath) && filesize($savePath) > 0;
    }

    private function extractZip(string $zipFile, string $extractPath): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            $success = $zip->extractTo($extractPath);
            $zip->close();
            return $success;
        }
        Yii::error('Failed to open zip file: ' . $zipFile, 'nucleus');
        return false;
    }

    /**
     * Gets module info including ID and namespace from the module source path
     * 
     * @param string $modulePath The path to the module
     * @return array|null Module information or null if not found
     */
    private function getModuleInfo($modulePath)
    {
        $moduleFiles = array_merge(
            glob($modulePath . '/Module.php'),
            glob($modulePath . '/module/Module.php')
        );

        foreach ($moduleFiles as $moduleFile) {
            $content = file_get_contents($moduleFile);
            $namespace = null;
            $moduleId = null;

            if (preg_match('/namespace\s+([^;]+)/i', $content, $matches)) {
                $namespace = $matches[1];
                $parts = explode('\\', $namespace);

                $moduleIndex = array_search('modules', $parts);
                if ($moduleIndex !== false && isset($parts[$moduleIndex + 1])) {
                    $moduleId = $parts[$moduleIndex + 1];
                }
            }

            if (!$moduleId && preg_match('/(?:const|public|protected|private|static)\s+(?:\$)?(?:MODULE_)?ID\s*=\s*[\'"]([^\'"]+)[\'"];?/i', $content, $matches)) {
                $moduleId = $matches[1];
            }

            if ($moduleId) {
                return [
                    'id' => $moduleId,
                    'namespace' => $namespace,
                    'path' => dirname($moduleFile)
                ];
            }
        }

        return [
            'id' => basename($modulePath),
            'namespace' => null
        ];
    }

    /**
     * Runs migrations for a module
     * 
     * @param string $moduleId The module ID
     * @param string|null $moduleNamespace The module namespace if known
     * @return array Result of migration attempt with detailed information
     */
    private function runMigrations(string $moduleId, ?string $moduleNamespace = null): array
    {
        try {
            $moduleTargetPath = "{$this->modulesPath}/{$moduleId}";

            $possibleMigrationPaths = [
                "{$moduleTargetPath}/migrations",
                "{$moduleTargetPath}/module/migrations",
                "{$moduleTargetPath}/Migration"
            ];

            $migrationPath = null;
            foreach ($possibleMigrationPaths as $path) {
                if (is_dir($path)) {
                    $migrationPath = $path;
                    break;
                }
            }

            if (!$migrationPath) {
                return ['success' => true, 'migrationsApplied' => 0, 'message' => 'No migrations directory found'];
            }

            $migrationNamespaces = [];

            if ($moduleNamespace) {
                $migrationNamespaces[] = $moduleNamespace . "\\migrations";
                $migrationNamespaces[] = $moduleNamespace . "\\Migration";
            }

            $migrationNamespaces[] = "humhub\\modules\\{$moduleId}\\migrations";
            $migrationNamespaces[] = "humhub\\modules\\{$moduleId}\\Migration";

            $pendingMigrations = [];
            $usedNamespace = null;

            foreach ($migrationNamespaces as $namespace) {

                try {
                    $migrations = $this->migrationService->getPendingMigrations($namespace);
                    if (!empty($migrations)) {
                        $pendingMigrations = $migrations;
                        $usedNamespace = $namespace;
                        break;
                    }
                } catch (\Exception $e) {
                    Yii::error("Error checking namespace {$namespace}: " . $e->getMessage(), 'nucleus');
                }
            }

            if (empty($pendingMigrations) || !$usedNamespace) {
                return ['success' => true, 'migrationsApplied' => 0, 'message' => 'No pending migrations'];
            }

            $migrationsApplied = $this->migrationService->migrateUp($usedNamespace);

            if ($migrationsApplied === false) {
                Yii::error("Migration failed for module: {$moduleId}", 'nucleus');
                return ['success' => false, 'migrationsApplied' => 0, 'message' => 'Migration execution failed'];
            }

            return ['success' => true, 'migrationsApplied' => $migrationsApplied, 'message' => 'Migrations applied successfully'];

        } catch (\Exception $e) {
            $errorMessage = 'Migration error: ' . $e->getMessage();
            Yii::error($errorMessage, 'nucleus');
            return ['success' => false, 'migrationsApplied' => 0, 'message' => $errorMessage];
        }
    }
}
