import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['input', 'name'];

  updateName() {
    const file = this.inputTarget.files?.[0];
    this.nameTarget.textContent = file ? file.name : 'Dosya seçilmedi';
  }
}
