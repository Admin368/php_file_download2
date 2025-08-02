<?php
// File Browser Configuration
$config = [
    'title' => 'File Server - Downloads',
    'base_path' => __DIR__,
    'show_hidden' => false,
    'allowed_extensions' => ['zip', 'rar', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'png', 'gif', 'mp3', 'mp4', 'avi', 'apk'],
    'max_name_length' => 50
];

// Get current directory from URL parameter
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$current_dir = ltrim($current_dir, '/');
$current_dir = rtrim($current_dir, '/');

// Security: Prevent directory traversal
$current_dir = preg_replace('/\.\.\//', '', $current_dir);
$full_path = $config['base_path'] . '/' . $current_dir;

// Ensure we don't go above the base path
if (!is_dir($full_path) || !str_starts_with(realpath($full_path), realpath($config['base_path']))) {
    $current_dir = '';
    $full_path = $config['base_path'];
}

// Handle file download
if (isset($_GET['download'])) {
    $download_path = ltrim($_GET['download'], '/');
    
    // Security: Only allow downloads from subdirectories (not root)
    if (empty($download_path) || strpos($download_path, '/') === false) {
        http_response_code(403);
        die('Access denied: Downloads only allowed from subdirectories');
    }
    
    $file_path = $config['base_path'] . '/' . $download_path;
    
    // Security check - ensure file exists and is within base path
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('File not found');
    }
    
    $real_file_path = realpath($file_path);
    $real_base_path = realpath($config['base_path']);
    
    if (!$real_file_path || !str_starts_with($real_file_path, $real_base_path)) {
        http_response_code(403);
        die('Access denied: Invalid file path');
    }
    
    // Additional check: ensure file is not a PHP or system file
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $system_extensions = ['php', 'htaccess', 'htpasswd', 'log', 'conf', 'ini', 'md','js'];
    
    if (in_array($extension, $system_extensions)) {
        http_response_code(403);
        die('Access denied: System files cannot be downloaded');
    }
    
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    
    // Clear any output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set download headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . addslashes($file_name) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Accept-Ranges: bytes');
    
    // Output file
    readfile($file_path);
    exit;
}

// Get directory contents
function scanDirectory($path, $config, $current_dir = '') {
    $items = [];
    
    if (!is_dir($path)) {
        return $items;
    }
    
    $files = scandir($path);
    $is_root = ($path === $config['base_path']);
    
    foreach ($files as $file) {
        if ($file === '.' || ($file === '..' && $path === $config['base_path'])) {
            continue;
        }
        
        if (!$config['show_hidden'] && $file[0] === '.' && $file !== '..') {
            continue;
        }
        
        $file_path = $path . '/' . $file;
        $is_directory = is_dir($file_path);
        
        // In root directory, only show directories (no individual files)
        if ($is_root && !$is_directory) {
            continue;
        }
        
        // Skip PHP files and other system files in all directories
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $system_extensions = ['php', 'htaccess', 'htpasswd', 'log', 'conf', 'ini', 'css', 'js', 'md'];
        if (in_array($extension, $system_extensions)) {
            continue;
        }
        
        $stat = stat($file_path);
        
        $item = [
            'name' => $file,
            'path' => $file_path,
            'type' => $is_directory ? 'directory' : 'file',
            'size' => $stat['size'],
            'modified' => $stat['mtime'],
            'extension' => $extension
        ];
        
        $items[] = $item;
    }
    
    // Sort: directories first, then by name
    usort($items, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'directory' ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $items;
}

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    
    return round($size, 2) . ' ' . $units[$unit];
}

function getFileIcon($item) {
    if ($item['type'] === 'directory') {
        return '📁';
    }
    
    $ext = $item['extension'];
    $icons = [
        'zip' => '📦', 'rar' => '📦', '7z' => '📦',
        'pdf' => '📄', 'doc' => '📝', 'docx' => '📝',
        'xls' => '📊', 'xlsx' => '📊',
        'txt' => '📄', 'md' => '📄',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'mp3' => '🎵', 'wav' => '🎵',
        'mp4' => '🎬', 'avi' => '🎬', 'mkv' => '🎬',
        'apk' => '📱'
    ];
    
    return $icons[$ext] ?? '📄';
}

function getBreadcrumbs($current_dir) {
    $parts = array_filter(explode('/', $current_dir));
    $breadcrumbs = [['name' => 'Home', 'path' => '']];
    
    $path = '';
    foreach ($parts as $part) {
        $path .= $part . '/';
        $breadcrumbs[] = ['name' => $part, 'path' => rtrim($path, '/')];
    }
    
    return $breadcrumbs;
}

