<?php require __DIR__.'/config.php';
$method=$_SERVER['REQUEST_METHOD'];
$rawPath=trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if(preg_match('#(?:^|/)api(?:/(.*))?$#', $rawPath, $m)) {
  $route = trim($m[1] ?? '', '/');
} else {
  $route = $rawPath;
}
$parts=$route===''?[]:explode('/',$route); $resource=$parts[0]??''; $id=isset($parts[1]) && ctype_digit($parts[1])?(int)$parts[1]:null;
if($route==='') out(['ok'=>true,'backend'=>'php']);
if($method==='GET' && $route==='health') out(['ok'=>true,'backend'=>'php']);

if($resource==='login' && $method==='POST'){
 $d=body(); $st=$pdo->prepare('SELECT id,nombre,apellido,usuario,password,rol,activo,edad FROM usuarios WHERE usuario=? LIMIT 1'); $st->execute([trim($d['usuario']??'')]); $u=$st->fetch();
 if(!$u||!$u['activo']||!verifyPass($d['password']??'',$u['password'])) out(['error'=>'Usuario o contraseña incorrectos'],401);
 unset($u['password']); $u['token']=$u['id'].'|'.$u['rol']; out(['user'=>$u]);
}
if($resource==='register' && $method==='POST'){
 $d=body(); $dob=$d['fecha_nacimiento']??null; $age=0; try{$age=(new DateTime($dob))->diff(new DateTime('today'))->y;}catch(Throwable $e){}
 if($age<18 || empty($d['nombre'])||empty($d['apellido'])||empty($d['ci'])||empty($d['usuario'])||strlen($d['password']??'')<8||empty($d['terminos'])) out(['error'=>'Datos inválidos o edad menor a 18'],422);
 $st=$pdo->prepare('SELECT id FROM usuarios WHERE usuario=? OR ci=?');$st->execute([$d['usuario'],$d['ci']]);if($st->fetch())out(['error'=>'Usuario o CI ya registrado'],409);
 $st=$pdo->prepare('INSERT INTO usuarios(nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,acepta_terminos,activo) VALUES(?,?,?,?,?,?,?,?,1,1)');$st->execute([$d['nombre'],$d['apellido'],$d['ci'],$dob,$age,$d['usuario'],hashPass($d['password']),'cliente']);out(['message'=>'Cuenta creada'],201);
}
if($resource==='clientes' || $resource==='usuarios'){
 requireRole(['administrador']);
 if($method==='GET'){
   $rolFilter = $_GET['rol'] ?? '';
   if($rolFilter && in_array($rolFilter,['cliente','cajero','repartidor','administrador','empleado'],true)){
     $st=$pdo->prepare("SELECT id,nombre,apellido,ci,fecha_nacimiento,edad,usuario,rol,cargo,activo,created_at FROM usuarios WHERE rol=? ORDER BY id DESC");
     $st->execute([$rolFilter]);
     out($st->fetchAll());
   } else {
     out($pdo->query("SELECT id,nombre,apellido,ci,fecha_nacimiento,edad,usuario,rol,cargo,activo,created_at FROM usuarios ORDER BY id DESC")->fetchAll());
   }
 }
 if($method==='DELETE' && $id){
   $currentAuth = auth();
   if($id === (int)$currentAuth['id']){
     out(['error'=>'No puedes eliminar tu propia cuenta en sesión'],400);
   }
   try{
     $st=$pdo->prepare("DELETE FROM usuarios WHERE id=?");
     $st->execute([$id]);
     if($st->rowCount()===0){ out(['error'=>'Usuario no encontrado'],404); }
     out(['message'=>'Usuario eliminado correctamente']);
   }catch(Throwable $e){
     try{
       $pdo->prepare("UPDATE usuarios SET activo=0 WHERE id=?")->execute([$id]);
       out(['message'=>'El usuario tiene registros asociados; fue desactivado en su lugar']);
     }catch(Throwable $e2){
       out(['error'=>'No se puede eliminar el usuario porque tiene registros relacionados'],409);
     }
   }
 }
 if($method==='POST'||$method==='PUT'){
   $d=body();
   $rol = in_array($d['rol']??'', ['cliente','cajero','repartidor','administrador','empleado'], true) ? $d['rol'] : 'cliente';
   $cargo = trim($d['cargo'] ?? ucfirst($rol));
   $dob = $d['fecha_nacimiento'] ?? '2000-01-01';
   $age = (new DateTime($dob))->diff(new DateTime('today'))->y;
   $active = isset($d['activo']) ? (int)$d['activo'] : 1;

   if($method==='POST'){
     $st=$pdo->prepare("INSERT INTO usuarios(nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,cargo,acepta_terminos,activo) VALUES(?,?,?,?,?,?,?,?,?,1,?)");
     $st->execute([$d['nombre'],$d['apellido'],$d['ci'],$dob,$age,$d['usuario'],hashPass($d['password']??'Usuario123'),$rol,$cargo,$active]);
     out(['message'=>'Usuario creado correctamente'],201);
   }
   if(!$id) out(['error'=>'ID requerido'],400);
   $sets='nombre=?,apellido=?,ci=?,fecha_nacimiento=?,edad=?,usuario=?,rol=?,cargo=?,activo=?';
   $args=[$d['nombre'],$d['apellido'],$d['ci'],$dob,$age,$d['usuario'],$rol,$cargo,$active];
   if(!empty($d['password']) && strlen($d['password'])>=8){
     $sets.=',password=?';
     $args[]=hashPass($d['password']);
   }
   $args[]=$id;
   $pdo->prepare("UPDATE usuarios SET $sets WHERE id=?")->execute($args);
   out(['message'=>'Usuario actualizado correctamente']);
 }
}
if($resource==='productos'){
 requireRole(['administrador','cajero']);
 if($method==='GET')out($pdo->query('SELECT * FROM productos ORDER BY nombre')->fetchAll());
 if($method==='DELETE'&&$id){try{$pdo->prepare('DELETE FROM productos WHERE id=?')->execute([$id]);out(['message'=>'Producto eliminado']);}catch(Throwable $e){$pdo->prepare('UPDATE productos SET activo=0 WHERE id=?')->execute([$id]);out(['message'=>'Producto desactivado']);}}
 $d=body(); if($method==='POST'){$pdo->prepare('INSERT INTO productos(nombre,descripcion,precio,stock,activo) VALUES(?,?,?,?,?)')->execute([$d['nombre'],$d['descripcion']??'',$d['precio']??0,$d['stock']??0,isset($d['activo'])?(int)$d['activo']:1]);out(['message'=>'Producto creado'],201);}
 if($method==='PUT'&&$id){$pdo->prepare('UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,activo=? WHERE id=?')->execute([$d['nombre'],$d['descripcion']??'',$d['precio']??0,$d['stock']??0,isset($d['activo'])?(int)$d['activo']:1,$id]);out(['message'=>'Producto actualizado']);}
}
if($resource==='productos-publicos'&&$method==='GET')out($pdo->query('SELECT * FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre')->fetchAll());
if($resource==='ventas'){
 requireRole(['administrador','cajero','cliente']);
 if($method==='GET'){
   $u=auth();
   if($u['rol']==='cliente'){
     $st=$pdo->prepare("SELECT v.id,v.usuario_id,v.total,v.estado,v.direccion_entrega,v.repartidor_id,v.created_at,u.nombre,u.apellido,r.nombre AS rep_nombre,r.apellido AS rep_apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN usuarios r ON r.id=v.repartidor_id WHERE v.usuario_id=? ORDER BY v.id DESC");
     $st->execute([$u['id']]);
     out($st->fetchAll());
   } else {
     out($pdo->query("SELECT v.id,v.usuario_id,v.total,v.estado,v.direccion_entrega,v.repartidor_id,v.created_at,u.nombre,u.apellido,r.nombre AS rep_nombre,r.apellido AS rep_apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id LEFT JOIN usuarios r ON r.id=v.repartidor_id ORDER BY v.id DESC")->fetchAll());
   }
 }
 if($method==='POST'){
   $d=body();$items=$d['items']??[];$cliente=(int)($d['cliente_id']??auth()['id']);
   $dirEntrega=trim($d['direccion_entrega']??'');
   $estadoInicial=(auth()['rol']==='cajero')?'pagada':'pendiente';
   $pdo->beginTransaction();
   try{
     $total=0;$rows=[];$st=$pdo->prepare('SELECT * FROM productos WHERE id=? AND activo=1 FOR UPDATE');
     foreach($items as $it){
       $st->execute([(int)$it['producto_id']]);$p=$st->fetch();$q=(int)$it['cantidad'];
       if(!$p||$q<1||$p['stock']<$q)throw new Exception('Stock insuficiente');
       $rows[]=[$p,$q];$total+=$p['precio']*$q;
     }
     $esCajero=(auth()['rol']==='cajero');
     $pdo->prepare("INSERT INTO ventas(usuario_id,cajero_id,tipo_venta,total,monto_cobrado,estado,estado_pago,direccion_entrega) VALUES(?,?,?,?,?,?,?,?)")
         ->execute([$cliente,$esCajero?auth()['id']:null,$esCajero?'mostrador':'delivery',$total,$esCajero?$total:0,$estadoInicial,$esCajero?'pagado':'pendiente',$dirEntrega?:null]);
     $vid=$pdo->lastInsertId();
     $ins=$pdo->prepare('INSERT INTO detalle_venta(venta_id,producto_id,cantidad,precio_unitario) VALUES(?,?,?,?)');
     $upd=$pdo->prepare('UPDATE productos SET stock=stock-? WHERE id=?');
     foreach($rows as [$p,$q]){$ins->execute([$vid,$p['id'],$q,$p['precio']]);$upd->execute([$q,$p['id']]);}
     $pdo->commit();
     out(['message'=>'Venta registrada','id'=>(int)$vid,'total'=>$total,'estado'=>$estadoInicial],201);
   }catch(Throwable $e){$pdo->rollBack();out(['error'=>$e->getMessage()],422);}
 }
}
if($resource==='entregas'){
 $u=requireRole(['repartidor']);
 if($method==='GET'){
   out($pdo->query("SELECT v.id,v.total,v.estado,v.direccion_entrega,v.created_at,u.nombre,u.apellido FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id WHERE v.estado IN ('pendiente','en_camino') ORDER BY v.id ASC")->fetchAll());
 }
 if($method==='PUT'&&$id){
   $d=body();
   $accion=$d['accion']??'entregado';
   if($accion==='no_entregado'){
     $pdo->beginTransaction();
     try{
       $pdo->prepare("UPDATE ventas SET estado='no_entregado',estado_pago='anulado',monto_cobrado=0,repartidor_id=?,fecha_entrega=NOW(),observacion_entrega=? WHERE id=? AND estado IN ('pendiente','en_camino')")->execute([$u['id'],trim($d['observacion']??'El cliente no recibio el pedido'),$id]);
       $st=$pdo->prepare("SELECT producto_id,cantidad FROM detalle_venta WHERE venta_id=?");
       $st->execute([$id]);
       $items=$st->fetchAll();
       $upd=$pdo->prepare("UPDATE productos SET stock=stock+? WHERE id=?");
       foreach($items as $it){$upd->execute([$it['cantidad'],$it['producto_id']]);}
       $pdo->commit();
       out(['message'=>'Pedido marcado como NO entregado. Los productos volvieron al inventario.']);
     }catch(Throwable $e){$pdo->rollBack();out(['error'=>$e->getMessage()],422);}
   } else {
     $pdo->prepare("UPDATE ventas SET estado='entregada',estado_pago='pagado',monto_cobrado=total,repartidor_id=?,fecha_entrega=NOW(),observacion_entrega=? WHERE id=? AND estado IN ('pendiente','en_camino')")->execute([$u['id'],trim($d['observacion']??'Entrega conforme'),$id]);
     out(['message'=>'Producto entregado. Monto registrado correctamente.']);
   }
 }
}
out(['error'=>'Ruta no encontrada'],404);

