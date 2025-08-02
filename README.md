# File Server - Download Manager

A modern, responsive file server with directory browsing and file management capabilities.

## Features

### 📁 File Browser (`index.php`)
- **Directory Navigation**: Browse through folders with breadcrumb navigation
- **File Listings**: View files and folders with icons, sizes, and modification dates
- **Download Links**: Direct download buttons for all files
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Security**: Protected against directory traversal attacks
- **File Type Icons**: Visual indicators for different file types (📦 archives, 📄 documents, 🖼️ images, etc.)

### 📤 File Upload Manager (`upload.php`)
- **Secure Login**: Admin authentication required for uploads
- **Drag & Drop**: Modern file upload interface
- **Multiple Files**: Upload multiple files simultaneously
- **Folder Management**: Create and organize files in folders
- **Progress Tracking**: Real-time upload progress indication
- **File Type Validation**: Supports multiple file formats
- **Large File Support**: Handles files up to 500MB

### 🎨 Modern UI
- **Beautiful Design**: Gradient backgrounds and modern styling
- **Responsive Layout**: Mobile-friendly design
- **Smooth Animations**: Hover effects and transitions
- **Professional Look**: Clean, modern interface

## Supported File Types

- **Archives**: zip, rar, 7z
- **Documents**: pdf, doc, docx, xls, xlsx, txt
- **Images**: jpg, jpeg, png, gif
- **Media**: mp3, mp4, avi
- **Mobile**: apk files
- **Executables**: exe, msi

## Security Features

- **Authentication**: Login required for file uploads
- **Directory Traversal Protection**: Prevents access outside base directory
- **File Type Validation**: Only allowed file types can be uploaded
- **Hidden File Protection**: System files are hidden from listings
- **Secure Downloads**: Forced download headers for file security

## Configuration

### Upload Settings
Edit the configuration in `upload.php`:
```php
$config = [
    'username' => 'admin',                    // Change username
    'password' => 'your_secure_password',     // Change password
    'upload_dir' => __DIR__ . '/',           // Upload directory
    'allowed_types' => [...]                  // Allowed file extensions
];
```

### File Browser Settings
Edit the configuration in `index.php`:
```php
$config = [
    'title' => 'File Server - Downloads',     // Page title
    'base_path' => __DIR__,                   // Base directory
    'show_hidden' => false,                   // Show hidden files
    'allowed_extensions' => [...],            // File type filters
    'max_name_length' => 50                   // Max filename display length
];
```

## Installation

1. Upload all files to your web server
2. Ensure the directory has write permissions for uploads
3. Change the default password in `upload.php`
4. Access `index.php` to browse files
5. Access `upload.php` to upload files (login required)

## File Structure

```
mws_download/
├── index.php          # Main file browser
├── upload.php         # File upload manager
├── style.css          # Styling and responsive design
├── .htaccess          # Server configuration
└── README.md          # This documentation
```

## Usage

### Browsing Files
- Visit `index.php` to see the file listing
- Click on folders to navigate into them
- Use breadcrumbs to navigate back up
- Click download buttons to download files

### Uploading Files
- Visit `upload.php` and login with admin credentials
- Create folders to organize files
- Drag and drop files or click to select
- Choose target folder from dropdown
- Click upload to transfer files

## Customization

### Styling
Edit `style.css` to customize the appearance:
- Change colors and gradients
- Modify layout and spacing
- Add custom animations

### File Icons
Add new file type icons in the `getFileIcon()` function in `index.php`

### Security
- Change default passwords immediately
- Consider adding HTTPS
- Regularly update file type restrictions
- Monitor upload directory size

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

## Server Requirements

- PHP 7.4 or higher
- Apache web server with mod_rewrite
- Write permissions on upload directory
- Adequate disk space for file storage

## Troubleshooting

### Upload Issues
- Check PHP upload limits in php.ini
- Verify directory write permissions
- Ensure sufficient disk space

### Display Issues
- Clear browser cache
- Check .htaccess configuration
- Verify CSS file is loading

## License

This file server is provided as-is for educational and personal use.
