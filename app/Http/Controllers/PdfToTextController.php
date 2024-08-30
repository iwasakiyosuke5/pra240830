<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Fragment;
// use Spatie\PdfToImage\Pdf;

class PdfToTextController extends Controller
{
    public function convertPng(Request $request)
    {
        // PNGファイルの取得
        $originalName = $request->file('image_file')->getClientOriginalName();
        $filename = pathinfo($originalName, PATHINFO_FILENAME) . '_' . time() . '.' . $request->file('image_file')->extension();
        $filePath = $request->file('image_file')->move(public_path('uploads'), $filename);

        // PNG画像をBase64形式にエンコード
        // $imageData = base64_encode(file_get_contents($imagePath));
        $imageData = base64_encode(file_get_contents($filePath));

        return $this->sendToOpenAI($imageData, 'uploads/' . $filename);
    }


    private function sendToOpenAI($imageData, $filePath)
    {
        // OpenAI APIへのリクエスト準備
        $apiKey = env('OPENAI_API_KEY');
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ];

        $payload = [
            'model' => 'gpt-4o-mini', // 視覚モデルを使用
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            // バージョン１
                            // 'text' => 'Can you summarize the important points from this chromatogram? Only give the peak data, gradient condition, and sample information in bullet points, and highlight the main peak information if available.'
                            // バージョン２
                            // 'text' => 'Please bullet point the important points from this chromatogram. In particular, please list the measurement date, measurement code or sample, column name, sample preparation method (sample concentration), inj volume, mobile phase, program, measurement time, and if there are multiple peaks, list the top two peaks along with their area %. There is no problem in using abbreviations for each item. If no items are found to list, please mention this at the end of the response.'                        ],
                            // まあまあ良い感じ
                            // 'text' => "Please analyze the provided HPLC chromatogram image and categorize the information into the following sections:\n\n"
                            //             . "1. Basic Information: Extract details such as the measurement date, compound (or code number/lot), and PO (purchase order number). If an item is not specified in the image, omit it from the report.\n"
                            //             . "2. Measurement Conditions: List the column used, temperature, mobile phases, and time program.\n"
                            //             . "3. Peak Analysis: Identify and report the top two peak area percentages along with their retention times (RT). If only one peak is present, report that single peak.\n\n"
                            //             . "Please conclude the response with only 'keyword:'"  ],
                            'text' => "Please analyze the provided HPLC chromatogram image and categorize the information into the following sections:\n\n"
                                        . "1. Basic Information: If available, extract details such as the measurement date, compound or code, PO and other basic information. If an item is not found in the image, omit it.\n"
                                        . "2. Measurement Conditions: If provided, list the column used, INJ_Vol and other measurement conditions. Only include information that is specified in the image.\n"
                                        . "3. Peak Analysis: Identify and report the top two peak area percentages along with their retention times (RT). If only one peak is present, report that single peak.\n\n"
                                        . "Please conclude the response with only 'keyword:'"  ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:image/png;base64,{$imageData}"
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 300,
        ];

        // OpenAI APIにリクエストを送信
        $response = Http::withHeaders($headers)->post('https://api.openai.com/v1/chat/completions', $payload);

        // レスポンスの取得
        $result = $response->json();

        // テキストデータを次のページに送信
        $textData = $result['choices'][0]['message']['content'] ?? 'テキストデータが取得できませんでした。';
        return view('watch', ['textData' => $textData, 'imageData' => $imageData, 'filePath' => $filePath]);
    }


    public function upload(Request $request)
    {
        $text = $request->input('text_data');
        $filePath = $request->input('file_path');
        // OpenAIでのベクトル化
        $vector = $this->vectorizeWithOpenAI($text);

        if ($vector === null) {
            return back()->withErrors(['msg' => 'ベクトル化に失敗しました。']);
        }

        // データベースに保存
        Fragment::create([
            'content' => $text,
            'vector' => json_encode($vector),
            'file_path' => $filePath,
        ]);

        return redirect('/')->with('success', 'データがアップロードされました。');
    }

    private function vectorizeWithOpenAI($text)
    {
        $apiKey = env('OPENAI_API_KEY');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => 'text-embedding-3-small',  // 使用するモデル
            'input' => $text,
        ]);

        if ($response->successful()) {
            return $response->json()['data'][0]['embedding'];
        } else {
            return null;
        }
    }
}
