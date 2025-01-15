# universal-core-updater-cli
Universal Core Updater CLI where you can specify zip url, target directory and exclude paths using fnmatch. Use as CLI or as function.

## Basic Usage:
```
git clone git@github.com:wlp-builders/universal-core-updater-cli.git && cd universal-core-updater
php universal-core-updater.php --url='http://localhost/wlp-v2.zip' --target='/var/www/wlp185.local'
```

### Full Usage
```
php universal-core-updater.php --help
Usage: php updateCore.php [options]
Options:
  --url=<url>           The URL to the update ZIP file (required)
  --target=<dir>        The directory to update (required)
  --download-dir=<dir>  The temporary directory to store the ZIP file (default: ./temp)
  --exclude=<dirs>      Comma-separated list of directories to exclude (default: **/.git)
  --help                Display this help message
```

### Use in other PHP library or CMS directly
```
require_once __DIR__.'/path/to/universal-core-updater.php';
// Example usage 
$excludeDirs = ['**/.git', '**/install/plugins/**']; // Folders to exclude using fnmatch for .gitignore-like pattern matching
$updateUrl = 'http://localhost/wlp-v2.zip'; // URL of the update ZIP file
$targetDir = '/var/www/cms1.local'; // Directory to update
$downloadDir = __DIR__ . '/temp'; // Temporary download directory

$updater = new CMSCoreUpdater($updateUrl, $targetDir, $downloadDir, $excludeDirs);
$result = $updater->updateCore();

// Output the result
var_dump($result);
```
