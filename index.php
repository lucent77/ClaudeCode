<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creodent AoX Dashboard</title>
</head>
<body>
    <script>
        // Redirect to login or dashboard based on authentication
        const token = localStorage.getItem('token');
        if (token) {
            window.location.href = '/dashboard.php';
        } else {
            window.location.href = '/login.php';
        }
    </script>
</body>
</html>
