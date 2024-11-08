<?php

namespace App\Livewire\Modal;

use Livewire\Component;

class DeleteModal extends Component
{
    public $showModal = false;
    public $itemId;

    protected $listeners = ['openDeleteModal' => 'open'];

    public function open($itemId)
    {
        $this->itemId = $itemId;
        $this->showModal = true;
    }

    public function close()
    {
        $this->reset(['showModal', 'itemId']);
    }

    public function deleteItem()
    {
        // Logic to delete item by $this->itemId
        $this->emit('itemDeleted', $this->itemId);
        $this->close();
    }

    public function render()
    {
        return view('livewire.modal.delete-modal');
    }
}
