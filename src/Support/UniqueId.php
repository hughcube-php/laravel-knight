<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2026/2/7
 * Time: 10:00.
 */

namespace HughCube\Laravel\Knight\Support;

use HughCube\Base\Base;

/**
 * 唯一短字符串 ID 生成器
 *
 * 通过组合 毫秒时间戳 + 进程PID + 主机名MD5 + 进程种子 + 递增计数器 + 随机数 等要素,
 * 使用 Base62/Base36 编码生成尽可能短的唯一 ID.
 *
 * 提供四个生成方法, 不同方法有不同的唯一性保证范围和 ID 长度:
 * - short():       单进程内唯一, 13 字符 (Base36, 固定长度)
 * - process():     同机器多进程唯一, 27 字符 (Base36, 固定长度)
 * - distributed(): 分布式跨机器唯一, 39 字符 (Base36, 固定长度, 推荐)
 * - secure():      分布式唯一且防枚举, 52 字符 (Base36, 固定长度)
 *
 * 唯一性保护机制:
 * - 计数器溢出保护: 同毫秒内计数器达到上限时自旋等待到下一毫秒, 避免回绕碰撞
 * - 时钟回拨保护: 检测到系统时钟倒退时自旋等待, 避免与历史 ID 碰撞
 * - 进程种子: 进程启动时生成一次性随机数(12位, ~40 bit), 解决 OS 回收复用 PID 导致的碰撞风险
 * - fork 安全: 检测 PID 变化自动重置状态, 避免 fork 后父子进程产生相同 ID 序列
 * - 主机标识混入 machine-id/boot_id/MAC 等机器特征, MD5 取 60-bit(19位十进制), 生日碰撞阈值约 10 亿台
 * - 避免容器环境中 hostname 重复导致的确定性碰撞
 * - PID 使用 10 位固定宽度, 覆盖 32-bit 无符号整数上限(兼容 Windows/Linux)
 * - 每个字段使用 Base::toStringWithPad() 固定宽度, 杜绝字段边界错位
 * - 每毫秒计数器随机化起始值(0-49999), 降低多进程同毫秒首个 ID 碰撞的概率
 * - 优先使用 hrtime 单调时钟, 避免 NTP 跳变和 microtime 精度抖动
 *
 * 唯一性分析 (distributed 模式, 约 196 bit 信息量):
 * - 碰撞需同时满足: 相同毫秒 + MD5 碰撞 + 相同 PID + 相同种子 + 相同计数器
 * - 联合概率远低于 UUID v4 碰撞概率, 工程上可视为绝对唯一
 *
 * 注意事项:
 * - short()/process() 模式不包含主机信息, 仅适用于其声明的唯一性范围
 */
class UniqueId
{
    const BASE36 = '0123456789abcdefghijklmnopqrstuvwxyz';
    const BASE62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** 字符集打乱, ID 不具备字典序排列特性, 外部无法通过排序推断生成顺序 */
    const UNORDERED_BASE36 = '5m3k1izgxevctar8p6n4l2j0hyfwdubs9q7o';
    const UNORDERED_BASE62 = 'bMnYzaLmXy9KlWx8JkVw7IjUv6HiTu5GhSt4FgRs3EfQr2DePq1CdOp0BcNoZA';

    /**
     * 自定义纪元: 2020-01-01 00:00:00 UTC (毫秒)
     * 使用相对时间戳减小数值位数, 从而缩短编码后的字符串长度
     */
    const EPOCH = 1577836800000;

    /**
     * 计数器最大值 (5位十进制: 0-99999)
     * 单进程每毫秒最多生成约 50000 个 ID (计数器随机起始 0-49999, 保留充足递增空间)
     */
    const MAX_COUNTER = 99999;

    /**
     * 静态计数器, 同毫秒同进程内递增
     *
     * @var int
     */
    protected static int $counter = 0;

    /**
     * 上次生成 ID 的毫秒时间戳, 用于计数器递增和时钟回拨检测
     *
     * @var int
     */
    protected static int $lastTimestamp = 0;

