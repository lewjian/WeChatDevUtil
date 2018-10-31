<?php
/**
 * 微信公众平台API封装
 */

namespace wechat;


class WeChat
{
    /**
     * 微信appid
     * @var string
     */
    public static $appId = '';
    /**
     * 微信app secret
     *
     * @var string
     */
    public static $appSecret = '';

    /**
     * 微信“普通”获取access token ，和授权获取access token不同
     * @url https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183
     */
    const WECHAT_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';

    /**
     * 微信授权登录跳转url
     */
    const WECHAT_AUTH_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    /**
     * 微信授权获取access token url
     */
    const WECHAT_AUTH_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * 微信授权获取用户信息，和使用open_id获取用户信息地址不同
     */
    const WECHAT_AUTH_USERINFO_URL = 'https://api.weixin.qq.com/sns/userinfo';

    /**
     * 根据open_id获取用户信息url地址，和授权获取用户信息不一样
     * @url https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839
     */
    const WECHAT_GET_USER_INFO_URL = 'https://api.weixin.qq.com/cgi-bin/user/info';

    /**
     * 微信发送客服消息接口url
     * @url https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140547
     */
    const WECHAT_SEND_KF_MSG_URL = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';

    /**
     * 服务号支持，根据open_id群发消息接口地址
     *
     * @url https://mp.weixin.qq.com/wiki?action=doc&id=mp1481187827_i0l21&t=0.5434504751470004#3
     */
    const WECHAT_SEND_GROUP_MSG_URL = 'https://api.weixin.qq.com/cgi-bin/message/mass/send';

    /**
     * 发送模板消息url
     *
     * @url https://mp.weixin.qq.com/wiki?action=doc&id=mp1433751277&t=0.47363522677025593#5
     */
    const WECHAT_SEND_TPL_MSG_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

    /**
     * WeChat 初始化函数
     *
     * @param string $appId
     * @param string $appSecret
     * @throws \Exception
     */
    public static function init($appId = '', $appSecret = '')
    {
        if (empty($appId) || empty($appId)) {
            throw new \Exception("初始化参数不完整");
        }
        self::$appId = $appId;
        self::$appSecret = $appSecret;
    }

    /**
     * 获取微信access token
     *
     * @return bool|string
     */
    public static function getAccessToken()
    {
        $param = [
            'grant_type' => 'client_credential',
            'appid' => self::$appId,
            'secret' => self::$appSecret
        ];
        $response = self::_http(self::WECHAT_ACCESS_TOKEN_URL, $param);
        $res_array = json_decode($response, true);
        if (empty($response)) {
            return false;
        }
        return $res_array['access_token'];
    }

    /**
     * 根据用户openid获取用户的基本信息，包括：昵称、头像、性别、所在城市、语言和关注时间
     * 如果用户未关注，无法获取到那么多信息
     *
     * @url https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839
     * @param string $open_id
     * @param string $access_token
     * @return mixed
     */
    public static function getUserInfo($open_id, $access_token)
    {
        $param = [
            'access_token' => $access_token,
            'openid' => $open_id
        ];
        $body = self::_http(self::WECHAT_GET_USER_INFO_URL, $param);
        return json_decode($body, true);
    }

    /**
     * 发送客服文本消息
     *
     * @param string $access_token
     * @param string $open_id
     * @param string $msg_content
     * @return mixed true-表示发送成功，否则返回错误提示信息
     */
    public static function text_sendKfMsg($access_token, $open_id, $msg_content)
    {
        $data = [
            'touser' => $open_id,
            'msgtype' => 'text',
            'text' => [
                'content' => urlencode($msg_content)
            ]
        ];
        $param = urldecode(json_encode($data));
        $url = self::WECHAT_SEND_KF_MSG_URL . "?access_token=" . $access_token;
        $body = self::_http($url, $param, 'post');
        $result = json_decode($body, true);
        if ($result['errcode'] === 0) {
            return true;
        }
        return $result['errcode'] . ": " . $result['errmsg'];
    }

