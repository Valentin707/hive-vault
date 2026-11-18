import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.csp.esm';
import { registerVaultGate } from './vault-gate';
import { registerVaultWorkspace } from './vault-workspace';

registerVaultGate(Alpine);
registerVaultWorkspace(Alpine);
Livewire.start();