    /**
     * 进程级随机种子, 进程生命周期内不变
     *
     * 解决 PID 复用问题: OS 回收已退出进程的 PID 后分配给新进程,
     * 若两个进程在同一毫秒内拥有相同 PID, 种子可将它们区分开.
     * 同时增加了外部不可观测的熵, 即使攻击者知道 PID 和主机名也无法推断种子.
     *
     * @var int|null
     */
    protected static ?int $processSeed = null;

    /**
     * 种子初始化时的 PID, 用于 fork 检测
     *
     * fork() 后子进程继承父进程的静态状态, 通过对比当前 PID 与种子 PID,
     * 检测 fork 并重置所有状态, 避免父子进程产生相同的 ID 序列.
     *
     * @var int|null
     */
    protected static ?int $seedPid = null;

    /**
     * 缓存的主机标识 (MD5 取 60-bit, 19 位十进制)
     *
     * @var int|null
     */
    protected static ?int $machineId = null;

    /**
     * 短模式: time(14) + counter(5) = 19 位十进制
     *
     * 组成: 毫秒时间戳(相对纪元, 14位) + 递增计数器(5位, 随机起始)
     * 长度: 13 字符 (Base36, 固定长度)
     * 唯一性: 仅在单个进程内保证唯一
     * 适用: 脚本、单线程任务、临时文件名等无需跨进程唯一的场景
     *
     * @param string $encoding 编码字符集, 默认 BASE36
     * @param bool $fixedLength 是否返回固定长度字符串, 默认 true
     *
     * @return string
     * @throws
     */
    public static function short(string $encoding = self::BASE36, bool $fixedLength = true): string
    {
        list($time, $counter) = static::tick();

        $decimal = Base::toStringWithPad($time, 14)
            . Base::toStringWithPad($counter, 5);

        $encoded = Base::conv($decimal, '0123456789', $encoding);

        return $fixedLength ? static::padToFixedLength($encoded, 19, $encoding) : $encoded;
    }

    /**
     * 进程模式: time(14) + pid(10) + seed(12) + counter(5) = 41 位十进制
     *
     * 组成: 毫秒时间戳(相对纪元, 14位) + 进程PID(10位) + 进程种子(12位, ~40 bit) + 递增计数器(5位, 随机起始)
     * 长度: 27 字符 (Base36, 固定长度)
     * 唯一性: 同一台机器上多进程间保证唯一, 含种子防 PID 复用碰撞, fork 安全
     * 适用: 队列 worker、多进程 CLI 任务等同机器多进程场景
     *
     * @param string $encoding 编码字符集, 默认 BASE36
     * @param bool $fixedLength 是否返回固定长度字符串, 默认 true
     *
     * @return string
     * @throws
     */
    public static function process(string $encoding = self::BASE36, bool $fixedLength = true): string
    {
        list($time, $counter) = static::tick();

        $decimal = Base::toStringWithPad($time, 14)
            . Base::toStringWithPad(getmypid(), 10)
            . Base::toStringWithPad(static::getProcessSeed(), 12)
            . Base::toStringWithPad($counter, 5);

        $encoded = Base::conv($decimal, '0123456789', $encoding);

        return $fixedLength ? static::padToFixedLength($encoded, 41, $encoding) : $encoded;
    }