$items = scanDirectory($full_path, $config, $current_dir);
$breadcrumbs = getBreadcrumbs($current_dir);

// Check if we're in root directory
$is_root_directory = empty($current_dir);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .upload-link {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }

        .upload-link:hover {
            background: #2980b9;
        }

        .breadcrumbs {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .breadcrumbs a {
            color: #3498db;
            text-decoration: none;
        }

        .breadcrumbs a:hover {
            text-decoration: underline;
        }

        .breadcrumbs span {
            margin: 0 10px;
            color: #7f8c8d;
        }

        .file-browser {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .file-list {
            width: 100%;
            border-collapse: collapse;
        }

        .file-list th,
        .file-list td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .file-list th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .file-list tr:hover {
            background: #f8f9fa;
        }

        .file-item {
            display: flex;
            align-items: center;
        }

        .file-icon {
            font-size: 20px;
            margin-right: 10px;
        }

        .file-name {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }

        .file-name:hover {
            color: #3498db;
        }

        .directory .file-name {
            color: #3498db;
        }

        .file-size {
            color: #7f8c8d;
            font-size: 14px;
        }

        .file-date {
            color: #7f8c8d;
            font-size: 14px;
        }

        .download-btn {
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
        }

        .download-btn:hover {
            background: #219a52;
        }

        .empty-message {
            padding: 40px;
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
        }

        .stats {
            background: #ecf0f1;
            padding: 10px 20px;
            font-size: 14px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .file-list th:nth-child(3),
            .file-list td:nth-child(3),
            .file-list th:nth-child(4),
            .file-list td:nth-child(4) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">        <div class="header">
            <h1><?php echo htmlspecialchars($config['title']); ?></h1>
            <?php if ($is_root_directory): ?>
                <p>Select a folder below to browse and download files</p>
            <?php else: ?>
                <p>Browse and download files from this directory</p>
            <?php endif; ?>
            <a href="upload.php" class="upload-link">📤 Upload Files</a>
        </div>

        <div class="breadcrumbs">
            <?php foreach ($breadcrumbs as $i => $breadcrumb): ?>
                <?php if ($i > 0): ?><span>›</span><?php endif; ?>
                <a href="?dir=<?php echo urlencode($breadcrumb['path']); ?>">
                    <?php echo htmlspecialchars($breadcrumb['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>        <div class="file-browser">
            <?php if (empty($items)): ?>
                <div class="empty-message">
                    <?php if ($is_root_directory): ?>
                        No folders found. Create folders using the upload manager to organize your files.
                    <?php else: ?>
                        This directory is empty.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if ($is_root_directory): ?>
                    <div style="background: #e8f4f8; padding: 15px 20px; border-bottom: 1px solid #ddd; color: #2c3e50;">
                        <strong>📁 Available Folders:</strong> Click on a folder to browse its contents and download files.
                    </div>
                <?php endif; ?>
                <table class="file-list">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <?php if (!$is_root_directory): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="<?php echo $item['type']; ?>">
                                <td>
                                    <div class="file-item">
                                        <span class="file-icon"><?php echo getFileIcon($item); ?></span>
                                        <?php if ($item['type'] === 'directory'): ?>
                                            <a href="?dir=<?php echo urlencode($current_dir ? $current_dir . '/' . $item['name'] : $item['name']); ?>" class="file-name">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="file-name">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="file-size">
                                    <?php echo $item['type'] === 'directory' ? '-' : formatFileSize($item['size']); ?>
                                </td>
                                <td class="file-date">
                                    <?php echo date('Y-m-d H:i', $item['modified']); ?>
                                </td>
                                <?php if (!$is_root_directory): ?>
                                    <td>
                                        <?php if ($item['type'] === 'file'): ?>
                                            <a href="?download=<?php echo urlencode($current_dir ? $current_dir . '/' . $item['name'] : $item['name']); ?>" class="download-btn">
                                                📥 Download
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="stats">
                <?php 
                $file_count = count(array_filter($items, fn($item) => $item['type'] === 'file'));
                $dir_count = count(array_filter($items, fn($item) => $item['type'] === 'directory'));
                echo "$dir_count folders, $file_count files";
                ?>
            </div>
        </div>
    </div>
</body>
</html>