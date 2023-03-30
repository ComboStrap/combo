## Bootstrap Alias Configuration


The alias configuration is in `vite.config.js`

it means that all `from "bootstrap"` are replaced by the alias. ie

```javascript
import {Modal} from "bootstrap";

let bootStrapModal = new Modal(this.modalRoot, options);
```
is replaced when bundling by:
```javascript
let bootStrapModal = new bootstrap.Modal(this.modalRoot, options);
```
