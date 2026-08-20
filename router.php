<?php
// Router script for PHP built-in server.
// Usage: php -S localhost:8000 router.php
// Serve from the project root (G:\awais).

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static assets — asset() returns /public/..., so strip the /public prefix
if (strpos($uri, '/public/') === 0) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        // Set correct MIME type
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'pdf'  => 'application/pdf',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($file);
        return true;
    }
}

// Serve any other existing static file
if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    return false; // Let PHP built-in server handle it
}

// Default: serve index.php for root
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// Route to PHP files directly
$file = __DIR__ . $uri;
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    return true;
}

// Fallback
http_response_code(404);
echo '404 Not Found';
return true;
