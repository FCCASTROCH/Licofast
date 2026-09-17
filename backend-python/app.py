from flask import Flask, request, jsonify
from flask_cors import CORS
import mysql.connector, hashlib, base64, secrets, datetime
app=Flask(__name__); CORS(app)
DB=dict(host='127.0.0.1',user='root',password='',database='licofast')
def db(): return mysql.connector.connect(**DB)
def q(sql,args=(),one=False,commit=False):
 c=db(); cur=c.cursor(dictionary=True); cur.execute(sql,args); r=cur.fetchone() if one else cur.fetchall();
 if commit:c.commit()
 cur.close();c.close();return r
def hashpass(p):
 salt=secrets.token_bytes(16); h=hashlib.pbkdf2_hmac('sha256',p.encode(),salt,120000,32); return f'PBKDF2-SHA256$120000${base64.b64encode(salt).decode()}${base64.b64encode(h).decode()}'
def verify(p,s):
 try:
  a=s.split('$');
  if a[0]!='PBKDF2-SHA256': return False
  salt=base64.b64decode(a[2]); old=base64.b64decode(a[3]); return secrets.compare_digest(hashlib.pbkdf2_hmac('sha256',p.encode(),salt,int(a[1]),32),old)
 except: return False
def auth(roles=None):
 t=request.headers.get('Authorization','').replace('Bearer ','');
 try:i,r=t.split('|'); i=int(i)
 except:return None,('Token requerido',401)
 if roles and r not in roles:return None,('Acceso denegado',403)
 return {'id':i,'rol':r},None
@app.get('/api/health')
def health():return jsonify(ok=True,backend='python')
@app.post('/api/login')
def login():
 d=request.json or {}; u=q('SELECT id,nombre,apellido,usuario,password,rol,activo,edad FROM usuarios WHERE usuario=%s LIMIT 1',(d.get('usuario',''),),True)
 if not u or not u['activo'] or not verify(d.get('password',''),u['password']):return jsonify(error='Usuario o contraseña incorrectos'),401
 u.pop('password');u['token']=f"{u['id']}|{u['rol']}";return jsonify(user=u)
@app.post('/api/register')
def register():
 d=request.json or {}; dob=d.get('fecha_nacimiento');
 try:age=(datetime.date.today()-datetime.date.fromisoformat(dob)).days//365
 except:age=0
 if age<18 or not all(d.get(x) for x in ['nombre','apellido','ci','usuario']) or len(d.get('password',''))<8 or not d.get('terminos'):return jsonify(error='Datos inválidos o edad menor a 18'),422
 if q('SELECT id FROM usuarios WHERE usuario=%s OR ci=%s',(d['usuario'],d['ci']),True):return jsonify(error='Usuario o CI ya registrado'),409
 q('INSERT INTO usuarios(nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,acepta_terminos,activo) VALUES(%s,%s,%s,%s,%s,%s,%s,\'cliente\',1,1)',(d['nombre'],d['apellido'],d['ci'],dob,age,d['usuario'],hashpass(d['password'])),commit=True);return jsonify(message='Cuenta creada'),201
@app.route('/api/clientes',methods=['GET','POST'])
@app.route('/api/usuarios',methods=['GET','POST'])
def clientes():
 u,e=auth(['administrador']);
 if e:return jsonify(error=e[0]),e[1]
 if request.method=='GET':
  rol=request.args.get('rol')
  if rol and rol in ['cliente','cajero','repartidor','administrador','empleado']:
   return jsonify(q("SELECT id,nombre,apellido,ci,fecha_nacimiento,edad,usuario,rol,cargo,activo,created_at FROM usuarios WHERE rol=%s ORDER BY id DESC",(rol,)))
  return jsonify(q("SELECT id,nombre,apellido,ci,fecha_nacimiento,edad,usuario,rol,cargo,activo,created_at FROM usuarios ORDER BY id DESC"))
 d=request.json or {};dob=d.get('fecha_nacimiento','2000-01-01');
 try:age=(datetime.date.today()-datetime.date.fromisoformat(dob)).days//365
 except:age=20
 rol=d.get('rol','cliente') if d.get('rol') in ['cliente','cajero','repartidor','administrador','empleado'] else 'cliente'
 cargo=d.get('cargo',rol.capitalize())
 q('INSERT INTO usuarios(nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,cargo,acepta_terminos,activo) VALUES(%s,%s,%s,%s,%s,%s,%s,%s,%s,1,1)',(d['nombre'],d['apellido'],d['ci'],dob,age,d['usuario'],hashpass(d.get('password','Usuario123')),rol,cargo),commit=True);return jsonify(message='Usuario creado'),201

