


<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <a href="{{ route('question') }}" role="button">投稿ページへ</a>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h4>Search Results</h4>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Your Question</h5>
                    <p class="card-text">{!! $question !!}</p>
                </div>
            </div>
            <br>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">AI Response</h5>
                    <p class="card-text">{!! $aiResponse !!}</p>
                </div>
            </div>
            <br>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ask a Follow-up Question</h5>
                    <form method="POST" action="{{ route('followup.question') }}">
                        @csrf
                        <div class="form-group">
                            <label for="followupQuestion">Your Follow-up Question:</label>
                            <textarea class="form-control" id="followupQuestion" name="followupQuestion" rows="3"></textarea>
                        </div>
                        <input type="hidden" name="previousQuestion" value="{{ $question }}">
                        <input type="hidden" name="previousResponse" value="{{ $aiResponse }}">
                        <button type="submit" class="btn btn-primary mt-3">Ask AI</button>
                    </form>
                </div>
            </div>
            <br>
            <h5>Similar Fragments</h5>
            @foreach($results as $result)
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="card-text">{{ $result['fragment']->content }}</p>
                        <p><strong>Similarity:</strong> {{ $result['similarity'] }}</p>
                        <p><strong>File Path:</strong> <a href="{{ asset($result['fragment']->file_path) }}" target="_blank">{{ $result['fragment']->file_path }}</a></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
</body>
</html>

