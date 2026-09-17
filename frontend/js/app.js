let token = localStorage.getItem('lf_token');
let current = JSON.parse(localStorage.getItem('lf_user') || 'null');
let clientListCache = [];
let currentActiveTab = 'clientes';

const $ = id => document.getElementById(id);

async function checkBackendHealth(base) {
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2000);
    const r = await fetch(base + '/health', { cache: 'no-store', signal: controller.signal });
    clearTimeout(timeoutId);
    return r.ok;
  } catch (e) {
    return false;
  }
}

async function detectBackend() {
  const select = $('backendSelect');
  const preferred = localStorage.getItem('lf_backend') || API_BASE;
  
  if (select) {
    if (![...select.options].some(o => o.value === preferred)) {
      const opt = document.createElement('option');
      opt.value = preferred;
      opt.textContent = preferred;
      select.appendChild(opt);
    }
    select.value = preferred;
  }

  const isPreferredOk = await checkBackendHealth(preferred);
  if (isPreferredOk) {
    setBackend(preferred, true);
    return;
  }

  for (const base of API_CANDIDATES) {
    if (base === preferred) continue;
    const ok = await checkBackendHealth(base);
    if (ok) {
      setBackend(base, true);
      return;
    }
  }

  setBackend(preferred, false);
}

function setBackend(base, isOnline) {
  API_BASE = base;
  localStorage.setItem('lf_backend', base);
  const select = $('backendSelect');
  if (select && select.value !== base) {
    select.value = base;
  }
  const label = $('backendLabel');
  if (label) {
    if (isOnline) {
      label.innerHTML = '<span style="color:#4ade80;font-weight:bold;">● En línea</span>';
    } else {
      label.innerHTML = '<span style="color:#f87171;font-weight:bold;">● Sin conexión</span> <span style="font-size:11px;color:#aaa;">(inicia el backend)</span>';
    }
  }
}

if ($('backendSelect')) {
  $('backendSelect').onchange = async (e) => {
    const newBase = e.target.value;
    $('backendLabel').innerHTML = '<span style="color:#fbbf24;">Verificando...</span>';
    const ok = await checkBackendHealth(newBase);
    setBackend(newBase, ok);
    if (ok && current) {
      showTab(currentActiveTab);
    }
  };
}

const api = async (path, opt = {}) => {
  opt.headers = {
    ...(opt.headers || {}),
    'Content-Type': 'application/json',
    ...(token ? { Authorization: 'Bearer ' + token } : {})
  };
  let r;
  try {
    r = await fetch(API_BASE + path, opt);
  } catch (err) {
    throw Error('No se puede conectar con el backend actual (' + API_BASE + '). Asegúrate de que esté iniciado.');
  }
  let d = await r.json().catch(() => ({ error: 'Respuesta inválida del servidor' }));
  if (!r.ok) throw Error(d.error || 'Ocurrió un error en la solicitud');
  return d;
};

$('backendLabel').textContent = 'Buscando backend...';
detectBackend();

$('loginForm').onsubmit = async e => {
  e.preventDefault();
  $('loginMsg').textContent = 'Iniciando sesión...';
  $('loginMsg').className = 'msg';
  try {
    let d = await api('/login', {
      method: 'POST',
      body: JSON.stringify({
        usuario: $('loginUser').value.trim(),
        password: $('loginPass').value
      })
    });
    current = d.user;
    token = current.token;
    localStorage.setItem('lf_token', token);
    localStorage.setItem('lf_user', JSON.stringify(current));
    $('loginMsg').textContent = '';
    start();
  } catch (x) {
    $('loginMsg').textContent = x.message;
    $('loginMsg').className = 'msg err';
  }
};

$('logout').onclick = () => {
  localStorage.removeItem('lf_token');
  localStorage.removeItem('lf_user');
  location.reload();
};

