<?php
/*
Script ini dibuat oleh Akbar Yahya (Yuki) dengan style sendiri dan terbuka untuk semuanya untuk modifikasi tapi mohon untuk tidak dijual script ini dan hapus pesan ini, jika saya lihat ada yang hapus atau jual script ini akan saya tutup repo ini untuk umum.
*/
function contains($str, array $arr)
{
    foreach ($arr as $a) {
        if (strstr($str, $a) !== false) {
            return true;
        }
    }
    return false;
}
function cut_str($str, $left, $right)
{
    $str = substr(stristr($str, $left), strlen($left));
    $leftLen = strlen(stristr($str, $right));
    $leftLen = $leftLen ? -($leftLen) : strlen($str);
    $str = substr($str, 0, $leftLen);
    return $str;
}
function get_headers_from_curl_response($headerContent)
{
    $headers = array();
    $arrRequests = explode("\r\n\r\n", $headerContent);
    for ($index = 0; $index < count($arrRequests) - 1; $index++) {
        foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
            if ($i === 0) {
                $headers[$index]['http_code'] = $line;
            } else {
                @list($key, $value) = explode(': ', $line);
                $headers[$index][$key] = $value;
            }
        }
    }
    return $headers;
}
function SEND($url, $config = array())
{
    $url = str_replace(' ', '%20', $url);
    $data = array();

    $dataxp    = @$config['post'];
    $metode    = @$config['metode'];
    $addbody   = @$config['body'];
    $kue       = @$config['cookie'];
    $ref       = @$config['referer'];
    $useragent = @$config['user_agent'] ?: "HELLO";
    $pz        = @$config['header'];
    $timeout   = @$config['timeout'] ?: 5;
    $nobody    = @$config['nobody'] ?: false;
    $proxyme   = @$config['port_proxy'];
    $pt        = @$config['proxy'];
    $onssl     = @$config['ssl'] ?: false;
    @$config['url'] = $url;
    $readkue   = @$config['readkue'];
    $savekue   = @$config['savekue'];
    $showraw   = @$config['raw'];

    try {
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($c, CURLOPT_USERAGENT, $useragent);
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $data['useragent'] = $useragent;

        if ($onssl) {
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($c, CURLOPT_FRESH_CONNECT, true); // is cache?

        if (!empty($proxyme)) {
            curl_setopt($c, CURLOPT_PORT, $proxyme);
        }

        if (!empty($metode)) {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $metode);
        }
        if (!empty($addbody)) {
            curl_setopt($c, CURLOPT_POSTFIELDS, $addbody);
        }

        if (!empty($dataxp)) {
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, $dataxp);
        }
        if (!empty($ref)) {
            curl_setopt($c, CURLOPT_REFERER, $ref);
        }

        if (!empty($kue)) {
            curl_setopt($c, CURLOPT_COOKIE, $kue);
        }

        if (!empty($readkue)) {
            curl_setopt($c, CURLOPT_COOKIEFILE, $readkue);
        }
        if (!empty($savekue)) {
            curl_setopt($c, CURLOPT_COOKIEJAR, $savekue);
        }

        if (!empty($pt)) {
            curl_setopt($c, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($c, CURLOPT_PROXY, $pt);
        }
        if (!empty($timeout)) {
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($c, CURLOPT_TIMEOUT, $timeout);
            $data['timeout'] = $timeout;
        }
        if (!empty($pz)) {
            curl_setopt($c, CURLOPT_HTTPHEADER, $pz);
        }

        $datax = curl_exec($c);

        // RAW DATA
        if ($showraw) {
            $data['raw'] = $datax;
        }

        $responseCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
        $header_len   = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $header       = substr($datax, 0, $header_len);

        $data['status'] = "Curl code " . $responseCode . " via " . $url;
        $data['code'] = $responseCode;
        if ($responseCode == 0) {
            $rawerror = curl_error($c);
            $data['status'] = "Curl Error: " . $rawerror . " via " . $url;
            $data['error'] = $rawerror;
        }

        $data['header'] = get_headers_from_curl_response($header);

        if (!$nobody) {
            //TODO: check body respon
            $data['body'] = substr($datax, $header_len);
        }

        $datax = null;

        curl_close($c);
    } catch (Exception $e) {
        $data['error'] = $e->getMessage();
    } catch (InvalidArgumentException $e) {
        $data['error'] = $e->getMessage();
    }

    $data['config'] = $config;
    return $data;
}
class Bot
{
    public static function DiscordWbhooks($url, $body)
    {
        $raw = SEND($url, array(
        'metode' => "POST",
        'header'=>[ 'Content-Type: application/json; charset=utf-8' ],
        'post' => json_encode($body)
    ));
        return $raw;
    }
}
class Downloader
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $destination;

    /**
     * Downloader constructor.
     * @param string $url
     * @param string $destination
     */
    public function __construct(string $url, string $destination)
    {
        $this->url = $this->prepareUrl($url);
        $this->destination = $this->prepareDestination($destination);
    }

    /**
     * @param string $url
     * @return string
     */
    private function prepareUrl(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->stderr('Invalid URL');
            die;
        }

        return $url;
    }

    /**
     * @param string $output
     */
    private function stderr(string $output): void
    {
        fwrite(STDERR, $output);
    }

    /**
     * @param string $destination
     * @return string
     */
    private function prepareDestination(string $destination): string
    {
        $fileName = basename($destination);
        $dirName = realpath(dirname($destination));
        if (!$dirName) {
            $this->stderr('Check the destination path of file');
            die;
        }

        return $dirName . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     *
     */
    public function download(): void
    {
        $ch = curl_init();
        $fp = fopen($this->destination, 'wb');

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FILE => $fp,
            CURLOPT_PROGRESSFUNCTION => [$this, 'progress'],
            CURLOPT_NOPROGRESS => false,
            CURLOPT_SSL_VERIFYHOST=>false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FAILONERROR => true
        ]);

        curl_exec($ch);

        if (curl_errno($ch)) {
            $this->stderr('Error: ' . curl_error($ch));
            die;
        }

        curl_close($ch);
        fclose($fp);
    }

    /**
     * @param $resource
     * @param int $download_size
     * @param int $downloaded
     * @param int $upload_size
     * @param int $uploaded
     */
    private function progress($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0): void
    {
        $percent = $download_size > 0 && $downloaded > 0 ? ceil($downloaded * 100 / $download_size) : 0;
        $this->stdout('[' . str_pad('', $percent, '#') . str_pad('', 100 - $percent) . "] $percent%\r");
    }

    /**
     * @param string $output
     */
    private function stdout(string $output): void
    {
        fwrite(STDOUT, $output);
    }
}
