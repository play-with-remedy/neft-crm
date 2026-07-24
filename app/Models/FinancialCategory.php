<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCategory extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Родительская категория.
     *
     * Пример:
     * "Разное" -> родитель "ФОТ"
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            FinancialCategory::class,
            'parent_id'
        );
    }

    /**
     * Прямые дочерние категории.
     *
     * Пример:
     * "ФОТ" -> "Финансы", "Администраторы", "Разное"
     */
    public function children(): HasMany
    {
        return $this->hasMany(
            FinancialCategory::class,
            'parent_id'
        )->orderBy('sort_order');
    }

    /**
     * Все дочерние категории рекурсивно.
     *
     * Нужно для загрузки всего дерева одним запросом.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with('childrenRecursive');
    }

    /**
     * Только корневые категории первого уровня.
     *
     * Использование:
     *
     * FinancialCategory::root()->get();
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Проверяет, является ли категория корневой.
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Проверяет, является ли категория конечной.
     *
     * Конечная категория — категория без дочерних элементов.
     * Позже именно для таких категорий будем вводить сумму.
     */
    public function isLeaf(): bool
    {
        return ! $this->children()->exists();
    }

    /**
     * Возвращает уровень категории.
     *
     * 1 — корневая категория
     * 2 — дочерняя категория
     * 3 — третий уровень
     *
     * Использование:
     *
     * $category->level;
     */
    public function getLevelAttribute(): int
    {
        $level = 1;
        $parent = $this->parent;

        while ($parent !== null) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    /**
     * Возвращает глубину текущей ветки.
     *
     * Пример:
     *
     * ФОТ
     * └── Разное
     *     └── ГС
     *
     * Для ФОТ глубина будет 3.
     * Для Разное — 2.
     * Для ГС — 1.
     */
    public function subtreeDepth(): int
    {
        $this->loadMissing('childrenRecursive');

        if ($this->children->isEmpty()) {
            return 1;
        }

        return 1 + $this->children->max(
            fn (FinancialCategory $child): int =>
                $child->subtreeDepth()
        );
    }

    /**
     * Проверяет, является ли текущая категория потомком указанной.
     *
     * Пример:
     *
     * ФОТ
     * └── Разное
     *     └── ГС
     *
     * $gs->isDescendantOf($fot) === true
     * $fot->isDescendantOf($gs) === false
     */
    public function isDescendantOf(
        FinancialCategory $category
    ): bool {
        $parent = $this->parent;

        while ($parent !== null) {
            if ($parent->is($category)) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Проверяет, является ли указанная категория потомком текущей.
     *
     * Пример:
     *
     * $fot->containsCategory($gs) === true
     */
    public function containsCategory(
        FinancialCategory $category
    ): bool {
        return $category->isDescendantOf($this);
    }

    /**
     * Проверяет, можно ли назначить указанного родителя.
     *
     * Проверяет:
     * - категория не становится родителем самой себе;
     * - родитель не является потомком текущей категории;
     * - глубина дерева не станет больше трёх уровней.
     */
    public function canMoveTo(
        ?FinancialCategory $newParent,
        int $maxDepth = 3
    ): bool {
        /*
         * Перенос в корень разрешён,
         * если глубина самой ветки не больше maxDepth.
         */
        if ($newParent === null) {
            return $this->subtreeDepth() <= $maxDepth;
        }

        /*
         * Нельзя назначить категорию родителем самой себе.
         */
        if ($this->exists && $this->is($newParent)) {
            return false;
        }

        /*
         * Нельзя назначить потомка родителем.
         *
         * Иначе получится цикл:
         *
         * ФОТ
         * └── Разное
         *
         * и затем ФОТ.parent_id = Разное.id
         */
        if (
            $this->exists
            && $this->containsCategory($newParent)
        ) {
            return false;
        }

        /*
         * Уровень нового родителя плюс глубина текущей ветки
         * не должен превышать maxDepth.
         */
        return $newParent->level + $this->subtreeDepth()
            <= $maxDepth;
    }

    /**
     * Возвращает все id потомков категории.
     *
     * Полезно в Filament, чтобы исключить из списка родителей:
     * - текущую категорию;
     * - всех её потомков.
     */
    public function descendantIds(): array
    {
        $this->loadMissing('childrenRecursive');

        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;

            $ids = [
                ...$ids,
                ...$child->descendantIds(),
            ];
        }

        return $ids;
    }

    /**
     * Возвращает название вместе с родительским путём.
     *
     * Например:
     *
     * ФОТ
     * ФОТ / Разное
     * ФОТ / Разное / ГС
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->name];
        $parent = $this->parent;

        while ($parent !== null) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $names);
    }

    /**
     * Значения статьи за разные месяцы.
     */
    public function values(): HasMany
    {
        return $this->hasMany(
            FinancialCategoryValue::class,
            'financial_category_id'
        );
    }

    public function valueForPeriod(string|\DateTimeInterface $period): ?FinancialCategoryValue 
    {
        return $this->values()
            ->whereDate(
                'period',
                \Illuminate\Support\Carbon::parse($period)
                    ->startOfMonth()
                    ->toDateString()
            )
            ->first();
    }
}
