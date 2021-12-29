<?php
ini_set('memory_limit', '8G');
require 'vendor/autoload.php';

$tes = new RSSAnime();
print_r($tes->otakudesu());

class RSSAnime
{
    public function otakudesu()
    {
        $data = array();

        // GET ONGOING
        $raw = $this->SEND("https://otakudesu.info/ongoing-anime/");
        $document = voku\helper\HtmlDomParser::str_get_html($raw['body']);
        $list = $document->find("div.venz > ul > li");
        $count=0;
        foreach ($list as $anime) {
            $nama  = $anime->find("h2[class=jdlflm]")[0]->plaintext;
            //$ep    = $anime->find("div[class=epz]")[0]->plaintext;
            $link  = $anime->find("a")[0]->getAttribute('href');
            //$data['data'][$count]['rawtes']=$get_fd;
            $data['data'][$count]['nama']=$nama;
            //$data['data'][$count]['episode']=$ep;
            $data['data'][$count]['link']=$link;
            // GET LIST EPISODE
            $raw_ep  = $this->SEND($link);
            $fd_ep    = voku\helper\HtmlDomParser::str_get_html($raw_ep['body']);
            $get_ep   = $fd_ep->find("div.episodelist > ul > li");
            $countep = 0;
            foreach ($get_ep as $ep) {
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
                    $data['data'][$count]['episode'][$countep]['DL'][$countdl]['format']=$type;
                    $data['data'][$count]['episode'][$countep]['DL'][$countdl]['size']=$ukur;
                    // GET LINK DOWNLOAD
                    $dl_linl   = $dl->find("a");
                    $countlink=0;
                    foreach ($dl_linl as $zglink) {
                        $nmser  = $zglink->plaintext;
                        $linkdl = $zglink->getAttribute('href');
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['name']=$nmser;
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['link']=$linkdl;
                        $countlink++;
                        //break;
                    }                    
                    $countdl++;
                    break;
                }
                $countep++;
                break;
            }
            $count++;
            break;
        }
        return $data;
    }
    public function get_headers_from_curl_response($headerContent)
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
    public function SEND($url, $config = array())
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
