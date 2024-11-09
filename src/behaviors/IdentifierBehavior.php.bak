<?php

namespace deele\devkit\behaviors;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;

/**
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\behaviors
 *
 * @property ActiveRecordInterface $owner
 */
class IdentifierBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive identifier value
     */
    public $identifierAttribute = 'identifier';
    /**
     * @var string the attribute that identifies an entry in database
     */
    public $idAttribute = 'id';
    /**
     * @var int the length of generated identifier
     */
    public $maximumLength = 10;
    /**
     * @var bool use readable character set
     */
    public $useReadableCharacterSet = true;
    /**
     * @var bool use only uppercase letters
     */
    public $useOnlyUppercaseLetters = true;
    /**
     * @var bool generate random identifier if no identifier is provided
     */
    public $generateOnEmpty = true;
    /**
     * @var string identifier prefix
     *
     * This is counted as part of unique identifier thus lowering number of possible identifiers in existence.
     */
    public $prefix;
    /**
     * @var string all identifier characters
     */
    public $allIdentifierCharacters = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    /**
     * @var string readable identifier characters
     */
    public $readableIdentifierCharacters = '23456789abcdefghjklmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    /**
     * {@inheritdoc}
     */
    public $value;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                Model::EVENT_BEFORE_VALIDATE => $this->identifierAttribute,
                BaseActiveRecord::EVENT_BEFORE_INSERT => $this->identifierAttribute,
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->identifierAttribute,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($event)
    {
        if ($this->value === null) {
            $this->ensureUniqueIdentifier();
            return $this->owner->{$this->identifierAttribute};
        }

        return parent::getValue($event);
    }


    /**
     * @return string
     */
    public function generateIdentifier(): string
    {
        if ($this->useReadableCharacterSet) {
            $characters = $this->readableIdentifierCharacters;
        } else {
            $characters = $this->allIdentifierCharacters;
        }
        if ($this->useOnlyUppercaseLetters) {
            $characters = preg_replace('~[^\p{Lu}]+~u', '', $characters);
        }
        $randomString = $this->prefix;
        $i = 0;
        while (strlen($randomString) < $this->maximumLength) {
            try {
                $randomChar = strtoupper(Yii::$app->security->generateRandomKey(1));
            } catch (Exception $e) {
                Yii::error(
                    'Could not generate identifier: ' . $e->getMessage()
                );
                break;
            }
            if (strpos($characters, $randomChar)) {
                $randomString .= $randomChar;
            }
            $i++;
        }
        Yii::info(
            sprintf('Generating new identifier took %d steps', $i),
            static::class
        );

        return $randomString;
    }

    /**
     * @param string $identifier
     * @param int|null $exceptId
     * @return bool
     */
    public function existsByIdentifier(string $identifier, int $exceptId = null): bool
    {
        return (
            (
                $exceptId === null &&
                $this->owner::find()->where([
                    $this->identifierAttribute => $identifier
                ])->exists()
            ) ||
            (
                $exceptId !== null &&
                $this->owner::find()->where([
                    '!=',
                    $this->idAttribute,
                    $exceptId
                ])->andWhere([
                    $this->identifierAttribute => $identifier
                ])->exists()
            )
        );
    }

    /**
     * @return string
     */
    public function generateUniqueIdentifier(): string
    {
        $identifier = null;
        while ($identifier === null || $this->existsByIdentifier($identifier)) {
            $identifier = $this->generateIdentifier();
        }

        return $identifier;
    }

    /**
     * Regenerates new random identifier
     *
     * @param bool $autoSave
     * @return bool
     */
    public function regenerateIdentifier(bool $autoSave = false): bool
    {
        $this->owner->{$this->identifierAttribute} = $this->generateUniqueIdentifier();
        if ($autoSave === true) {
            if ($this->owner->isNewRecord) {
                return false;
            }
            return $this->owner->save();
        }
        return true;
    }

    /**
     * Ensures unique identifier is set either manually or automatically
     */
    public function ensureUniqueIdentifier(): void
    {
        if (empty($this->owner->{$this->identifierAttribute})) {
            if ($this->generateOnEmpty) {
                $this->regenerateIdentifier();
            }
        } else {
            // Trim to maximum length
            if (mb_strlen($this->owner->{$this->identifierAttribute}) > $this->maximumLength) {
                $this->owner->{$this->identifierAttribute} = mb_substr(
                    $this->owner->{$this->identifierAttribute},
                    0,
                    $this->maximumLength
                );
            }

            // If given identifier exists, append ever incrementing number at the end of it
            $iterationSuffix = 2;
            $originalIdentifier = $this->owner->{$this->identifierAttribute};
            while ($this->existsByIdentifier(
                $this->owner->{$this->identifierAttribute},
                $this->owner->{$this->idAttribute}
            )) {
                $this->owner->{$this->identifierAttribute} = mb_substr(
                    $originalIdentifier,
                    0,
                    $this->maximumLength - strlen((string)$iterationSuffix)
                ) .
                    $iterationSuffix;
                $iterationSuffix++;
            }
        }
    }
}
