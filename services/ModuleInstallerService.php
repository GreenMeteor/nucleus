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

            $moduleId = $this->getModuleId($directories[0]);
            if (!$moduleId) {
                return ['success' => false, 'message' => 'Could not determine module ID.'];
            }

            $moduleTargetPath = "{$this->modulesPath}/{$moduleId}";
            $backupPath = null;

            if (is_dir($moduleTargetPath)) {
                $backupPath = "{$moduleTargetPath}_backup_" . date('YmdHis');
                rename($moduleTargetPath, $backupPath);
            }

            FileHelper::copyDirectory($directories[0], $moduleTargetPath);

            $migrationResult = $this->runMigrations($moduleId);

            FileHelper::removeDirectory($downloadPath);

            if ($backupPath !== null) {
                FileHelper::removeDirectory($backupPath);
            }

            return ['success' => true, 'message' => "Module {$moduleId} installed." . ($migrationResult ? ' Migrations applied.' : ''), 'moduleId' => $moduleId];
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

        curl_close($ch);
        fclose($fp);

        return $success;
    }

    private function extractZip(string $zipFile, string $extractPath): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            $success = $zip->extractTo($extractPath);
            $zip->close();
            return $success;
        }
        return false;
    }

    /**
     * Determines the module ID from the module source path
     * 
     * @param string $modulePath The path to the module
     * @return string|null The module ID or null if not found
     */
    private function getModuleId($modulePath)
    {
        $moduleFiles = glob($modulePath . '/Module.php');

        if (!empty($moduleFiles)) {
            $content = file_get_contents($moduleFiles[0]);

            if (preg_match('/namespace\s+([^;]+)/i', $content, $matches)) {
                $namespace = $matches[1];

                $parts = explode('\\', $namespace);

                $moduleIndex = array_search('modules', $parts);

                if ($moduleIndex !== false && isset($parts[$moduleIndex + 1])) {
                    return $parts[$moduleIndex + 1];
                }
            }
        }

        return basename($modulePath);
    }

    private function runMigrations(string $moduleId): bool
    {
        try {
            $migrationPath = "{$this->modulesPath}/{$moduleId}/migrations";
            if (!is_dir($migrationPath) || !$this->migrationService->hasMigrations($migrationPath)) {
                return false;
            }

            $migrationNamespace = "humhub\\modules\\{$moduleId}\\migrations";
            $pendingMigrations = $this->migrationService->getPendingMigrations($migrationNamespace);

            if (empty($pendingMigrations)) {
                Yii::info("No pending migrations for module: {$moduleId}", 'nucleus');
                return false;
            }

            return $this->migrationService->migrateUp($migrationNamespace) > 0;
        } catch (\Exception $e) {
            Yii::error('Migration error: ' . $e->getMessage(), 'nucleus');
            return false;
        }
    }
}