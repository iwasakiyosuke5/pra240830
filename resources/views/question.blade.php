<!-- resources/views/question.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Question</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <a href="{{ route('request') }}" role="button" class="ml-2 text-xl text-sky-400">投稿ページへ</a>
</head>
<body>
    <h1 class="ml-2 text-4xl">Ask a Question</h1>
    <form action="{{ route('search') }}" method="POST">
        @csrf
        <label class="ml-2" for="query">Enter your question:</label>
        <input class="ml-2 w-1/3 border border-3-black" type="text" id="query" name="query" required>
        <button class="ml-2 border border-black rounded-md bg-indigo-500 p-2" type="submit">Submit</button>
    </form>
</body>
</html>