    /**
     * 分布式模式 (推荐): time(14) + machine(19) + pid(10) + seed(12) + counter(5) = 60 位十进制
     *
     * 组成: 毫秒时间戳(相对纪元, 14位) + 机器特征MD5取60-bit(19位) + 进程PID(10位) + 进程种子(12位, ~40 bit) + 递增计数器(5位, 随机起始)
     * 长度: 39 字符 (Base36, 固定长度)
     * 唯一性: 跨机器分布式环境下唯一, 含种子防 PID 复用碰撞, fork 安全, 工程上可视为绝对唯一
     * 适用: 分布式系统、微服务、多节点部署等需要全局唯一的场景
     *
     * @param string $encoding 编码字符集, 默认 BASE36
     * @param bool $fixedLength 是否返回固定长度字符串, 默认 true
     *
     * @return string
     * @throws
     */
    public static function distributed(string $encoding = self::BASE36, bool $fixedLength = true): string
    {
        list($time, $counter) = static::tick();

        $decimal = Base::toStringWithPad($time, 14)
            . Base::toStringWithPad(static::getMachineId(), 19)
            . Base::toStringWithPad(getmypid(), 10)
            . Base::toStringWithPad(static::getProcessSeed(), 12)
            . Base::toStringWithPad($counter, 5);

        $encoded = Base::conv($decimal, '0123456789', $encoding);

        return $fixedLength ? static::padToFixedLength($encoded, 60, $encoding) : $encoded;
    }

    /**
     * 安全模式: time(14) + machine(19) + pid(10) + seed(12) + counter(5) + random(20) = 80 位十进制
     *
     * 组成: 毫秒时间戳(相对纪元, 14位) + 机器特征MD5取60-bit(19位) + 进程PID(10位)
     *       + 进程种子(12位, ~40 bit) + 递增计数器(5位, 随机起始) + 随机数(20位, ~66 bit)
     * 长度: 52 字符 (Base36, 固定长度)
     * 唯一性: 分布式唯一 + 20位随机数防枚举(~66 bit 熵, 暴力枚举需 10^20 次)
     * 适用: 对外暴露的 ID(如订单号、文件URL)、防止用户猜测和遍历的场景
     *
     * @param string $encoding 编码字符集, 默认 BASE36
     * @param bool $fixedLength 是否返回固定长度字符串, 默认 true
     *
     * @return string
     * @throws
     */
    public static function secure(string $encoding = self::BASE36, bool $fixedLength = true): string
    {
        list($time, $counter) = static::tick();

        $decimal = Base::toStringWithPad($time, 14)
            . Base::toStringWithPad(static::getMachineId(), 19)
            . Base::toStringWithPad(getmypid(), 10)
            . Base::toStringWithPad(static::getProcessSeed(), 12)
            . Base::toStringWithPad($counter, 5)
            . Base::toStringWithPad(random_int(0, 9999999999), 10)
            . Base::toStringWithPad(random_int(0, 9999999999), 10);

        $encoded = Base::conv($decimal, '0123456789', $encoding);

        return $fixedLength ? static::padToFixedLength($encoded, 80, $encoding) : $encoded;
    }

    /**
     * 获取主机标识 (MD5 取 60-bit, 惰性初始化)
     *
     * 混入 hostname + machine-id/boot_id + MAC 地址等机器特征, 避免容器环境 hostname 重复.
     * 使用 MD5 取前 15 个十六进制字符(60-bit), 生日碰撞阈值约 10 亿台.
     *
     * @return int
     */
    protected static function getMachineId(): int
    {
        if (static::$machineId === null) {
            $identity = gethostname();

            // 混入更多机器级熵源, 降低容器环境中 hostname 重复导致的碰撞
            foreach (['/etc/machine-id', '/proc/sys/kernel/random/boot_id'] as $file) {
                if (is_readable($file)) {
                    $identity .= file_get_contents($file);
                    break;
                }
            }

            // 混入网卡 MAC 地址 (net_get_interfaces 需要 PHP 8.0+)
            if (function_exists('net_get_interfaces')) {
                foreach (net_get_interfaces() as $iface) {
                    if (!empty($iface['mac']) && $iface['mac'] !== '00:00:00:00:00:00') {
                        $identity .= $iface['mac'];
                        break;
                    }
                }
            }

            // 取 MD5 前 15 个十六进制字符 (60-bit), 生日碰撞阈值约 10 亿台
            static::$machineId = hexdec(substr(md5($identity), 0, 15));
        }

        return static::$machineId;
    }