    /**
     * 根据open_id 群发消息，占用每月4次名额
     *
     * @url https://mp.weixin.qq.com/wiki?action=doc&id=mp1481187827_i0l21&t=0.5434504751470004#3
     * @param string $access_token
     * @param string $open_id
     * @param string $msg_content
     * @return bool|string true-表示发送成功，否则返回错误提示信息
     */
    public static function text_sendGroupMsg($access_token, $open_id, $msg_content)
    {
        if (!is_array($open_id)) {
            // open_id必须是数组且大于2个
            $open_id = [$open_id, $open_id];
        }
        $data = [
            'touser' => $open_id,
            'msgtype' => 'text',
            'text' => [
                'content' => urlencode($msg_content)
            ]
        ];
        $param = urldecode(json_encode($data));
        $url = self::WECHAT_SEND_GROUP_MSG_URL . "?access_token=" . $access_token;
        $body = self::_http($url, $param, 'post');
        $result = json_decode($body, true);
        if ($result['errcode'] === 0) {
            return true;
        }
        return $result['errcode'] . ": " . $result['errmsg'];
    }

    /**
     * @url https://mp.weixin.qq.com/wiki?action=doc&id=mp1433751277&t=0.47363522677025593#5
     * @param string $access_token
     * @param string $tpl_id 模板消息id
     * @param string $open_id
     * @param array $content 模板消息内容，格式如下，具体要看tpl_id对应的定义模板
     [
        'first' => ['value' => '恭喜您购买成功！', 'color' => '#acacac'],
        'keyword1' => ['value' => '巧克力！', 'color' => '#bbbbbb'],
        'keyword2' => ['value' => '￥38.00', 'color' => '#cccccc'],
        'remark' => ['value' => '欢迎再次购买！', 'color' => '#000000'],

     ]
     * @param string $url 模板消息跳转地址
     * @return bool|string 成功返回true，失败返回错误描述
     */
    public static function tpl_sendMsg($access_token, $tpl_id, $open_id, $content, $url = '')
    {
        $data = [
            'touser' => $open_id,
            'template_id' => $tpl_id,
            'url' => $url,
            'data' => $content
        ];
        $param = json_encode($data);
        $url = self::WECHAT_SEND_TPL_MSG_URL . "?access_token=" . $access_token;
        $body = self::_http($url, $param, 'post');
        $result = json_decode($body, true);
        if ($result['errcode'] === 0) {
            return true;
        }
        return $result['errcode'] . ": " . $result['errmsg'];
    }

    /**
     * 获取用户openid，获取失败返回false
     *
     * @return bool|string
     */
    public static function getOpenId()
    {
        // 获取授权码
        $code = self::_getAuthCode();
        // 利用code获取openid
        $param = [
            'appid' => self::$appId,
            'secret' => self::$appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $response = self::_http(self::WECHAT_AUTH_ACCESS_TOKEN_URL, $param);
        $result = json_decode($response, true);
        if (empty($result)) {
            return false;
        }
        return $result['openid'];

    }

    /**
     * 获取微信用户授权码，5分钟有效期
     *
     * @param string $scope 授权范围，可选值：snsapi_base-基础信息；snsapi_userinfo-需要弹屏确认，可以获取用户详细信息
     * @param string $state 重定向会带上的标记字段，暂时无用
     * @return string
     */
    private static function _getAuthCode($scope = 'snsapi_base', $state = '')
    {
        // 检查当前url里面是否已经携带了code参数
        if (!isset($_GET['code'])) {
            // 当前url
            $url = self::_getCurrentUrl();
            // 构建请求参数数据
            $data = [
                'appid' => self::$appId,
                'redirect_uri' => $url,
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $state
            ];
            $query_string = http_build_query($data);
            $url = self::WECHAT_AUTH_URL . "?" . $query_string . "#wechat_redirect";
            header('location:' . $url);
            exit();
        } else {
            // 授权完成，返回code
            return trim($_GET['code']);
        }

    }

    /**
     * 获取当前访问url
     *
     * @return string
     */
    private static function _getCurrentUrl()
    {
        $protocol = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        return $protocol . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }

    /**
     * 发送http请求
     *
     * @param string $url 请求地址
     * @param array|string $data 请求数据
     * @param string $method 请求类型：post、get
     * @return mixed
     */
    private static function _http($url, $data, $method = "get")
    {
        if (is_array($data)) {
            $data = http_build_query($data);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 180);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // 确认请求类型
        if (strtolower($method) === 'get') {
            if (strpos($url, '?') !== false) {
                $url .= "&" . $data;
            } else {
                $url .= "?" . $data;
            }
        } else {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        // 发送请求
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    public static function getReadableJsonStr($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        $data = self::_urlencodeArrayValue($data);
        return urldecode(json_encode($data));
    }

    /**
     * 将一个数组的value进行urlencode
     *
     * @param array $data
     * @return array
     */
    private static function _urlencodeArrayValue($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::_urlencodeArrayValue($value);
            } else {
                $data[$key] = urlencode($value);
            }
        }
        return $data;
    }


}