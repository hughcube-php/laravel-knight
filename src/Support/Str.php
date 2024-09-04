<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Support;

class Str extends \Illuminate\Support\Str
{
    public static function getMobilePattern(): string
    {
        return '/^(13[0-9]|14[0-9]|15[0-9]|16[0-9]|17[0-9]|18[0-9]|19[0-9])\d{8}$/';
    }

    public static function checkMobile($mobile, $iddCode = null): bool
    {
        if (!is_string($mobile) && !ctype_digit(strval($mobile))) {
            return false;
        }

        if (86 == $iddCode || null == $iddCode) {
            return false != preg_match(static::getMobilePattern(), $mobile);
        }

        return true;
    }

    public static function maskMobile($string, $offset = 3, $length = 4): string
    {
        return substr_replace($string, '****', $offset, $length);
    }

    public static function maskChinaIdCode($string, $offset = 6, $length = 8): string
    {
        return substr_replace($string, '********', $offset, $length);
    }

    public static function splitWhitespace($string): array
    {
        return preg_split('/\s+/', $string) ?: [];
    }

    /**
     * 判断一个字符串的编码是否为UTF-8.
     */
    public static function isUtf8($string): bool
    {
        if (null === $string) {
            return true;
        }

        $json = @json_encode([$string]);

        return '[null]' !== $json && !empty($json);

        // $temp1 = @iconv("GBK", "UTF-8", $string);
        // $temp2 = @iconv("UTF-8", "GBK", $temp1);
        // return $temp1 == $temp2;

        // return preg_match('%^(?:
        //     [\x09\x0A\x0D\x20-\x7E]              # ASCII
        //     | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        //     | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
        //     | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        //     | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
        //     | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
        //     | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        //     | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        //     )*$%xs', $string);

        //return static::encoding($string, 'UTF-8');
    }

    /**
     * 判断一个字符串是否为八进制字符.
     */
    public static function isOctal($string): bool
    {
        return 0 < preg_match('/[^0-7]+/', $string);
    }

    /**
     * 判断一个字符串是否为二进制字符.
     */
    public static function isBinary($string): bool
    {
        return 0 < preg_match('/[^01]+/', $string);
    }

    /**
     * 判断一个字符串是否为十六进制字符.
     */
    public static function isHex($string): bool
    {
        return 0 < preg_match('/[^0-9a-f]+/i', $string);
    }

    /**
     * 判断一个字符串是否是数字和字母组成.
     */
    public static function isAlnum($string): bool
    {
        return ctype_alnum($string);
    }

    /**
     * 判断一个字符串是否是字母组成.
     */
    public static function isAlpha($string): bool
    {
        return ctype_alpha($string);
    }

    /**
     * 判断一个字符串是否是符合的命名规则.
     */
    public static function isNaming($string): bool
    {
        return 0 < preg_match('/^[a-z\_][a-z1-9\_]*/i', $string);
    }

    /**
     * 判断一个字符串是否为空白符,空格制表符回车等都被视作为空白符,类是\n\r\t;.
     */
    public static function isWhitespace($string): bool
    {
        return ctype_cntrl($string);
    }

    /**
     * 判断是否为整数.
     */
    public static function isDigit($string): bool
    {
        return is_numeric($string) && ctype_digit(strval($string));
    }

    /**
     * 判断是否是一个合法的邮箱.
     */
    public static function isEmail($string, bool $isStrict = false): bool
    {
        $result = false !== filter_var($string, FILTER_VALIDATE_EMAIL);

        if ($result && $isStrict && function_exists('getmxrr')) {
            list($prefix, $domain) = explode('@', $string);
            $result = getmxrr($domain, $mxhosts);
        }

        return $result;
    }

    /**
     * 判断是否是一个合法的固定电话号码;.
     */
    public static function isTel($string): bool
    {
        $pattern = '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/';

        return 0 < preg_match($pattern, $string);
    }

    /**
     * 判断是否为一个合法的IP地址
     */
    public static function isIp($string): bool
    {
        return false !== filter_var($string, FILTER_VALIDATE_IP);
    }

