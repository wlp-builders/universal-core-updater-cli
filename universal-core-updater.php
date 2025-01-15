<?php

class CMSCoreUpdater
{
    private $updateUrl;        // URL to the update ZIP file
    private $targetDir;        // Target directory to update
    private $downloadDir;      // Temporary directory for downloads
    private $excludeDirs;      // Directories to exclude during the update
    private $result;           // Store counts of operations

    public function __construct($updateUrl, $targetDir, $downloadDir, $excludeDirs = [])
    {
        $this->updateUrl = $updateUrl;
        $this->targetDir = rtrim($targetDir, '/') . '/';
        $this->downloadDir = rtrim($downloadDir, '/') . '/';
        $this->excludeDirs = $excludeDirs;
        $this->result = [
            'files_replaced' => 0,
            'folders_created' => 0,
            'excluded' => 0,
            'errors' => 0
        ];

        // Ensure the download directory exists
        if (!is_dir($this->downloadDir)) {
            mkdir($this->downloadDir, 0755, true);
        }
    }

    public function updateCore()
    {
        // Step 1: Download the update ZIP file
        $updateFile = $this->downloadUpdateFile();
        if (!$updateFile) {
            $this->result['errors']++;
            return $this->result;
        }

        // Step 2: Extract the ZIP file
        $extractPath = $this->downloadDir . 'extracted/';
        if (!$this->extractZip($updateFile, $extractPath)) {
            $this->result['errors']++;
            return $this->result;
        }

        // Step 3: Detect the top-level folder in the extracted content
        $topLevelFolder = $this->getTopLevelFolder($extractPath);
        if (!$topLevelFolder) {
            $this->result['errors']++;
            return $this->result;
        }

        // Step 4: Replace the core files
        $sourcePath = $extractPath . $topLevelFolder . '/';
        $this->replaceCore($sourcePath);

        return $this->result;
    }

    private function downloadUpdateFile()
    {
        $filePath = $this->downloadDir . 'latest.zip';

        $data = file_get_contents($this->updateUrl); // Use cURL for better error handling in production
        if ($data && file_put_contents($filePath, $data)) {
            return $filePath;
        }

        return false;
    }

    private function extractZip($zipFile, $extractTo)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile)) {
            $zip->extractTo($extractTo);
            $zip->close();
            return true;
        }
        return false;
    }

    private function getTopLevelFolder($path)
    {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_dir($path . $file)) {
                return $file;
            }
        }
        return false;
    }

    private function replaceCore($sourcePath)
    {
        $this->recursiveReplace($sourcePath, $this->targetDir);
    }

    private function recursiveReplace($source, $destination)
    {
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;

            $srcPath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            // Check if this directory should be excluded
            // Check if this directory should be excluded
            foreach ($this->excludeDirs as $exclude) {
                // Use fnmatch for .gitignore-like pattern matching
                if (fnmatch($exclude, $destPath)) {
                    $this->result['excluded']++;
                    continue 2;
                }
            }

            // If it's a directory, recurse
            if (is_dir($srcPath)) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                    $this->result['folders_created']++;
                }
                $this->recursiveReplace($srcPath, $destPath);
            } else {
                // If it's a file, replace it
                if (copy($srcPath, $destPath)) {
                    $this->result['files_replaced']++;
                } else {
                    $this->result['errors']++;
                }
            }
        }
    }

  public static function printUsage()
    {
        echo "Usage: php updateCore.php [options]\n";
        echo "Options:\n";
        echo "  --url=<url>           The URL to the update ZIP file (required)\n";
        echo "  --target=<dir>        The directory to update (required)\n";
        echo "  --download-dir=<dir>  The temporary directory to store the ZIP file (default: ./temp)\n";
	echo "  --exclude=<dirs>      Comma-separated list of directories to exclude (default: **/.git)\n";
        echo "  --help                Display this help message\n";
    }

    public static function parseArgs($argv)
    {
        $params = [];
        foreach ($argv as $arg) {
            if (strpos($arg, '--') === 0) {
                $split = explode('=', substr($arg, 2), 2);
                if (count($split) === 2) {
                    $params[$split[0]] = $split[1];
                }
            }
        }
        return $params;
    }
}

// Example usage 1/2 (uncomment both)
// $excludeDirs = ['**/.git', '**/install/plugins/**']; // Folders to exclude
/*
// Example Usage 2/2
$updateUrl = 'http://localhost/wlp-v2.zip'; // URL of the update ZIP file
$targetDir = '/var/www/cms1.local/double-check'; // Directory to update
$downloadDir = __DIR__ . '/temp'; // Temporary download directory
// Use fnmatch for .gitignore-like pattern matching

$updater = new CMSCoreUpdater($updateUrl, $targetDir, $downloadDir, $excludeDirs);
$result = $updater->updateCore();

// Output the result
var_dump($result);
 */

//  CLI mode if executed using php
if (php_sapi_name() == "cli") { 

// Command-line execution
if ($argc < 2 || in_array('--help', $argv)) {
    CMSCoreUpdater::printUsage();
    exit;
}

// Parse the arguments
$params = CMSCoreUpdater::parseArgs($argv);

if (!isset($params['url']) || !isset($params['target'])) {
    echo "Error: Both --url and --target are required.\n";
    CMSCoreUpdater::printUsage();
    exit(1);
}

$updateUrl = $params['url'];
$targetDir = $params['target'];
$downloadDir = $params['download-dir'] ?? __DIR__ . '/temp';
$excludeDirs = isset($params['exclude']) ? explode(',', $params['exclude']) : ['**/.git'];

$updater = new CMSCoreUpdater($updateUrl, $targetDir, $downloadDir, $excludeDirs);
$result = $updater->updateCore();

// Output the result
echo json_encode($result,JSON_PRETTY_PRINT).PHP_EOL;
}


