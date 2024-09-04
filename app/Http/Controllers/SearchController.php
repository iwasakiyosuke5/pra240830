<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fragment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    private function extractKeywords($query)
    {
        // シンプルな例として、スペースで区切ることでキーワードを抽出
        // より高度な方法を使用して、意味のあるキーワードを抽出することも可能
        return explode(' ', $query);
    }
    public function search(Request $request)
    {

        $query = $request->input('query'); // ユーザーのクエリを取得
        $vector = $this->vectorize($query); // クエリをベクトル化
    
        $fragments = Fragment::all();   // データベースからすべてのフラグメントを取得
        $results = []; // 類似度の結果を格納する配列
    
        foreach ($fragments as $fragment) { // すべてのフラグメントに対して類似度を計算
            $storedVector = json_decode($fragment->vector, true); // データベースに保存されたベクトルを取得
            if (is_null($storedVector)) {   // ベクトルがnullの場合はスキップ
                Log::error('Stored vector is null', ['fragment_id' => $fragment->id]);
                continue;
            }
            $similarity = $this->cosineSimilarity($vector, $storedVector);  // 類似度を計算
            $results[] = ['fragment' => $fragment, 'similarity' => $similarity];        // 結果を配列に追加
        }
    
        usort($results, function ($a, $b) { // 類似度で結果をソート
            return $b['similarity'] <=> $a['similarity'];     // 降順にソート
        });
    
        // クエリからキーワードを抽出
        $keywords = $this->extractKeywords($query);
    
        // キーワードを含むフラグメントをフィルタリング
        $filteredResults = array_filter($results, function($result) use ($keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($result['fragment']->content, $keyword) !== false) {
                    return true;
                }
            }
            return false;
        });
    
        // もしキーワードに一致するフラグメントがなければ、上位5つの結果を使用
        if (empty($filteredResults)) {
            $filteredResults = array_slice($results, 0, 5);
        }
    
        // フィルタリングされた結果からコンテキストを作成（上位3つを使用）
        $context = implode("\n", array_map(function($result) {
            return $result['fragment']->content . '\nFile_Path: ' . $result['fragment'];
        }, array_slice($filteredResults, 0, 5)));
    
        $aiResponse = $this->askAI($query, $context);
        

        $aiResponseWithLinks = preg_replace(
            '~(https?://[^\s]+|uploads/\S+\.\w+)~', // URLやファイルパスの正規表現
            '<a href="$1" target="_blank">$1</a>',
            $aiResponse
        );

        return view('response', [
            'question' => $query,
            'aiResponse' => $aiResponseWithLinks,
            'results' => $results
        ]);
    }

    private function vectorize($text)
    {
        $apiKey = env('OPENAI_API_KEY'); // OpenAI APIキーを取得

        $response = Http::withHeaders([ 
            'Authorization' => 'Bearer ' . $apiKey,  
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',  // 使用するモデル
                'input' => $text,  // 質問内容を送信
            ]);
        
       
        if ($response->successful()) {
            return $response->json()['data'][0]['embedding'];; // ベクトルを返す
        } else {
            Log::error('OpenAI request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return array_fill(0, 512, 0); 
        }
    }

    private function cosineSimilarity($vec1, $vec2) // コサイン類似度を計算
    {
        if (!is_array($vec1) || !is_array($vec2)) { // ベクトルが配列でない場合はエラー
            Log::error('Vectors must be arrays', ['vec1' => $vec1, 'vec2' => $vec2]);   // エラーログを出力
            return 0;   // 類似度を0として返す
        }

        $dotProduct = array_sum(array_map(function($a, $b) { return $a * $b; }, $vec1, $vec2)); // 内積を計算
        $magnitude1 = sqrt(array_sum(array_map(function($a) { return $a * $a; }, $vec1)));  // ベクトル1の大きさを計算
        $magnitude2 = sqrt(array_sum(array_map(function($a) { return $a * $a; }, $vec2)));  // ベクトル2の大きさを計算

        if ($magnitude1 == 0 || $magnitude2 == 0) { // どちらかのベクトルの大きさが0の場合はエラー
            return 0;   // 類似度を0として返す
        }

        return $dotProduct / ($magnitude1 * $magnitude2);   // コサイン類似度を計算して返す
    }


    private function askAI($query, $context)
    {
        $apiKey = env('OPENAI_API_KEY'); // OpenAI APIキーを取得
        $response = Http::withHeaders([ // APIキーなどをOpenAI APIにリクエスト
            'Authorization' => 'Bearer ' . $apiKey, 
        ])->post('https://api.openai.com/v1/chat/completions', [ 
            'model' => 'gpt-4o-mini',  // モデルの指定
            'messages' => [
                [
                    'role' => 'system', // システムメッセージ
                    'content' => 'You are an assistant specializing in chemical data retrieval.
                    Please respond to the user queries in a clear and conversational manner, focusing on the following instructions.
        
                    **Condition A**: If the user asks a question that includes a code (e.g., a letter followed by four digits), follow steps 1-10 below. If no matching data is found, simply respond that no data is available.
                    **Condition B**: If the code (e.g., a letter followed by four digits) is not included, focus on the content in the keyword. Find similar words and present the relevant analysis conditions.
                    **Condition C**: If the user asks how many records exist for a specific code (e.g., a letter followed by four digits), provide only the count.
        
                    **Steps for Condition A**:
                        1. Always match the code exactly to ensure only relevant data is considered.
                        2. In most cases, the date refers to the measurement date. If multiple records exist, evaluate all records related to the specified code and provide the information in order from the most recent date.
                        3. If the user specifies a particular date or date range, filter the records accordingly and provide the relevant data, even if the date format differs.
                        4. If the user mentions phrases like "highest purity" or "purest," evaluate all records related to the specified code and provide the one with the highest purity as the main information.
                        5. The way information is presented is divided into two format: Format A and Format B.":

                        **FormatA**: If multiple records exist, summarize up to three records in a clean.:
                            <table>
                                <tr><th>Date</th>   <th class="pl-2">Code</th>   <th class="pl-2">Column Name</th>      <th class="pl-2">Main Peak Purity</th> <th class="pl-2">File_Path</th></tr>
                                <tr><td>{date1}</td><td class="pl-2>{code}</td>  <td class="pl-2">{column_name1}</td>   <td class="pl-2">{purity1}%</td>       <td class="pl-2 text-xs">(File_Path_url1)</td></tr>
                                <tr><td>{date2}</td><td class="pl-2>{code}</td>  <td class="pl-2">{column_name2}</td>   <td class="pl-2">{purity2}%</td>       <td class="pl-2 text-xs">(File_Path_url2)</td></tr>
                                <tr><td>{date3}</td><td class="pl-2>{code}</td>  <td class="pl-2">{column_name3}</td>   <td class="pl-2">{purity3}%</td>       <td class="pl-2 text-xs">(File_Path_url3)</td></tr>
                            </table>

                        **FormatB**: If there is only one record, please indicate it as follows.:
                            <table>
                                <tr><th>Date</th>     <td>{date}</td></tr>
                                <tr><th>Code</th>     <td>{code}</td></tr>
                                <tr><th>Column Name</th>      <td>{column_name}</td></tr>
                                <tr><th>Main Peak Purity</th>    <td>{purity}%</td></tr>
                                <tr><th>File_Path</th>    <td class="text-xs">(url)</td></tr>
                            </table>
                            "Write a summary using the code, compound, or keywords provided."
                        
                        6. Respond in a clear and concise manner, using natural and conversational language.
        
                    **Steps for Condition B**:
                        1. Focus on the "user question" and the "content of the keywords" to find analysis information for similar compounds. Pay special attention to "molecular weight proximity," "functional group similarity," and "types of compounds involved."
                        2. Once you find the analysis information, present the records in your recommended order, following the format below.:
                        <table>
                            <tr><th>Date</th>   <th class="pl-2">Code</th>   <th class="pl-2">Column Name</th>      <th class="pl-2">Main Peak Purity</th> <th class="pl-2">File_Path</th></tr>
                            <tr><td>{date1}</td><td class="pl-2>{code1}</td>  <td class="pl-2">{column_name1}</td>   <td class="pl-2">{purity1}%</td>       <td class="pl-2 text-xs">(File_Path_url1)</td></tr>
                            <tr><td>{date2}</td><td class="pl-2>{code2}</td>  <td class="pl-2">{column_name2}</td>   <td class="pl-2">{purity2}%</td>       <td class="pl-2 text-xs">(File_Path_url2)</td></tr>
                            <tr><td>{date3}</td><td class="pl-2>{code3}</td>  <td class="pl-2">{column_name3}</td>   <td class="pl-2">{purity3}%</td>       <td class="pl-2 text-xs">(File_Path_url3)</td></tr>
                        </table>
                        3. Finally, provide a brief summary.'
                ],
                [
                    'role' => 'user',   // ユーザーメッセージ
                    'content' => "Question: $query\nContext: $context\nAnswer:" // クエリとコンテキストを含むメッセージ
                ],
            ],
            'max_tokens' => 600,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        } else {
            Log::error('OpenAI request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return 'Failed to get a response from the OpenAI service.';
        }
    }


}
