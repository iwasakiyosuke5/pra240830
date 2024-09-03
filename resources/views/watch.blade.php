<!DOCTYPE html>
<html>
<head>
    <title>テキスト結果</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    
    <div class="flex w-full">
        <div class="w-1/3">
            <div class="justify-center">
                <div>
                    <h1>AIが抽出した内容を確認の上、入力欄に付加情報を加えてください。</h1>
                    <p>分析条件だけでなく、AIが情報を探すのに有益な情報を加えましよう</p>
                    <p>分子量、主な官能基、化合物のキーワードなど</p>
                </div>
                <div>
                    <h1>具体例1</h1>
                    <img src="{{ asset('img/G0589.png') }}" alt="">
                    <p>分子量は1702.9。6糖の糖脂質。</p>
                    <p>官能基:19個の水酸基とシアル酸、セラミドを持つ無保護糖</p>
    
                    <h1>具体例２</h1>
                    <img src="{{ asset('img/A2470.png') }}" alt="">
                    <p>分子量204.23。</p>
                    <p>キーワード:アミノ酸ビルディングブロック、無保護の-NH2とCOOHあり</p>
        
                    <h1>具体例3</h1>
                    <img src="{{ asset('img/B5588.png') }}" alt="">
                    <p>分子量319.21。芳香族ボロン酸エステル</p>
                    <p>キーワード:-NHBocあり。</p>
                </div>
    
            </div>
        </div>
        <div class="w-1/3">
            <h1 class="text-sky-800">PDFから抽出されたテキスト</h1>
            <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <textarea class="border-2 border-black w-4/5" name="text_data" id="" cols="" rows="30">{{ $textData }}</textarea>
                <input type="hidden" name='file_path' value={{ $filePath }}>
                <button class="border border-black rounded-md bg-indigo-500 p-2" type="submit">dataをUpload</button>
            </form>
            
        </div>

        <div class="w-1/3">
            {{-- pngの表示 --}}
            <div  class="zoom-container border-4 border-black">
                <img src="data:image/png;base64,{{ $imageData }}" alt="Uploaded Image" class="zoom-image"  id="zoom-image">
            </div>
        </div>
    </div>
    

    <a href="{{ route('request') }}" class="ml-2 rounded-md text-rose-500 p-2">別のPDFをアップロード</a>


    <style>
        .zoom-container {
            width: 100%;
            height: 800px; /* 表示エリアの高さを指定 */
            overflow-y: auto; /* 縦方向のスクロールを有効に */
            overflow-x: auto; /* 横方向のスクロールを有効に */
            position: relative;
            border: 1px solid #ccc; /* 画像の周りに枠を追加 */
            cursor: zoom-in;
        }

        .zoom-image {
            width: 100%;
            height: 100%;
            object-fit: contain; /* 画像をコンテナ内に収める */
            transition: transform 0.3s ease; /* 拡大縮小のスムーズなトランジション */

            
        }

        /* 拡大時の状態 */
        .zoomed {
            cursor: zoom-out; /* 拡大時のカーソル */
            height: auto;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const image = document.getElementById('zoom-image');
            let isZoomed = false; // 現在のズーム状態を追跡

            image.addEventListener('click', function (event) {
                if (!isZoomed) {
                    const rect = image.getBoundingClientRect();
                    const offsetX = event.clientX - rect.left;
                    const offsetY = event.clientY - rect.top;

                    const scale = 2;
                    const originX = (offsetX / rect.width) * 100;
                    const originY = (offsetY / rect.height) * 100;

                    image.style.transformOrigin = `${originX}% ${originY}%`;
                    image.style.transform = `scale(${scale})`;
                    image.classList.add('zoomed');
                    isZoomed = true;
                } else {
                    image.style.transform = `scale(1)`;
                    image.classList.remove('zoomed');
                    isZoomed = false;
                }
            });
        });
    </script>

    
</body>
</html>
