<?php

namespace deele\devkit\behaviors;

use deele\devkit\helpers\IdentifierCreator;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;

/**
 * Behavior of an ActiveRecord
 *
 * @property string $allIdentifierCharacters {@see IdentifierCreator::$charset}
 * @property bool $useOnlyUppercaseLetters {@see IdentifierCreator::$excludeLowercaseCharacters}
 * @property bool $useReadableCharacterSet {@see IdentifierCreator::$excludeLookAlikeCharacters}
 * @property string $prefix {@see IdentifierCreator::$prefix}
 * @property string $suffix {@see IdentifierCreator::$suffix}
 * @property ActiveRecordInterface $owner {@see Behavior::$owner}
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\behaviors
 */
class IdentifierBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive identifier value
     */
    public string $identifierAttribute = 'identifier';

    /**
     * @var string the attribute that identifies an entry in database
     */
    public string $idAttribute = 'id';

    /**
     * @var int the length of generated identifier
     */
    public int $maximumLength = 10;

    /**
     * @var bool generate random identifier if no identifier is provided
     */
    public bool $generateOnEmpty = true;

    /**
     * @var string|null identifier prefix
     *
     * This is counted as part of unique identifier thus lowering number of possible identifiers in existence.
     */
    public ?string $suffix = null;

    /**
     * {@inheritdoc}
     */
    public $value;

    /**
     * @var IdentifierCreator|null
     */
    protected ?IdentifierCreator $_identifierCreator = null;

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
        $this->_identifierCreator = new IdentifierCreator();
    }

    /**
     * Setting to bool true will remove look-alike characters ("O", "0", "I", "|", "l" and "1") from the character set.
     *
     * @param bool $value
     *
     * @return $this
     * @see IdentifierBehavior::getUseReadableCharacterSet()
     *
     */
    public function setUseReadableCharacterSet(bool $value): self
    {
        $this->_identifierCreator->excludeLookAlikeCharacters = $value;
        return $this;
    }

    /**
     * @return bool false by default
     * @see IdentifierBehavior::setUseReadableCharacterSet()
     *
     */
    public function getUseReadableCharacterSet(): bool
    {
        return $this->_identifierCreator->excludeLookAlikeCharacters;
    }

    /**
     * Setting to bool true will remove lowercase characters from the character set.
     *
     * @param bool $value
     *
     * @return static
     * @see IdentifierBehavior::getUseOnlyUppercaseLetters()
     *
     */
    public function setUseOnlyUppercaseLetters(bool $value): self
    {
        $this->_identifierCreator->excludeLowercaseCharacters = $value;
        return $this;
    }

    /**
     * @return bool false by default
     * @see IdentifierBehavior::setUseOnlyUppercaseLetters()
     *
     */
    public function getUseOnlyUppercaseLetters(): bool
    {
        return $this->_identifierCreator->excludeLowercaseCharacters;
    }

    /**
     * @param string $value set of characters used for identifier generation
     *
     * @return static
     * @see IdentifierBehavior::getAllIdentifierCharacters()
     *
     */
    public function setAllIdentifierCharacters(string $value): self
    {
        $this->_identifierCreator->charset = $value;
        return $this;
    }

    /**
     * @return string alpha-numeric ASCII characters by default
     * @see IdentifierBehavior::setAllIdentifierCharacters()
     *
     */
    public function getAllIdentifierCharacters(): string
    {
        return $this->_identifierCreator->charset;
    }

    /**
     * Identifier prefix.
     *
     * This is counted as part of unique identifier thus lowering number of possible identifiers in existence.
     *
     * @param string $value prefix
     *
     * @return static
     * @see IdentifierBehavior::getPrefix()
     *
     */
    public function setPrefix(string $value): self
    {
        $this->_identifierCreator->prefix = $value;
        return $this;
    }

    /**
     * @return string empty by default
     * @see IdentifierBehavior::setPrefix()
     *
     */
    public function getPrefix(): string
    {
        return $this->_identifierCreator->prefix;
    }

    /**
     * Identifier suffix.
     *
     * This is counted as part of unique identifier thus lowering number of possible identifiers in existence.
     *
     * @param string $value suffix
     *
     * @return static
     * @see IdentifierBehavior::getSuffix()
     *
     */
    public function setSuffix(string $value): self
    {
        $this->_identifierCreator->suffix = $value;
        return $this;
    }

    /**
     * @return string empty by default
     * @see IdentifierBehavior::setSuffix()
     *
     */
    public function getSuffix(): string
    {
        return $this->_identifierCreator->suffix;
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
        return $this->_identifierCreator->generate($this->maximumLength);
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
