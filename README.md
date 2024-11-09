# yii2-dev-kit

This is a Yii framework v2.x component development kit that provides additions, helpers, behaviors and other shorthands.

## Tools

### Traits

- Ensure ID Trait
  - `deele\devkit\base\EnsureIdTrait`
- Has Statuses Trait
    - `deele\devkit\base\HasStatusesTrait`
- Has Types Trait
    - `deele\devkit\base\HasTypesTrait`
- Cached by Tag Trait
    - `deele\devkit\cache\CachedByTagTrait`
- Column Name Trait
    - `deele\devkit\db\ColumnNameTrait`

### Behaviors

- Identifier Behavior
    - `deele\devkit\behaviors\IdentifierBehavior`
- Statuses Behavior
    - `deele\devkit\behaviors\StatusesBehavior`
- Status Transitions Behavior
    - `deele\devkit\behaviors\StatusTransitionsBehavior`

### Helpers

- Date formatter
    - `deele\devkit\helpers\DateFormatter`
- File helper
    - `deele\devkit\helpers\FileHelper`
- Active Query helper
  - `deele\devkit\db\ActiveQueryHelper`
- Schema helper
  - `deele\devkit\db\SchemaHelper`

### Interfaces

- Statuses Interface
    - `deele\devkit\interfaces\StatusesInterface`
- Statuses Static Interface
    - `deele\devkit\interfaces\StatusesStaticInterface`
- Transitionable Statuses Interface
    - `deele\devkit\interfaces\TransitionableStatusesInterface`
- Transitionable Statuses Static Interface
    - `deele\devkit\interfaces\TransitionableStatusesStaticInterface`

## Use cases

### Active record with statuses

This is for situations, when ActiveRecord extending class has attribute named "status" that has a numeric value.

#### Statuses trait for simple statuses

For simplest case where any status can be changed at any time and there are no limitations, there is a trait 
`deele\devkit\base\HasStatusesTrait` that you have to use on your ActiveRecord class, that brings couple standard
methods.

To use it:

1. Add `use deele\devkit\base\HasStatusesTrait;` at the top of your class
2. Add `$this->listenForStatusChanges();` to your class `init()`
3. Create each status to your class as constant `public const STATUS_ONE = 1;` (best practice)
4. Create `getStatuses()` static method with all possible statuses

```php
public static function getStatuses(?string $language = null, bool $withLabels = true): array
{
    if ($withLabels) {
        return [
            static::STATUS_ONE => Yii::t('app', 'One', [], $language),
            static::STATUS_TWO => Yii::t('app', 'Two', [], $language),
            static::STATUS_THREE => Yii::t('app', 'Three', [], $language),
        ];
    }
    return [
        static::STATUS_ONE,
        static::STATUS_TWO,
        static::STATUS_THREE,
    ];
}
```

##### Use cases

[//]: <> (@todo Document deele\devkit\base\HasStatusesTrait methods)

###### Validating statuses

[//]: <> (@todo Example for status validation)

###### Providing dropdown options for forms

[//]: <> (@todo Example for input)

###### Providing dropdown options for Grid View filters

[//]: <> (@todo Example for GridView filters)

###### Output current status title

[//]: <> (@todo Example of output current status title)

###### Change status in object-oriented way

[//]: <> (@todo Example of status change)

###### React after status change has happened

[//]: <> (@todo Example of listening to status change event)

#### Statuses behavior for complex statuses

[//]: <> (@todo Document deele\devkit\behaviors\IdentifierBehavior)