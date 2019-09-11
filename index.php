<?php
require 'vendor/autoload.php';
function is_xhr(){return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');}
function is_post(){return is_xhr() && strtolower($_SERVER['REQUEST_METHOD'])=='post';}
function is_get(){return is_xhr() && strtolower($_SERVER['REQUEST_METHOD'])=='get';}
function post($key, $default=null){return isset($_POST[$key]) ? $_POST[$key] : $default;}
function get($key, $default=null){return isset($_GET[$key]) ? $_GET[$key] : $default;}
function get_themes(){
    $list = [];
    foreach (glob('styles/*.css') as $filename){
        $list[] = str_replace(['.css', 'styles/'],'',$filename);
    }
    return $list;
}
function paste_email_code(){
    $code = post('code');
    $themes = post('css');
    $css =null;
    foreach ($themes as $theme){
        $css .= file_get_contents('styles/'.$theme.'.css').PHP_EOL;
    }
    $emogrifier = new \Pelago\Emogrifier($code, $css);
    $body = $emogrifier->emogrifyBodyContent();
    //$body = $emogrifier->emogrify();
    //return htmlentities($body);
    return $body;
}
if (is_xhr()){
    $res = null;
    $do = post('do');
    switch ($do){
	case 'get_themes': $res = json_encode(get_themes());break;
	case 'paste_email_code': $res = paste_email_code();break;
    }
    echo $res;
    exit;
}
?>
<html>
<head>
    <!--<link rel="stylesheet" href="./styles/default.css" />-->
    <link id="theme-css" rel="stylesheet" href="" />
    <style>
    .col{width:50%;height:100%;float:left}
	#snippet{display:block;background:#eee}
	pre{width:100%;height:75%;margin:0;padding:0}
	textarea{width:100%;height:25%}

	code{height:100%;width:100%}
	.tab{height:100%;width:100%;display:none}
    #preview{text-align:center}
    </style>
</head>
<body>
    <section id="preview">
        <select id="langs" name="langs" data-target="snippet"></select>
        <select id="themes" name="themes" data-target="snippet"></select>
        <input id="paste-preview-code" value="PREVIEW" type="button" data-target="snippet" />
        <input id="paste-email-code" value="EMAIL" type="button" data-target="output" />
    </section>
    <textarea id="bucket" class="col" spellcheck="false"></textarea>
    <div class="col">
        <section>
	        <div id="snippet" class="tab">
	            <pre><code id="code" class="hljs" spellcheck="false"></code></pre>
	            <textarea spellcheck="false"></textarea>
	        </div>
	        <div id="output" class="tab">
	            <pre></pre>
	            <textarea spellcheck="false"></textarea>
	        </div>
        </section>
    </div>
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="highlight.pack.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
    <script>
    $(window).load(function(){
        // update langs with hljs.listLanguages()
	    var langs = hljs.listLanguages(), slangs=$('#langs');
        for (var i=0; i<langs.length; i++) {
            slangs.append('<option name="'+langs[i]+'">'+langs[i]+'</option>');
        }
	    var sthemes=$('#themes');
	    // update themes
	    $.post('/', {do: 'get_themes'}, function(data){
	        themes = $.parseJSON(data);
	        for (var i=0; i<themes.length; i++) {
	            sthemes.append('<option name="'+themes[i]+'">'+themes[i]+'</option>');
	        }
	    });
        // TODO make sure to hightlight code on focusout
        //$('body').on('focusout', 'code[contenteditable]',function(){hljs.highlightBlock(document.getElementById('snippet'));});
    });
    $('body').on('change click', 'input,select', function(){
	    var target=$(this).data('target'), theme=$('#themes').val(), lang=$('#langs').val(), raw=$('#bucket').val(), content=hljs.highlight(lang, raw).value;
	    $('.tab').hide();
	    $('body').find('#'+target).show();
        $('#theme-css').attr('href', './styles/'+theme+'.css');
        $('code').attr('class', "hljs " + lang);
	    switch (target) {
	        case 'snippet':
			var area = $('#snippet textarea'), snippet=$('#snippet pre code');
			//snippet.html(String(content).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
			snippet.html(content);
			area.html(content);
			break;
	        case 'output':
			var area = $('#output textarea'), output=$('#output pre');
			$.post('/',{
				do: 'paste_email_code',
				code: '<code class="hljs '+lang+'" spellcheck=false>'+content+'</code>',
                //code: String(content).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'),
				css: [theme]
			}, function(data) {
				// update textarea
				area.html(data);
				//output.html(String(data).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
				//output.html($(data));
				output.html(data);
			});
			break;
	    }
    });
    </script>
</body>
</html>
