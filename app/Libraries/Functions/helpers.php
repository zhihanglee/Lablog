<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Libraries\Extensions\Tree;
use Illuminate\Support\Facades\Mail;
use HyperDown\Parser;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Redis;

if (!function_exists('transform_time')) {
    /**
     * 转换日期或者时间戳为距离现在的时间
     *
     * @param $time
     * @return bool|string
     */
    function transform_time($time)
    {
        // 如果是日期格式的时间;则先转为时间戳
        if (!is_integer($time)) {
            $time = strtotime($time);
        }
        $int = time() - $time;
        if ($int <= 2) {
            $str = sprintf('刚刚', $int);
        } elseif ($int < 60) {
            $str = sprintf('%d秒前', $int);
        } elseif ($int < 3600) {
            $str = sprintf('%d分钟前', floor($int / 60));
        } elseif ($int < 86400) {
            $str = sprintf('%d小时前', floor($int / 3600));
        } elseif ($int < 1728000) {
            $str = sprintf('%d天前', floor($int / 86400));
        } else {
            $str = date('Y-m-d H:i', $time);
        }
        return $str;
    }
}
if (!function_exists('show_message')) {
    /**
     * 操作成功或者失败的提示
     *
     * @param string $message
     * @param bool $success
     */
    function show_message($message = '成功', $success = true)
    {
        $alertType = $success ? 'success' : 'error';
        Session::flash('alertMessage', $message);
        Session::flash('alertType', $alertType);
    }
}
if (!function_exists('set_active')) {
    /**
     * 设置导航栏状态
     *
     * @param string $route
     */
    function set_active($route)
    {
        return (request()->is($route . '/*') || request()->is($route)) ? "active" : '';
    }
}
if (!function_exists('ajax_return')) {
    /**
     * ajax返回数据
     *
     * @param string $data 需要返回的数据
     * @param int $status_code
     * @return \Illuminate\Http\JsonResponse
     */
    function ajax_return($status_code = 200, $data = '')
    {
        //如果如果是错误 返回错误信息
        if ($status_code != 200) {
            //增加status_code
            $data = ['status_code' => $status_code, 'message' => $data,];
            return response()->json($data, $status_code);
        }
        //如果是对象 先转成数组
        if (is_object($data)) {
            $data = $data->toArray();
        }
        /**
         * 将数组递归转字符串
         * @param  array $arr 需要转的数组
         * @return array       转换后的数组
         */
        function to_string($arr)
        {
            // app 禁止使用和为了统一字段做的判断
            $reserved_words = [];
            foreach ($arr as $k => $v) {
                //如果是对象先转数组
                if (is_object($v)) {
                    $v = $v->toArray();
                }
                //如果是数组；则递归转字符串
                if (is_array($v)) {
                    $arr[$k] = to_string($v);
                } else {
                    //判断是否有移动端禁止使用的字段
                    in_array($k, $reserved_words, true) && die('不允许使用【' . $k . '】这个键名 —— 此提示是helper.php 中的ajaxReturn函数返回的');
                    //转成字符串类型
                    $arr[$k] = strval($v);
                }
            }
            return $arr;
        }

        //判断是否有返回的数据
        if (is_array($data)) {
            //先把所有字段都转成字符串类型
            $data = to_string($data);
        }
        return response()->json($data, $status_code);
    }
}
if (!function_exists('re_substr')) {
    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $str 需要转换的字符串
     * @param integer $start 开始位置
     * @param string $length 截取长度
     * @param boolean $suffix 截断显示字符
     * @param string $charset 编码格式
     * @return string
     */
    function re_substr($str, $start = 0, $length, $suffix = true, $charset = "utf-8")
    {
        $slice = mb_substr($str, $start, $length, $charset);
        $omit = mb_strlen($str) >= $length ? '...' : '';
        return $suffix ? $slice . $omit : $slice;
    }
}
if (!function_exists('get_tree')) {
    /**
     * 获取子孙目录树
     *
     * @param array $data 要转换的数据集
     * @param string $pid parent标记字段
     * @param string $count 获取个数
     */
    function get_tree($data, $pid, $count = null)
    {
        $tree = [];                                //每次都声明一个新数组用来放子元素
        foreach ($data as $v) {
            if ($v['pid'] == $pid) {
                //匹配子记录
                $v['children'] = get_tree($data, $v['id'], null);
                //递归获取子记录
                if ($v['children'] == null) {
                    unset($v['children']);
                    //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
                }
                //将记录存入新数组
                $tree[] = $v;

                if ($count === count($tree)) {
                    break;
                } elseif (is_null($count)) {
                }
            }
        }
        return $tree;
        //返回新数组
    }
}
if (!function_exists('get_select')) {
    /**
     * 获取树形下拉框数据
     *
     * @param array $data 数据
     * @param integer $selectedId 所选id
     * @return void
     */
    function get_select($data, $selectedId = 0)
    {
        $tree = new Tree();
        $tree->init($data);
        $str = "<option value=\$id \$selected>\$spacer \$name</option>" . PHP_EOL; //生成的形式
        return $tree->get_tree(0, $str, $selectedId);
    }
}
if (!function_exists('ip_to_city')) {
    /**
     * 根据ip获取城市
     *
     * @param string $ip
     * @return array
     */
    function ip_to_city($ip)
    {

        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if(empty($res)){ return false; }
        $jsonMatches = [];
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if(!isset($jsonMatches[0])){ return false; }
        $json = json_decode($jsonMatches[0], true);
        if(isset($json['ret']) && $json['ret'] == 1){
            $json['ip'] = $ip;
            unset($json['ret']);
        }else{
            return false;
        }
        return $json['country'] . '.' . $json['city'];

    }
}
if (!function_exists('markdown_to_html')) {
    /**
     * markdown 转 html
     *
     * @param string $markdown
     * @return array
     */
    function markdown_to_html($markdown)
    {
        preg_match_all('/&lt;iframe.*iframe&gt;/', $markdown, $iframe);
        // 如果有iframe 则先替换为临时字符串
        if (!empty($iframe[0])) {
            $tmp = [];
            // 组合临时字符串
            foreach ($iframe[0] as $k => $v) {
                $tmp[] = '【iframe' . $k . '】';
            }
            // 替换临时字符串
            $markdown = str_replace($iframe[0], $tmp, $markdown);
            // 讲iframe转义
            $replace = array_map(function ($v) {
                return htmlspecialchars_decode($v);
            }, $iframe[0]);
        }
        // markdown转html
        $parser = new Parser();
        $html = $parser->makeHtml($markdown);
        $html = str_replace('<code class="', '<code class="lang-', $html);
        // 将临时字符串替换为iframe
        if (!empty($iframe[0])) {
            $html = str_replace($tmp, $replace, $html);
        }
        return $html;
    }
}
if (!function_exists('send_email')) {
    /**
     * 发送邮件函数
     *
     * @param $email            收件人邮箱  如果群发 则传入数组
     * @param $name             收件人名称
     * @param $subject          标题
     * @param array $data 邮件模板中用的变量 示例：['name'=>'帅白','phone'=>'110']
     * @param string $template 邮件模板
     * @return array            发送状态
     */
    function send_email($email, $name, $subject, $data = [], $template = 'emails.base')
    {
        Mail::send($template, $data, function ($message) use ($email, $name, $subject) {
            //如果是数组；则群发邮件
            if (is_array($email)) {
                foreach ($email as $k => $v) {
                    $message->to($v, $name)->subject($subject);
                }
            } else {
                $message->to($email, $name)->subject($subject);
            }
        });
        return (count(Mail::failures()) > 0)?['status_code' => 500, 'message' => '邮件发送失败']:['status_code' => 200, 'message' => '邮件发送成功'];
    }
}
if ( !function_exists('upload') ) {
	/**
	 * 上传文件函数
	 *
	 * @param $file             表单的name名
	 * @param string $path      上传的路径
	 * @param bool $childPath   是否根据日期生成子目录
	 * @return array            上传的状态
	 */
	function upload($file, $path = 'upload', $childPath = true)
	{
		//判断请求中是否包含name=file的上传文件
		if (!request()->hasFile($file)) {
			$data = ['status_code' => 500, 'message' => '上传文件为空'];
			return $data;
		}
		$file = request()->file($file);
		//判断文件上传过程中是否出错
		if (!$file->isValid()) {
			$data = ['status_code' => 500, 'message' => '文件上传出错'];
			return $data;
		}
		//兼容性的处理路径的问题
		if ($childPath == true) {
			$path = './' . trim($path, './') . '/' . date('Ymd') . '/';
		} else {
			$path = './' . trim($path, './') . '/';
		}
		if (!is_dir($path)) {
			mkdir($path, 0755, true);
		}
		//获取上传的文件名
		$oldName = $file->getClientOriginalName();
		//组合新的文件名
		$newName = uniqid() . '.' . $file->getClientOriginalExtension();
		//上传失败
		if (!$file->move($path, $newName)) {
			return ['status_code' => 500, 'message' => '保存文件失败'];
		}
		//上传成功
		return ['status_code' => 200, 'message' => '上传成功', 'data' => ['old_name' => $oldName, 'new_name' => $newName, 'path' => trim($path, '.')]];
	}
}
if (! function_exists('redis')) {
    /**
     * redis的便捷操作方法
     *
     * @param $key
     * @param null $value
     * @param null $expire
     * @return bool|string
     */
    function redis($key = null, $value = null, $expire = null)
    {
        if (is_null($key)) {
            return app('redis');
        }

        if (is_null($value)) {
            $content = Redis::get($key);
            if (is_null($content)) {
                return null;
            }
            return is_null($content) ? null : unserialize($content);
        }

        Redis::set($key, serialize($value));
        if (! is_null($expire)) {
            Redis::expire($key, $expire);
        }
    }
}
if (!function_exists('baidu_push')) {
    /**
     * 百度推广推送
     *
     * @param int $id
     * @return bool
     */
    function baidu_push($id)
    {
        $urls=[];
        $urls[]=route('article',$id);
        $api=env('BAIDU_PUSH_API');
        $ch=curl_init();
        $options=[
            CURLOPT_URL=>$api,
            CURLOPT_POST=>true,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POSTFIELDS=>implode("\n", $urls),
            CURLOPT_HTTPHEADER=>['Content-Type: text/plain'],
        ];
        curl_setopt_array($ch, $options);
        $result=curl_exec($ch);
        $msg=json_decode($result, true);
        if (array_key_exists('error',$msg)) {
            curl_exec($ch);
        }
        curl_close($ch);
    }
}
