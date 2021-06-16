// ファイル名: 任意の文字列.php
// 動作保証はありませんが、自由に編集して利用してください。
// Created by ActiveTK. <webmaster@activetk.stars.ne.jp>

// 現在時刻を取得
$dt = date("Ymd-His");

//////////////// //////////////// ////////////////
//////////////// ここから編集必要 ////////////////
//////////////// //////////////// ////////////////

// バックアップしたいフォルダーの絶対パス
// レンタルサーバーだと
// /home/ユーザー名/ドメイン/public_html/
// などの形式になる事が多い
$dir = "/home/example/example.com/public_html/";

// 保存先
// 拡張子を必ずzipにする事!
// $dt には現在時刻が入る (ex: 20210606-1634)
// 出来れば非公開ディレクトリが良い
$file = '/home/example/example.com/backup/backup-'.$dt.'.zip';

// バックアップの結果を送信する送信先のメールアドレス
// Cronの結果は出力出来ないので設定する事を推奨
// レンタルサーバーだと迷惑メール扱いされる可能性あり
$tomail = 'sp@activetk.cf';

// バックアップの結果を送信する送信「元」のメールアドレス
// @の後のドメインと送信元IPアドレスのドメインが違うと迷惑メール扱いされる事が多い
// 「serverlog@自分のサイトのドメイン名」にする事をオススメ
$mailfrom = 'serverlog@activetk.cf';

//////////////// //////////////// ////////////////
//////////////// ここから編集不要 ////////////////
//////////////// //////////////// ////////////////

// zip圧縮関数
// https://php-archive.net/php/zip-directory/
function zipDirectory($dir, $file, $root=""){
    $zip = new ZipArchive();
    $res = $zip->open($file, ZipArchive::CREATE);

    if($res){
        if($root != "") {

            $zip->addEmptyDir($root);
            $root .= DIRECTORY_SEPARATOR;
        }
        $baseLen = mb_strlen($dir);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS
                |FilesystemIterator::KEY_AS_PATHNAME
                |FilesystemIterator::CURRENT_AS_FILEINFO
            ), RecursiveIteratorIterator::SELF_FIRST
        );
        $list = array();
        foreach($iterator as $pathname => $info){
            $localpath = $root . mb_substr($pathname, $baseLen);
            if( $info->isFile() ){
                // パスに「backup」が入っているものはコピーしない
                // 任意だが、バックアップしたzipファイルをさらにバックアップしてしまう可能性がある為、
                // 残しておく事を推奨
                if (strpos($pathname, "backup") === false)
                  $zip->addFile($pathname, $localpath);
            } else {
                $res = $zip->addEmptyDir($localpath);
            }
        }
        $zip->close();
    } else {
        return false;
    }
}

// 単位変換関数
function convert($size)
{
    $unit=array('Byte','KB','MB','GB','TB','PD');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

// 時間計測用
$phpstarttime = microtime(true);
$starttime = time();
$startmemory = convert(Memory_get_usage()).", ".Memory_get_usage()."Byte";

// 圧縮実行
zipDirectory($dir, $file);

// メール送信用にファイル数をカウント
$fairusuu = 0;
$dsuu = 0;
$iterator = new RecursiveDirectoryIterator("/home/activetk/");
$iterator = new RecursiveIteratorIterator($iterator);
foreach ($iterator as $fileinfo) {
    if ($fileinfo->isFile()) {
        $fairusuu++;
    }
    else if ($fileinfo->isDir()){
        $dsuu++;
    }
}

// 時間計測用
$endtime = (microtime(true) - $phpstarttime)."s";
$endtimex = time();
$edt = date("Ymd-His");
$endmemory = convert(Memory_get_usage()).", ".Memory_get_usage()."Byte";

// メール送信
$body = '<body style="background-color:#e6e6fa;text:#363636;"><div align="center"><p>[バックアップ実行ログ]</p><hr color="#363636" size="2"><h2>バックアップを実行した条件及び結果は以下の通りでした。</h2>'.
   '<p>実行時刻: '.$dt.' ('.$starttime.')</p><p>終了時刻: '.$edt.' ('.$endtimex.')</p><p>経過時間: '.$endtime.'</p><p>バックアップ元: '.$dir.'</p><p>出力先: '.$file.'</p><p>ファイルサイズ: '.@convert(@filesize($file)).'</p><p>開始時メモリー: '.$startmemory.'</p>'.
   '<p>終了時メモリー: '.$endmemory.'</p>'.
   '<br><hr color="#363636" size="2"><font style="background-color:#06f5f3;">Copyright &copy; 2021 ActiveTK. All rights reserved.</font></div></body>';
mb_language("Japanese");
mb_internal_encoding("UTF-8");
define("MAIL_TO_ADDRESS", $tomail);
define("MAIL_SUBJECT", "[バックアップ実行ログ]");
define("MAIL_BODY", $body);
define("MAIL_FROM_ADDRESS", $mailfrom);
define("MAIL_FROM_NAME", $mailfrom);
define("MAIL_HEADER", "Content-Type: text/html; charset=UTF-8 \n".
"From: " . MAIL_FROM_NAME . "\n".
"Sender: " . MAIL_FROM_ADDRESS ." \n".
"Return-Path: " . MAIL_FROM_ADDRESS . " \n".
"Reply-To: " . MAIL_FROM_ADDRESS . " \n".
"Content-Transfer-Encoding: BASE64\n");
if (!mb_send_mail(MAIL_TO_ADDRESS , MAIL_SUBJECT , MAIL_BODY , MAIL_HEADER, "-f ".MAIL_FROM_ADDRESS)) {
  // メールを送信出来なかった場合の処理
  // echoで表示する
  echo "メールを送信出来ませんでした。";
}

// 一応echoで表示
echo $body;

// 脆弱性対策にPHPの終了タグは無し