function start() {
  $('loginView').hidden = true;
  $('appView').hidden = false;
  $('logout').hidden = false;
  $('welcome').textContent = 'Hola, ' + current.nombre + ' (' + current.rol + ')';
  document.querySelectorAll('.toolbar button').forEach(b => b.hidden = false);

  if (current.rol === 'administrador') showTab('clientes');
  else if (current.rol === 'cajero') showTab('productos');
  else if (current.rol === 'repartidor') showTab('entregas');
  else showTab('tienda');
}

function showTab(t) {
  currentActiveTab = t;
  if (t === 'clientes' && current.rol === 'administrador') clientes();
  else if (t === 'productos' && ['administrador', 'cajero'].includes(current.rol)) productos();
  else if (t === 'tienda') tienda();
  else if (t === 'ventas' && ['administrador', 'cajero', 'cliente'].includes(current.rol)) ventas();
  else if (t === 'entregas' && current.rol === 'repartidor') entregas();
}

async function clientes() {
  try {
    let rows = await api('/clientes');
    clientListCache = rows;
    $('content').innerHTML = `
      <div id="clientFormCard" class="card">
        <h2 id="clientFormTitle">➕ Agregar nuevo cliente</h2>
        <form id="clientForm">
          <input type="hidden" id="clientId" value="">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label style="font-size:12px;font-weight:bold;">Nombre</label>
              <input id="cNombre" placeholder="Ej. Juan" required style="width:100%;">
            </div>
            <div>
              <label style="font-size:12px;font-weight:bold;">Apellido</label>
              <input id="cApellido" placeholder="Ej. Pérez" required style="width:100%;">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label style="font-size:12px;font-weight:bold;">Cédula de Identidad (CI)</label>
              <input id="cCi" placeholder="Ej. 1234567" required style="width:100%;">
            </div>
            <div>
              <label style="font-size:12px;font-weight:bold;">Fecha de nacimiento</label>
              <input id="cFecha" type="date" value="2000-01-01" required style="width:100%;">
            </div>
          </div>
          <div>
            <label style="font-size:12px;font-weight:bold;">Nombre de usuario</label>
            <input id="cUsuario" placeholder="Ej. juanperez" required style="width:100%;">
          </div>
          <div id="cPassContainer">
            <label style="font-size:12px;font-weight:bold;">Contraseña (mínimo 8 caracteres)</label>
            <input id="cPass" type="password" placeholder="Contraseña" value="Cliente123" minlength="8" style="width:100%;">
          </div>
          <div id="cActivoContainer" style="display:none;margin-top:4px;">
            <label style="cursor:pointer;">
              <input type="checkbox" id="cActivo" checked> Cliente Activo
            </label>
          </div>
          <div style="display:flex;gap:10px;margin-top:10px;">
            <button id="btnSubmitClient" class="btn">Guardar cliente</button>
            <button id="btnCancelClient" type="button" class="btn secondary" style="display:none;" onclick="cancelEditClient()">Cancelar</button>
          </div>
        </form>
      </div>

      <div class="card">
        <h2>Listado de Clientes (${rows.length})</h2>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre completo</th>
              <th>CI</th>
              <th>Usuario</th>
              <th>Edad</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            ${rows.length === 0 ? '<tr><td colspan="7" style="text-align:center;">No hay clientes registrados.</td></tr>' : rows.map(c => `
              <tr>
                <td>#${c.id}</td>
                <td><b>${escapeHtml(c.nombre)} ${escapeHtml(c.apellido)}</b></td>
                <td>${escapeHtml(c.ci)}</td>
                <td>${escapeHtml(c.usuario)}</td>
                <td>${c.edad || '-'} años</td>
                <td>
                  <span class="badge" style="${c.activo ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;'}">
                    ${c.activo ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td style="white-space:nowrap;">
                  <button class="btn" style="padding:6px 12px;font-size:13px;" onclick="editClient(${c.id})">✏️ Modificar</button>
                  <button class="btn danger" style="padding:6px 12px;font-size:13px;" onclick="deleteClient(${c.id}, '${escapeHtml(c.nombre)}')">🗑️ Eliminar</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;

    $('clientForm').onsubmit = async (e) => {
      e.preventDefault();
      const id = $('clientId').value;
      const payload = {
        nombre: $('cNombre').value.trim(),
        apellido: $('cApellido').value.trim(),
        ci: $('cCi').value.trim(),
        fecha_nacimiento: $('cFecha').value,
        usuario: $('cUsuario').value.trim()
      };

      try {
        if (id) {
          payload.activo = $('cActivo').checked ? 1 : 0;
          let res = await api('/clientes/' + id, {
            method: 'PUT',
            body: JSON.stringify(payload)
          });
          alert(res.message || 'Cliente modificado correctamente.');
        } else {
          payload.password = $('cPass').value;
          let res = await api('/clientes', {
            method: 'POST',
            body: JSON.stringify(payload)
          });
          alert(res.message || 'Cliente agregado correctamente.');
        }
        clientes();
      } catch (x) {
        alert(x.message);
      }
    };
  } catch (err) {
    $('content').innerHTML = `<div class="card msg err">Error al cargar clientes desde ${API_BASE}: ${err.message}</div>`;
  }
}

function editClient(id) {
  const c = clientListCache.find(x => x.id === id);
  if (!c) return;

  $('clientFormTitle').textContent = `✏️ Modificar cliente #${c.id} — ${c.nombre} ${c.apellido}`;
  $('clientId').value = c.id;
  $('cNombre').value = c.nombre;
  $('cApellido').value = c.apellido;
  $('cCi').value = c.ci;
  $('cFecha').value = (c.fecha_nacimiento || '2000-01-01').substring(0, 10);
  $('cUsuario').value = c.usuario;
  $('cPassContainer').style.display = 'none';
  $('cActivoContainer').style.display = 'block';
  $('cActivo').checked = !!Number(c.activo);
  $('btnSubmitClient').textContent = 'Actualizar cliente';
  $('btnCancelClient').style.display = 'inline-block';
  $('clientFormCard').scrollIntoView({ behavior: 'smooth' });
}

function cancelEditClient() {
  $('clientFormTitle').textContent = '➕ Agregar nuevo cliente';
  $('clientId').value = '';
  $('clientForm').reset();
  $('cFecha').value = '2000-01-01';
  $('cPassContainer').style.display = 'block';
  $('cActivoContainer').style.display = 'none';
  $('btnSubmitClient').textContent = 'Guardar cliente';
  $('btnCancelClient').style.display = 'none';
}

async function deleteClient(id, name) {
  if (!confirm(`¿Confirmas que deseas eliminar al cliente #${id} (${name})?`)) return;
  try {
    let res = await api('/clientes/' + id, { method: 'DELETE' });
    alert(res.message || 'Cliente eliminado correctamente.');
    clientes();
  } catch (x) {
    alert(x.message);
  }
}

async function productos() {
  try {
    let rows = await api('/productos');
    $('content').innerHTML = `
      <div class="card">
        <h2>➕ Agregar producto</h2>
        <form id="newProd">
          <input name="nombre" placeholder="Nombre" required>
          <textarea name="descripcion" placeholder="Descripción"></textarea>
          <input name="precio" type="number" step="0.01" placeholder="Precio" required>
          <input name="stock" type="number" placeholder="Stock" required>
          <button class="btn">Agregar producto</button>
        </form>
      </div>
      <div class="card">
        <h2>Inventario de Productos (${rows.length})</h2>
        <table>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Acción</th>
          </tr>
          ${rows.map(p => `
            <tr>
              <td><b>${escapeHtml(p.nombre)}</b></td>
              <td>Bs ${Number(p.precio).toFixed(2)}</td>
              <td>${p.stock}</td>
              <td>
                <button class="btn danger" style="padding:6px 12px;font-size:13px;" onclick="deleteProduct(${p.id})">Eliminar</button>
              </td>
            </tr>
          `).join('')}
        </table>
      </div>
    `;
    $('newProd').onsubmit = async e => {
      e.preventDefault();
      let o = Object.fromEntries(new FormData(e.target));
      o.precio = +o.precio;
      o.stock = +o.stock;
      try {
        await api('/productos', { method: 'POST', body: JSON.stringify(o) });
        productos();
      } catch (x) {
        alert(x.message);
      }
    };
  } catch (err) {
    $('content').innerHTML = `<div class="card msg err">Error al cargar productos: ${err.message}</div>`;
  }
}

async function deleteProduct(id) {
  if (!confirm('¿Deseas eliminar este producto?')) return;
  try {
    await api('/productos/' + id, { method: 'DELETE' });
    productos();
  } catch (x) {
    alert(x.message);
  }
}

async function tienda() {
  try {
    let rows = await api('/productos-publicos');
    $('content').innerHTML = '<div class="grid">' + rows.map(p => `
      <div class="card product">
        <div style="font-size:50px;text-align:center;">🍾</div>
        <h3>${escapeHtml(p.nombre)}</h3>
        <p>${escapeHtml(p.descripcion || '')}</p>
        <p class="price">Bs ${Number(p.precio).toFixed(2)}</p>
        <p>Stock disponible: <b>${p.stock}</b></p>
        <div style="display:flex;gap:8px;margin-top:auto;">
          <input id="q${p.id}" type="number" min="1" max="${p.stock}" value="1" style="width:70px;">
          <button class="btn" onclick="buy(${p.id})" style="flex:1;">Comprar</button>
        </div>
      </div>
    `).join('') + '</div><div id="buymsg"></div>';
  } catch (err) {
    $('content').innerHTML = `<div class="card msg err">Error al cargar tienda: ${err.message}</div>`;
  }
}

async function buy(id) {
  let q = +document.getElementById('q' + id).value;
  try {
    let d = await api('/ventas', {
      method: 'POST',
      body: JSON.stringify({
        items: [{ producto_id: id, cantidad: q }],
        cliente_id: current.id
      })
    });
    $('buymsg').textContent = 'Compra registrada con éxito. Venta #' + d.id;
    $('buymsg').className = 'msg ok';
    tienda();
  } catch (x) {
    alert(x.message);
  }
}

async function ventas() {
  try {
    let rows = await api('/ventas');
    $('content').innerHTML = `
      <div class="card">
        <h2>Historial de Ventas (${rows.length})</h2>
        <table>
          <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Estado</th>
          </tr>
          ${rows.map(v => `
            <tr>
              <td>#${v.id}</td>
              <td>${v.nombre ? escapeHtml(v.nombre + ' ' + v.apellido) : 'Venta mostrador'}</td>
              <td>Bs ${Number(v.total).toFixed(2)}</td>
              <td><span class="badge">${escapeHtml(v.estado)}</span></td>
            </tr>
          `).join('')}
        </table>
      </div>
    `;
  } catch (err) {
    $('content').innerHTML = `<div class="card msg err">Error al cargar ventas: ${err.message}</div>`;
  }
}

async function entregas() {
  try {
    let rows = await api('/entregas');
    $('content').innerHTML = `
      <div class="card">
        <h2>Encargos de Entrega (${rows.length})</h2>
        <table>
          <tr>
            <th>Venta</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Acción</th>
          </tr>
          ${rows.map(v => `
            <tr>
              <td>#${v.id}</td>
              <td>${escapeHtml(v.nombre || 'Tienda')}</td>
              <td>Bs ${Number(v.total).toFixed(2)}</td>
              <td><button class="btn" onclick="deliver(${v.id})">Marcar entregada</button></td>
            </tr>
          `).join('')}
        </table>
      </div>
    `;
  } catch (err) {
    $('content').innerHTML = `<div class="card msg err">Error al cargar entregas: ${err.message}</div>`;
  }
}

async function deliver(id) {
  try {
    await api('/entregas/' + id, { method: 'PUT' });
    entregas();
  } catch (x) {
    alert(x.message);
  }
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  })[m]);
}

if (token && current) {
  start();
}

