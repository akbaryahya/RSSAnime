<?php
ini_set('memory_limit', '8G');
require 'vendor/autoload.php';

$info = getopt("", ["doing:","resolution:","link_source:"]);

$isdoing         = @$info['doing'];
$set_resolution  = @$info['resolution'];
$set_link_source = @$info['link_source'];

$GoRSS = new RSSAnime();

if ($isdoing == "tes") {
    print_r($GoRSS->otakudesu());
} elseif ($isdoing == "dl") {
    $GoRSS->dl($set_resolution, $set_link_source);
}

/*
$n = "595030%2";
$b = "595030%3";
$z = "595033";
$nt = explode('%', $n);
$bt = explode('%', $b);
echo(($nt[0] % $nt[1]) + ($bt[0] % $bt[1]) + $z - 3);
*/
//nilai asli 595031

class RSSAnime
{
    private $DB_DL;
    private $host = "mongodb://localhost:27017";

    public function __construct($config = null)
    {
        //TODO: add config
        $this->db = new MongoDB\Client($this->host);
        $this->DB_DL  = $this->db->dl->link;
    }

    public function dl($set_resolution="", $set_link_source="", $limit_dl=1)
    {     
        $source = array();
        $source['source']['otakudesu'] = $this->otakudesu();
        // Get Source
        foreach ($source['source'] as $gsource) {
            // Get episode
            foreach ($gsource['data'] as $gep) {
                // Get Format
                foreach ($gep['episode'] as $gdetail) {
                    $name = $gdetail['name'];
                    $link = $gdetail['link'];
                    echo "-> $name ($link)" . PHP_EOL;
                    // Format file
                    usort($gdetail['DL'], function ($a, $b) {
                        return $b['resolution'] <=> $a['resolution'];
                    });
                    $tmp_limit_dl=0;
                    foreach ($gdetail['DL'] as $gdl) {
                        $format = $gdl['format'];
                        $resolu = $gdl['resolution'];
                        // Fiter Format
                        if (!empty($set_resolution)) {
                            $set_resolutionn  = explode(',', $set_resolution);
                            if (!$this->contains($resolu, $set_resolutionn)) {
                                continue;
                            }
                        }
                        if ($tmp_limit_dl >= $limit_dl) {
                            break;
                        }
                        echo "--> $format / $resolu" . PHP_EOL;
                        // Source link
                        foreach ($gdl['link'] as $glk) {
                            $lname = $glk['name'];
                            $llink = $glk['link'];
                            // Fiter link
                            if (!empty($set_link_source)) {
                                $set_link_sourcen = explode(',', $set_link_source);
                                if (@!$this->contains($lname, $set_link_sourcen)) {
                                    continue;
                                }
                            }
                            echo "---> $lname ($llink)" . PHP_EOL;
                        }
                        $tmp_limit_dl++;
                    }
                }
            }
        }
        //print_r($source);
    }

