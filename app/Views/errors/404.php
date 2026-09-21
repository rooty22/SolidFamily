<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>404 - الصفحة غير موجودة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background:#0f1e3d; color:#fff; height:100vh; display:flex; align-items:center; justify-content:center; flex-direction:column; margin:0; }
        h1 { font-size: 90px; margin:0; background: linear-gradient(135deg,#3b82f6,#f59e0b); -webkit-background-clip:text; background-clip:text; color:transparent; }
        p { color:#b9c6e3; }
        a { color:#3b82f6; font-weight:700; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>الصفحة التي تبحث عنها غير موجودة.</p>
    <a href="<?= url('/') ?>">العودة للصفحة الرئيسية</a>
</body>
</html>
