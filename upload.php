<?php
session_start();

// Configuration
$config = [
    'username' => 'admin368',
    'password' => '36880076',
    'upload_dir' => __DIR__ . '/',
    'allowed_types' => ['zip', 'rar', '7z', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'mp3', 'mp4', 'avi', 'apk', 'exe', 'msi']
];

// Handle login
if (isset($_POST['login'])) {
    if ($_POST['username'] === $config['username'] && $_POST['password'] === $config['password']) {
        $_SESSION['logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Invalid credentials';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle folder creation
if (isset($_POST['create_folder']) && $_SESSION['logged_in']) {
    $folder_name = preg_replace('/[^a-zA-Z0-9-_]/', '', $_POST['folder_name']);
    if (!empty($folder_name)) {
        $new_folder = $config['upload_dir'] . $folder_name;
        if (!file_exists($new_folder)) {
            mkdir($new_folder, 0755);
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files']) && $_SESSION['logged_in']) {
    $target_dir = $config['upload_dir'] . (isset($_POST['subfolder']) ? $_POST['subfolder'] . '/' : '');
    $response = ['success' => true, 'messages' => []];

    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = $_FILES['files']['name'][$key];
            $destination = $target_dir . basename($filename);
            
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $config['allowed_types'])) {
                $response['messages'][] = "File type not allowed: $filename";
                $response['success'] = false;
                continue;
            }

            if (move_uploaded_file($tmp_name, $destination)) {
                $response['messages'][] = "Successfully uploaded: $filename";
            } else {
                $response['messages'][] = "Error uploading: $filename";
                $response['success'] = false;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get list of subfolders
function getSubfolders($dir) {
    $subfolders = array_filter(glob($dir . '*'), 'is_dir');
    return array_map(function($folder) use ($dir) {
        return str_replace($dir, '', $folder);
    }, $subfolders);
}

$subfolders = getSubfolders($config['upload_dir']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .login-form {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .upload-area {
            border: 2px dashed #3498db;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .upload-area.dragover {
            background: #e3f2fd;
        }

        .progress-container {
            margin: 10px 0;
        }

        .progress {
            height: 20px;
            background: #f5f5f5;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: #3498db;
            width: 0;
            transition: width 0.3s ease;
        }

        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            color: #fff;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
            line-height: 20px;
        }

        .folder-controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }

        .button:hover {
            background: #2980b9;
        }

        .error {
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .success {
            color: #27ae60;
        }

        .logout {
            float: right;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['logged_in'])): ?>
        <div class="login-form">
            <h2>Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="button">Login</button>
            </form>
        </div>
    <?php else: ?>        <div class="upload-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>File Upload Manager</h1>
                <div>
                    <a href="index.php" class="button" style="margin-right: 10px;">📁 Browse Files</a>
                    <a href="?logout" class="button logout">Logout</a>
                </div>
            </div>
            
            <div class="folder-controls">
                <h3>Folder Management</h3>
                <form method="post" class="form-group" style="display: inline-block;">
                    <input type="text" name="folder_name" placeholder="New folder name" required>
                    <button type="submit" name="create_folder" class="button">Create Folder</button>
                </form>
                
                <div class="form-group">
                    <label for="subfolder">Upload to folder:</label>
                    <select id="subfolder" class="form-control">
                        <option value="">Root directory</option>
                        <?php foreach ($subfolders as $folder): ?>
                            <option value="<?php echo htmlspecialchars($folder); ?>">
                                <?php echo htmlspecialchars($folder); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="upload-area" id="dropZone">
                <p>Drag & Drop files here or click to select files</p>
                <input type="file" id="fileInput" multiple style="display: none">
            </div>
            
            <div id="fileList"></div>
            
            <div class="progress-container" style="display: none">
                <div class="progress">
                    <div class="progress-bar"></div>
                    <div class="progress-text">0%</div>
                </div>
                <div class="current-file"></div>
            </div>
            
            <button class="button" id="uploadButton" style="display: none">Upload Files</button>
            <div id="messages"></div>
        </div>

        <script>
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const fileList = document.getElementById('fileList');
            const uploadButton = document.getElementById('uploadButton');
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.progress-text');
            const progressContainer = document.querySelector('.progress-container');
            const currentFileDiv = document.querySelector('.current-file');
            const messages = document.getElementById('messages');
            const subfolderSelect = document.getElementById('subfolder');
            let files = [];

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                const droppedFiles = Array.from(e.dataTransfer.files);
                handleFiles(droppedFiles);
            });

            dropZone.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', (e) => {
                const selectedFiles = Array.from(e.target.files);
                handleFiles(selectedFiles);
            });

            function handleFiles(newFiles) {
                files = [...files, ...newFiles];
                updateFileList();
                uploadButton.style.display = 'block';
            }

            function updateFileList() {
                fileList.innerHTML = files.map(file => `
                    <div class="file-item">
                        ${file.name} (${formatSize(file.size)})
                    </div>
                `).join('');
            }

            function formatSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            uploadButton.addEventListener('click', async () => {
                if (files.length === 0) return;

                progressContainer.style.display = 'block';
                messages.innerHTML = '';
                let totalUploaded = 0;

                for (let i = 0; i < files.length; i++) {
                    const formData = new FormData();
                    formData.append('files[]', files[i]);
                    if (subfolderSelect.value) {
                        formData.append('subfolder', subfolderSelect.value);
                    }

                    currentFileDiv.textContent = `Uploading: ${files[i].name}`;

                    try {
                        const response = await fetch('upload.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        
                        result.messages.forEach(message => {
                            const div = document.createElement('div');
                            div.textContent = message;
                            div.className = message.includes('Successfully') ? 'success' : 'error';
                            messages.appendChild(div);
                        });

                        totalUploaded++;
                        const progress = (totalUploaded / files.length) * 100;
                        progressBar.style.width = progress + '%';
                        progressText.textContent = Math.round(progress) + '%';
                    } catch (error) {
                        messages.innerHTML += '<div class="error">Upload failed. Please try again.</div>';
                    }
                }

                files = [];
                updateFileList();
                uploadButton.style.display = 'none';
                
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    progressBar.style.width = '0';
                    progressText.textContent = '0%';
                    currentFileDiv.textContent = '';
                }, 2000);
            });
        </script>
    <?php endif; ?>
</body>
</html> 