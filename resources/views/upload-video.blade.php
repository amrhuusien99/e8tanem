<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>رفع فيديو</title>
</head>
<body>
    <h1>رفع فيديو</h1>

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <form action="{{ route('upload.video.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="video" required>
        <button type="submit">رفع</button>
    </form>
</body>
</html>