<?php

namespace App\Forms\Components;

use Awcodes\TableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class CustomTableRepeater extends TableRepeater
{
    protected bool|Closure $reorderAtStart = false;

    protected View|Htmlable|Closure|null $footerItem = null;

    /**
     * @var array<string> | Closure | null
     *                                     Atribut yang akan dikecualikan saat melakukan clone/duplikasi baris
     */
    protected array|Closure|null $excludedAttributesForCloning = [
        'id',
        'uuid',
        'invoice_id',
        'created_at',
        'updated_at',
    ];

    public function reorderAtStart(bool|Closure $condition = true): static
    {
        $this->reorderAtStart = $condition;

        return $this;
    }

    public function isReorderAtStart(): bool
    {
        return $this->evaluate($this->reorderAtStart) && $this->isReorderable();
    }

    /**
     * Set footer item untuk menampilkan total, subtotal, atau informasi lainnya
     * Contoh penggunaan:
     * ->footerItem(view('components.invoice-total', ['total' => $total]))
     * atau
     * ->footerItem(fn() => 'Total: Rp ' . number_format($this->calculateTotal()))
     */
    public function footerItem(View|Htmlable|Closure|null $footer = null): static
    {
        $this->footerItem = $footer;

        return $this;
    }

    public function getFooterItem(): View|Htmlable|null
    {
        return $this->evaluate($this->footerItem);
    }

    public function hasFooterItem(): bool
    {
        return $this->footerItem !== null;
    }

    /**
     * Set atribut yang akan dikecualikan saat melakukan clone/duplikasi baris
     * Berguna untuk invoice item agar tidak menduplikasi ID, timestamps, dll
     *
     * @param  array<string> | Closure | null  $attributes
     */
    public function excludeAttributesForCloning(array|Closure|null $attributes): static
    {
        $this->excludedAttributesForCloning = $attributes;

        return $this;
    }

    /**
     * @return array<string> | null
     */
    public function getExcludedAttributesForCloning(): ?array
    {
        return $this->evaluate($this->excludedAttributesForCloning);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->minItems(1);

        $this->stackAt(MaxWidth::Large);

        // Reorder action customization
        $this->reorderAction(function (Action $action) {
            if ($this->isReorderAtStart()) {
                $action->icon('heroicon-m-bars-3');
            }

            return $action;
        });

        // Custom clone action untuk invoice/service items
        // Menghapus atribut yang tidak diinginkan saat duplikasi
        $this->cloneAction(function (Action $action) {
            return $action
                ->label('Duplikasi Item')
                ->icon('heroicon-m-document-duplicate')
                ->action(function (array $arguments, CustomTableRepeater $component): void {
                    $newUuid = $component->generateUuid();
                    $items = $component->getState();

                    $clone = $items[$arguments['item']];

                    // Hapus atribut yang dikecualikan untuk cloning
                    foreach ($component->getExcludedAttributesForCloning() ?? [] as $attribute) {
                        unset($clone[$attribute]);
                    }

                    // Reset quantity ke 1 untuk item baru (opsional)
                    if (isset($clone['quantity'])) {
                        $clone['quantity'] = 1;
                    }

                    if ($newUuid) {
                        $items[$newUuid] = $clone;
                    } else {
                        $items[] = $clone;
                    }

                    $component->state($items);
                    $component->collapsed(false, shouldMakeComponentCollapsible: false);
                    $component->callAfterStateUpdated();
                });
        });
    }

    public function getView(): string
    {
        return 'forms.components.custom-table-repeater';
    }
}
