import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.csp.esm';
import { registerVaultGate } from './vault-gate';

registerVaultGate(Alpine);
Livewire.start();
