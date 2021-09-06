
<?php
header("Content-type:text/html;charset=utf-8");
$h = date("H",time());
$i = date("i",time());
$w = date('w');
date_default_timezone_set("Asia/Shanghai");


function request_post($url = '', $post_data = array(),$dataType='') {
	if (empty($url) || empty($post_data)) {
		return false;
	}
	$curlPost = $post_data;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if($dataType=='json'){javascript:;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
				'Content-Length: ' . strlen($curlPost)
			)
		);
	}
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$data = curl_exec($ch);
	return $data;
}

function https_request ($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $out = curl_exec($ch);
    curl_close($ch);
    return  json_decode($out,true);
}

// 获取access_token
function getToken(){
    // 定义id和secret
    $corpid='自己的企业微信corpid';
    $corpsecret='自己的应用secret';
    // 读取access_token
    include './access_token.php';
    // 判断是否过期
    if (time() > $access_token['expires']){
        // 如果已经过期就得重新获取并缓存
        $access_token = array();
        $access_token['access_token'] = getNewToken($corpid,$corpsecret);
        $access_token['expires']=time()+7000;
        // 将数组写入php文件
        $arr = '<?php'.PHP_EOL.'$access_token = '.var_export($access_token,true).';'.PHP_EOL.'?>';
        $arrfile = fopen("./access_token.php","w");
        fwrite($arrfile,$arr);
        fclose($arrfile);
        // 返回当前的access_token
        return $access_token['access_token'];
    }else{
        // 如果没有过期就直接读取缓存文件
        return $access_token['access_token'];
    }
}

// 获取新的access_token
function getNewToken($corpid,$corpsecret){
    $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$corpsecret}";
    $access_token_Arr = https_request($url);
    return $access_token_Arr['access_token'];
}

// 发送消息
function sc_send($comment_id)  { 
    // GroupUrl是企业微信群机器人的发送地址，AppUrl是应用的发送地址
    $GroupUrl = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=自己机器人的key';
    $AppUrl =  'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.getToken();
    
    
    $comment = get_comment($comment_id); 
	$cont = $comment->comment_content;
    $author = $comment->comment_author;
    $title = get_post($comment->comment_post_ID)->post_title;
    $headImg = "http://blogimg.lieme.cn/FmgJkCHkCJWHQdLXAXWjGL204IDx";
    $pageUrl = site_url().'/?page_id='.$comment->comment_post_ID ;

    // 企业微信群机器人消息通知模板
	// 文本消息
    $textData = array(
        "msgtype"=>"text",
        "text"=>array(
            "content"=>$cont,
            "mentioned_list"=>array("@all")
        )
    );
    // 图文消息
	$mediaData = array(
		"msgtype"=>"news",
		"news"=>array(
		    "articles"=>array(
		        array(
    		        "title"=>date("Y-m-d H:i:s",time())."@".$author."给你发来一条评论",
                    "description"=>$cont,
                    "url"=>$pageUrl,
                    "picurl"=> $headImg,
                )
		    )
        )
    );
    // 企业微信应用通知模板
	$postdata = array(
        'touser' => '@all',
        'msgtype' => 'template_card',
        'agentid' => '1000003',
        'template_card' => array(
            "card_type" => "text_notice",
            "source" => array(
                "icon_url"=>"https://blogimg.lieme.cn/cropped-2021043007534461.png",
                "desc"=> get_bloginfo('name')
            ),
            "main_title" => array(
                "title" => '你收到收到一条新的评论',
                "desc"=> date("Y-m-d H:i:s",time())
            ),
            "horizontal_content_list" => array(
                array(
    		        "keyname"=>'评论人',
                    "value"=>$author,
                ),
                array(
    		        "keyname"=>'评论文章',
                    "value"=> $title,
                ),
                array(
    		        "keyname"=>'评论内容',
                    "value"=>$cont,
                ),
            ),
            "jump_list" => array(
                array(
                    "type" => 1,
                    "title"=>"Go Go Go",
                    "url" => $pageUrl,
                ),
            ),
            "card_action"=> array(
                "type" => 1,
                "url"=> $pageUrl,
            )
        ),
        'enable_id_trans' => 0,
        'enable_duplicate_check' => 0,
        'duplicate_check_interval' => 1800
    );
	$res = request_post($AppUrl, json_encode($postdata,'320'),'json');
}  
add_action('comment_post', 'sc_send', 19, 2);