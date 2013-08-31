// WordPress側で生成する設定情報
var app_config = {
  blog_id:'1',
  username:'admin',
  password:'',
  xmlrpc_endpoint:'http://example.com/xmlrpc.php',
  author:1,
  post_status:'publish',
  local_config:true,
  thumbnail:{
    targetWidth:100,
    targetHeight: 100
  },
  category:{
    1: '未分類',
    15:'技術'
  }
};


function saveConfig() {
  var local_config = {
    username:$('#username').val(),
    password:$('#password').val()
  };
  localStorage.setItem("local_config", JSON.stringify(local_config));
}

function loadConfig(){
  var local_config = JSON.parse(localStorage.getItem('local_config'));
  for (var key in local_config){
    app_config[key] = local_config[key];
  }
}

$(document).ready(function(){
  if (app_config.local_config) {
    loadConfig();
  }
  $('#username').val(app_config.username);
  $('#password').val(app_config.password);
  
  $("#save_config").on('submit',function(){
    saveConfig();
    location.reload();
    return false;
  });
  
  // カテゴリ生成
  putCategory();
});
function putCategory(){
　for (var key in app_config.category){
  $("#wp_post [name=category]")
  .append('<input type="checkbox" name="category[]" value="'+ key +'" id="t'+ key +'"><label for="t'+ key +'">'+ app_config.category[key] +'</label>');
  }
}
