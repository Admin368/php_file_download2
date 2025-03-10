<?php
session_start();

// Basic security check - you should implement proper authentication
$allowed_ips = ['YOUR_IP_ADDRESS']; // Add your IP addresses
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = __DIR__ . '/'; // Current directory
    $response = ['success' => true, 'messages' => []];

    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = $_FILES['files']['name'][$key];
            $destination = $upload_dir . basename($filename);

            // Basic security check for file type
            $allowed_types = ['zip', 'rar', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_types)) {
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
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

        .progress {
            height: 20px;
            background: #f5f5f5;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-bar {
            height: 100%;
            background: #3498db;
            width: 0;
            transition: width 0.3s ease;
        }

        #fileList {
            margin-top: 20px;
        }

        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .button:hover {
            background: #2980b9;
        }

        #messages {
            margin-top: 20px;
        }

        .success {
            color: #27ae60;
        }

        .error {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>Upload Files</h1>
        <div class="upload-area" id="dropZone">
            <p>Drag & Drop files here or click to select files</p>
            <input type="file" id="fileInput" multiple style="display: none">
        </div>
        <div id="fileList"></div>
        <div class="progress" style="display: none">
            <div class="progress-bar"></div>
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
        const progress = document.querySelector('.progress');
        const messages = document.getElementById('messages');
        let files = [];

        // Handle drag and drop
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

        // Handle click to select files
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

            const formData = new FormData();
            files.forEach(file => {
                formData.append('files[]', file);
            });

            progress.style.display = 'block';
            messages.innerHTML = '';

            try {
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData,
                    xhr: () => {
                        const xhr = new XMLHttpRequest();
                        xhr.upload.addEventListener('progress', (e) => {
                            if (e.lengthComputable) {
                                const percentComplete = (e.loaded / e.total) * 100;
                                progressBar.style.width = percentComplete + '%';
                            }
                        });
                        return xhr;
                    }
                });

                const result = await response.json();
                
                result.messages.forEach(message => {
                    const div = document.createElement('div');
                    div.textContent = message;
                    div.className = message.includes('Successfully') ? 'success' : 'error';
                    messages.appendChild(div);
                });

                if (result.success) {
                    files = [];
                    updateFileList();
                    uploadButton.style.display = 'none';
                }
            } catch (error) {
                messages.innerHTML = '<div class="error">Upload failed. Please try again.</div>';
            }

            setTimeout(() => {
                progress.style.display = 'none';
                progressBar.style.width = '0';
            }, 1000);
        });
    </script>
</body>
</html> 