    public function link($url)
    {
        if (strpos($url, 'zippyshare') !== false) {
            return $this->zippyshare($url);
        }

        return "";
    }
    public function zippyshare($url="https://www87.zippyshare.com/v/SGTX2ZT5/file.html")
    {
        $data= array();

        $data['url']=$url;

        // GET POST
        $raw = $this->SEND($url);
        $raw_body = $raw['body'];

        // GET HTML
        $raw_linkz = voku\helper\HtmlDomParser::str_get_html($raw_body);
        $javaScript = $raw_linkz->find("#lrbox > div:nth-child(2) > div:nth-child(2) > div > script")[0]->plaintext;
        $fileName   = $raw_linkz->find("#lrbox > div:nth-child(2) > div:nth-child(1) > font:nth-child(4)")[0]->plaintext;

        $data['fileName']=$fileName;
        //$data['script']=$javaScript;

        // formula javaScript (fix?)
        $n = $this->cut_str($javaScript, "var n = ", ';');
        $b = $this->cut_str($javaScript, "var b = ", ';');
        $z = $this->cut_str($javaScript, "var z = ", ';');
        //$data['n']=$n;
        //$data['b']=$b;
        //$data['z']=$z;
        $nt = explode('%', $n);
        $bt = explode('%', $b);
        $formula = (($nt[0] % $nt[1]) + ($bt[0] % $bt[1]) + $z - 3);

        $url = str_replace("/v/", "/d/", $url);
        $url = str_replace("/file.html", "", $url);
        $url_real = "$url/$formula/$fileName";
        $data['dl']=$url_real;
        $data['formula']=$formula;
        return $data;
    }
    public function rapidleech($url="https://www87.zippyshare.com/v/SGTX2ZT5/file.html", $server="https://s2.rapidleech.gq")
    {
        $DATA = http_build_query([
            "audl" => "doum",
            "link" => $url,
           // "cookie"=>"%85%F7%88%5Bv%F3d%EF%8Btif%D8%1D%A3%83T%FA%EA%0F%96%AF%D9%D9%5C%28%22%C1%18%98%7D%3F%9E6%0F%C5%97%1C%23%96%22%8F%3D%FF%90%C8%02%DF%13s%C0%A6%84%07%CA-%C8%BEk%EFn%FD%09%06%95%26%01%EA%A6%24%E3%E6",
           // "cookie_encrypted"=>1,
           // "referer"=>$url,
            "cleanname"=>1
        ]);
        $raw = $this->SEND($server."/index.php", array(
            'metode' => "GET",
            'post' => $DATA
        ));
        return $raw;
    }
    public function otakudesu()
    {
        $data = array();

        $limit_ongoing=3;
        $limit_episode=1;

        // GET ONGOING
        $raw = $this->SEND("https://otakudesu.info/ongoing-anime/");
        $document = voku\helper\HtmlDomParser::str_get_html($raw['body']);
        $list = $document->find("div.venz > ul > li");
        $count=0;
        foreach ($list as $anime) {
            if ($count >= $limit_ongoing) {
                break;
            }
            $name  = $anime->find("h2[class=jdlflm]")[0]->plaintext;
            $epz   = $anime->find("div[class=epz]")[0]->plaintext;
            $link  = $anime->find("a")[0]->getAttribute('href');
            $data['data'][$count]['name']=$name;
            $data['data'][$count]['ep_num']=$epz;
            $data['data'][$count]['link']=$link;
            // GET LIST EPISODE
            $raw_ep  = $this->SEND($link);
            $fd_ep    = voku\helper\HtmlDomParser::str_get_html($raw_ep['body']);
            $get_ep   = $fd_ep->find("div.episodelist > ul > li");
            $countep = 0;
            foreach ($get_ep as $ep) {
                if ($countep >= $limit_episode) {
                    break;
                }
                $glink = $ep->find("a")[0];
                $nama_ep  = $glink->plaintext;
                $link_ep  = $glink->getAttribute('href');
                $data['data'][$count]['episode'][$countep]['name']=$nama_ep;
                $data['data'][$count]['episode'][$countep]['link']=$link_ep;
                // GET DETAILS EPISODE
                $raw_detail  = $this->SEND($link_ep);
                $fd_dl    = voku\helper\HtmlDomParser::str_get_html($raw_detail['body']);
                $get_dl   = $fd_dl->find("div.download > ul > li");
                $countdl=0;
                foreach ($get_dl as $dl) {
                    $type      = $dl->find("strong")[0]->plaintext;
                    $ukur      = $dl->find("i")[0]->plaintext;
                    $ntype     = explode(' ', $type);
                    $data['data'][$count]['episode'][$countep]['DL'][$countdl]['format']=$ntype[0];
                    $data['data'][$count]['episode'][$countep]['DL'][$countdl]['resolution']=intval($ntype[1]);
                    $data['data'][$count]['episode'][$countep]['DL'][$countdl]['size']=$ukur;
                    // GET LINK DOWNLOAD
                    $dl_linl   = $dl->find("a");
                    $countlink=0;
                    foreach ($dl_linl as $zglink) {
                        $nmser  = $zglink->plaintext;
                        $linkdl = $zglink->getAttribute('href');
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['name']=$nmser;
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['link']=$linkdl;
                        //$data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['dl']=$this->link($linkdl);
                        $countlink++;
                        //break;
                    }
                    $countdl++;
                    //break;
                }
                $countep++;
                //break;
            }
            $count++;
            //break;
        }
        return $data;
    }
    private function contains($str, array $arr)
    {
        foreach ($arr as $a) {
            if (strstr($str, $a) !== false) {
                return true;
            }
        }
        return false;
    }
    private function cut_str($str, $left, $right)
    {
        $str = substr(stristr($str, $left), strlen($left));
        $leftLen = strlen(stristr($str, $right));
        $leftLen = $leftLen ? -($leftLen) : strlen($str);
        $str = substr($str, 0, $leftLen);
        return $str;
    }
    private function get_headers_from_curl_response($headerContent)
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
    private function SEND($url, $config = array())
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

            $data['header'] = $this->get_headers_from_curl_response($header);

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
}
