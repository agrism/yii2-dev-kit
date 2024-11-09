<?php

namespace deele\devkit\helpers;

use Yii;
use yii\helpers\BaseFileHelper;
use yii\helpers\Url;

/**
 * File helper class
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\helpers
 */
class FileHelper extends BaseFileHelper
{

    /**
     * @param string $path
     * @param bool|string $scheme the URI scheme to use in the generated URL:
     *
     * - `false` (default): generating a relative URL.
     * - `true`: returning an absolute base URL whose scheme is the same as that in [[\yii\web\UrlManager::$hostInfo]].
     * - string: generating an absolute URL with the specified scheme (either `http`, `https` or empty string
     *   for protocol-relative URL).
     *
     * @return string|null
     */
    public static function createFileUrl(string $path, $scheme = true): ?string
    {
        if (empty($path)) {
            return null;
        }
        return Url::to(str_replace(
            '@webroot',
            '@web',
            $path
        ), $scheme);
    }

    /**
     * @param string $pathAlias
     * @return int
     */
    public static function measureFileSize(string $pathAlias): int
    {
        if (empty($pathAlias)) {
            return 0;
        }
        $path = Yii::getAlias($pathAlias);
        if (file_exists($path)) {
            return filesize($path);
        }
        return 0;
    }

    /**
     * Returns a file size limit in bytes based on the PHP upload_max_filesize and post_max_size
     *
     * @param int|null $userLimit
     * @return false|float|int|mixed
     */
    public static function fileUploadMaxSize(?int $userLimit = null)
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = static::parseSize(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = static::parseSize(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        if ($userLimit === null) {
            return $max_size;
        }

        return min($max_size, $userLimit);
    }

    /**
     * This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
     *
     * @param string $size
     * @return float
     */
    public static function parseSize(string $size): float
    {
        // Remove the non-unit characters from the size.
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        // Remove the non-numeric characters from the size.
        $size = preg_replace('/[^0-9.]/', '', $size);
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * (1024 ** stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }
}
