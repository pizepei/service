<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/10 13:12
 * @title gif 验证码
 */
namespace pizepei\service\verifyCode;

use \pizepei\service\verifyCode\GIFEncoder;

class GifverifyCode
{
    private static $Debug = 0;
    private static $Code = '';
    private static $Chars = 'bcdefhkmnrstuvwxyABCDEFGHKMNPRSTUVWXY34568';
    //private static $Chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';
    private static $TextGap = 20;
    private static $TextMargin = 5;
    private static $FontFilePath = "static/font/"; //相对地本代码文件的位置
    private static $FontFileName =array("3.ttf");// array("1.ttf", "2.ttf", "3.ttf", "4.ttf", "5.ttf", "6.ttf", "7.ttf", "8.ttf"); //
    private static $Img = 'GIF89a'; //GIF header 6 bytes
    private static $BUF = Array();
    private static $LOP = 0;
    private static $DIS = 2;
    private static $COL = -1;
    private static $IMG = -1;

    /**
    生成GIF图片验证
    @param int $L 验证码长度
    @param int $F 生成Gif图的帧数
    @param int $W 宽度
    @param int $H 高度
    @param int $MixCnt 干扰线数
    @param int $lineGap 网格线间隔
    @param int $noisyCnt 澡点数
    @param int $sessionName 验证码Session名称
     */
    public static function Draw($L = 4, $F = 1, $W = 150, $H = 30, $MixCnt = 2, $lineGap = 0, $noisyCnt = 10, $sessionName = "Code") {
        ob_start();
        ob_clean();

        for ($i = 0; $i < $L; $i++) {
            self::$Code .= SubStr(self::$Chars, mt_rand(0, strlen(self::$Chars) - 1), 1);
        }

        if (!isset($_SESSION))
            session_start();
        $_SESSION[$sessionName] = strtolower(self::$Code);

        $bgRGB = array(rand(0, 255), rand(0, 255), rand(0, 255));
        //生成一个多帧的GIF动画
        for ($i = 0; $i < $F; $i++) {
            $img = ImageCreate($W, $H);

            //背景色
            $bgColor = imagecolorallocate($img, $bgRGB[0], $bgRGB[1], $bgRGB[2]);
            ImageColorTransparent($img, $bgColor);
            unset($bgColor);

            //添加噪点
            $maxNoisy = rand(0, $noisyCnt);
            $noisyColor = imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255));
            for ($k = 0; $k <= $maxNoisy; $k++) {
                imagesetpixel($img, rand(0, $W), rand(0, $H), $noisyColor);
            }

            //添加网格
            if ($lineGap > 0) {
                for ($m = 0; $m < ($W / $lineGap); $m++) { //竖线
                    imageline($img, $m * $lineGap, 0, $m * $lineGap, $H, $noisyColor);
                }
                for ($n = 0; $n < ($H / $lineGap); $n++) { //横线
                    imageline($img, 0, $n * $lineGap, $W, $n * $lineGap, $noisyColor);
                }
            }
            unset($noisyColor);

            // 添加干扰线
            for ($k = 0; $k < $MixCnt; $k++) {
                $wr = mt_rand(0, $W);
                $hr = mt_rand(0, $W);
                $lineColor = imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255));
                imagearc($img, $W - floor($wr / 2), floor($hr / 2), $wr, $hr, rand(90, 180), rand(180, 270), $lineColor);
                unset($lineColor);
                unset($wr, $hr);
            }

            //第一帧忽略文字
            if ($i != 0 || $F <= 1) {
                //文字
                $foreColor = imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255));
                for ($j = 0; $j < $L; $j++) {
                    $fontFile = self::$FontFilePath . self::$FontFileName[rand(0, count(self::$FontFileName) - 1)];
                    if (!file_exists($fontFile))
                        imagestring($img, 4, self::$TextMargin + $j * self::$TextGap, ($H - rand($H / 2, $H)), self::$Code[$j], $foreColor);
                    else
                        imageTTFtext($img, rand(15, 18), rand(-15, 15), self::$TextMargin + $j * self::$TextGap, ($H - rand(7, 10)), $foreColor, $fontFile, self::$Code[$j]);
                }
                unset($foreColor);
            }

            ImageGif($img);
            Imagedestroy($img);
            $Imdata[] = ob_get_contents();
            OB_clean();
        }

        unset($W, $H, $B);
        if (self::$Debug) {
            echo $_SESSION['code'];
            echo '<pre>', Var_Dump($Imdata), '</pre>';
            die();
        }
