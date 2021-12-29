<?php
ini_set('memory_limit', '8G');
require 'vendor/autoload.php';
require 'lib/tool.php';

$info = getopt("", ["doing:","resolution:","link_source:",'limit_ongoing:','limit_dl:','autodl:']);

$isdoing         = @$info['doing'];
$set_resolution  = @$info['resolution'];
$set_link_source = @$info['link_source'];
$set_autodl      = @$info['autodl'] ?: false;

$set_limit_ongoing     = @$info['limit_ongoing'] ?: 1;
$set_limit_dl          = @$info['limit_dl'] ?: 1;

$GoRSS = new RSSAnime();

if ($isdoing == "tes") {
    print_r($GoRSS->otakudesu());
} elseif ($isdoing == "dl") {
    $GoRSS->dl($set_resolution, $set_link_source, $set_limit_dl, $set_limit_ongoing, $set_autodl);
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

    public function dl($set_resolution="", $set_link_source="", $set_limit_dl=1, $set_limit_ongoing=1, $set_autodl=false)
    {
        $source = array();
        $source['source']['otakudesu'] = $this->otakudesu($set_limit_ongoing);
        // Get Source
        foreach ($source['source'] as $gsource) {
            // Get episode
            foreach ($gsource['data'] as $gep) {
                // Get Format
                //print_r($gep);
                $name_nm = $gep['name'];
                $name_nx = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $name_nm);
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
                            if (!contains($resolu, $set_resolutionn)) {
                                continue;
                            }
                        }
                        if ($tmp_limit_dl >= $set_limit_dl) {
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
                                if (!contains($lname, $set_link_sourcen)) {
                                    continue;
                                }
                            }
                            echo "---> $lname ($llink)" . PHP_EOL;
                            if ((bool)$set_autodl == true) {
                                $setpo   = @$this->link($llink);
                                $gtrealx = @$setpo['dl'];
                                if (!empty($gtrealx)) {
                                    $fileName = @$setpo['fileName'];
                                    echo "----> DL: $gtrealx ($fileName)" . PHP_EOL;
                                    $linkfd = "dl/$name_nx/";
                                    if (!file_exists($linkfd)) {
                                        echo "----> DL: No Found Folder so Make it: $name_nx" . PHP_EOL;
                                        mkdir($linkfd, 0777, true);
                                    }
                                    $spot=$linkfd.$fileName;
                                    if (!file_exists($spot)) {
                                        $dw = new Downloader($gtrealx, $spot);
                                        $dw->download();
                                    } else {
                                        echo "----> DL: file already exists" . PHP_EOL;
                                    }
                                } else {
                                    echo "----> DL: No Found" . PHP_EOL;
                                }
                            }
                        }
                        $tmp_limit_dl++;
                    }
                }
            }
        }
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
        $url_real="";

        // GET POST
        $raw = SEND($url);
        if ($raw['code']==200) {
            $raw_body = $raw['body'];
            // GET HTML
            $raw_linkz = voku\helper\HtmlDomParser::str_get_html($raw_body);
            $javaScript = $raw_linkz->find("#lrbox > div:nth-child(2) > div:nth-child(2) > div > script")[0]->plaintext;
            $fileName   = $raw_linkz->find("#lrbox > div:nth-child(2) > div:nth-child(1) > font:nth-child(4)")[0]->plaintext;

            $data['fileName']=$fileName;
            //$data['script']=$javaScript;

            // formula javaScript (fix?)
            $n = cut_str($javaScript, "var n = ", ';');
            $b = cut_str($javaScript, "var b = ", ';');
            $z = cut_str($javaScript, "var z = ", ';');
            //$data['n']=$n;
            //$data['b']=$b;
            //$data['z']=$z;
            $nt = explode('%', $n);
            $bt = explode('%', $b);
            $formula = (($nt[0] % $nt[1]) + ($bt[0] % $bt[1]) + $z - 3);

            $url = str_replace("/v/", "/d/", $url);
            $url = str_replace("/file.html", "", $url);
            $url_real = "$url/$formula/$fileName";
        }
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
        $raw = SEND($server."/index.php", array(
            'metode' => "GET",
            'post' => $DATA
        ));
        return $raw;
    }
    public function otakudesu($limit_ongoing=3, $limit_episode=1)
    {
        $data = array();

        // GET ONGOING
        $raw = SEND("https://otakudesu.info/ongoing-anime/");
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
            $raw_ep  = SEND($link);
            $fd_ep    = voku\helper\HtmlDomParser::str_get_html($raw_ep['body']);
            $get_ep   = $fd_ep->find("div.episodelist > ul > li");
            $countep = 0;
            foreach ($get_ep as $ep) {
                if ($countep >= $limit_episode) {
                    break;
                }
                $glink = $ep->find("a")[0];
                $nama_ep  = $glink->plaintext;
                if (strpos($nama_ep, 'BATCH') !== false) {
                    continue;
                }
                $link_ep  = $glink->getAttribute('href');
                $data['data'][$count]['episode'][$countep]['name']=$nama_ep;
                $data['data'][$count]['episode'][$countep]['link']=$link_ep;
                // GET DETAILS EPISODE
                $raw_detail  = SEND($link_ep);
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
                        //$data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['dl']=link($linkdl);
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
}
