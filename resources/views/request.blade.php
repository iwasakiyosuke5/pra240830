

<!DOCTYPE html>
<html>
<head>
    <title>画像アップロード</title>
    <script src="https://cdn.tailwindcss.com"></script>

</head>
<body>
    <h1 class="ml-2 text-sky-400">PNG画像をアップロードしてください</h1>
    <form action="{{ route('convert.png') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input class="ml-2" type="file" name="image_file" accept="image/png" required>
        <button class="border border-black rounded-md bg-indigo-500 p-2" type="submit">AIに要約依頼</button>
    </form>
    <a href="{{ route('question') }}" class="ml-2 rounded-md text-rose-500 p-2" >AIに質問する</a>
</body>
</html>
