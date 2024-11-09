<?php

namespace deele\devkit\helpers;

use DateTime;
use DateTimeZone;
use Exception;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Date formatting helper class
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\helpers
 */
class DateFormatter
{

    /**
     * @param string|null $dateTimeString
     * @param string|null $dateTimeZoneString
     * @param string|null $convertToDateTimeZoneString
     *
     * @return DateTime|null
     */
    public static function createDateTime(
        ?string $dateTimeString,
        ?string $dateTimeZoneString = null,
        ?string $convertToDateTimeZoneString = null
    ): ?DateTime {
        if (!static::isEmptyDateTime($dateTimeString)) {
            if ($dateTimeZoneString !== null) {
                try {
                    $dateTimeZone = new DateTimeZone($dateTimeZoneString);
                } catch (Exception $e) {
                    Yii::error(sprintf(
                        'Could not create DateTimeZone "%s": %s',
                        $dateTimeZoneString,
                        $e->getMessage()
                    ));
                    return null;
                }
            } else {
                $dateTimeZone = null;
            }
            try {
                $dateTime = new DateTime($dateTimeString, $dateTimeZone);
            } catch (Exception $e) {
                Yii::error(sprintf(
                    'Could not format date "%s": %s',
                    $dateTimeString,
                    $e->getMessage()
                ));
                return null;
            }
            if ($convertToDateTimeZoneString !== null) {
                try {
                    $convertToDateTimeZone = new DateTimeZone($convertToDateTimeZoneString);
                } catch (Exception $e) {
                    Yii::error(sprintf(
                        'Could not create DateTimeZone "%s": %s',
                        $convertToDateTimeZoneString,
                        $e->getMessage()
                    ));
                    return null;
                }
                $dateTime->setTimezone($convertToDateTimeZone);
            }

            return $dateTime;
        }

        return null;
    }

    /**
     * @param string|null $dateTimeString
     *
     * @return DateTime|null
     */
    public static function createUtcDate(?string $dateTimeString): ?DateTime
    {
        return static::createDateTime($dateTimeString, 'UTC');
    }

    /**
     * @param string|null $dateTimeString
     * @param string $format
     *
     * @return string
     */
    public static function formatUtcDate(?string $dateTimeString, string $format = 'c'): string
    {
        $date = static::createUtcDate($dateTimeString);
        if ($date !== null) {
            return $date->format($format);
        }

        return '';
    }

    /**
     * @return array
     */
    public static function weekdayNames(): array
    {
        return [
            Yii::t('deele.devkit.DateFormatter', 'Monday'),
            Yii::t('deele.devkit.DateFormatter', 'Tuesday'),
            Yii::t('deele.devkit.DateFormatter', 'Thursday'),
            Yii::t('deele.devkit.DateFormatter', 'Wednesday'),
            Yii::t('deele.devkit.DateFormatter', 'Friday'),
            Yii::t('deele.devkit.DateFormatter', 'Saturday'),
            Yii::t('deele.devkit.DateFormatter', 'Sunday'),
        ];
    }

    /**
     * @return array
     */
    public static function monthNames(): array
    {
        return [
            1 => Yii::t('deele.devkit.DateFormatter', 'January'),
            2 => Yii::t('deele.devkit.DateFormatter', 'February'),
            3 => Yii::t('deele.devkit.DateFormatter', 'March'),
            4 => Yii::t('deele.devkit.DateFormatter', 'April'),
            5 => Yii::t('deele.devkit.DateFormatter', 'May'),
            6 => Yii::t('deele.devkit.DateFormatter', 'June'),
            7 => Yii::t('deele.devkit.DateFormatter', 'July'),
            8 => Yii::t('deele.devkit.DateFormatter', 'August'),
            9 => Yii::t('deele.devkit.DateFormatter', 'September'),
            10 => Yii::t('deele.devkit.DateFormatter', 'October'),
            11 => Yii::t('deele.devkit.DateFormatter', 'November'),
            12 => Yii::t('deele.devkit.DateFormatter', 'December'),
        ];
    }