@app.route('/api/clientes/<int:i>',methods=['PUT','DELETE'])
@app.route('/api/usuarios/<int:i>',methods=['PUT','DELETE'])
def cliente(i):
 u,e=auth(['administrador']);
 if e:return jsonify(error=e[0]),e[1]
 if request.method=='DELETE':
  if i == u['id']: return jsonify(error='No puedes eliminar tu propia cuenta en sesion'),400
  try:
   q("DELETE FROM usuarios WHERE id=%s",(i,),commit=True)
   return jsonify(message='Usuario eliminado correctamente')
  except Exception:
   try:
    q("UPDATE usuarios SET activo=0 WHERE id=%s",(i,),commit=True)
    return jsonify(message='El usuario tiene registros asociados; fue desactivado en su lugar')
   except Exception as ex:
    return jsonify(error=f'No se pudo eliminar el usuario: {ex}'),409
 d=request.json or {};dob=d.get('fecha_nacimiento','2000-01-01');
 try:age=(datetime.date.today()-datetime.date.fromisoformat(dob)).days//365
 except:age=20
 rol=d.get('rol','cliente') if d.get('rol') in ['cliente','cajero','repartidor','administrador','empleado'] else 'cliente'
 cargo=d.get('cargo',rol.capitalize())
 if d.get('password') and len(d['password'])>=8:
  q("UPDATE usuarios SET nombre=%s,apellido=%s,ci=%s,fecha_nacimiento=%s,edad=%s,usuario=%s,password=%s,rol=%s,cargo=%s,activo=%s WHERE id=%s",(d['nombre'],d['apellido'],d['ci'],dob,age,d['usuario'],hashpass(d['password']),rol,cargo,int(d.get('activo',1)),i),commit=True)
 else:
  q("UPDATE usuarios SET nombre=%s,apellido=%s,ci=%s,fecha_nacimiento=%s,edad=%s,usuario=%s,rol=%s,cargo=%s,activo=%s WHERE id=%s",(d['nombre'],d['apellido'],d['ci'],dob,age,d['usuario'],rol,cargo,int(d.get('activo',1)),i),commit=True)
 return jsonify(message='Usuario actualizado')

@app.get('/api/productos-publicos')
def public_products():return jsonify(q('SELECT * FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre'))
@app.route('/api/productos',methods=['GET','POST'])
def products():
 u,e=auth(['administrador','cajero']);
 if e:return jsonify(error=e[0]),e[1]
 if request.method=='GET':return jsonify(q('SELECT * FROM productos ORDER BY nombre'))
 d=request.json or {};q('INSERT INTO productos(nombre,descripcion,precio,stock,activo) VALUES(%s,%s,%s,%s,%s)',(d['nombre'],d.get('descripcion',''),d.get('precio',0),d.get('stock',0),int(d.get('activo',1))),commit=True);return jsonify(message='Producto creado'),201
@app.route('/api/productos/<int:i>',methods=['PUT','DELETE'])
def product(i):
 u,e=auth(['administrador','cajero']);
 if e:return jsonify(error=e[0]),e[1]
 if request.method=='DELETE':q('DELETE FROM productos WHERE id=%s',(i,),commit=True);return jsonify(message='Producto eliminado')
 d=request.json or {};q('UPDATE productos SET nombre=%s,descripcion=%s,precio=%s,stock=%s,activo=%s WHERE id=%s',(d['nombre'],d.get('descripcion',''),d.get('precio',0),d.get('stock',0),int(d.get('activo',1)),i),commit=True);return jsonify(message='Producto actualizado')
@app.get('/api/ventas')
def sales():
 u,e=auth(['administrador','cajero','cliente']);
 if e:return jsonify(error=e[0]),e[1]
 if u['rol']=='cliente':
  return jsonify(q("SELECT v.id,v.usuario_id,v.total,v.estado,v.direccion_entrega,v.repartidor_id,v.created_at,u.nombre,u.apellido,r.nombre AS rep_nombre,r.apellido AS rep_apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN usuarios r ON r.id=v.repartidor_id WHERE v.usuario_id=%s ORDER BY v.id DESC",(u['id'],)))
 return jsonify(q("SELECT v.id,v.usuario_id,v.total,v.estado,v.direccion_entrega,v.repartidor_id,v.created_at,u.nombre,u.apellido,r.nombre AS rep_nombre,r.apellido AS rep_apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN usuarios r ON r.id=v.repartidor_id ORDER BY v.id DESC"))
