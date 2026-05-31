<?php

namespace App\Livewire\Presets;

use App\Models\DiscountPreset;
use Livewire\Component;

class Discount extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $type = 'percentage';
    public string|float $value = 0;
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ];
    }

    public function savePreset(): void
    {
        $data = $this->validate();

        if ($data['type'] === 'percentage') {
            $data['value'] = min(100, (float) $data['value']);
        }

        if ($this->editingId) {
            DiscountPreset::query()->whereKey($this->editingId)->update($data);
            session()->flash('success', __('Discount preset updated'));
        } else {
            DiscountPreset::create($data);
            session()->flash('success', __('Discount preset created'));
        }

        $this->resetForm();
        $this->redirect(route('settings.discounts'));
    }

    public function editPreset(int $id): void
    {
        $preset = DiscountPreset::query()->whereKey($id)->first();
        if (! $preset) {
            return;
        }

        $this->editingId = $preset->id;
        $this->name = (string) $preset->name;
        $this->type = (string) $preset->type;
        $this->value = (float) $preset->value;
        $this->is_active = (bool) $preset->is_active;
    }

    public function toggleActive(int $id): void
    {
        $preset = DiscountPreset::query()->whereKey($id)->first();
        if (! $preset) {
            return;
        }

        $wasActive = (bool) $preset->is_active;

        $preset->update([
            'is_active' => ! $preset->is_active,
        ]);

        session()->flash('success', $wasActive ? __('Discount preset disabled') : __('Discount preset enabled'));
        $this->redirect(route('settings.discounts'));
    }

    public function deletePreset(int $id): void
    {
        $preset = DiscountPreset::query()->whereKey($id)->first();
        if (! $preset) {
            return;
        }

        DiscountPreset::query()->whereKey($id)->delete();
        session()->flash('success', __('Discount preset deleted'));

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->redirect(route('settings.discounts'));
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->type = 'percentage';
        $this->value = 0;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.presets.discount', [
            'presets' => DiscountPreset::query()->orderByDesc('is_active')->orderBy('name', 'asc')->get(),
        ]);
    }
}
