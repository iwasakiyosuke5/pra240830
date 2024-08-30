

<!DOCTYPE html>
<html>
<head>
    <title>画像アップロード</title>
    <script src="https://cdn.tailwindcss.com"></script>

</head>
<body>
    <h1 class="text-sky-400">PNG画像をアップロードしてください</h1>
    <form action="{{ route('convert.png') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="image_file" accept="image/png" required>
        <button type="submit">テキストに変換</button>
    </form>
    <a href="/question">AIに聞く</a>
</body>
</html>