@app.post('/api/ventas')
def sale():
 u,e=auth(['administrador','cajero','cliente']);
 if e:return jsonify(error=e[0]),e[1]
 d=request.json or {};items=d.get('items',[]);c=db();cur=c.cursor(dictionary=True)
 dir_entrega=d.get('direccion_entrega','').strip() or None
 initial_state='pagada' if u['rol']=='cajero' else 'pendiente'
 try:
  total=0;rows=[]
  for it in items:
   cur.execute('SELECT * FROM productos WHERE id=%s AND activo=1 FOR UPDATE',(int(it['producto_id']),));p=cur.fetchone();qty=int(it['cantidad'])
   if not p or qty<1 or p['stock']<qty:raise Exception('Stock insuficiente')
   rows.append((p,qty));total+=float(p['precio'])*qty
  es_cajero=(u['rol']=='cajero')
  cur.execute("INSERT INTO ventas(usuario_id,cajero_id,tipo_venta,total,monto_cobrado,estado,estado_pago,direccion_entrega) VALUES(%s,%s,%s,%s,%s,%s,%s,%s)",(d.get('cliente_id',u['id']),u['id'] if es_cajero else None,'mostrador' if es_cajero else 'delivery',total,total if es_cajero else 0,initial_state,'pagado' if es_cajero else 'pendiente',dir_entrega));vid=cur.lastrowid
  for p,qty in rows:cur.execute('INSERT INTO detalle_venta(venta_id,producto_id,cantidad,precio_unitario) VALUES(%s,%s,%s,%s)',(vid,p['id'],qty,p['precio']));cur.execute('UPDATE productos SET stock=stock-%s WHERE id=%s',(qty,p['id']))
  c.commit();return jsonify(message='Venta registrada',id=vid,total=total,estado=initial_state),201
 except Exception as ex:c.rollback();return jsonify(error=str(ex)),422
 finally:cur.close();c.close()
@app.route('/api/entregas',methods=['GET'])
def deliveries():
 u,e=auth(['repartidor']);
 if e:return jsonify(error=e[0]),e[1]
 return jsonify(q("SELECT v.id,v.total,v.estado,v.direccion_entrega,v.created_at,u.nombre,u.apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id WHERE v.estado IN ('pendiente','en_camino') ORDER BY v.id ASC"))
@app.put('/api/entregas/<int:i>')
def deliver(i):
 u,e=auth(['repartidor']);
 if e:return jsonify(error=e[0]),e[1]
 d=request.json or {}
 accion=d.get('accion','entregado')
 if accion=='no_entregado':
  c=db();cur=c.cursor(dictionary=True)
  try:
   cur.execute("UPDATE ventas SET estado='no_entregado',estado_pago='anulado',monto_cobrado=0,repartidor_id=%s,fecha_entrega=NOW(),observacion_entrega=%s WHERE id=%s AND estado IN ('pendiente','en_camino')",(u['id'],d.get('observacion','El cliente no recibio el pedido'),i))
   cur.execute("SELECT producto_id,cantidad FROM detalle_venta WHERE venta_id=%s",(i,))
   items=cur.fetchall()
   for it in items:cur.execute("UPDATE productos SET stock=stock+%s WHERE id=%s",(it['cantidad'],it['producto_id']))
   c.commit()
   return jsonify(message='Pedido marcado como NO entregado. Los productos volvieron al inventario.')
  except Exception as ex:
   c.rollback();return jsonify(error=str(ex)),422
  finally:
   cur.close();c.close()
 else:
  q("UPDATE ventas SET estado='entregada',estado_pago='pagado',monto_cobrado=total,repartidor_id=%s,fecha_entrega=NOW(),observacion_entrega=%s WHERE id=%s AND estado IN ('pendiente','en_camino')",(u['id'],d.get('observacion','Entrega conforme'),i),commit=True);return jsonify(message='Producto entregado. Monto registrado correctamente.')
if __name__=='__main__':app.run(host='0.0.0.0',port=5000,debug=True)
