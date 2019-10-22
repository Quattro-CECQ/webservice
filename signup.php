<?php

//ログを取るか
ini_set('log_errors','on');
//ログの出力ファイルを指定
ini_set('error_log','php.log');

//エラーメッセージを定数に設定
define('MSG01','入力必須です');
define('MSG02', 'Emailの形式で入力してください');
define('MSG03','パスワード（再入力）が合っていません');
define('MSG04','半角英数字のみご利用いただけます');
define('MSG05','6文字以上で入力してください');
define('MSG06','256文字以内で入力してください');
define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');
define('MSG08','そのEmailはすでに登録されています');

//配列$err_msgを用意
$err_msg = array();
//dbアクセス結果用
$dbRst = false;

//バリデーション関数（未入力チェック）
function validRequired($str, $key){
  if(empty($str)){
    global $err_msg;
    $err_msg[$key] = MSG01;
  }
}
//バリデーション関数（未入力チェック）
function validEmail($str, $key){
  if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG02;
  }
}
function validEmailDup($email){
  global $err_msg;
  //例外処理
  try{
    //DBへ接続
    $dbh = dbConnect();
    //SQL文作成
    $sql = 'SELECT count(*) FROM users WHERE email = :email';
    $data = array(':email' => $email);
    //クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    //クエリ結果の値を取得
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!empty($result)){
      $err_msg['email'] = MSG08;
    }
  } catch (Exception $e) {
    error_log('エラー発生:' . $e->getMessage());
    $err_msg['common'] = MSG07;
  }
}
//バリデーション関数（同値チェック）
function validMatch($str1, $str2, $key){
  if($str1 !== $str2){
    global $err_msg;
    $err_msg[$key] = MSG03;
  }
}
//バリデーション関数（最小文字数チェック）
function validMinLen($str, $key, $min = 6){
  if(mb_strlen($str) < $min){
    global $err_msg;
    $err_msg[$key] = MSG05;
  }
}
//バリデーション関数（最大文字数チェック）
function validMaxLen($str, $key, $max = 255){
  if(mb_strlen($str) > $max){
    global $err_msg;
    $err_msg[$key] = MSG06;
  }
}
//バリデーション関数（半角チェック）
function validHalf($str, $key){
  if(!preg_match("/^[a-zA-Z0-9]+$/", $key)){
    global $err_msg;
    $err_msg[$key] = MSG04;
  }
}
//DB接続関数
function dbConnect(){
  //DBへの接続準備
  $dsn = 'mysql:dbname = freemarket;host = localhost; charset = utf8';
  $user = 'root';
  $password = 'root';
  $options = array(
    //SQL実行失敗時にはエラーコードのみ設定
    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
    //デフォルトフェッチモードを連想配列形式に設定
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    //バッファードクエリを使う(一度に結果セットを全て取得し、サーバー負荷を軽減)
    //SELECTで得た得た結果に対してもrowCountメソッドを使えるようにする
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
  );
  //PDOオブジェクト生成(DBへ接続)
  $dbh = new PDO($dsn, $user, $password, $options);
  return $dbh;
}
function queryPost($dbh, $sql, $data){
  //クエリー生成
  $stmt = $dbh->prepare($sql);
  //プレースホルダに値をセットし、SQL文を実行
  $stmt->execute($data);
  return $stmt;
}

//post送信されていた場合
if(!empty($_POST)){
  
  //変数にユーザー情報を代入
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  $pass_re = $_POST['pass_retype'];

  //未入力チェック
  validRequired($email, $err_msg['email']);
  validRequired($pass, $err_msg['pass']);
  validRequired($pass_re, $err_msg['pass_retype']);
  
  //バリデーションエラーがない場合
  if(empty($err_msg)){

    //emailの形式チェック
    validEmail($email, 'email');
    //emailの最大文字数チェック
    validMaxLen($email, 'email');
    //email重複チェック
    validEmailDup($email);

    //パスワードの半角英数字チェック
    validHalf($pass, 'pass');
    //パスワードの最大文字数チェック
    validMaxLen($pass, 'pass');
    //パスワードの最小文字数チェック
    validMinLen($pass, 'pass');

    //パスワード(再入力)の最大文字数チェック
    validMaxLen($pass_re, 'pass_re');
    //パスワード(再入力)の最小文字数チェック
    validMinLen($pass_re, 'pass_re');

    //パスワードとパスワード再入力が合っているかチェック
    validMatch($pass, $pass_re, $err_msg['pass']);
    
    //バリデーションエラーがない場合
    if(empty($err_msg)){

      //パスワードの半角英数字チェック
      validHalf($pass, $err_msg['pass']);
      //パスワードの最小文字数チェック
      validMinLen($pass, $err_msg['pass']);
      //パスワードの最大文字数チェック
      validMaxLen($pass, $err_msg['pass']);
      
      //バリデーションエラーがない場合
      if(empty($err_msg)){

        //DBへの接続準備
        $dsn = 'mysql:dbname=freemarket;host=localhost;charset=utf8';
        $user = 'root';
        $password = 'root';
        $options = array(
          // SQL実行失敗時に例外をスロー
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          // デフォルトフェッチモードを連想配列形式に設定
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          // バッファードクエリを使う(一度に結果セットをすべて取得し、サーバー負荷を軽減)
          // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
          PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        );

        // PDOオブジェクト生成（DBへ接続）
        $dbh = new PDO($dsn, $user, $password, $options);
        return $dbh;

        //SQL文（クエリー作成）
        $stmt = $dbh->prepare('INSERT INTO users (email,pass,login_time) VALUES (:email,:pass,:login_time)');

        //プレースホルダに値をセットし、SQL文を実行
        $dbRst = $stmt->execute(array(':email' => $email, ':pass' => $pass, ':login_time' => date('Y-m-d H:i:s')));
        
        //SQL実行結果が成功の場合
        if($dbRst){
          header("Location:mypage.html"); //マイページへ
        }
      }

    }
  }
}

?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="utf-8">
    <title>ユーザー登録 | WEBUKATU MARKET</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
  </head>

  <body class="page-signup page-1colum">

    <!-- メニュー -->
    <header>
      <div class="site-width">
        <h1><a href="index.html">WEBUKATU MARKET</a></h1>
        <nav id="top-nav">
          <ul>
            <li><a href="signup.html" class="btn btn-primary">ユーザー登録</a></li>
            <li><a href="login.html">ログイン</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="mypage.html" class="form">
            <h2 class="title">ユーザー登録</h2>
            <div class="area-msg">
             <?php 
              if(!empty($err_msg)){
                foreach($err_msg as $msg){
                  echo $msg.'<br>';
                }
              }
              ?>
            </div>
            <label>
              Email
              <input type="text" name="email">
            </label>
            <label>
              パスワード <span style="font-size:12px">※英数字６文字以上</span>
              <input type="password" name="pass">
            </label>
            <label>
              パスワード（再入力）
              <input type="paswword" name="pass">
            </label>
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="登録する">
            </div>
          </form>
        </div>

      </section>

    </div>

    <!-- footer -->
    <footer id="footer">
      Copyright <a href="http://webukatu.com/">ウェブカツ!!WEBサービス部</a>. All Rights Reserved.
    </footer>

    <script src="js/vendor/jquery-2.2.2.min.js"></script>
    <script>
      $(function(){
        var $ftr = $('#footer');
        if( window.innerHeight > $ftr.offset().top + $ftr.outerHeight() ){
          $ftr.attr({'style': 'position:fixed; top:' + (window.innerHeight - $ftr.outerHeight()) +'px;' });
        }
      });
    </script>
  </body>
</html>