    /**
     * 判断是否为一个合法的IPv4地址
     */
    public static function isIp4($string): bool
    {
        return false !== filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * 判断是否为一个合法的IPv6地址
     */
    public static function isIp6($string): bool
    {
        return false !== filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * 判断是否是内网ip.
     */
    public static function isPrivateIp($string): bool
    {
        return false === filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)
            && false !== filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * 判断是否是外网ip.
     */
    public static function isPublicIp($string): bool
    {
        return !static::isPrivateIp($string);
    }

    /**
     * 是否合法的端口.
     */
    public static function isPort($string): bool
    {
        return is_numeric($string)
            && ctype_digit(strval($string))
            && 1 <= $string
            && $string <= 65535;
    }

    /**
     * 判断是否是真值
     */
    public static function isTrue($string): bool
    {
        if (is_bool($string) && $string) {
            return true;
        }

        if (true === filter_var($string, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否中文名字.
     */
    public static function isChineseName($string): bool
    {
        return 0 < preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,15}$/', $string);
    }

    /**
     * 是否含有中文.
     */
    public static function hasChinese($string): bool
    {
        return 0 < preg_match('/[\x{4e00}-\x{9fa5}]/u', $string);
    }

    /**
     * 是否中文.
     */
    public static function isChinese($string): bool
    {
        return 0 < preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $string);
    }

    /**
     * 改变字符的编码
     */
    public static function convEncoding($contents, $from = 'gbk', $to = 'utf-8'): string
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        $from = $from == 'UTF8' ? 'utf-8' : $from;
        $to = $to == 'UTF8' ? 'utf-8' : $to;

        if ($from === $to || empty($contents) || (is_scalar($contents) && !is_string($contents))) {
            return $contents;
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($contents, $to, $from);
        } else {
            return iconv($from, $to, $contents);
        }
    }

    /**
     * 函数msubstr,实现中文截取字符串;.
     */
    public static function msubstr($str, $start = 0, $length = null, $suffix = '...', $charset = 'utf-8'): string
    {
        $length = null === $length ? strlen($str) : $length;
        $charLen = in_array($charset, ['utf-8', 'UTF8']) ? 3 : 2;

        // 小于指定长度，直接返回
        if (strlen($str) <= ($length * $charLen)) {
            return $str;
        }

        if (function_exists('mb_substr')) {
            $slice = mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            // @codingStandardsIgnoreStart
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            // @codingStandardsIgnoreEnd

            preg_match_all($re[$charset], $str, $match);
            $slice = join('', array_slice($match[0], $start, $length));
        }

        return $slice.$suffix;
    }

    /**
     * 统计单词数.
     */
    public static function countWords($string): int
    {
        return count(preg_split('/\s+/u', $string, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * 获取指定位置的字符, 不能支持负数位置.
     */
    public static function offsetGet($string, $index): ?string
    {
        $char = substr($string, $index, 1);

        /** @phpstan-ignore-next-line */
        return false === $char ? null : $char;
    }

    /**
     * 过滤不完整的UTF8字符，UTF8的合法字符范围为：.
     *
     * 一字节字符：0x00-0x7F
     * 二字节字符：0xC0-0xDF 0x80-0xBF
     * 三字节字符：0xE0-0xEF 0x80-0xBF 0x80-0xBF
     * 四字节字符：0xF0-0xF7 0x80-0xBF 0x80-0xBF 0x80-0xBF
     */
    public static function filterPartialUTF8($string): string
    {
        // @codingStandardsIgnoreStart
        $string = preg_replace('/[\\xC0-\\xDF](?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/', '', $string);
        $string = preg_replace('/[\\xE0-\\xEF][\\x80-\\xBF]{0,1}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/', '', $string);
        $string = preg_replace('/[\\xF0-\\xF7][\\x80-\\xBF]{0,2}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/', '', $string);
        // @codingStandardsIgnoreEnd

        return strval($string);
    }

    /**
     * 比较两个版本的大小
     * 0: 两个版本相等
     * 1: $a > $b
     * 2: $b < $a
     * <、 lt、<=、 le、>、 gt、>=、 ge、==、 =、eq、 !=、<> 和 ne。
     */
    public static function versionCompare(
        string $a,
        string $b,
        ?string $operator = null,
        ?int $compareDepth = null
    ): int {
        /**
         * 分割成数组.
         */
        $a = explode('.', $a);
        $b = explode('.', $b);

        /**
         * 确定最大比较的深度.
         */
        $maxDepth = max(count($a), count($b));
        $maxDepth = (null != $compareDepth && $maxDepth > $compareDepth) ? $compareDepth : $maxDepth;

        /**
         * 补全长度, 防止 1.0.1 < 1.0.1.0 的情况.
         */
        $a = array_pad($a, $maxDepth, '0');
        $b = array_pad($b, $maxDepth, '0');

        /**
         * 截取长度, 只比较指定深度.
         */
        $a = array_slice($a, 0, $maxDepth);
        $b = array_slice($b, 0, $maxDepth);

        /**
         * 重新拼接成字符串.
         */
        $a = implode('.', $a);
        $b = implode('.', $b);

        return null === $operator ? version_compare($a, $b) : version_compare($a, $b, $operator);
    }

    public static function mbSplit(string $string, int $length = 1, ?string $encoding = null): array
    {
        $strlen = mb_strlen($string);
        $encoding = $encoding ?? 'UTF-8';

        $array = [];
        while ($strlen > 0) {
            $array[] = mb_substr($string, 0, $length, $encoding);
            $string = mb_substr($string, $length, $strlen, $encoding);
            $strlen = mb_strlen($string);
        }

        return $array;
    }

    /**
     * 计算两个字符串的相同的字符
     */
    public static function countCommonChars(string $a, string $b, bool $inOrder = false): int
    {
        $aChars = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
        $bChars = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);

        if (!$inOrder) {
            return count(array_intersect($aChars, $bChars));
        }

        $aCount = count($aChars);
        $bCount = count($bChars);

        $count = 0;

        $lastIndex = 0;
        for ($aIndex = 0; $aIndex < $aCount; $aIndex++) {
            for ($bIndex = $lastIndex; $bIndex < $bCount; $bIndex++) {
                if ($aChars[$aIndex] === $bChars[$bIndex]) {
                    $count++;
                    $lastIndex = $bIndex;
                    break;
                }
            }
        }

        return $count;
    }

    public static function matchKeywordPrefix($text, $keyword): int
    {
        $match_length = 0;
        $keyword_length = mb_strlen($keyword);
        while (true) {
            if (
                $match_length >= $keyword_length
                || false === mb_strpos($text, mb_substr($keyword, 0, ($match_length + 1)))
            ) {
                break;
            }
            $match_length++;
        }

        return $match_length;
    }

    public static function matchKeywordSuffix($text, $keyword): int
    {
        $match_length = 0;
        $keyword_length = mb_strlen($keyword);
        while (true) {
            if (
                $match_length >= $keyword_length
                || false === mb_strpos($text, mb_substr($keyword, 0 - ($match_length + 1)))
            ) {
                break;
            }
            $match_length++;
        }

        return $match_length;
    }

    public static function matchKeywordExact($text, $keyword): int
    {
        return false === mb_strpos($text, $keyword) ? 0 : mb_strlen($keyword);
    }
}
