/*
 * フォームの値を元にWordPressに投稿を行います。
 * 投稿はXMLRPCプロトコルを利用します
 * 投稿のカテゴリはconfig.jsをカスタマイズすることで対応可能です
 * カスタム投稿タイプやカスタムフィールドに対応する場合はコードを改造してください。
 */
$(document).ready(function(){
  // 投稿ページの作成
  $("#wp_post").on('submit',function(){
    // フォームの受け取り
    var content = {
      post_title: $('#wp_post [name=title]').val(),
      post_content: $('#wp_post [name=content]').val(),
      post_type:'post'
    };
    
    // もしサムネイルがあれば、先にアップする処理を実施
    if($('#post_thumbnail').attr('src')){
      content.post_thumbnail = uploadFile($('#post_thumbnail').attr('src'));
    } 

    // タクソノミー対応
    content.terms = {};
    content.terms.category = [];
    $('[name="category[]"]:checked').each(function(){
      content.terms.category.push($(this).val());
    });
    // フォーム投稿
    newPost(content);
    location.reload();
		return false;
	});
  
  // 固定ページの作成
  $("#wp_page").on('submit',function(){
    // フォームの受け取り
    var content = {
      post_title: $('#wp_page [name=title]').val(),
      post_content: $('#wp_page [name=content]').val(),
      post_type:'page'
    };
    // もしサムネイルがあれば、先にアップする処理を実施
    if($('#page_thumbnail').attr('src')){
      content.post_thumbnail = uploadFile($('#page_thumbnail').attr('src'));
    } 
    
    // フォーム投稿
    newPost(content);
    location.reload();
		return false;
	});
  
  // カスタム投稿ページの作成 (ご自身のカスタム投稿ページに合わせてカスタマイズしてください)
  $("#wp_custom1").on('submit',function(){
    // フォームの受け取り
    var content = {
      post_title: $('#wp_custom1 [name=title]').val(),
      post_content: $('#wp_custom1 [name=content]').val(),
      post_type:'book'
    };
    
    // カスタムフィールド対応
    content.custom_fields = [];
    if ($('#isbn10').val()) {
      content.custom_fields.push({key:'isbn10', value:$('#isbn10').val()});
    }
    if ($('#isbn13').val()) {
      content.custom_fields.push({key:'isbn13', value:$('#isbn13').val()});
    }
    
    // タクソノミー対応
    content.terms = {};
    content.terms.book_genre = [];
    $('[name="book_genre[]"]:checked').each(function(){
      content.terms.book_genre.push($(this).val());
    });
    
    // もしサムネイルがあれば、先にアップする処理を実施
    if($('#custom1_thumbnail').attr('src')){
      content.post_thumbnail = uploadFile($('#custom1_thumbnail').attr('src'));
    } 
    
    // フォーム投稿
    newPost(content);
    location.reload();
		return false;
	});
});

/**
 *  投稿を行い、結果を返します
 */
function newPost(content){
  content.post_status = app_config.post_status;
  content.author = app_config.author;

  var request = new XmlRpcRequest(app_config.xmlrpc_endpoint, 'wp.newPost');  
  request.addParam(app_config.blog_id);
  request.addParam(app_config.username);
  request.addParam(app_config.password);
  request.addParam(content);
  return request.send();  
}

/**
 * PhoneGapのカメラAPIを利用して写真かフォトライブラリの画像を取得し、サムネイル表示領域に表示する
 */
function setThumbnail (id,source) {
  var options = {
    destinationType : Camera.DestinationType.DATA_URL, 
    targetWidth: app_config.thumbnail.targetWidth,
    targetHeight: app_config.thumbnail.targetHeight
  };
  var image = $("#"+id);

  if (source == 'camera') {
  } else if (source == 'library') {
    options.sourceType = navigator.camera.PictureSourceType.PHOTOLIBRARY;
  } else {
    image.removeAttr('src');
    image.css('display','none');
    return;
  }
  
  navigator.camera.getPicture(onSuccess, onFail, options);
  
  // 画像取得に成功した際に呼ばれるコールバック関数
  function onSuccess (imageData) {
    image.attr("src", 'data:image/jpeg;base64,' + imageData);
    image.css('display','block');
  }
  // 画像取得に失敗した場合に呼ばれるコールバック関数
  function onFail (message) {
    alert('画像取得に失敗しました');
  }
}

/*
 * Base64形式の画像データをWordPressに投稿し、IDを取得する
 * なお、ファイル名は現在時刻を利用します
 */
function uploadFile(src) {
  // src値をパースしてmimicが用意するBase64オブジェクトにbase64値をセット。また、mimic側でのエンコードを行わないようにする。
  var base64 = src.split(',')[1];
  var file = new Base64(base64);
  file.encode = function() { return this.bytes ; };
  
  var filename = +new Date() + '.jpg';
  var data = {
    name:filename,
    type:'image/jpeg',
    bits:file,
  };
  
  var request = new XmlRpcRequest(app_config.xmlrpc_endpoint,"wp.uploadFile");  
  request.addParam(app_config.blog_id);
  request.addParam(app_config.username);
  request.addParam(app_config.password);
  request.addParam(data);
  var response = request.send();  
  
  // XML>JSONの順にパースしてIDを返す
  return JSON.parse(response.parseXML().id);
}
