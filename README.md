# LicoFast · Sistema de venta de bebidas por roles

Aplicación web en **PHP + MySQL** con 4 roles de usuario (administrador, cajero, cliente y repartidor/delivery).
Además incluye la API desacoplada con 3 backends intercambiables (**PHP**, **Java** y **Python**) y un frontend SPA.

---

## 1. Instalación rápida (XAMPP)

1. Copia la carpeta `LicoFast1` dentro de `C:\xampp\htdocs\`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Entra a [http://localhost/phpmyadmin](http://localhost/phpmyadmin) e importa `database/licofast.sql`.
   - Si **ya tenías** la base de datos de la versión anterior, importa además `database/actualizacion.sql`
     (agrega las columnas nuevas sin borrar tus datos).
4. Abre [http://localhost/LicoFast1/](http://localhost/LicoFast1/).

### Usuarios de prueba (contraseña de todos: `Admin1234`)

| Rol | Usuario |
|---|---|
| Administrador | `admin_api` |
| Cajero | `cajero1` |
| Repartidor (delivery) | `repartidor1` |
| Cliente | `cliente1` |

---

## 2. Flujo del sistema por rol

### 👤 Administrador (`admin/`)
- Panel con estadísticas: productos, usuarios, compras, entregas pendientes y monto cobrado.
- **Registrar productos**: `admin/productos.php` → botón *Nuevo producto* (crear, editar, eliminar/desactivar).
- **Registrar usuarios y asignar roles**: `admin/usuarios.php` → *Nuevo usuario*.
  En el formulario elige el rol: **Cliente**, **Cajero**, **Repartidor (Delivery)**, **Administrador** o **Empleado**.
  También puede filtrar la lista por rol, editar y eliminar.
- **Apartado de compras**: `admin/compras.php` con filtros por estado, totales facturados,
  monto realmente cobrado y `admin/compra_detalle.php` con el detalle producto por producto.

### 💵 Cajero (`cajero/`)
- Inicia sesión y **solo registra compras** (`cajero/index.php`).
- Elige cliente (opcional) y cantidades; al guardar se descuenta stock,
  la venta queda `pagada` y el pago `pagado` (venta de mostrador).
- `cajero/ventas.php`: historial de las compras que él registró.

### 🛒 Cliente (`cliente/`)
1. `cliente/index.php`: ve el catálogo de productos disponibles.
2. Agrega lo que quiera al **carrito** (`cliente/carrito.php`).
3. Al confirmar pasa a `cliente/checkout.php`, donde **registra el lugar de entrega**
   (dirección, referencia y teléfono).
4. El pedido se guarda con estado **pendiente** y **estado de pago: pendiente**.
5. `cliente/pedidos.php`: sigue el estado de cada pedido.

### 🛵 Repartidor / Delivery (`repartidor/`)
1. `repartidor/index.php`: lista de **compras pendientes** con dirección, teléfono y monto a cobrar.
2. Botón **Entregar** → abre `repartidor/entrega.php` con **dos opciones**:
   - ✅ **Producto entregado** → la venta pasa a `entregada`, el pago a `pagado` y
     **el monto queda registrado** (`monto_cobrado = total`, con fecha de entrega y repartidor).
   - ❌ **No entregado** → la venta pasa a `no_entregado`, el pago se `anula`, no se registra monto y
     **los productos vuelven automáticamente al stock**.
3. `repartidor/historial.php`: entregas realizadas y monto total cobrado.

---

## 3. Cambios de diseño (CSS)

`assets/css/style.css` fue rediseñado: nueva paleta ámbar/madera, barra superior con degradado,
franja que indica el rol activo y **un color de acento distinto por rol**:

| Rol | Color |
|---|---|
| Administrador | Ámbar oscuro |
| Cajero | Verde azulado |
| Cliente | Rojo vino |
| Repartidor | Azul |

También hay tarjetas de estadística (`.stat`), insignias de estado por color
(`pendiente`, `en_camino`, `entregada`, `no_entregado`, `pagado`, `anulado`) y tablas responsivas.

---

## 4. Base de datos

Tabla `ventas` (columnas nuevas):

| Columna | Uso |
|---|---|
| `cajero_id` | Cajero que registró la venta de mostrador |
| `repartidor_id` | Repartidor que hizo la entrega |
| `tipo_venta` | `mostrador` o `delivery` |
| `monto_cobrado` | Monto realmente cobrado (se llena al entregar) |
| `estado` | `pendiente`, `pagada`, `en_camino`, `entregada`, `no_entregado` |
| `estado_pago` | `pendiente`, `pagado`, `anulado` |
| `direccion_entrega`, `referencia_entrega`, `telefono_contacto` | Lugar de entrega registrado por el cliente |
| `observacion_entrega`, `fecha_entrega` | Resultado de la entrega |

---

## 5. API opcional (PHP / Java / Python)

Se mantiene la arquitectura desacoplada del proyecto original:

- **PHP**: `http://localhost/LicoFast1/backend-php/api`
- **Java (Spring Boot)**: `cd backend-java && mvn spring-boot:run` → `http://localhost:8080/api`
- **Python (Flask)**: `cd backend-python && pip install -r requirements.txt && python app.py` → `http://localhost:5000/api`

Frontend SPA: `http://localhost/LicoFast1/frontend/` (selector de backend en la barra superior).

| Operación | Método | Ruta | Rol |
|---|---|---|---|
| Salud | `GET` | `/api/health` | Público |
| Login | `POST` | `/api/login` | Público |
| Registro | `POST` | `/api/register` | Público |
| Usuarios/clientes | `GET/POST/PUT/DELETE` | `/api/clientes[/{id}]` | administrador |
| Productos | `GET/POST/PUT/DELETE` | `/api/productos[/{id}]` | administrador, cajero |
| Catálogo público | `GET` | `/api/productos-publicos` | Público |
| Ventas | `GET/POST` | `/api/ventas` | administrador, cajero, cliente |
| Entregas pendientes | `GET` | `/api/entregas` | repartidor |
| Marcar entrega | `PUT` | `/api/entregas/{id}` body `{"accion":"entregado"｜"no_entregado"}` | repartidor |
