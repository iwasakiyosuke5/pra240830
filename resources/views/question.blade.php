<!-- resources/views/question.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Question</title>
    <a href="/" role="button">投稿ページへ</a>
</head>
<body>
    <h1>Ask a Question</h1>
    <form action="{{ route('search') }}" method="POST">
        @csrf
        <label for="query">Enter your question:</label>
        <input type="text" id="query" name="query" required>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
