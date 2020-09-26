<?php

class Curl
{
    private $curl;
    private $url;
    private $content;

    public function __construct()
    {
        $this->reload();
    }

    public function reload()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        $this->returnHeader(true);
        $this->setTimeout(10);
        $this->setUA("Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36");
        return $this;
    }

    public function createFile($fileName)
    {
        return curl_file_create($fileName, 'image/jpeg', '1.jpg');
    }

    public function ignoreSSL()
    {
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function setUA($ua)
    {
        curl_setopt($this->curl, CURLOPT_USERAGENT, $ua);
        return $this;
    }

    public function setUrl($url)
    {
        // if ($reload) {
        //     $this->reload();
        // }
        $this->url = $url;
        curl_setopt($this->curl, CURLOPT_URL, $url);
        return $this;
    }

    public function returnHeader($bool)
    {
        curl_setopt($this->curl, CURLOPT_HEADER, ($bool == true) ? 1 : 0);
        return $this;
    }

    public function returnBody($bool)
    {
        curl_setopt($this->curl, CURLOPT_NOBODY, ($bool == false) ? 1 : 0);
        return $this;
    }

    public function setHeader($arr)
    {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $arr);
        return $this;
    }

    public function setCookie($cookies)
    {
        $payload = '';
        foreach ($cookies as $key => $cookie) {
            $payload .= "$key=" . urlencode($cookie) . "; ";
        }
        curl_setopt($this->curl, CURLOPT_COOKIE, $payload);
        return $this;
    }

    public function setReferer($referer)
    {
        curl_setopt($this->curl, CURLOPT_REFERER, $referer);
        return $this;
    }

    public function setGet($get)
    {
        $payload = '?';
        foreach ($get as $key => $content) {
            $payload .= urlencode($key) . '=' . urlencode($content) . '&';
        }
        $url = $this->url . $payload;
        $url = substr($url, 0, strlen($url) - 1);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        return $this;
    }

    public function setPost($post)
    {
        $payload = '';
        foreach ($post as $key => $content) {
            $payload .= urlencode($key) . '=' . urlencode($content) . '&';
        }
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);
        return $this;
    }

    public function setRawPost($post)
    {
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
        return $this;
    }

    public function setContentType($type)
    {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
            "Content-Type: $type"
        ]);
        return $this;
    }

    public function setTimeout($timeout)
    {
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    public function keepCookie()
    {
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, '');
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, '');
        return $this;
    }

    public function exec()
    {
        $this->content = curl_exec($this->curl);
        // $this->reload();
        return $this->content;
    }

    public function getStatusCode()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    public function getCookie()
    {
        preg_match_all('/Set-Cookie: (.*);/iU', $this->content, $this->cookies);
        $payload = [];
        foreach ($this->cookies[1] as $this->cookie) {
            $key = explode('=', $this->cookie);
            if (isset($payload[$key[0]]) and $payload[$key[0]] !== '') {
                continue;
            }
            $payload[$key[0]] = $key[1];
        }
        return $payload;
    }

    public function getLocation()
    {
        if (preg_match('/Location: (.*)$/m', $this->content, $location)) {
            return $location[1] ? substr($location[1], 0, strlen($location[1]) - 1) : '';
        }
        return '';
    }

    public function getContent()
    {
        return $this->content;
    }

    public function isError()
    {
        return (curl_errno($this->curl)) ? true : false;
    }
}