//        header('Content-type:image/gif');
        return self::CreateGif($Imdata, 20);
        unset($Imdata);
    }

    private static function CreateGif($GIF_src, $GIF_dly = 10, $GIF_lop = 0, $GIF_dis = 0, $GIF_red = 0, $GIF_grn = 0, $GIF_blu = 0, $GIF_mod = 'bin') {
        if (!is_array($GIF_src) && !is_array($GIF_tim)) {
            throw New Exception('Error:' . __LINE__ . ',Does not supported function for only one image!!');
            die();
        }
        self::$LOP = ($GIF_lop > -1) ? $GIF_lop : 0;
        self::$DIS = ($GIF_dis > -1) ? (($GIF_dis < 3) ? $GIF_dis : 3) : 2;
        self::$COL = ($GIF_red > -1 && $GIF_grn > -1 && $GIF_blu > -1) ? ($GIF_red | ($GIF_grn << 8) | ($GIF_blu << 16)) : -1;
        for ($i = 0, $src_count = count($GIF_src); $i < $src_count; $i++) {
            if (strToLower($GIF_mod) == 'url') {
                self::$BUF[] = fread(fopen($GIF_src[$i], 'rb'), filesize($GIF_src[$i]));
            } elseif (strToLower($GIF_mod) == 'bin') {
                self::$BUF[] = $GIF_src[$i];
            } else {
                throw New Exception('Error:' . __LINE__ . ',Unintelligible flag (' . $GIF_mod . ')!');
                die();
            }
            if (!(Substr(self::$BUF[$i], 0, 6) == 'GIF87a' Or Substr(self::$BUF[$i], 0, 6) == 'GIF89a')) {
                throw New Exception('Error:' . __LINE__ . ',Source ' . $i . ' is not a GIF image!');
                die();
            }
            for ($j = (13 + 3 * (2 << (ord(self::$BUF[$i]{10}) & 0x07))), $k = TRUE; $k; $j++) {
                switch (self::$BUF[$i]{$j}) {
                    case '!':
                        if ((substr(self::$BUF[$i], ($j + 3), 8)) == 'NETSCAPE') {
                            throw New Exception('Error:' . __LINE__ . ',Could not make animation from animated GIF source (' . ($i + 1) . ')!');
                            die();
                        }
                        break;
                    case ';':
                        $k = FALSE;
                        break;
                }
            }
        }
        self::AddHeader();
        for ($i = 0, $count_buf = count(self::$BUF); $i < $count_buf; $i++) {
            self::AddFrames($i, $GIF_dly);
        }
        self::$Img .= ';';
        return (self::$Img);
    }

    private static function AddHeader() {
        $i = 0;
        if (ord(self::$BUF[0]{10}) & 0x80) {
            $i = 3 * (2 << (ord(self::$BUF[0]{10}) & 0x07));
            self::$Img .= substr(self::$BUF[0], 6, 7);
            self::$Img .= substr(self::$BUF[0], 13, $i);
            self::$Img .= "!\377\13NETSCAPE2.0\3\1" . chr(self::$LOP & 0xFF) . chr((self::$LOP >> 8) & 0xFF) . "\0";
        }
        unset($i);
    }

    private static function AddFrames($i, $d) {
        $L_str = 13 + 3 * (2 << (ord(self::$BUF[$i]{10}) & 0x07));
        $L_end = strlen(self::$BUF[$i]) - $L_str - 1;
        $L_tmp = substr(self::$BUF[$i], $L_str, $L_end);
        $G_len = 2 << (ord(self::$BUF[0]{10}) & 0x07);
        $L_len = 2 << (ord(self::$BUF[$i]{10}) & 0x07);
        $G_rgb = substr(self::$BUF[0], 13, 3 * (2 << (ord(self::$BUF[0]{10}) & 0x07)));
        $L_rgb = substr(self::$BUF[$i], 13, 3 * (2 << (ord(self::$BUF[$i]{10}) & 0x07)));
        $L_ext = "!\xF9\x04" . chr((self::$DIS << 2) + 0) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . "\x0\x0";
        if (self::$COL > -1 && ord(self::$BUF[$i]{10}) & 0x80) {
            for ($j = 0; $j < (2 << (ord(self::$BUF[$i]{10}) & 0x07)); $j++) {
                if (ord($L_rgb{3 * $j + 0}) == (self::$COL >> 0) & 0xFF && ord($L_rgb{3 * $j + 1}) == (self::$COL >> 8) & 0xFF && ord($L_rgb{3 * $j + 2}) == (self::$COL >> 16) & 0xFF) {
                    $L_ext = "!\xF9\x04" . chr((self::$DIS << 2) + 1) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . chr($j) . "\x0";
                    break;
                }
            }
        }
        switch ($L_tmp{0}) {
            case '!':
                $L_img = substr($L_tmp, 8, 10);
                $L_tmp = substr($L_tmp, 18, strlen($L_tmp) - 18);
                break;
            case ',':
                $L_img = substr($L_tmp, 0, 10);
                $L_tmp = substr($L_tmp, 10, strlen($L_tmp) - 10);
                break;
        }
        if (ord(self::$BUF[$i]{10}) & 0x80 && self::$IMG > -1) {
            if ($G_len == $L_len) {
                if (self::Compare($G_rgb, $L_rgb, $G_len)) {
                    self::$Img .= ($L_ext . $L_img . $L_tmp);
                } else {
                    $byte = ord($L_img{9});
                    $byte |= 0x80;
                    $byte &= 0xF8;
                    $byte |= (ord(self::$BUF[0]{10}) & 0x07);
                    $L_img{9} = chr($byte);
                    self::$Img .= ($L_ext . $L_img . $L_rgb . $L_tmp);
                }
            } else {
                $byte = ord($L_img{9});
                $byte |= 0x80;
                $byte &= 0xF8;
                $byte |= (ord(self::$BUF[$i]{10}) & 0x07);
                $L_img{9} = chr($byte);
                self::$Img .= ($L_ext . $L_img . $L_rgb . $L_tmp);
            }
        } else {
            self::$Img .= ($L_ext . $L_img . $L_tmp);
        }
        self::$IMG = 1;
    }

    private static function Compare($G_Block, $L_Block, $Len) {
        for ($i = 0; $i < $Len; $i++) {
            if ($G_Block{3 * $i + 0} != $L_Block{3 * $i + 0} || $G_Block{3 * $i + 1} != $L_Block{3 * $i + 1} || $G_Block{3 * $i + 2} != $L_Block{3 * $i + 2}) {
                return (0);
            }
        }
        return (1);
    }

}