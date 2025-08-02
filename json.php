<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function scanDirectory($dir, $baseUrl) {
    $result = [];
    
    // Get the current directory name
    $folderName = basename($dir);
      // Skip hidden directories, system directories, and .git folder
    if (strpos($folderName, '.') === 0 || $folderName === '.git') {
        return $result;
    }
    
    $fileUrls = [];
    
    if (is_dir($dir) && is_readable($dir)) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $relativeUrl = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
            $relativeUrl = str_replace('\\', '/', $relativeUrl);
            
            if (is_file($filePath)) {
                // Skip hidden files and sensitive files
                if (strpos($file, '.') === 0 || 
                    in_array(pathinfo($file, PATHINFO_EXTENSION), ['htaccess', 'htpasswd', 'log', 'conf', 'ini'])) {
                    continue;
                }
                
                $fileUrls[] = $baseUrl . $relativeUrl;
            }
        }
    }
    
    // Only add folders that contain files
    if (!empty($fileUrls)) {
        $result[] = [
            'folder_name' => $folderName,
            'file_urls' => $fileUrls
        ];
    }
    
    return $result;
}

function getAllFoldersWithFiles($rootDir, $baseUrl) {
    $allFolders = [];
    
    // Add root directory files
    $rootFiles = scanDirectory($rootDir, $baseUrl);
    $allFolders = array_merge($allFolders, $rootFiles);
    
    // Scan subdirectories
    if (is_dir($rootDir) && is_readable($rootDir)) {
        $items = scandir($rootDir);
          foreach ($items as $item) {
            if ($item === '.' || $item === '..' || strpos($item, '.') === 0) {
                continue;
            }
            
            $itemPath = $rootDir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($itemPath)) {
                $subFolders = scanDirectory($itemPath, $baseUrl);
                $allFolders = array_merge($allFolders, $subFolders);
                
                // Recursively scan subdirectories
                $deeperFolders = getAllFoldersWithFiles($itemPath, $baseUrl);
                $allFolders = array_merge($allFolders, $deeperFolders);
            }
        }
    }
    
    return $allFolders;
}

try {
    // Get the current directory (where this script is located)
    $currentDir = __DIR__;
    
    // Construct base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . '://' . $host . $scriptDir;
    
    // Ensure base URL ends with /
    if (substr($baseUrl, -1) !== '/') {
        $baseUrl .= '/';
    }
    
    // Get all folders with their files
    $folders = getAllFoldersWithFiles($currentDir, $baseUrl);
    
    // Return JSON response
    echo json_encode($folders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