    /**
     * 获取进程级随机种子 (惰性初始化, 进程生命周期内只生成一次)
     *
     * 12 位十进制(~40 bit 熵), 生日碰撞阈值约 100 万个进程.
     *
     * @return int
     * @throws
     */
    protected static function getProcessSeed(): int
    {
        if (static::$processSeed === null) {
            static::$processSeed = random_int(0, 999999999999);
            static::$seedPid = getmypid();
        }

        return static::$processSeed;
    }

    /**
     * Fork 检测: 当 PID 发生变化时重置所有进程级状态
     *
     * fork() 后子进程继承父进程的所有静态变量, 如果不重置将导致:
     * - 父子进程共享相同的种子, 仅靠 PID 不同来区分(short 模式无 PID 字段, 会直接碰撞)
     * - 父子进程共享相同的计数器和时间戳状态, 可能产生相同的计数器序列
     *
     * @return void
     */
    protected static function checkFork(): void
    {
        $currentPid = getmypid();
        if (static::$seedPid !== null && static::$seedPid !== $currentPid) {
            static::$processSeed = null;
            static::$seedPid = null;
            static::$counter = 0;
            static::$lastTimestamp = 0;
        }
    }

    /**
     * 推进时间序列, 返回当前相对时间戳和计数器
     *
     * 包含 fork 检测、时钟回拨保护、计数器溢出保护和计数器随机化:
     * - fork 检测: PID 变化时重置所有状态, 避免父子进程产生相同 ID
     * - 时钟回拨: 系统时钟倒退时自旋等待, 避免与历史 ID 碰撞
     * - 计数器溢出: 同毫秒超出上限时自旋等待到下一毫秒, 避免回绕碰撞
     * - 计数器随机化: 每毫秒随机起始值(0-49999), 降低多进程同毫秒首个 ID 碰撞概率
     *
     * @return array [int $relativeTime, int $counter]
     * @throws
     */
    protected static function tick(): array
    {
        static::checkFork();

        $timestamp = static::currentTimeMillis();

        // 时钟回拨保护
        while ($timestamp < static::$lastTimestamp) {
            $timestamp = static::currentTimeMillis();
        }

        if ($timestamp === static::$lastTimestamp) {
            static::$counter++;

            // 计数器溢出保护
            if (static::$counter > self::MAX_COUNTER) {
                while ($timestamp <= static::$lastTimestamp) {
                    $timestamp = static::currentTimeMillis();
                }
                static::$counter = random_int(0, 49999);
            }
        } else {
            static::$counter = random_int(0, 49999);
        }

        static::$lastTimestamp = $timestamp;

        return [$timestamp - static::EPOCH, static::$counter];
    }

    /**
     * 将编码后的字符串左补齐到固定长度
     *
     * 使用编码字符集的首字符('0')左补齐, 保证:
     * - 所有 ID 长度固定, 适合数据库定长字段存储
     * - 补齐字符 '0' 是字典序最小字符, 不影响排序, ID 仍保持时间递增有序
     *
     * @param string $encoded 编码后的字符串
     * @param int $decimalDigits 原始十进制数的位数
     * @param string $encoding 编码字符集
     *
     * @return string
     */
    protected static function padToFixedLength(string $encoded, int $decimalDigits, string $encoding): string
    {
        $base = strlen($encoding);
        $maxLength = (int) ceil($decimalDigits * log(10) / log($base));

        return str_pad($encoded, $maxLength, $encoding[0], STR_PAD_LEFT);
    }

    /**
     * 当前毫秒时间戳
     *
     * @return int
     */
    protected static function currentTimeMillis(): int
    {
        // 优先使用 hrtime 单调时钟, 避免 NTP 跳变和精度抖动
        if (function_exists('hrtime')) {
            static $offset = null;
            if ($offset === null) {
                $offset = (int) (microtime(true) * 1000) - intdiv(hrtime(true), 1000000);
            }

            return $offset + intdiv(hrtime(true), 1000000);
        }

        return (int) (microtime(true) * 1000);
    }
}