    /**
     * @param DateTime|string $dateTimeInput
     * @param string|null $dateTimeZoneString
     * @param string $template
     *
     * @return string
     */
    public static function formatDate(
        $dateTimeInput,
        ?string $dateTimeZoneString = null,
        string $template = '{monthName} {day} {year}, {hours}:{minutes}'
    ): string {
        if ($dateTimeInput instanceof DateTime) {
            $dateTime = clone $dateTimeInput;
        } else {
            $dateTime = static::createDateTime($dateTimeInput, $dateTimeZoneString);
        }
        if ($dateTime === null) {
            return '';
        }
        if (!empty($dateTimeZoneString)) {
            $dateTime->setTimezone(new DateTimeZone($dateTimeZoneString));
        }
        $weekdayNames = self::weekdayNames();
        $monthNames = self::monthNames();
        return strtr(
            $template,
            [
                '{year}' => $dateTime->format('Y'),
                '{month}' => $dateTime->format('n'),
                '{monthName}' => $monthNames[$dateTime->format('n')],
                '{day}' => $dateTime->format('j'),
                '{weekday}' => $dateTime->format('N'),
                '{weekdayName}' => $weekdayNames[$dateTime->format('N') - 1],
                '{hours}' => $dateTime->format('H'),
                '{minutes}' => $dateTime->format('i'),
                '{seconds}' => $dateTime->format('s'),
            ]
        );
    }

    /**
     * @param string|null $dateTimeString in "Y-M-D H:i:s" format
     * @return bool
     */
    public static function isEmptyDateTime(?string $dateTimeString): bool
    {
        return !is_string($dateTimeString) || empty($dateTimeString) || $dateTimeString === '0000-00-00 00:00:00';
    }

    /**
     * @param string|null $dateTimeString
     * @param array $options
     * @return mixed|string|null
     */
    public static function convertDateTimeTimezone(?string $dateTimeString, array $options = [])
    {
        try {
            if (static::isEmptyDateTime($dateTimeString)) {
                return ArrayHelper::getValue($options, 'defaultValue');
            }
            $timeZoneFrom = ArrayHelper::getValue($options, 'timeZoneFrom', 'UTC');
            $timeZoneTo = ArrayHelper::getValue($options, 'timeZoneTo', Yii::$app->timeZone);
            $format = ArrayHelper::getValue($options, 'format', 'Y-m-d H:i:s');
            $skipZeroTime = (bool) ArrayHelper::getValue($options, 'skipZeroTime', false);
        } catch (Exception $e) {
            Yii::error(
                'Could not begin date and time conversion: ' . $e->getMessage()
            );
            return '';
        }
        $dateTime = static::createDateTime($dateTimeString, $timeZoneFrom, $timeZoneTo);
        if ($dateTime === null) {
            return '';
        }
        if ($format === null) {
            return $dateTime;
        }
        if ($skipZeroTime && $dateTime->format('H:i:s') === '00:00:00') {
            if (str_contains($format, 'H:i:s')) {
                $format = str_replace('H:i:s', '', $format);
            } elseif (str_contains($format, 'H:i')) {
                $format = str_replace('H:i', '', $format);
            }
        }

        return $dateTime->format($format);
    }

    /**
     * @param Model $model
     * @param string $attribute
     * @param array $options
     */
    public static function convertModelDateTimeTimezoneForDisplay(Model $model, string $attribute, array $options = [])
    {
        if ($model->canSetProperty($attribute)) {
            $model->{$attribute} = static::convertDateTimeTimezone(
                $model->{$attribute},
                ArrayHelper::merge(
                    [
                        'timeZoneFrom' => 'UTC',
                        'timeZoneTo' => Yii::$app->timeZone,
                    ],
                    $options
                )
            );
        }
    }

    /**
     * @param Model $model
     * @param string $attribute
     * @param array $options
     */
    public static function convertModelDateTimeTimezoneForStorage(Model $model, string $attribute, array $options = [])
    {
        if ($model->canSetProperty($attribute)) {
            if (empty($model->{$attribute})) {
                $model->{$attribute} = null;
            } else {
                $model->{$attribute} = static::convertDateTimeTimezone(
                    $model->{$attribute},
                    ArrayHelper::merge(
                        [
                            'timeZoneFrom' => Yii::$app->timeZone,
                            'timeZoneTo' => 'UTC',
                        ],
                        $options
                    )
                );
            }
        }
    }
}
