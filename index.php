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
	#snippet{display:block}
	pre{width:100%;height:75%}
	textarea{width:100%;height:25%}

	#code{height:100%;width:100%}
	.tab{height:100%;width:100%;display:none}

	#preview{position:absolute;top:0;right:0}
    </style>
</head>
<body>
    <section>
	<div id="snippet" class="tab">
	<pre><code id="code" class="hljs" spellcheck="false"  contenteditable><?=htmlentities('class StepController extends AbstractController {
	use \Application\Service\ServiceLocatorAwareTrait;
	use \Euromaster\Quotation\Service\QuoteServiceTrait;
	use \Euromaster\Quotation\Service\VehicleServiceTrait;')?>
	</code></pre>
	<textarea spellcheck="false"></textarea>
	</div>
	<div id="output" class="tab">
	<pre></pre>
	<textarea spellcheck="false"></textarea>
	</div>
    </section>
    <section id="preview">
            <!--<select id="langs" name="langs"></select>-->
            <select id="themes" name="themes" data-target="snippet"></select>
	    <input id="paste-email-code" value="EMAIL" type="button" data-target="output" />
    </section>
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="highlight.pack.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
    <script>
    $(window).load(function(){
        // update langs with hljs.listLanguages()
	/*var langs = hljs.listLanguages(), slangs=$('#langs');
	for (var i=0; i<langs.length; i++) {
	    slangs.append('<option name="'+langs[i]+'">'+langs[i]+'</option>');
        }*/
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
	
	/*$('#langs').on('change', function(e){
	    $('#snippet code').attr('class', "hljs " + this.value);
        });*/
    });
    $('body').on('change click', 'input,select', function(){
	    var target=$(this).data('target');
	    $('.tab').hide();
	    $('body').find('#'+target).show();
	    switch (target) {
	        case 'snippet':
			$('#theme-css').attr('href', './styles/'+this.value+'.css');
			var area = $('#snippet textarea'), content=$('#snippet pre').html();
			area.html(content);
			break;
	        case 'output':
			var area = $('#output textarea'), content=$('#snippet pre').html(), output=$('#output pre');
			$.post('/',{
				do: 'paste_email_code',
				code: content,
				css: [$('#themes').children('option:selected').val()]
			}, function(data) {
				// update textarea
				area.html(data);
				//output.html(String(data).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
				output.html($(data));
			});
			break;
	    }
    });
    /*$('#themes').on('change', function(e){
        $('#theme-css').attr('href', './styles/'+this.value+'.css');
    });*/

    //$('body').on('focusout', 'code[contenteditable]',function(){hljs.highlightBlock(document.getElementById('snippet'));});

    /*$('#paste-code').on('click', function(e){
	var area = $('textarea'), content=$('#snippet').html(), output = $('#output');
	area.html(content);
    });
    $('#paste-email-code').on('click', function(e){
	var area = $('textarea'), content=$('#snippet').html(), output=$('#snippet');
	$.post('/',{
	        do: 'paste_email_code',
	        code: $('#snippet').html(),
		css: [$('#themes').children('option:selected').val()]
	    },
	    function(data){
		// update textarea
		area.html(data);
		//output.html(String(data).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
		output.html($(data));
	    }
	);
    });*/
    </script>
</body>
</html>
